<?php

use App\Enums\BookingStatus;
use App\Jobs\SendTelegramMessage;
use App\Mail\BookingMail;
use App\Models\Booking;
use App\Models\Departure;
use App\Models\Operator;
use App\Models\PrivatePrice;
use App\Models\Tour;
use App\Services\Booking\BookingWorkflow;
use App\Services\Booking\InvalidBookingAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    $this->operator = Operator::factory()->create([
        'commission_rate' => 15,
        'whatsapp' => '+996 700 111 222',
        'phone' => '+996 555 333 444',
    ]);
    $this->tour = Tour::factory()->for($this->operator)->create(['duration_days' => 3]);
    $this->departure = Departure::factory()->for($this->tour)->create([
        'starts_on' => now()->addDays(60)->startOfDay(),
        'ends_on' => now()->addDays(62)->startOfDay(),
        'price_cents' => 33000,
        'seats_total' => 8,
        'seats_booked' => 5,
    ]);
    PrivatePrice::factory()->for($this->tour)->create(['group_size_from' => 1, 'group_size_to' => 4, 'price_per_person_cents' => 45000, 'child_price_cents' => 20000]);
});

function bookingPayload(array $overrides = []): array
{
    return [
        'tour' => test()->tour->slug,
        'departure_id' => test()->departure->id,
        'adults' => 2,
        'customer_name' => 'Anna Weber',
        'email' => 'anna@example.com',
        'whatsapp' => '+49 151 1234567',
        'country' => 'DE',
        'travelers' => ['Anna Weber', 'Jonas Weber'],
        'terms_accepted' => true,
        ...$overrides,
    ];
}

it('creates a request priced from the database, not from the browser', function () {
    $response = $this->postJson('/api/v1/bookings', bookingPayload(['total_cents' => 100, 'deposit_cents' => 1]))
        ->assertCreated();

    $booking = Booking::firstOrFail();
    expect($response->json('data.code'))->toMatch('/^TT-\d{2}-\d{4}$/')
        ->and($booking->status)->toBe(BookingStatus::New)
        ->and($booking->total_cents)->toBe(66000)
        ->and($booking->deposit_cents)->toBe(9900)
        ->and($booking->balance_cents)->toBe(56100)
        ->and($booking->travelers()->pluck('name')->all())->toBe(['Anna Weber', 'Jonas Weber'])
        ->and($booking->events()->first()->to_status)->toBe(BookingStatus::New)
        ->and(Booking::findByPublicToken($response->json('data.token'))?->is($booking))->toBeTrue();

    Mail::assertQueued(BookingMail::class, fn (BookingMail $m) => $m->type === 'received' && $m->hasTo('anna@example.com'));
    Queue::assertPushed(SendTelegramMessage::class, fn ($job) => str_contains($job->text, $booking->code));
});

it('prices a private tour with children and the bracket for the group size', function () {
    $this->postJson('/api/v1/bookings', bookingPayload([
        'departure_id' => null,
        'date_from' => now(config('brand.timezone'))->addDays(40)->toDateString(),
        'adults' => 2,
        'children' => 1,
    ]))->assertCreated();

    $booking = Booking::firstOrFail();
    expect($booking->total_cents)->toBe(2 * 45000 + 20000)
        ->and($booking->deposit_cents)->toBe(16500)
        ->and($booking->date_to->diffInDays($booking->date_from, true))->toBe(2.0);
});

it('rejects requests that cannot be served', function (array $overrides, string $field) {
    $this->postJson('/api/v1/bookings', bookingPayload($overrides))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'more travelers than seats left' => [['adults' => 4], 'departure_id'],
    'private group too large' => [['departure_id' => null, 'date_from' => '2030-07-01', 'adults' => 5], 'adults'],
    'private date today' => [['departure_id' => null, 'date_from' => now('Asia/Bishkek')->toDateString()], 'date_from'],
    'terms not accepted' => [['terms_accepted' => false], 'terms_accepted'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
]);

it('limits booking requests to 5 per hour per IP', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/bookings', bookingPayload(['adults' => 1, 'email' => "t{$i}@example.com"]))->assertCreated();
    }
    $this->postJson('/api/v1/bookings', bookingPayload(['adults' => 1]))->assertTooManyRequests();
});

it('holds seats when confirmed and releases them when the link expires', function () {
    [$booking] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());
    $workflow = app(BookingWorkflow::class);

    $workflow->confirm($booking, 'https://pay.example/abc');
    expect($this->departure->fresh()->seats_booked)->toBe(7)
        ->and($booking->fresh()->status)->toBe(BookingStatus::AwaitingPayment);
    Mail::assertQueued(BookingMail::class, fn ($m) => $m->type === 'confirmed');

    $workflow->expire($booking->fresh());
    expect($this->departure->fresh()->seats_booked)->toBe(5);
});

