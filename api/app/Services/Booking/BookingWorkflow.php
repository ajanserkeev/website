<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Booking actions used by the public API and the admin. Each one goes through BookingTransitions.
 * Flow (launch document, section 02): new → checking → awaiting_payment (48 h) → deposit_paid → voucher_sent → completed.
 */
final class BookingWorkflow
{
    public const PAYMENT_LINK_HOURS = 48;

    public function __construct(
        private readonly BookingTransitions $transitions,
        private readonly BookingPricing $pricing,
        private readonly RefundPolicy $refunds,
        private readonly BookingNotifier $notifier,
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * Creates a request from the site. Prices come from the database; the traveler only picks dates and numbers.
     *
     * @param  array{departure_id?: ?int, date_from?: ?string, adults: int, children?: ?int, customer_name: string,
     *     email: string, whatsapp?: ?string, country?: ?string, special_requests?: ?string, travelers?: ?array, utm?: ?array}  $input
     * @return array{0: Booking, 1: string} the booking and the plain "My booking" token
     */
    public function request(Tour $tour, array $input, ?string $ip = null): array
    {
        $children = (int) ($input['children'] ?? 0);
        $quote = $this->pricing->quote($tour, $input['departure_id'] ?? null, $input['date_from'] ?? null, (int) $input['adults'], $children);
        $token = Str::random(40);

        $booking = DB::transaction(function () use ($tour, $input, $quote, $token, $ip) {
            $booking = new Booking([
                'customer_name' => $input['customer_name'],
                'email' => $input['email'],
                'whatsapp' => $input['whatsapp'] ?? null,
                'country' => $input['country'] ?? null,
                'special_requests' => $input['special_requests'] ?? null,
                'adults' => (int) $input['adults'],
                'children' => (int) ($input['children'] ?? 0),
            ]);
            $booking->forceFill([
                ...$quote,
                'code' => 'tmp-'.Str::lower(Str::random(14)), // replaced by TT-yy-NNNN right after insert
                'status' => BookingStatus::New,
                'tour_id' => $tour->id,
                'operator_id' => $tour->operator_id,
                'terms_accepted_at' => now(),
                'terms_version' => config('brand.terms_version'),
                'terms_ip' => $ip,
                'utm' => $input['utm'] ?? null,
            ]);
            $booking->setPublicToken($token);
            $booking->save();

            // Human code from the id: TT-26-0007. Sequential and unique without a separate counter.
            $booking->code = sprintf('%s-%s-%04d', config('brand.booking_prefix'), now(config('brand.timezone'))->format('y'), $booking->id);
            $booking->save();

            foreach (array_values($input['travelers'] ?? []) as $i => $name) {
                if (filled($name)) {
                    $booking->travelers()->create(['name' => $name, 'sort' => $i]);
                }
            }
            $booking->events()->create(['from_status' => null, 'to_status' => BookingStatus::New, 'note' => 'Request from the site']);

            return $booking;
        });

        DB::afterCommit(fn () => $this->notifier->requested($booking->fresh()));

        return [$booking, $token];
    }

    public function startChecking(Booking $booking, ?User $user = null): Booking
    {
        return $this->transitions->transition($booking, BookingStatus::Checking, $user);
    }

    /** The operator confirmed the spots: send the traveler the payment link, valid for 48 hours. */
    public function confirm(Booking $booking, string $paymentLinkUrl, ?User $user = null): Booking
    {
        // One transaction: if the seats can't be held, the booking stays where it was.
        return DB::transaction(function () use ($booking, $paymentLinkUrl, $user) {
            if ($booking->status === BookingStatus::New) {
                $this->startChecking($booking, $user);
            }

            return $this->transitions->transition($booking, BookingStatus::AwaitingPayment, $user, null, [
                'payment_link_url' => $paymentLinkUrl,
                'payment_link_expires_at' => now()->addHours(self::PAYMENT_LINK_HOURS),
                'payment_reminder_sent_at' => null,
            ]);
        });
    }

    public function decline(Booking $booking, ?string $note = null, ?User $user = null): Booking
    {
        return $this->transitions->transition($booking, BookingStatus::Declined, $user, $note);
    }

    /** Deposit received: record the payment, then send the voucher with the operator's contacts. */
    public function markPaid(Booking $booking, ?string $providerRef = null, ?User $user = null): Booking
    {
        if ($booking->status !== BookingStatus::AwaitingPayment) {
            throw new InvalidBookingAction('Only a booking that is awaiting the deposit can be marked as paid.');
        }

        return DB::transaction(function () use ($booking, $providerRef, $user) {
            $booking->payments()->create([
                'provider' => $this->gateway->name(),
                'provider_ref' => $providerRef,
                'amount_cents' => $booking->deposit_cents,
                'currency' => 'USD',
                'status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
            ]);
            $this->transitions->transition($booking, BookingStatus::DepositPaid, $user, $providerRef ? "Payment {$providerRef}" : null);

            return $this->transitions->transition($booking, BookingStatus::VoucherSent, null, 'Voucher sent automatically');
        });
    }

    public function expire(Booking $booking): Booking
    {
        return $this->transitions->transition($booking, BookingStatus::Expired, null, 'Payment link expired');
    }

    public function complete(Booking $booking): Booking
    {
        return $this->transitions->transition($booking, BookingStatus::Completed);
    }

    /** Traveler cancels: the refund follows RefundPolicy and is recorded for staff to pay back. */
    public function cancelByTourist(Booking $booking, ?string $reason = null, ?User $user = null): int
    {
        $refund = $this->refunds->touristCancellationCents($booking);
        $this->recordRefund($booking, $refund, 'Cancelled by traveler', $user);
        $this->transitions->transition($booking, BookingStatus::CancelledByTourist, $user, $reason, ['cancel_reason' => $reason]);

        return $refund;
    }

    public function cancelByOperator(Booking $booking, ?string $reason = null, ?User $user = null): int
    {
        $refund = $this->refunds->operatorCancellationCents($booking);
        $this->recordRefund($booking, $refund, 'Cancelled by operator', $user);
        $this->transitions->transition($booking, BookingStatus::CancelledByOperator, $user, $reason, ['cancel_reason' => $reason]);

        return $refund;
    }

    private function recordRefund(Booking $booking, int $amount, string $reason, ?User $user): void
    {
        $payment = $booking->payments()->where('status', PaymentStatus::Succeeded)->latest('id')->first();
        if ($amount <= 0 || ! $payment) {
            return;
        }
        // Pending until staff return the money in the acquirer's cabinet (API refunds come with step 6.4).
        $payment->refunds()->create([
            'amount_cents' => $amount,
            'reason' => $reason,
            'status' => RefundStatus::Pending,
            'user_id' => $user?->id,
        ]);
    }
}
