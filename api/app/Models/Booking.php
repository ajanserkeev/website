<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PricingSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A booking request and its deposit. Only the traveler's own fields are mass assignable:
 * prices, commission, status and the token are set by the booking services, never from a request
 * (launch document, section 07: amounts come from the database, not the browser).
 */
#[Fillable(['customer_name', 'email', 'whatsapp', 'country', 'special_requests', 'adults', 'children'])]
#[Hidden(['public_token_hash', 'public_token_encrypted', 'terms_ip'])]
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'pricing_source' => PricingSource::class,
            'date_from' => 'date',
            'date_to' => 'date',
            'commission_rate' => 'decimal:2',
            'terms_accepted_at' => 'datetime',
            'payment_link_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'voucher_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_reminder_sent_at' => 'datetime',
            'pre_trip_reminder_sent_at' => 'datetime',
            'review_requested_at' => 'datetime',
            'public_token_encrypted' => 'encrypted',
            'utm' => 'array',
        ];
    }

    /**
     * New secret for the "My booking" link. Only the SHA-256 hash is stored (plan, recommendation 8).
     *
     * @return array{0: string, 1: string} [plain token for the email, hash for the database]
     */
    public static function newPublicToken(): array
    {
        $token = Str::random(40);

        return [$token, self::hashToken($token)];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByPublicToken(string $token): ?self
    {
        return self::query()->where('public_token_hash', self::hashToken($token))->first();
    }

    /** Sets both the lookup hash and the encrypted copy used to put the link into later emails. */
    public function setPublicToken(string $token): void
    {
        $this->public_token_hash = self::hashToken($token);
        $this->public_token_encrypted = $token;
    }

    /** Link to the traveler's "My booking" page, or null for bookings created without a stored token. */
    public function myBookingUrl(): ?string
    {
        return $this->public_token_encrypted
            ? config('brand.frontend_url').'/booking/'.$this->public_token_encrypted
            : null;
    }

    public function travelerCount(): int
    {
        return $this->adults + $this->children;
    }

    /** Traveler's account: set when they booked signed in, or linked later by the same verified email. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function travelers(): HasMany
    {
        return $this->hasMany(BookingTraveler::class)->orderBy('sort');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