it('refuses to hold more seats than are left', function () {
    [$booking] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());
    $this->departure->update(['seats_booked' => 7]);

    app(BookingWorkflow::class)->confirm($booking, 'https://pay.example/abc');
})->throws(InvalidBookingAction::class);

it('does not allow skipping the payment', function () {
    [$booking] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());

    app(BookingWorkflow::class)->markPaid($booking);
})->throws(InvalidBookingAction::class);

it('shows operator contacts on my booking only after the deposit', function () {
    [$booking, $token] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());
    $workflow = app(BookingWorkflow::class);

    $this->getJson("/api/v1/bookings/{$token}")->assertOk()
        ->assertJsonPath('data.operator.contacts', null)
        ->assertJsonPath('data.payment', null)
        ->assertDontSee('+996 700 111 222');

    $workflow->confirm($booking, 'https://pay.example/abc');
    $this->getJson("/api/v1/bookings/{$token}")->assertJsonPath('data.payment.url', 'https://pay.example/abc');

    $workflow->markPaid($booking->fresh(), 'TX-1');
    $this->getJson("/api/v1/bookings/{$token}")->assertOk()
        ->assertJsonPath('data.status', 'voucher_sent')
        ->assertJsonPath('data.operator.contacts.whatsapp', '+996 700 111 222')
        ->assertJsonPath('data.payment', null);
    Mail::assertQueued(BookingMail::class, fn ($m) => $m->type === 'voucher');

    $this->getJson('/api/v1/bookings/wrong-token')->assertNotFound();
});

it('refunds the deposit by the days left before the start, in Bishkek time', function (int $daysBefore, int $refund) {
    [$booking, $token] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());
    $workflow = app(BookingWorkflow::class);
    $workflow->confirm($booking, 'https://pay.example/abc');
    $workflow->markPaid($booking->fresh());

    $this->travelTo($booking->date_from->copy()->shiftTimezone(config('brand.timezone'))->subDays($daysBefore)->setTime(12, 0));
    $this->postJson("/api/v1/bookings/{$token}/cancel")->assertOk()->assertJsonPath('data.refundCents', $refund);

    expect($booking->fresh()->status)->toBe(BookingStatus::CancelledByTourist)
        ->and($this->departure->fresh()->seats_booked)->toBe(5);
})->with([
    '30 days: full' => [30, 9900],
    '29 days: half' => [29, 4950],
    '14 days: half' => [14, 4950],
    '13 days: none' => [13, 0],
]);

it('cancels an unpaid request without any refund', function () {
    [, $token] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());

    $this->postJson("/api/v1/bookings/{$token}/cancel")->assertOk()
        ->assertJsonPath('data.status', 'cancelled_by_tourist')
        ->assertJsonPath('data.refundCents', 0);
});

it('runs the booking timers once per step', function () {
    $workflow = app(BookingWorkflow::class);
    [$unpaid] = $workflow->request($this->tour, bookingPayload());
    $workflow->confirm($unpaid, 'https://pay.example/abc');

    $this->travelTo(now()->addHours(30));
    $this->artisan('bookings:tick')->assertSuccessful();
    $this->artisan('bookings:tick')->assertSuccessful();
    Mail::assertQueuedCount(3); // received, confirmed, one payment reminder

    $this->travelTo(now()->addHours(20));
    $this->artisan('bookings:tick')->assertSuccessful();
    expect($unpaid->fresh()->status)->toBe(BookingStatus::Expired);
});

it('sends the pre-trip reminder, completes the trip and asks for a review', function () {
    $workflow = app(BookingWorkflow::class);
    [$booking] = $workflow->request($this->tour, bookingPayload());
    $workflow->confirm($booking, 'https://pay.example/abc');
    $workflow->markPaid($booking->fresh());

    $this->travelTo($booking->date_from->copy()->subDays(7)->setTime(10, 0));
    $this->artisan('bookings:tick');
    Mail::assertQueued(BookingMail::class, fn ($m) => $m->type === 'pre_trip');

    $this->travelTo($booking->date_to->copy()->addDays(1)->setTime(10, 0));
    $this->artisan('bookings:tick');
    expect($booking->fresh()->status)->toBe(BookingStatus::Completed);

    $this->travelTo($booking->date_to->copy()->addDays(3)->setTime(10, 0));
    $this->artisan('bookings:tick');
    $this->artisan('bookings:tick');
    Mail::assertQueued(BookingMail::class, fn ($m) => $m->type === 'review_request');
    expect(Mail::queued(BookingMail::class, fn ($m) => $m->type === 'review_request'))->toHaveCount(1);
});

it('renders every traveler email', function (string $type) {
    [$booking] = app(BookingWorkflow::class)->request($this->tour, bookingPayload());
    $booking->forceFill(['payment_link_url' => 'https://pay.example/abc', 'payment_link_expires_at' => now()->addDay()])->save();

    $html = (new BookingMail($booking->fresh(), $type))->render();

    expect($html)->toContain($booking->code);
})->with(array_keys(BookingMail::SUBJECTS));
