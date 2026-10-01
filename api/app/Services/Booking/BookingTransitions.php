<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Departure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only way a booking changes status (step 4.7). Checks the allowed transition, holds or releases
 * departure seats under a row lock (plan, recommendation 7), writes booking_events and, after commit,
 * sends the emails and Telegram alerts for the new status.
 */
final class BookingTransitions
{
    public function __construct(private readonly BookingNotifier $notifier) {}

    /** @param  array<string, mixed>  $attributes  extra fields set together with the status (payment link, timestamps) */
    public function transition(Booking $booking, BookingStatus $to, ?User $user = null, ?string $note = null, array $attributes = []): Booking
    {
        $from = DB::transaction(function () use ($booking, $to, $user, $note, $attributes) {
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw new InvalidBookingAction("A booking that is «{$from->getLabel()}» cannot become «{$to->getLabel()}».");
            }

            if ($to->holdsSeats() && ! $from->holdsSeats()) {
                $this->holdSeats($locked);
            } elseif ($from->holdsSeats() && ! $to->holdsSeats()) {
                $this->releaseSeats($locked);
            }

            $locked->forceFill([...$attributes, 'status' => $to, ...$this->timestamps($to)])->save();
            $locked->events()->create([
                'user_id' => $user?->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);

            $booking->setRawAttributes($locked->getAttributes(), true);

            return $from;
        });

        DB::afterCommit(fn () => $this->notifier->statusChanged($booking->fresh(), $from, $to));

        return $booking;
    }

    private function holdSeats(Booking $booking): void
    {
        if (! $booking->departure_id) {
            return;
        }
        /** @var Departure $departure */
        $departure = Departure::query()->lockForUpdate()->findOrFail($booking->departure_id);
        if ($departure->seatsLeft() < $booking->travelerCount()) {
            throw new InvalidBookingAction("Only {$departure->seatsLeft()} seats are left on this departure; the booking needs {$booking->travelerCount()}.");
        }
        $departure->increment('seats_booked', $booking->travelerCount());
    }

    private function releaseSeats(Booking $booking): void
    {
        if (! $booking->departure_id) {
            return;
        }
        /** @var Departure $departure */
        $departure = Departure::query()->lockForUpdate()->find($booking->departure_id);
        if ($departure) {
            $departure->seats_booked = max(0, $departure->seats_booked - $booking->travelerCount());
            $departure->save();
        }
    }

    /** @return array<string, mixed> */
    private function timestamps(BookingStatus $to): array
    {
        return match ($to) {
            BookingStatus::DepositPaid => ['paid_at' => now()],
            BookingStatus::VoucherSent => ['voucher_sent_at' => now()],
            BookingStatus::CancelledByTourist, BookingStatus::CancelledByOperator => ['cancelled_at' => now()],
            default => [],
        };
    }
}
