<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MapController;
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
    Route::get('map', MapController::class);

    Route::controller(BookingController::class)->group(function () {
        Route::post('bookings', 'store')->middleware('throttle:booking-requests');
        Route::get('bookings/{token}', 'show')->middleware('throttle:60,1');
        Route::post('bookings/{token}/cancel', 'cancel')->middleware('throttle:10,1');
    });

    // Traveler accounts: the site's BFF signs in with Google and keeps the Sanctum token in an httpOnly cookie.
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::get('providers', 'providers');
        Route::get('google/url', 'googleUrl');
        Route::post('google', 'google')->middleware('throttle:10,1');
        Route::post('dev-login', 'devLogin')->middleware('throttle:20,1');
        Route::post('logout', 'logout')->middleware('auth:sanctum');
    });

    Route::prefix('me')->middleware(['auth:sanctum', 'throttle:120,1'])->controller(AccountController::class)->group(function () {
        Route::get('/', 'me');
        Route::get('bookings', 'bookings');
        Route::post('bookings/{code}/review', 'review');
        Route::get('saved-tours', 'savedTours');
        Route::put('saved-tours/{slug}', 'saveTour');
        Route::delete('saved-tours/{slug}', 'unsaveTour');
    });
});
