<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Booking lifecycle (launch document, section 02). Every transition is written to booking_events;
 * the transition service (step 4.7) only allows moves listed in allowedTransitions().
 */
enum BookingStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Checking = 'checking';
    case AwaitingPayment = 'awaiting_payment';
    case DepositPaid = 'deposit_paid';
    case VoucherSent = 'voucher_sent';
    case Completed = 'completed';
    case Declined = 'declined';
    case Expired = 'expired';
    case CancelledByTourist = 'cancelled_by_tourist';
    case CancelledByOperator = 'cancelled_by_operator';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New request',
            self::Checking => 'Checking with operator',
            self::AwaitingPayment => 'Awaiting deposit',
            self::DepositPaid => 'Deposit paid',
            self::VoucherSent => 'Voucher sent',
            self::Completed => 'Completed',
            self::Declined => 'Declined by operator',
            self::Expired => 'Payment link expired',
            self::CancelledByTourist => 'Cancelled by traveler',
            self::CancelledByOperator => 'Cancelled by operator',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Checking, self::Declined],
            self::Checking => [self::AwaitingPayment, self::Declined],
            self::AwaitingPayment => [self::DepositPaid, self::Expired, self::CancelledByTourist],
            self::DepositPaid => [self::VoucherSent, self::CancelledByTourist, self::CancelledByOperator],
            self::VoucherSent => [self::Completed, self::CancelledByTourist, self::CancelledByOperator],
            self::Completed, self::Declined, self::Expired, self::CancelledByTourist, self::CancelledByOperator => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** Seats are held from confirmation until the booking ends or is cancelled (plan, recommendation 7). */
    public function holdsSeats(): bool
    {
        return in_array($this, [self::AwaitingPayment, self::DepositPaid, self::VoucherSent, self::Completed], true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /** Russian label for the admin panel. */
    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новая заявка',
            self::Checking => 'Уточняем у фирмы',
            self::AwaitingPayment => 'Ждём предоплату',
            self::DepositPaid => 'Предоплата получена',
            self::VoucherSent => 'Ваучер отправлен',
            self::Completed => 'Тур прошёл',
            self::Declined => 'Фирма отказала',
            self::Expired => 'Не оплачено (истекло)',
            self::CancelledByTourist => 'Отменил турист',
            self::CancelledByOperator => 'Отменила фирма',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::Checking => 'warning',
            self::AwaitingPayment => 'warning',
            self::DepositPaid => 'success',
            self::VoucherSent => 'success',
            self::Completed => 'gray',
            self::Declined => 'gray',
            self::Expired => 'gray',
            self::CancelledByTourist => 'gray',
            self::CancelledByOperator => 'gray',
        };
    }
}
