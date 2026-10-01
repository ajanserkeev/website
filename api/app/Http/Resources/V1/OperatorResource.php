<?php

namespace App\Http\Resources\V1;

use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public operator profile. Whitelisted fields only: phone, WhatsApp and email are revealed
 * to a traveler in the voucher after deposit_paid, never here.
 *
 * @mixin Operator
 */
class OperatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'baseCity' => $this->base_city,
            'foundedYear' => $this->founded_year,
            'description' => $this->description,
            'commissionRate' => (float) $this->commission_rate,
            'logo' => Photo::first($this->resource, 'logo', $this->name),
            'ratings' => collect([
                ['source' => 'TripAdvisor', 'rating' => $this->tripadvisor_rating, 'reviews' => $this->tripadvisor_reviews, 'url' => $this->tripadvisor_url],
                ['source' => 'Google', 'rating' => $this->google_rating, 'reviews' => $this->google_reviews, 'url' => $this->google_url],
            ])
                ->filter(fn ($r) => $r['rating'] !== null)
                ->map(fn ($r) => [...$r, 'rating' => (float) $r['rating'], 'reviews' => (int) $r['reviews']])
                ->values(),
            'guides' => $this->whenLoaded('guides', fn () => $this->guides->map(fn ($g) => [
                'name' => $g->name,
                'languages' => $g->languages ?? [],
                'note' => $g->note,
            ])->values(), []),
        ];
    }
}
