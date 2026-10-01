<x-mail::message>
# One week to go, {{ $booking->customer_name }}!

Your {{ $booking->tour->title }} starts on {{ $booking->date_from->format('j F') }}. Remember to bring {{ $money($booking->balance_cents) }} for the operator on day 1, warm layers and a power bank. If you haven't heard about the meeting point yet, message {{ $booking->operator->name }} or reply to this email.

@include('mail.booking._summary')

@include('mail.booking._footer')
</x-mail::message>
