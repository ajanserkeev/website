<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Staff alert to every user with a Telegram chat id (step 4.8). No-op until a bot token is configured. */
class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $text) {}

    public function handle(): void
    {
        $token = config('brand.telegram_bot_token');
        if (! $token) {
            return;
        }

        $chatIds = User::query()->whereNotNull('telegram_chat_id')->pluck('telegram_chat_id');
        foreach ($chatIds as $chatId) {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $this->text,
                'disable_web_page_preview' => true,
            ]);
            if ($response->failed()) {
                Log::warning('Telegram alert failed', ['chat_id' => $chatId, 'status' => $response->status()]);
            }
        }
    }
}
