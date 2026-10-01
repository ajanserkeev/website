<x-mail::message>
# Sorry, {{ $booking->customer_name }}

{{ $booking->operator->name }} can't take your group on these dates. You haven't paid anything. Reply to this email and we'll suggest other dates or a similar tour.

@include('mail.booking._summary')

@include('mail.booking._footer')
</x-mail::message>
