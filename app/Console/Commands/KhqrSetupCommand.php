<?php

namespace App\Console\Commands;

use App\Models\AbaPaywayMerchant;
use App\Models\Author;
use App\Models\Books;
use App\Models\User;
use App\Services\AbaPayWayService;
use App\Services\StripePaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class KhqrSetupCommand extends Command
{
    protected $signature = 'elibra:khqr
        {action=status : status|setup|test}
        {--merchant_id= : ABA PayWay merchant_id}
        {--api_key= : ABA PayWay API key}
        {--email= : Author user email to attach merchant (setup)}
        {--environment=sandbox : sandbox|production}
        {--currency=USD : USD|KHR}
        {--book= : Book UUID for test generate}';

    protected $description = 'Check / setup / test KHQR (ABA PayWay + Stripe)';

    public function handle(AbaPayWayService $payway, StripePaymentService $stripe): int
    {
        return match ($this->argument('action')) {
            'setup' => $this->setup(),
            'test' => $this->testGenerate($payway),
            default => $this->status($payway, $stripe),
        };
    }

    private function status(AbaPayWayService $payway, StripePaymentService $stripe): int
    {
        $this->info('=== Elibra KHQR status ===');
        $this->line('Stripe configured: ' . ($stripe->isConfigured() ? 'yes' : 'no'));
        $this->line('Stripe KHQR enabled (env): ' . (config('services.stripe.khqr_enabled') ? 'yes' : 'no'));
        $this->line('Platform PayWay env: ' . ($payway->isPlatformConfigured() ? 'yes' : 'no'));
        $this->line('DB merchants: ' . AbaPaywayMerchant::count() . ' (active ' . AbaPaywayMerchant::where('is_active', true)->count() . ')');
        $this->line('Authors: ' . Author::count());
        $this->line('Paid books: ' . Books::where('price', '>', 0)->count());
        $this->line('Callback URL: ' . config('services.aba_payway.return_url'));

        if (! $payway->isPlatformConfigured() && AbaPaywayMerchant::where('is_active', true)->count() === 0) {
            $this->newLine();
            $this->warn('KHQR is NOT ready: no ABA merchant in DB (and no .env fallback).');
            $this->line('Save credentials in DB (recommended):');
            $this->line('  - Dashboard: Account → PayWay (check "Company / platform" for company KHQR)');
            $this->line('  - API author: PUT /api/v1/payway/me  { merchant_id, api_key, environment, currency }');
            $this->line('  - API admin:  POST /api/v1/payway/merchants  (+ is_platform=true for company)');
            $this->line('Or CLI: php artisan elibra:khqr setup --email=... --merchant_id=... --api_key=...');
            $this->line('Optional .env fallback only: ABA_PAYWAY_MERCHANT_ID + ABA_PAYWAY_API_KEY');
            $this->line('Then: php artisan elibra:khqr test');
        } else {
            $this->newLine();
            $this->info('PayWay credentials present (DB and/or .env). Run: php artisan elibra:khqr test');
        }

        if (! config('services.stripe.khqr_enabled')) {
            $this->warn('Stripe KHQR is disabled (account does not support khqr). Use payway_khqr.');
        }

        return self::SUCCESS;
    }

    private function setup(): int
    {
        $merchantId = (string) ($this->option('merchant_id') ?: config('services.aba_payway.merchant_id'));
        $apiKey = (string) ($this->option('api_key') ?: config('services.aba_payway.api_key'));
        $email = (string) $this->option('email');

        if ($merchantId === '' || $apiKey === '') {
            $this->error('Provide --merchant_id and --api_key (or set ABA_PAYWAY_MERCHANT_ID / ABA_PAYWAY_API_KEY in .env).');

            return self::FAILURE;
        }

        if ($email === '') {
            $author = Author::query()->with('user')->latest()->first();
            $user = $author?->user;
            if (! $user) {
                $this->error('No author found. Pass --email=author@...');

                return self::FAILURE;
            }
        } else {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->error("User not found: {$email}");

                return self::FAILURE;
            }
            if (! $user->authorProfile) {
                $this->error('That user has no author profile.');

                return self::FAILURE;
            }
        }

        $merchant = AbaPaywayMerchant::updateOrCreate(
            ['user_id' => $user->id],
            [
                'merchant_id' => $merchantId,
                'api_key' => $apiKey,
                'merchant_name' => $user->name . ' KHQR',
                'environment' => $this->option('environment') === 'production' ? 'production' : 'sandbox',
                'currency' => strtoupper((string) $this->option('currency') ?: 'USD'),
                'payment_option' => 'abapay_khqr',
                'is_active' => true,
                'notes' => 'Created via elibra:khqr setup',
            ]
        );

        $this->info("ABA PayWay merchant saved for {$user->email} ({$merchant->merchant_id}).");
        $this->line('Next: php artisan elibra:khqr test');

        return self::SUCCESS;
    }

    private function testGenerate(AbaPayWayService $payway): int
    {
        $bookId = $this->option('book');
        $book = $bookId
            ? Books::with('author.user')->find($bookId)
            : Books::with('author.user')->where('price', '>', 0)->whereNotNull('author_id')->latest()->first();

        if (! $book || ! $book->author?->user_id) {
            $this->error('No paid book with author found.');

            return self::FAILURE;
        }

        if (! $payway->isConfiguredFor($book->author->user_id)) {
            $this->error('No PayWay merchant for this book author (and no platform .env fallback). Run setup first.');

            return self::FAILURE;
        }

        $buyer = User::query()->whereHas('role', fn ($q) => $q->where('role', 'user'))->first()
            ?? User::query()->first();

        if (! $buyer) {
            $this->error('No buyer user in database.');

            return self::FAILURE;
        }

        $purchase = \App\Models\UserBuyBook::updateOrCreate(
            ['user_id' => $buyer->id, 'book_id' => $book->id],
            [
                'amount' => (float) $book->price,
                'payment_method' => 'payway_khqr',
                'status' => 'pending',
                'purchased_at' => null,
            ]
        );

        $tranId = Str::limit('T' . now()->format('ymdHis') . Str::upper(Str::random(3)), 20, '');
        $purchase->update(['payway_tran_id' => $tranId]);

        $this->line("Testing generate-qr for book: {$book->title}");
        $this->line("tran_id={$tranId}");

        try {
            $khqr = $payway->generateBookKhqr($buyer, $book, $purchase->fresh(), ['tran_id' => $tranId]);
        } catch (\Throwable $e) {
            $this->error('Generate failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line('status_code=' . ($khqr['status_code'] ?? 'null'));
        $this->line('status_message=' . ($khqr['status_message'] ?? ''));
        $this->line('qr_string=' . (filled($khqr['qr_string'] ?? null) ? 'yes' : 'no'));
        $this->line('qr_image=' . (filled($khqr['qr_image'] ?? null) ? 'yes' : 'no'));
        $this->line('deeplink=' . (filled($khqr['abapay_deeplink'] ?? null) ? 'yes' : 'no'));

        if (filled($khqr['qr_string'] ?? null) || filled($khqr['qr_image'] ?? null)) {
            $this->info('KHQR generation OK — ready to use with payment_method=payway_khqr');

            return self::SUCCESS;
        }

        $this->error('ABA did not return QR. Check merchant_id/api_key, sandbox flag, and domain whitelist in PayWay portal.');

        return self::FAILURE;
    }
}
