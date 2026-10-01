<x-mail::message>
# How was {{ $booking->tour->title }}?

We hope the trip was everything you wanted. Would you share a short review? It takes two minutes and helps other travelers choose.

@if($myBookingUrl)
<x-mail::button :url="$myBookingUrl . '#review'">Write a review</x-mail::button>
@endif

Booking {{ $booking->code }}, {{ $dates }}.

@include('mail.booking._footer')
</x-mail::message>
