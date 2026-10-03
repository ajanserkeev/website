<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Partner\Pages\Dashboard;
use App\Filament\Partner\Resources\Bookings\Pages\ListBookings;
use App\Filament\Partner\Widgets\PartnerOverview;
use App\Filament\Partner\Widgets\ToursSummary;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Booking;
use App\Models\Operator;
use App\Models\Tour;
use App\Models\User;
use App\Services\Partner\PartnerStats;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    $this->operator = Operator::factory()->create(['commission_rate' => 15]);
    $this->tour = Tour::factory()->for($this->operator)->create(['title' => 'Song-Kul Ride']);
    $this->otherTour = Tour::factory()->create(['title' => 'Somebody Else Trek']);
    $this->partner = User::factory()->partner($this->operator)->create();
});

/** A booking in a given status straight in the database, like the demo seeder does. */
function partnerBooking(Tour $tour, BookingStatus $status, int $total, int $adults = 2, string $from = '2027-07-10'): Booking
{
    $booking = Booking::factory()->for($tour)->create([
        'operator_id' => $tour->operator_id,
        'adults' => $adults,
        'date_from' => $from,
        'date_to' => $from,
    ]);
    $deposit = (int) round($total * 0.15 / 100) * 100;
    $booking->forceFill(['status' => $status, 'total_cents' => $total, 'deposit_cents' => $deposit, 'balance_cents' => $total - $deposit, 'commission_rate' => 15])->save();

    return $booking;
}

it('lets each role into its own panel only', function (string $path, ?UserRole $role, int $status) {
    $user = match ($role) {
        UserRole::Partner => $this->partner,
        null => User::factory()->partner()->create(['operator_id' => null]),
        default => User::factory()->create(['role' => $role]),
    };

    $this->actingAs($user)->get($path)->assertStatus($status);
})->with([
    'partner in /partner' => ['/partner', UserRole::Partner, 200],
    'partner in /admin' => ['/admin', UserRole::Partner, 403],
    'partner without operator' => ['/partner', null, 403],
    'super admin in /admin' => ['/admin', UserRole::Admin, 200],
    'admin in /partner' => ['/partner', UserRole::Admin, 403],
    'tourist in /admin' => ['/admin', UserRole::Tourist, 403],
    'tourist in /partner' => ['/partner', UserRole::Tourist, 403],
]);

it('counts only bookings with the deposit paid', function () {
    partnerBooking($this->tour, BookingStatus::DepositPaid, 60000, 2);
    partnerBooking($this->tour, BookingStatus::Completed, 40000, 3, '2026-08-01');
    partnerBooking($this->tour, BookingStatus::New, 99000);
    partnerBooking($this->tour, BookingStatus::CancelledByTourist, 50000);
    partnerBooking($this->otherTour, BookingStatus::DepositPaid, 70000);

    $totals = (new PartnerStats($this->operator->id))->totals();

    expect($totals)->toMatchArray([
        'bookings' => 2, 'travelers' => 5, 'total_cents' => 100000, 'commission_cents' => 15000, 'operator_cents' => 85000,
        'waiting' => 1, 'cancelled' => 1, 'average_commission_rate' => 15.0,
    ])
        ->and((new PartnerStats($this->operator->id, '2027-01-01', '2027-12-31'))->totals()['bookings'])->toBe(1)
        ->and(array_keys((new PartnerStats($this->operator->id))->byMonth()))->toBe(['2026-08', '2027-07'])
        ->and((new PartnerStats(null))->totals()['bookings'])->toBe(3);
});

it('shows the partner their dashboard, tours and bookings, not anyone else\'s', function () {
    $own = partnerBooking($this->tour, BookingStatus::VoucherSent, 60000);
    $foreign = partnerBooking($this->otherTour, BookingStatus::VoucherSent, 70000);
    $this->actingAs($this->partner);
    Filament::setCurrentPanel('partner');

    $this->get('/partner')->assertOk()->assertSee($this->operator->name);
    Livewire::test(PartnerOverview::class)->assertSee('$600')->assertSee('$510')->assertDontSee('$700');
    Livewire::test(ToursSummary::class)->assertSee('Song-Kul Ride')->assertDontSee('Somebody Else Trek');
    Livewire::test(ListBookings::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$foreign]);

    $this->get("/partner/tours/{$this->tour->id}")->assertOk()->assertSee('Итоги по туру');
    $this->get("/partner/bookings/{$own->id}")->assertOk()->assertSee($own->email);
    $this->get("/partner/tours/{$this->otherTour->id}")->assertNotFound();
    $this->get("/partner/bookings/{$foreign->id}")->assertNotFound();
});

it('hides traveler contacts until the deposit is paid', function () {
    $waiting = partnerBooking($this->tour, BookingStatus::AwaitingPayment, 60000);
    $waiting->update(['email' => 'secret@traveler.test']);

    $this->actingAs($this->partner)->get("/partner/bookings/{$waiting->id}")
        ->assertOk()->assertDontSee('secret@traveler.test')->assertSee('Появятся после предоплаты');
});

it('filters the dashboard by the booking date', function () {
    $booking = partnerBooking($this->tour, BookingStatus::DepositPaid, 60000);
    $booking->forceFill(['created_at' => '2026-02-01'])->save();
    $this->actingAs($this->partner);
    Filament::setCurrentPanel('partner');

    Livewire::test(PartnerOverview::class, ['pageFilters' => ['date_by' => 'booked', 'from' => '2026-03-01']])->assertDontSee('$600');
    Livewire::test(PartnerOverview::class, ['pageFilters' => ['date_by' => 'booked', 'from' => '2026-01-01']])->assertSee('$600');
    Livewire::test(Dashboard::class)->assertOk();
});

it('lets the super admin create a partner tied to an operator', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Arstan', 'email' => 'arstan@example.com', 'role' => UserRole::Partner, 'password' => 'long-enough-1'])
        ->call('create')
        ->assertHasFormErrors(['operator_id' => 'required']);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Arstan', 'email' => 'arstan@example.com', 'role' => UserRole::Partner, 'operator_id' => $this->operator->id, 'password' => 'long-enough-1'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'arstan@example.com')->first())->operator_id->toBe($this->operator->id);
});

it('does not let managers manage users', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Manager]))->get('/admin/users')->assertForbidden();
});
