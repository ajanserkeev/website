<x-mail::panel>
**{{ $booking->tour->title }}**<br>
{{ $dates }} · {{ $booking->adults }} {{ Str::plural('adult', $booking->adults) }}{{ $booking->children ? ', '.$booking->children.' '.Str::plural('child', $booking->children) : '' }}<br>
Total {{ $money($booking->total_cents) }} · deposit {{ $money($booking->deposit_cents) }} · pay the operator {{ $money($booking->balance_cents) }} on day 1<br>
Booking {{ $booking->code }}
</x-mail::panel>
