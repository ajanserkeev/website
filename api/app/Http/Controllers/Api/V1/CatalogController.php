<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Photo;
use App\Http\Resources\V1\PostResource;
use App\Http\Resources\V1\ReviewResource;
use App\Http\Resources\V1\TaxonomyResource;
use App\Http\Resources\V1\TourResource;
use App\Http\Resources\V1\TourSummaryResource;
use App\Models\Activity;
use App\Models\Collection;
use App\Models\CurrencyRate;
use App\Models\Post;
use App\Models\Region;
use App\Models\Review;
use App\Services\Catalog\TourCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/** Public read API for the site (launch document, section 07). */
class CatalogController extends Controller
{
    public function __construct(private readonly TourCatalog $catalog) {}

    public function tours(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'activity' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'date_format:Y-m'],
            'duration' => ['nullable', Rule::in(TourCatalog::DURATIONS)],
            'difficulty' => ['nullable', Rule::in(TourCatalog::DIFFICULTIES)],
            'maxPrice' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in(TourCatalog::SORTS)],
            'slugs' => ['nullable', 'array', 'max:50'],
            'slugs.*' => ['string'],
        ]);

        $tours = $this->catalog->search($filters);

        return TourSummaryResource::collection($tours)->additional(['meta' => ['total' => $tours->count()]]);
    }

    public function tour(string $slug): TourResource
    {
        return new TourResource($this->catalog->find($slug) ?? abort(404));
    }

    public function regions(): AnonymousResourceCollection
    {
        return TaxonomyResource::collection(Region::query()->withCount(['tours' => $this->publicTours(...)])->with('media')->orderBy('sort')->get());
    }

    public function region(string $slug): TaxonomyResource
    {
        $region = Region::query()->withCount(['tours' => $this->publicTours(...)])->where('slug', $slug)->first();

        return new TaxonomyResource($region ?? abort(404));
    }

    public function activities(): AnonymousResourceCollection
    {
        return TaxonomyResource::collection(Activity::query()->withCount(['tours' => $this->publicTours(...)])->with('media')->orderBy('sort')->get());
    }

    public function activity(string $slug): TaxonomyResource
    {
        $activity = Activity::query()->withCount(['tours' => $this->publicTours(...)])->where('slug', $slug)->first();

        return new TaxonomyResource($activity ?? abort(404));
    }

    public function collections(Request $request): JsonResponse
    {
        $collections = Collection::query()
            ->when($request->boolean('featured'), fn (Builder $q) => $q->where('is_featured', true))
            ->with(['media', 'tours' => fn ($q) => $this->publicTours($q)->with(TourCatalog::eagerLoads())])
            ->orderBy('sort')
            ->get();

        return response()->json(['data' => $collections->map(fn (Collection $c) => $this->collectionData($c, $request))]);
    }

    public function collection(Request $request, string $slug): JsonResponse
    {
        $collection = Collection::query()
            ->with(['media', 'tours' => fn ($q) => $this->publicTours($q)->with(TourCatalog::eagerLoads())])
            ->where('slug', $slug)
            ->first() ?? abort(404);

        return response()->json(['data' => $this->collectionData($collection, $request)]);
    }

    public function posts(): AnonymousResourceCollection
    {
        return PostResource::collection(Post::query()->published()->with('media')->latest('published_at')->get());
    }

    public function post(string $slug): PostResource
    {
        $post = Post::query()->published()
            ->with(['media', 'tours' => fn ($q) => $this->publicTours($q)->with(TourCatalog::eagerLoads())])
            ->where('slug', $slug)
            ->first();

        return new PostResource($post ?? abort(404));
    }

    public function featuredReviews(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->integer('limit', 3), 12);

        return ReviewResource::collection(
            Review::query()->published()
                ->whereHas('tour', fn (Builder $q) => $this->publicTours($q))
                ->with('tour')
                ->orderByDesc('trip_month')
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get()
        );
    }

    public function currencyRates(): JsonResponse
    {
        return response()->json([
            'data' => CurrencyRate::query()->pluck('rate_per_usd', 'code')->map(fn ($rate) => (float) $rate),
        ]);
    }

    /** Published tours of active operators: the same rule as TourCatalog::query(). */
    private function publicTours(Builder|Relation $query): Builder|Relation
    {
        return $query->published()->whereHas('operator', fn (Builder $q) => $q->where('is_active', true));
    }

    private function collectionData(Collection $collection, Request $request): array
    {
        return [
            'slug' => $collection->slug,
            'title' => $collection->title,
            'intro' => $collection->intro,
            'hero' => Photo::first($collection, 'hero', $collection->title),
            'tours' => TourSummaryResource::collection($collection->tours)->resolve($request),
        ];
    }
}
