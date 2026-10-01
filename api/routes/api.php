<?php

use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    Route::controller(CatalogController::class)->group(function () {
        Route::get('tours', 'tours');
        Route::get('tours/{slug}', 'tour');
        Route::get('regions', 'regions');
        Route::get('regions/{slug}', 'region');
        Route::get('activities', 'activities');
        Route::get('activities/{slug}', 'activity');
        Route::get('collections', 'collections');
        Route::get('collections/{slug}', 'collection');
        Route::get('posts', 'posts');
        Route::get('posts/{slug}', 'post');
        Route::get('reviews/featured', 'featuredReviews');
        Route::get('currency-rates', 'currencyRates');
    });

    Route::controller(BookingController::class)->group(function () {
        Route::post('bookings', 'store')->middleware('throttle:booking-requests');
        Route::get('bookings/{token}', 'show')->middleware('throttle:60,1');
        Route::post('bookings/{token}/cancel', 'cancel')->middleware('throttle:10,1');
    });
});
