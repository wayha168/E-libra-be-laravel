<?php

namespace App\Console\Commands;

use App\Support\TelegramNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('telegram:test')]
#[Description('List recent Telegram chat IDs (when chat_id is empty) or send a test alert')]
class TelegramTest extends Command
{
    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in your .env.');

            return self::FAILURE;
        }

        if (empty($chatId)) {
            $this->warn('TELEGRAM_CHAT_ID is empty. Send any message to your bot, then read the chat id(s) below.');

            $response = Http::timeout(8)->get("https://api.telegram.org/bot{$token}/getUpdates");

            if (!$response->successful()) {
                $this->error('getUpdates failed: ' . $response->body());

                return self::FAILURE;
            }

            $updates = $response->json('result', []);

            if (empty($updates)) {
                $this->line('No updates yet. Open Telegram, message your bot, then run this again.');

                return self::SUCCESS;
            }

            foreach ($updates as $update) {
                $chat = $update['message']['chat'] ?? $update['channel_post']['chat'] ?? null;
                if ($chat) {
                    $this->info(sprintf(
                        'chat_id=%s  type=%s  name=%s',
                        $chat['id'] ?? '?',
                        $chat['type'] ?? '?',
                        $chat['title'] ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')) ?: ($chat['username'] ?? '')
                    ));
                }
            }

            $this->line('Copy the chat_id you want into TELEGRAM_CHAT_ID in .env, then run: php artisan telegram:test');

            return self::SUCCESS;
        }

        $ok = TelegramNotifier::sendMessage(
            "\u{2705} *e\\-Libra test alert*\n\nYour Telegram sale alerts are configured correctly\\."
        );

        if ($ok) {
            $this->info('Test message sent to chat_id ' . $chatId);

            return self::SUCCESS;
        }

        $this->error('Failed to send test message. Check storage/logs/laravel.log for details.');

        return self::FAILURE;
    }
}
