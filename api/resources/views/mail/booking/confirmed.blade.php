<x-mail::message>
# Good news, {{ $booking->customer_name }}!

{{ $booking->operator->name }} has confirmed your spots. Pay the deposit of **{{ $money($booking->deposit_cents) }}** to secure them; the rest, {{ $money($booking->balance_cents) }}, you pay the operator on day 1.

@include('mail.booking._summary')

<x-mail::button :url="$booking->payment_link_url" color="success">Pay {{ $money($booking->deposit_cents) }} securely</x-mail::button>

This link is valid until {{ $booking->payment_link_expires_at->timezone(config('brand.timezone'))->format('j F, H:i') }} (Bishkek time). Cancel 30 or more days before departure and you get the deposit back in full.

@if($myBookingUrl)
[My booking]({{ $myBookingUrl }})
@endif

@include('mail.booking._footer')
</x-mail::message>
