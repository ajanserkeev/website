<?php

namespace App\Http\Resources\V1;

use App\Models\Tour;
use App\Services\Catalog\TourPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tour card for lists (catalog, collections, guides). Matches TourCardData in web/src/lib/catalog.ts.
 *
 * @mixin Tour
 */
class TourSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $presenter = app(TourPresenter::class);
        $level = $presenter->difficultyLevel($this->resource);

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'type' => $this->type->value,
            'image' => Photo::first($this->resource, 'gallery', $this->title),
            'durationDays' => $this->duration_days,
            'regions' => $this->regions->map(fn ($r) => ['slug' => $r->slug, 'name' => $r->name])->values(),
            'regionNames' => $this->regions->pluck('name')->values(),
            'activities' => $this->activities->map(fn ($a) => ['slug' => $a->slug, 'name' => $a->name])->values(),
            'difficulty' => ucfirst($level),
            'difficultyLevel' => $level,
            'priceFromCents' => $presenter->priceFromCents($this->resource),
            'depositFromCents' => $presenter->depositFromCents($this->resource),
            'commissionRate' => $this->effectiveCommissionRate(),
            'rating' => $presenter->rating($this->resource),
            'badges' => $presenter->badges($this->resource),
        ];
    }
}
