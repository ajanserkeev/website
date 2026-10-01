<x-mail::message>
# You're booked, {{ $booking->customer_name }}!

We received your deposit of {{ $money($booking->deposit_cents) }}. Here is everything you need for the trip.

@include('mail.booking._summary')

## Your operator
@php($op = $booking->operator)
**{{ $op->name }}**{{ $op->base_city ? ', '.$op->base_city : '' }}<br>
@foreach (['Contact' => $op->contact_name, 'WhatsApp' => $op->whatsapp, 'Phone' => $op->phone, 'Email' => $op->email] as $label => $value)
@if ($value)
{{ $label }}: {{ $value }}<br>
@endif
@endforeach

**Pay the operator {{ $money($booking->balance_cents) }} on day 1**, in cash or by card if they accept cards. The operator will message you with the meeting point and time.

@if($booking->travelers->isNotEmpty())
Travelers: {{ $booking->travelers->pluck('name')->join(', ') }}
@endif

@if($myBookingUrl)
<x-mail::button :url="$myBookingUrl">My booking</x-mail::button>
@endif

@include('mail.booking._footer')
</x-mail::message>
