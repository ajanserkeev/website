<x-mail::message>
# We're sorry: the operator cancelled your tour

{{ $booking->operator->name }} had to cancel {{ $booking->tour->title }} on your dates.
@if($booking->payments()->exists())
We'll refund your full deposit of **{{ $money($booking->deposit_cents) }}** within 10 working days.
@endif
Reply to this email and we'll help you find a similar tour on the same dates.

@include('mail.booking._summary')

@include('mail.booking._footer')
</x-mail::message>
