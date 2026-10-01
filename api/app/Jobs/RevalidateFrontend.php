<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells the Next.js site to drop its cached catalog after an admin edit (plan, recommendation 13).
 * Unique for a few seconds: saving a tour with its days, dates and photos triggers one call, not dozens.
 * If the site is unreachable the cache simply expires on its own within 5 minutes.
 */
class RevalidateFrontend implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 5;

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(): void
    {
        $url = config('brand.frontend_internal_url');
        $secret = config('brand.frontend_revalidate_secret');
        if (! $url || ! $secret) {
            return;
        }

        try {
            Http::timeout(5)->withToken($secret)->post("{$url}/api/revalidate")->throw();
        } catch (Throwable $e) {
            Log::warning('Frontend revalidation failed', ['error' => $e->getMessage()]);
            $this->release($this->backoff);
        }
    }
}
