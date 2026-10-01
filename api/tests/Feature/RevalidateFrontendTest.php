<?php

use App\Jobs\RevalidateFrontend;
use App\Models\Tour;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('queues a site cache reset when the catalog changes', function () {
    Queue::fake();

    $tour = Tour::factory()->create();
    $tour->update(['title' => 'Renamed tour']);

    Queue::assertPushed(RevalidateFrontend::class);
});

it('calls the site with the shared secret', function () {
    config(['brand.frontend_internal_url' => 'http://web:3000', 'brand.frontend_revalidate_secret' => 'shh']);
    Http::fake(['web:3000/*' => Http::response(['revalidated' => true])]);

    (new RevalidateFrontend)->handle();

    Http::assertSent(fn ($request) => $request->url() === 'http://web:3000/api/revalidate'
        && $request->hasHeader('Authorization', 'Bearer shh'));
});

it('does nothing without a secret', function () {
    config(['brand.frontend_revalidate_secret' => null]);
    Http::fake();

    (new RevalidateFrontend)->handle();

    Http::assertNothingSent();
});
