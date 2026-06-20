<?php

namespace App\Support;

use App\Models\UserBuyBook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public static function isConfigured(): bool
    {
        return (bool) config('services.telegram.enabled', true)
            && !empty(config('services.telegram.bot_token'))
            && !empty(config('services.telegram.chat_id'));
    }

    public static function sendPurchasePaid(UserBuyBook $purchase): void
    {
        if (!self::isConfigured()) {
            return;
        }

        $purchase->loadMissing(['user:id,name,email', 'book:id,title']);

        $buyer = $purchase->user?->name ?? 'Unknown buyer';
        $title = $purchase->book?->title ?? 'Unknown book';
        $amount = '$' . number_format((float) ($purchase->amount ?? 0), 2);
        $method = $purchase->paymentMethodLabel();
        $when = optional($purchase->purchased_at ?? now())->format('Y-m-d H:i');

        $text = "\u{2705} *New Book Sale*\n\n"
            . "\u{1F4D6} *Book:* " . self::escape($title) . "\n"
            . "\u{1F464} *Buyer:* " . self::escape($buyer) . "\n"
            . "\u{1F4B5} *Amount:* " . self::escape($amount) . "\n"
            . "\u{1F4B3} *Method:* " . self::escape($method) . "\n"
            . "\u{1F552} *Date:* " . self::escape($when);

        self::sendMessage($text);
    }

    public static function sendMessage(string $text, ?string $chatId = null): bool
    {
        $token = config('services.telegram.bot_token');
        $chatId = $chatId ?? config('services.telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'MarkdownV2',
                    'disable_web_page_preview' => true,
                ]);

            if (!$response->successful()) {
                Log::warning('Telegram sendMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Escape text for Telegram MarkdownV2.
     * Reserved chars: _ * [ ] ( ) ~ ` > # + - = | { } . !
     */
    public static function escape(string $value): string
    {
        return preg_replace('/([_*\[\]()~`>#+\-=|{}.!\\\\])/', '\\\\$1', $value);
    }
}
