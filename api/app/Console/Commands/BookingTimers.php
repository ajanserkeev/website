<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\BookingWorkflow;
use App\Services\Booking\InvalidBookingAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Time-based booking steps (step 4.9), every 15 minutes:
 * payment reminder 24 h before the link expires, expiry after 48 h, reminder 7 days before the trip,
 * completion after the last day, review request 3 days later. Each email is sent once.
 */
#[Signature('bookings:tick')]
#[Description('Expire unpaid bookings, send reminders, complete finished trips')]
class BookingTimers extends Command
{
    public function handle(BookingWorkflow $workflow, BookingNotifier $notifier): int
    {
        $today = CarbonImmutable::now(config('brand.timezone'))->startOfDay();
        $counts = array_fill_keys(['expired', 'payment_reminders', 'pre_trip', 'completed', 'review_requests'], 0);

        Booking::query()->where('status', BookingStatus::AwaitingPayment)
            ->where('payment_link_expires_at', '<=', now())
            ->each(function (Booking $b) use ($workflow, &$counts) {
                $this->safely(fn () => $workflow->expire($b)) && $counts['expired']++;
            });

        Booking::query()->where('status', BookingStatus::AwaitingPayment)
            ->whereNull('payment_reminder_sent_at')
            ->whereBetween('payment_link_expires_at', [now(), now()->addHours(24)])
            ->each(function (Booking $b) use ($notifier, &$counts) {
                $notifier->paymentReminder($b);
                $b->forceFill(['payment_reminder_sent_at' => now()])->save();
                $counts['payment_reminders']++;
            });

        Booking::query()->where('status', BookingStatus::VoucherSent)
            ->whereNull('pre_trip_reminder_sent_at')
            ->whereDate('date_from', '<=', $today->addDays(7)->toDateString())
            ->whereDate('date_from', '>', $today->toDateString())
            ->each(function (Booking $b) use ($notifier, &$counts) {
                $notifier->preTripReminder($b);
                $b->forceFill(['pre_trip_reminder_sent_at' => now()])->save();
                $counts['pre_trip']++;
            });

        Booking::query()->where('status', BookingStatus::VoucherSent)
            ->whereDate('date_to', '<', $today->toDateString())
            ->each(function (Booking $b) use ($workflow, &$counts) {
                $this->safely(fn () => $workflow->complete($b)) && $counts['completed']++;
            });

        Booking::query()->where('status', BookingStatus::Completed)
            ->whereNull('review_requested_at')
            ->whereDate('date_to', '<=', $today->subDays(3)->toDateString())
            ->each(function (Booking $b) use ($notifier, &$counts) {
                $notifier->reviewRequest($b);
                $b->forceFill(['review_requested_at' => now()])->save();
                $counts['review_requests']++;
            });

        $this->info(collect($counts)->map(fn ($n, $k) => "{$k}: {$n}")->join(', '));

        return self::SUCCESS;
    }

    /** A booking changed by staff in the meantime is skipped, not a failure of the whole run. */
    private function safely(callable $action): bool
    {
        try {
            $action();

            return true;
        } catch (InvalidBookingAction) {
            return false;
        }
    }
}
