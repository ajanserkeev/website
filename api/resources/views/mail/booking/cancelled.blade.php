<x-mail::message>
# Your booking is cancelled

We've cancelled booking {{ $booking->code }} as you asked.
@if($booking->payments()->exists())
@php($refund = $booking->payments->flatMap->refunds->sum('amount_cents'))
@if($refund > 0)
We'll refund **{{ $money($refund) }}** to your card within 10 working days.
@else
Under the cancellation policy the deposit is not refundable this close to departure.
@endif
@endif

@include('mail.booking._summary')

@include('mail.booking._footer')
</x-mail::message>
