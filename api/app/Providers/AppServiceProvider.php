<?php

namespace App\Providers;

use App\Jobs\RevalidateFrontend;
use App\Models\Activity;
use App\Models\Collection;
use App\Models\CurrencyRate;
use App\Models\Departure;
use App\Models\Operator;
use App\Models\OperatorGuide;
use App\Models\Post;
use App\Models\PrivatePrice;
use App\Models\Region;
use App\Models\Review;
use App\Models\Tour;
use App\Models\TourDay;
use App\Models\TourFaq;
use App\Models\TourItem;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /** Models whose changes show up on the public site. */
    private const CATALOG_MODELS = [
        Tour::class, TourDay::class, TourItem::class, TourFaq::class, Departure::class, PrivatePrice::class,
        Operator::class, OperatorGuide::class, Region::class, Activity::class, Collection::class, Post::class,
        Review::class, CurrencyRate::class, Media::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $revalidate = fn () => RevalidateFrontend::dispatch()->afterCommit();
        foreach (self::CATALOG_MODELS as $model) {
            $model::saved($revalidate);
            $model::deleted($revalidate);
        }
    }
}
