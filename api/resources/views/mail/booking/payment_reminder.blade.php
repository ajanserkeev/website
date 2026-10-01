<x-mail::message>
# Your spots are held for 24 more hours

Your spots on {{ $booking->tour->title }} are still held. Pay {{ $money($booking->deposit_cents) }} before {{ $booking->payment_link_expires_at->timezone(config('brand.timezone'))->format('j F, H:i') }} (Bishkek time) to keep them. Questions before paying? Just reply.

<x-mail::button :url="$booking->payment_link_url" color="success">Pay {{ $money($booking->deposit_cents) }} securely</x-mail::button>

@include('mail.booking._summary')

@include('mail.booking._footer')
</x-mail::message>
