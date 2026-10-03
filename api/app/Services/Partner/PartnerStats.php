<?php

namespace App\Services\Partner;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Numbers of the partner cabinet (and of the platform for the super admin when no operator is given).
 *
 * Only bookings with the deposit paid count as sold: the traveler paid the platform its commission
 * (deposit_cents, the rate snapshotted on the booking) and pays the operator the rest on arrival (balance_cents).
 * Requests still waiting and cancellations are shown apart.
 */
final class PartnerStats
{
    public const SOLD = [BookingStatus::DepositPaid, BookingStatus::VoucherSent, BookingStatus::Completed];

    public const WAITING = [BookingStatus::New, BookingStatus::Checking, BookingStatus::AwaitingPayment];

    public const CANCELLED = [BookingStatus::Declined, BookingStatus::Expired, BookingStatus::CancelledByTourist, BookingStatus::CancelledByOperator];

    /**
     * @param  'trip'|'booked'  $dateBy  filter by the trip start date or by the day the booking was made
     */
    public function __construct(
        private readonly ?int $operatorId,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
        private readonly string $dateBy = 'trip',
        private readonly ?int $tourId = null,
    ) {}

    /** The same numbers for one tour, all time. */
    public static function forTour(?int $operatorId, int $tourId): self
    {
        return new self($operatorId, tourId: $tourId);
    }

    /** From the dashboard filters form; invalid values are ignored (the form is live and not validated). */
    public static function fromFilters(?int $operatorId, ?array $filters): self
    {
        $date = fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value) ? substr($value, 0, 10) : null;

        return new self(
            $operatorId,
            $date($filters['from'] ?? null),
            $date($filters['to'] ?? null),
            ($filters['date_by'] ?? 'trip') === 'booked' ? 'booked' : 'trip',
        );
    }

    /** Bookings of the operator within the period, any status. */
    public function bookings(): Builder
    {
        $column = $this->dateBy === 'booked' ? 'bookings.created_at' : 'bookings.date_from';

        return Booking::query()
            ->when($this->operatorId, fn (Builder $q, int $id) => $q->where('bookings.operator_id', $id))
            ->when($this->tourId, fn (Builder $q, int $id) => $q->where('bookings.tour_id', $id))
            ->when($this->from, fn (Builder $q, string $from) => $q->whereDate($column, '>=', $from))
            ->when($this->to, fn (Builder $q, string $to) => $q->whereDate($column, '<=', $to));
    }

    public function sold(): Builder
    {
        return $this->bookings()->whereIn('bookings.status', self::SOLD);
    }

    /**
     * @return array{bookings: int, travelers: int, total_cents: int, commission_cents: int, operator_cents: int, waiting: int, cancelled: int, average_commission_rate: float|null}
     */
    public function totals(): array
    {
        $row = $this->sold()->selectRaw(
            'count(*) as bookings, coalesce(sum(adults + children), 0) as travelers, coalesce(sum(total_cents), 0) as total,
             coalesce(sum(deposit_cents), 0) as commission, coalesce(sum(balance_cents), 0) as operator'
        )->toBase()->first();

        return [
            'bookings' => (int) $row->bookings,
            'travelers' => (int) $row->travelers,
            'total_cents' => (int) $row->total,
            'commission_cents' => (int) $row->commission,
            'operator_cents' => (int) $row->operator,
            'waiting' => $this->bookings()->whereIn('status', self::WAITING)->count(),
            'cancelled' => $this->bookings()->whereIn('status', self::CANCELLED)->count(),
            'average_commission_rate' => $row->total > 0 ? round($row->commission / $row->total * 100, 1) : null,
        ];
    }

    /**
     * Sold bookings per month of the period, for the chart.
     *
     * @return array<string, array{bookings: int, travelers: int, total_cents: int, commission_cents: int, operator_cents: int}> keyed by Y-m
     */
    public function byMonth(): array
    {
        $months = [];
        $column = $this->dateBy === 'booked' ? 'created_at' : 'date_from';
        foreach ($this->sold()->get(['adults', 'children', 'total_cents', 'deposit_cents', 'balance_cents', 'date_from', 'created_at']) as $booking) {
            $key = Carbon::parse($booking->{$column})->format('Y-m');
            $months[$key] ??= ['bookings' => 0, 'travelers' => 0, 'total_cents' => 0, 'commission_cents' => 0, 'operator_cents' => 0];
            $months[$key]['bookings']++;
            $months[$key]['travelers'] += $booking->adults + $booking->children;
            $months[$key]['total_cents'] += $booking->total_cents;
            $months[$key]['commission_cents'] += $booking->deposit_cents;
            $months[$key]['operator_cents'] += $booking->balance_cents;
        }
        ksort($months);

        return $months;
    }

    /** Aggregates of sold bookings to attach to a tours (or operators) query with withCount / withSum. */
    public function soldConstraint(): \Closure
    {
        $sold = $this->sold();

        return fn (Builder $q) => $q->whereIn('bookings.id', $sold->select('bookings.id'));
    }
}
