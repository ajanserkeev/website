<?php

namespace App\Http\Resources\V1;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Booking\RefundPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The traveler's "My booking" page. Operator contacts appear only once the deposit is paid
 * (plan, recommendation 9); the payment link only while it is valid.
 *
 * @mixin Booking
 */
class MyBookingResource extends JsonResource
{
    private const PAID = [BookingStatus::DepositPaid, BookingStatus::VoucherSent, BookingStatus::Completed];

    private const CANCELLABLE = [
        BookingStatus::New, BookingStatus::Checking, BookingStatus::AwaitingPayment,
        BookingStatus::DepositPaid, BookingStatus::VoucherSent,
    ];

    public function toArray(Request $request): array
    {
        $paid = in_array($this->status, self::PAID, true);
        $linkValid = $this->status === BookingStatus::AwaitingPayment && $this->payment_link_url
            && $this->payment_link_expires_at?->isFuture();
        $operator = $this->operator;

        return [
            'code' => $this->code,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'createdAt' => $this->created_at->toIso8601String(),
            'tour' => [
                'slug' => $this->tour->slug,
                'title' => $this->tour->title,
                'image' => Photo::first($this->tour, 'gallery', $this->tour->title),
            ],
            'dateFrom' => $this->date_from->toDateString(),
            'dateTo' => $this->date_to->toDateString(),
            'adults' => $this->adults,
            'children' => $this->children,
            'travelers' => $this->travelers->pluck('name')->values(),
            'customerName' => $this->customer_name,
            'pricingSource' => $this->pricing_source->value,
            'totalCents' => $this->total_cents,
            'depositCents' => $this->deposit_cents,
            'balanceCents' => $this->balance_cents,
            'commissionRate' => (float) $this->commission_rate,
            'payment' => $linkValid ? [
                'url' => $this->payment_link_url,
                'expiresAt' => $this->payment_link_expires_at->toIso8601String(),
            ] : null,
            'paidAt' => $this->paid_at?->toIso8601String(),
            'operator' => [
                'name' => $operator->name,
                'baseCity' => $operator->base_city,
                // Revealed only after the deposit, in the voucher.
                'contacts' => $paid ? [
                    'contactName' => $operator->contact_name,
                    'whatsapp' => $operator->whatsapp,
                    'phone' => $operator->phone,
                    'email' => $operator->email,
                ] : null,
            ],
            'canCancel' => in_array($this->status, self::CANCELLABLE, true) && $this->date_from->isFuture(),
            'refundIfCancelledCents' => app(RefundPolicy::class)->touristCancellationCents($this->resource),
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'timeline' => $this->events->map(fn ($e) => [
                'status' => $e->to_status->value,
                'label' => $e->to_status->label(),
                'at' => $e->created_at->toIso8601String(),
            ])->values(),
        ];
    }
}
