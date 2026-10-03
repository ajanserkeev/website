<?php

namespace App\Providers;

use App\Jobs\RevalidateFrontend;
use App\Models\Activity;
use App\Models\Collection;
use App\Models\CurrencyRate;
use App\Models\Departure;
use App\Models\Operator;
use App\Models\OperatorGuide;
use App\Models\Place;
use App\Models\Post;
use App\Models\PrivatePrice;
use App\Models\Region;
use App\Models\Review;
use App\Models\Tour;
use App\Models\TourDay;
use App\Models\TourFaq;
use App\Models\TourItem;
use App\Payments\ManualGateway;
use App\Payments\PaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /** Models whose changes show up on the public site. */
    private const CATALOG_MODELS = [
        Tour::class, TourDay::class, TourItem::class, TourFaq::class, Departure::class, PrivatePrice::class,
        Operator::class, OperatorGuide::class, Region::class, Activity::class, Collection::class, Post::class,
        Review::class, CurrencyRate::class, Place::class, Media::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Manual payments until an acquirer is connected (step 6.2 adds its driver).
        $this->app->bind(PaymentGateway::class, ManualGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 5 booking requests per hour per traveler IP (step 4.6). The site's BFF forwards the real IP.
        RateLimiter::for('booking-requests', fn (Request $request) => Limit::perHour(5)->by($request->ip()));

        $revalidate = fn () => RevalidateFrontend::dispatch()->afterCommit();
        foreach (self::CATALOG_MODELS as $model) {
            $model::saved($revalidate);
            $model::deleted($revalidate);
        }
    }
}
