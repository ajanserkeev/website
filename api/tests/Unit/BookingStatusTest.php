<?php

use App\Enums\BookingStatus;

it('follows the booking flow from the launch document', function () {
    expect(BookingStatus::New->canTransitionTo(BookingStatus::Checking))->toBeTrue()
        ->and(BookingStatus::Checking->canTransitionTo(BookingStatus::AwaitingPayment))->toBeTrue()
        ->and(BookingStatus::AwaitingPayment->canTransitionTo(BookingStatus::DepositPaid))->toBeTrue()
        ->and(BookingStatus::DepositPaid->canTransitionTo(BookingStatus::VoucherSent))->toBeTrue()
        ->and(BookingStatus::VoucherSent->canTransitionTo(BookingStatus::Completed))->toBeTrue();
});

it('does not skip the payment', function () {
    expect(BookingStatus::New->canTransitionTo(BookingStatus::DepositPaid))->toBeFalse()
        ->and(BookingStatus::Checking->canTransitionTo(BookingStatus::VoucherSent))->toBeFalse();
});

it('has no way out of final states', function (BookingStatus $status) {
    expect($status->isFinal())->toBeTrue()
        ->and($status->allowedTransitions())->toBe([]);
})->with([
    BookingStatus::Completed,
    BookingStatus::Declined,
    BookingStatus::Expired,
    BookingStatus::CancelledByTourist,
    BookingStatus::CancelledByOperator,
]);

it('holds seats from confirmation until the end', function () {
    expect(BookingStatus::New->holdsSeats())->toBeFalse()
        ->and(BookingStatus::AwaitingPayment->holdsSeats())->toBeTrue()
        ->and(BookingStatus::Expired->holdsSeats())->toBeFalse();
});

it('has a label for every status', function () {
    foreach (BookingStatus::cases() as $status) {
        expect($status->label())->not->toBeEmpty();
    }
});
