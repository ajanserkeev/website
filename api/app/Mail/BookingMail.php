<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\Booking\RefundPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Traveler emails along the booking (launch document, section 11). One class, one template per type
 * in resources/views/mail/booking/{type}.blade.php.
 */
class BookingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const SUBJECTS = [
        'received' => 'Request received: :tour, :dates',
        'confirmed' => 'Confirmed: :tour, :dates. Pay :deposit to secure your spots',
        'payment_reminder' => 'Your spots are held for 24 more hours: :tour',
        'voucher' => 'Your voucher: :tour, :dates',
        'declined' => 'Not available: :tour, :dates',
        'expired' => 'Your hold has expired: :tour',
        'cancelled' => 'Booking cancelled: :tour, :dates',
        'cancelled_by_operator' => 'Your tour was cancelled by the operator: :tour',
        'pre_trip' => 'One week to go: :tour',
        'review_request' => 'How was :tour?',
    ];

    public function __construct(public Booking $booking, public string $type) {}

    public function envelope(): Envelope
    {
        $this->booking->loadMissing('tour');

        return new Envelope(
            subject: strtr(self::SUBJECTS[$this->type], [
                ':tour' => $this->booking->tour->title,
                ':dates' => $this->dates(),
                ':deposit' => '$'.number_format($this->booking->deposit_cents / 100),
            ]).' · '.$this->booking->code,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['tour', 'operator', 'travelers']);

        return new Content(
            markdown: "mail.booking.{$this->type}",
            with: [
                'booking' => $this->booking,
                'dates' => $this->dates(),
                'myBookingUrl' => $this->booking->myBookingUrl(),
                'money' => fn (int $cents) => '$'.number_format($cents / 100, $cents % 100 ? 2 : 0),
                'refundCents' => app(RefundPolicy::class)->touristCancellationCents($this->booking),
                'brand' => config('brand.name'),
                'whatsapp' => config('brand.whatsapp'),
            ],
        );
    }

    private function dates(): string
    {
        $from = $this->booking->date_from;
        $to = $this->booking->date_to;

        return match (true) {
            $from->equalTo($to) => $from->format('j F Y'),
            $from->format('Y-m') === $to->format('Y-m') => $from->format('j').'–'.$to->format('j F Y'),
            default => $from->format('j F').' – '.$to->format('j F Y'),
        };
    }
}
