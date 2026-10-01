<?php

namespace App\Models;

use App\Enums\DepartureStatus;
use App\Enums\TourItemKind;
use App\Enums\TourStatus;
use App\Enums\TourType;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Guarded(['id'])]
class Tour extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TourType::class,
            'status' => TourStatus::class,
            'guide_languages' => 'array',
            'highlights' => 'array',
            'route_geojson' => 'array',
            'has_group_dates' => 'boolean',
            'has_private_option' => 'boolean',
            'commission_rate' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate();
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', TourStatus::Published);
    }

    /** Tour override first, otherwise the operator's rate. */
    public function effectiveCommissionRate(): float
    {
        return (float) ($this->commission_rate ?? $this->operator->commission_rate);
    }

    /** Departures that can still be requested: in the future and not cancelled (Bishkek dates). */
    public function upcomingDepartures(): HasMany
    {
        return $this->departures()
            ->where('starts_on', '>', Carbon::now(config('brand.timezone'))->toDateString())
            ->where('status', '!=', DepartureStatus::Cancelled)
            ->orderBy('starts_on');
    }

    /** Lowest per-person price across bookable departures and private prices, in cents. */
    public function priceFromCents(): ?int
    {
        $departure = $this->upcomingDepartures()->where('status', '!=', DepartureStatus::Full)->min('price_cents');
        $private = $this->privatePrices()->min('price_per_person_cents');
        $prices = array_filter([$departure, $private], fn ($p) => $p !== null);

        return $prices ? (int) min($prices) : null;
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(TourDay::class)->orderBy('day_number');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TourItem::class)->orderBy('sort');
    }

    public function includedItems(): HasMany
    {
        return $this->items()->where('kind', TourItemKind::Included);
    }

    public function excludedItems(): HasMany
    {
        return $this->items()->where('kind', TourItemKind::Excluded);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(TourFaq::class)->orderBy('sort');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(Departure::class);
    }

    public function privatePrices(): HasMany
    {
        return $this->hasMany(PrivatePrice::class)->orderBy('group_size_from');
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class)->withPivot('sort')->orderByPivot('sort');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withPivot('sort')->orderByPivot('sort');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('sort');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
