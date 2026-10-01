<?php

namespace App\Http\Resources\V1;

use App\Enums\DepartureStatus;
use App\Enums\TourItemKind;
use App\Models\Departure;
use App\Models\Tour;
use App\Services\Catalog\TourPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything the tour page shows. Matches the Tour type in web/src/lib/types.ts.
 * Operator contacts are never included (plan, recommendation 9).
 *
 * @mixin Tour
 */
class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $presenter = app(TourPresenter::class);

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => array_values(array_filter(array_map('trim', preg_split("/\R{2,}/", (string) $this->description)))),
            'type' => $this->type->value,
            'durationDays' => $this->duration_days,
            'regions' => $this->regions->map(fn ($r) => ['slug' => $r->slug, 'name' => $r->name])->values(),
            'activities' => $this->activities->map(fn ($a) => ['slug' => $a->slug, 'name' => $a->name])->values(),
            'difficulty' => $this->difficulty,
            'difficultyNote' => $this->difficulty_note,
            'groupSizeMin' => $this->group_size_min,
            'groupSizeMax' => $this->group_size_max,
            'guideLanguages' => $this->guide_languages ?? [],
            'route' => $this->route,
            'maxAltitudeM' => $this->max_altitude_m,
            'season' => ['from' => $this->season_from, 'to' => $this->season_to],
            'minAge' => $this->min_age,
            'images' => Photo::all($this->resource, 'gallery', $this->title),
            'highlights' => $this->highlights ?? [],
            'days' => $this->days->map(fn ($d) => [
                'day' => $d->day_number,
                'title' => $d->title,
                'description' => $d->description,
                'overnight' => $d->overnight,
                'meals' => $d->meals ?? [],
                'activityHours' => $d->activity_hours,
                'maxAltitudeM' => $d->max_altitude_m,
            ])->values(),
            'included' => $this->items->where('kind', TourItemKind::Included)->pluck('text')->values(),
            'excluded' => $this->items->where('kind', TourItemKind::Excluded)->pluck('text')->values(),
            'faqs' => $this->faqs->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])->values(),
            'operator' => new OperatorResource($this->operator),
            'commissionRate' => $this->effectiveCommissionRate(),
            'departures' => $presenter->upcomingDepartures($this->resource)->map(fn (Departure $d) => [
                'id' => (string) $d->id,
                'startsOn' => $d->starts_on->toDateString(),
                'endsOn' => $d->ends_on->toDateString(),
                'priceCents' => $d->price_cents,
                'childPriceCents' => $d->child_price_cents,
                'seatsLeft' => $d->status === DepartureStatus::Full ? 0 : $d->seatsLeft(),
                'status' => $d->status->value,
            ])->values(),
            'privatePrices' => $this->privatePrices->map(fn ($p) => [
                'groupSizeFrom' => $p->group_size_from,
                'groupSizeTo' => $p->group_size_to,
                'pricePerPersonCents' => $p->price_per_person_cents,
                'childPriceCents' => $p->child_price_cents,
            ])->values(),
            'reviews' => ReviewResource::collection($this->reviews),
            'priceFromCents' => $presenter->priceFromCents($this->resource),
            'depositFromCents' => $presenter->depositFromCents($this->resource),
            'rating' => $presenter->rating($this->resource),
            'badges' => $presenter->badges($this->resource),
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
        ];
    }
}
