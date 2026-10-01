<?php

namespace App\Services\Booking;

use App\Models\Booking;

/**
 * WhatsApp texts for the operator in Russian (launch document, section 11): the availability request,
 * and the booking sheet after the deposit. Staff send them from the admin with one click.
 */
final class OperatorMessages
{
    public static function availabilityRequest(Booking $booking): string
    {
        $booking->loadMissing(['tour', 'operator']);
        $money = fn (int $c) => '$'.number_format($c / 100);

        return implode("\n", array_filter([
            'Здравствуйте'.($booking->operator->contact_name ? ", {$booking->operator->contact_name}" : '').'! Новый запрос с '.config('brand.name').", бронь {$booking->code}.",
            "Тур: {$booking->tour->title}",
            'Даты: '.self::dates($booking).($booking->departure_id ? ' (групповой заезд)' : ' (индивидуально)'),
            'Туристы: '.self::people($booking).($booking->country ? ", {$booking->country}" : '').'.',
            $booking->special_requests ? "Пожелания: {$booking->special_requests}" : null,
            "Сумма тура: {$money($booking->total_cents)}. Турист платит нам предоплату {$money($booking->deposit_cents)} ({$booking->commission_rate}%), вам на месте {$money($booking->balance_cents)}.",
            'Подтвердите, пожалуйста, наличие мест. Если даты заняты, предложите ближайшие свободные.',
        ]));
    }

    public static function bookingSheet(Booking $booking): string
    {
        $booking->loadMissing(['tour', 'travelers']);
        $money = fn (int $c) => '$'.number_format($c / 100);
        $names = $booking->travelers->pluck('name')->join(', ') ?: $booking->customer_name;

        return implode("\n", array_filter([
            "Бронь {$booking->code} оплачена ✅",
            "Тур: {$booking->tour->title}, ".self::dates($booking),
            "Туристы: {$names}".($booking->country ? " ({$booking->country})" : ''),
            $booking->whatsapp ? "WhatsApp туриста: {$booking->whatsapp}" : null,
            "Email туриста: {$booking->email}",
            "Получить с туристов на месте: {$money($booking->balance_cents)}",
            $booking->special_requests ? "Особые пожелания: {$booking->special_requests}" : null,
            'Пожалуйста, напишите туристу место и время встречи.',
        ]));
    }

    public static function whatsappUrl(?string $phone, string $text): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits ? "https://wa.me/{$digits}?text=".rawurlencode($text) : null;
    }

    private static function dates(Booking $booking): string
    {
        return $booking->date_from->format('d.m.Y').' – '.$booking->date_to->format('d.m.Y');
    }

    private static function people(Booking $booking): string
    {
        return $booking->adults.' взр.'.($booking->children ? " + {$booking->children} дет." : '');
    }
}
