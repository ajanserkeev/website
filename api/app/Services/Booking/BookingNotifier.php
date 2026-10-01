<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Jobs\SendTelegramMessage;
use App\Mail\BookingMail;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

/**
 * Who hears about what (launch document, section 11): the traveler by email in English,
 * the team in Telegram in Russian. Everything is queued.
 */
final class BookingNotifier
{
    public function requested(Booking $booking): void
    {
        $this->mail($booking, 'received');
        $this->telegram($booking, '🆕 Новая заявка');
    }

    public function statusChanged(Booking $booking, BookingStatus $from, BookingStatus $to): void
    {
        match ($to) {
            BookingStatus::AwaitingPayment => $this->mail($booking, 'confirmed'),
            BookingStatus::DepositPaid => $this->telegram($booking, '💰 Предоплата получена'),
            BookingStatus::VoucherSent => $this->mail($booking, 'voucher'),
            BookingStatus::Declined => $this->mail($booking, 'declined'),
            BookingStatus::Expired => $this->mail($booking, 'expired'),
            BookingStatus::CancelledByTourist => [$this->mail($booking, 'cancelled'), $this->telegram($booking, '❌ Турист отменил бронь')],
            BookingStatus::CancelledByOperator => [$this->mail($booking, 'cancelled_by_operator'), $this->telegram($booking, '⚠️ Фирма отменила бронь')],
            default => null,
        };
    }

    public function paymentReminder(Booking $booking): void
    {
        $this->mail($booking, 'payment_reminder');
    }

    public function preTripReminder(Booking $booking): void
    {
        $this->mail($booking, 'pre_trip');
    }

    public function reviewRequest(Booking $booking): void
    {
        $this->mail($booking, 'review_request');
    }

    private function mail(Booking $booking, string $type): void
    {
        Mail::to($booking->email, $booking->customer_name)->queue(new BookingMail($booking, $type));
    }

    private function telegram(Booking $booking, string $title): void
    {
        $booking->loadMissing('tour');
        $people = $booking->adults.' взр.'.($booking->children ? " + {$booking->children} дет." : '');
        $text = implode("\n", [
            "{$title} · {$booking->code}",
            $booking->tour->title,
            "{$booking->date_from->format('d.m.Y')} – {$booking->date_to->format('d.m.Y')}, {$people}",
            "{$booking->customer_name}".($booking->country ? " ({$booking->country})" : '').($booking->whatsapp ? ", {$booking->whatsapp}" : ''),
            'Сумма $'.number_format($booking->total_cents / 100).', предоплата $'.number_format($booking->deposit_cents / 100),
            rtrim((string) config('app.url'), '/')."/admin/bookings/{$booking->id}",
        ]);
        SendTelegramMessage::dispatch($text);
    }
}
