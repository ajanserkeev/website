<x-mail::message>
# Thanks, {{ $booking->customer_name }}!

We're checking spots with {{ $booking->operator->name }} for your dates. You'll hear from us within 24 hours, usually much sooner. **No payment is needed yet.**

@include('mail.booking._summary')

@if($myBookingUrl)
<x-mail::button :url="$myBookingUrl">My booking</x-mail::button>
@endif

@include('mail.booking._footer')
</x-mail::message>
