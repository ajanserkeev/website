<x-mail::message>
# Your hold has expired

We didn't receive the deposit within 48 hours, so the spots on {{ $booking->tour->title }} were released. If you still want to go, reply to this email and we'll check availability again.

@include('mail.booking._summary')

@include('mail.booking._footer')
</x-mail::message>
