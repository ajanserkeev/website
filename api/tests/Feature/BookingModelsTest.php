<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\WebhookEvent;
use Filament\Facades\Filament;
use Illuminate\Database\UniqueConstraintViolationException;

it('stores only a hash of the my-booking token and finds the booking by the plain token', function () {
    [$token, $hash] = Booking::newPublicToken();
    $booking = Booking::factory()->create(['public_token_hash' => $hash]);

    expect(strlen($token))->toBeGreaterThanOrEqual(26)
        ->and($booking->public_token_hash)->not->toBe($token)
        ->and(Booking::findByPublicToken($token)?->is($booking))->toBeTrue()
        ->and(Booking::findByPublicToken('wrong-token'))->toBeNull()
        ->and($booking->toArray())->not->toHaveKey('public_token_hash');
});

it('ignores money and status fields in mass assignment', function () {
    $booking = new Booking([
        'customer_name' => 'Anna Weber',
        'adults' => 2,
        'total_cents' => 100,
        'deposit_cents' => 1,
        'status' => 'deposit_paid',
    ]);

    expect($booking->customer_name)->toBe('Anna Weber')
        ->and($booking->adults)->toBe(2)
        ->and($booking->total_cents)->toBeNull()
        ->and($booking->deposit_cents)->toBeNull()
        ->and($booking->status)->toBeNull();
});

it('logs status changes as booking events', function () {
    $booking = Booking::factory()->create();
    $booking->events()->create(['from_status' => BookingStatus::New, 'to_status' => BookingStatus::Checking]);

    expect($booking->events()->first()->to_status)->toBe(BookingStatus::Checking);
});

it('rejects a second webhook with the same provider event id', function () {
    WebhookEvent::create(['provider' => 'freedompay', 'event_id' => 'evt_1', 'payload' => []]);

    WebhookEvent::create(['provider' => 'freedompay', 'event_id' => 'evt_1', 'payload' => []]);
})->throws(UniqueConstraintViolationException::class);

it('allows one review per booking', function () {
    $booking = Booking::factory()->create();
    Review::factory()->create(['tour_id' => $booking->tour_id, 'booking_id' => $booking->id]);

    Review::factory()->create(['tour_id' => $booking->tour_id, 'booking_id' => $booking->id]);
})->throws(UniqueConstraintViolationException::class);

it('lets staff roles into the admin panel', function () {
    $panel = Filament::getPanel('admin');

    expect(User::factory()->make(['role' => UserRole::Manager])->canAccessPanel($panel))->toBeTrue();
});
