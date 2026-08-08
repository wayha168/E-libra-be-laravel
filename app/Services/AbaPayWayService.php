<?php

namespace App\Services;

use App\Models\AbaPaywayMerchant;
use App\Models\Books;
use App\Models\User;
use App\Models\UserBuyBook;
use App\Support\BookPricing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AbaPayWayService
{
    public const SOURCE_AUTHOR = 'author';

    public const SOURCE_COMPANY_DB = 'company_db';

    public const SOURCE_ADMIN_DB = 'admin_db';

    public const SOURCE_ENV = 'env';

    /**
     * Author personal merchant from DB only (admin/author input).
     */
    public function personalMerchantFor(User|string $user): ?AbaPaywayMerchant
    {
        $userId = $user instanceof User ? $user->id : $user;

        return AbaPaywayMerchant::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Company/platform merchant saved in DB (admin input, is_platform=true).
     */
    public function companyMerchantFromDb(): ?AbaPaywayMerchant
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('aba_payway_merchants', 'is_platform')) {
            return null;
        }

        return AbaPaywayMerchant::query()
            ->where('is_active', true)
            ->where('is_platform', true)
            ->latest()
            ->first();
    }

    /**
     * Any active merchant owned by admin/super_admin (DB fallback).
     */
    public function adminMerchantFromDb(): ?AbaPaywayMerchant
    {
        return AbaPaywayMerchant::query()
            ->where('is_active', true)
            ->whereHas('user.role', fn ($q) => $q->whereIn('role', ['admin', 'super_admin']))
            ->latest()
            ->first();
    }

    /**
     * Resolve merchant for a book purchase (DB first, .env last).
     *
     * Order:
     * 1) book author's personal DB merchant
     * 2) company platform merchant in DB (is_platform)
     * 3) any admin/super_admin merchant in DB
     * 4) optional .env platform credentials
     *
     * @return array{merchant: ?AbaPaywayMerchant, source: ?string}
     */
    public function resolveForBook(?Books $book): array
    {
        $authorUserId = null;
        if ($book) {
            $book->loadMissing('author');
            $authorUserId = $book->author?->user_id;
        }

        if ($authorUserId) {
            $personal = $this->personalMerchantFor($authorUserId);
            if ($this->isUsable($personal)) {
                return ['merchant' => $personal, 'source' => self::SOURCE_AUTHOR];
            }
        }

        return $this->resolveCompanyFallback();
    }

    /**
     * Resolve merchant for a user (author) with company DB/.env fallback.
     *
     * @return array{merchant: ?AbaPaywayMerchant, source: ?string}
     */
    public function resolveForUser(User|string|null $user): array
    {
        if ($user) {
            $personal = $this->personalMerchantFor($user);
            if ($this->isUsable($personal)) {
                return ['merchant' => $personal, 'source' => self::SOURCE_AUTHOR];
            }
        }

        return $this->resolveCompanyFallback();
    }

    /**
     * @return array{merchant: ?AbaPaywayMerchant, source: ?string}
     */
    public function resolveCompanyFallback(): array
    {
        $company = $this->companyMerchantFromDb();
        if ($this->isUsable($company)) {
            return ['merchant' => $company, 'source' => self::SOURCE_COMPANY_DB];
        }

        $admin = $this->adminMerchantFromDb();
        if ($this->isUsable($admin)) {
            return ['merchant' => $admin, 'source' => self::SOURCE_ADMIN_DB];
        }

        $env = $this->platformMerchantFromEnv();
        if ($this->isUsable($env)) {
            return ['merchant' => $env, 'source' => self::SOURCE_ENV];
        }

        return ['merchant' => null, 'source' => null];
    }

    public function forUser(User|string $user): ?AbaPaywayMerchant
    {
        return $this->resolveForUser($user)['merchant'];
    }

    public function isConfiguredFor(User|string $user): bool
    {
        return $this->isUsable($this->forUser($user));
    }

    public function isConfiguredForBook(?Books $book): bool
    {
        return $this->isUsable($this->resolveForBook($book)['merchant']);
    }

    /**
     * Optional platform-level ABA PayWay merchant from env (last resort).
     * Leave ABA_PAYWAY_MERCHANT_ID / API_KEY empty when using DB-only credentials.
     */
    public function platformMerchantFromEnv(): ?AbaPaywayMerchant
    {
        $merchantId = (string) config('services.aba_payway.merchant_id', '');
        $apiKey = (string) config('services.aba_payway.api_key', '');

        if ($merchantId === '' || $apiKey === '') {
            return null;
        }

        $merchant = new AbaPaywayMerchant([
            'user_id' => null,
            'merchant_id' => $merchantId,
            'api_key' => $apiKey,
            'merchant_name' => (string) config('services.aba_payway.merchant_name', 'e-Libra Platform'),
            'environment' => (bool) config('services.aba_payway.sandbox', true) ? 'sandbox' : 'production',
            'currency' => strtoupper((string) config('services.aba_payway.default_currency', 'USD')),
            'payment_option' => 'abapay_khqr',
            'is_active' => true,
            'is_platform' => true,
            'notes' => 'Platform fallback from .env',
        ]);
        $merchant->exists = false;

        return $merchant;
    }

    /** @deprecated Use platformMerchantFromEnv() */
    public function platformMerchant(): ?AbaPaywayMerchant
    {
        return $this->resolveCompanyFallback()['merchant'];
    }

    public function isPlatformConfigured(): bool
    {
        return $this->isUsable($this->resolveCompanyFallback()['merchant']);
    }

    public function isUsable(?AbaPaywayMerchant $merchant): bool
    {
        return $merchant !== null
            && ($merchant->is_active ?? true)
            && filled($merchant->merchant_id)
            && filled($merchant->api_key);
    }

    /**
     * Build the multipart purchase payload + HMAC hash for ABA PayWay hosted checkout.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{endpoint: string, fields: array<string, mixed>, merchant: AbaPaywayMerchant}
     */
    public function buildPurchasePayload(
        AbaPaywayMerchant $merchant,
        User $buyer,
        float $amount,
        string $tranId,
        array $overrides = [],
    ): array {
        if (! $merchant->is_active) {
            throw new RuntimeException('ABA PayWay merchant is inactive.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Purchase amount must be greater than zero.');
        }

        $currency = strtoupper((string) ($overrides['currency'] ?? $merchant->currency ?: 'USD'));
        $amountValue = $currency === 'KHR'
            ? (string) (int) round($amount)
            : number_format($amount, 2, '.', '');

        $nameParts = preg_split('/\s+/', trim((string) $buyer->name), 2) ?: [];
        $firstname = (string) ($overrides['firstname'] ?? ($nameParts[0] ?? 'Customer'));
        $lastname = (string) ($overrides['lastname'] ?? ($nameParts[1] ?? 'User'));

        $fields = [
            'req_time' => now('UTC')->format('YmdHis'),
            'merchant_id' => $merchant->merchant_id,
            'tran_id' => Str::limit($tranId, 20, ''),
            'amount' => $amountValue,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => (string) ($overrides['email'] ?? $buyer->email ?? ''),
            'phone' => (string) ($overrides['phone'] ?? ''),
            'type' => (string) ($overrides['type'] ?? 'purchase'),
            'payment_option' => (string) ($overrides['payment_option'] ?? ($merchant->payment_option ?: 'abapay_khqr')),
            'items' => (string) ($overrides['items'] ?? ''),
            'shipping' => (string) ($overrides['shipping'] ?? ''),
            'currency' => $currency,
            'return_url' => (string) ($overrides['return_url'] ?? $this->encodedReturnUrl()),
            'cancel_url' => (string) ($overrides['cancel_url'] ?? config('services.aba_payway.cancel_url')),
            'continue_success_url' => (string) ($overrides['continue_success_url'] ?? config('services.aba_payway.success_url')),
            'return_deeplink' => (string) ($overrides['return_deeplink'] ?? ''),
            'custom_fields' => (string) ($overrides['custom_fields'] ?? ''),
            'return_params' => (string) ($overrides['return_params'] ?? ''),
            'payout' => (string) ($overrides['payout'] ?? ''),
            'lifetime' => (string) ($overrides['lifetime'] ?? ''),
            'additional_params' => (string) ($overrides['additional_params'] ?? ''),
            'google_pay_token' => (string) ($overrides['google_pay_token'] ?? ''),
            'skip_success_page' => (string) ($overrides['skip_success_page'] ?? ''),
            'view_type' => (string) ($overrides['view_type'] ?? config('services.aba_payway.view_type', 'hosted_view')),
            'payment_gate' => (string) ($overrides['payment_gate'] ?? '0'),
        ];

        $fields['hash'] = $this->generatePurchaseHash($merchant->api_key, $fields);

        return [
            'endpoint' => $merchant->purchaseEndpoint(),
            'fields' => $fields,
            'merchant' => $merchant,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{endpoint: string, fields: array<string, mixed>, merchant: AbaPaywayMerchant}
     */
    public function buildBookPurchasePayload(
        User $buyer,
        Books $book,
        UserBuyBook $purchase,
        array $overrides = [],
    ): array {
        $resolved = $this->resolveForBook($book->loadMissing('author'));
        $merchant = $resolved['merchant'];
        if (! $merchant) {
            throw new RuntimeException('ABA PayWay is not configured (no author/company merchant in DB and no .env fallback).');
        }

        $amount = BookPricing::effectivePrice($book) ?? (float) $book->price;
        $tranId = Str::limit(str_replace('-', '', (string) $purchase->id), 20, '');

        $items = base64_encode(json_encode([
            [
                'name' => Str::limit((string) $book->title, 80, ''),
                'quantity' => 1,
                'price' => round((float) $amount, 2),
            ],
        ], JSON_THROW_ON_ERROR));

        $customFields = base64_encode(json_encode([
            'purchase_id' => (string) $purchase->id,
            'book_id' => (string) $book->id,
            'user_id' => (string) $buyer->id,
            'merchant_source' => $resolved['source'],
        ], JSON_THROW_ON_ERROR));

        return $this->buildPurchasePayload($merchant, $buyer, (float) $amount, $tranId, array_merge([
            'items' => $items,
            'custom_fields' => $customFields,
            'return_params' => 'purchase_id=' . $purchase->id,
            'payment_option' => $merchant->payment_option ?: 'abapay_khqr',
        ], $overrides));
    }

    /**
     * Call ABA PayWay Generate QR API and return KHQR payload for the frontend.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{
     *   merchant: AbaPaywayMerchant,
     *   tran_id: string,
     *   request: array<string, mixed>,
     *   qr_string: ?string,
     *   qr_image: ?string,
     *   abapay_deeplink: ?string,
     *   app_store: ?string,
     *   play_store: ?string,
     *   amount: float|int|string|null,
     *   currency: ?string,
     *   status_code: ?string,
     *   status_message: ?string,
     *   raw: array<string, mixed>|null,
     *   hosted: array{endpoint: string, fields: array<string, mixed>}
     * }
     */
    public function generateBookKhqr(
        User $buyer,
        Books $book,
        UserBuyBook $purchase,
        array $overrides = [],
    ): array {
        $resolved = $this->resolveForBook($book->loadMissing('author'));
        $merchant = $resolved['merchant'];
        if (! $merchant) {
            throw new RuntimeException('ABA PayWay is not configured (no author/company merchant in DB and no .env fallback).');
        }

        $amount = BookPricing::effectivePrice($book) ?? (float) $book->price;
        if ($amount <= 0) {
            throw new RuntimeException('Purchase amount must be greater than zero.');
        }

        $currency = strtoupper((string) ($overrides['currency'] ?? $merchant->currency ?: 'USD'));
        $amountValue = $currency === 'KHR'
            ? (string) (int) round($amount)
            : number_format($amount, 2, '.', '');

        $tranId = (string) ($overrides['tran_id'] ?? Str::limit(str_replace('-', '', (string) $purchase->id), 20, ''));
        $nameParts = preg_split('/\s+/', trim((string) $buyer->name), 2) ?: [];
        $firstName = (string) ($overrides['first_name'] ?? ($nameParts[0] ?? 'Customer'));
        $lastName = (string) ($overrides['last_name'] ?? ($nameParts[1] ?? 'User'));

        $items = (string) ($overrides['items'] ?? base64_encode(json_encode([
            [
                'name' => Str::limit((string) $book->title, 80, ''),
                'quantity' => 1,
                'price' => round((float) $amount, 2),
            ],
        ], JSON_THROW_ON_ERROR)));

        $customFields = (string) ($overrides['custom_fields'] ?? base64_encode(json_encode([
            'purchase_id' => (string) $purchase->id,
            'book_id' => (string) $book->id,
            'user_id' => (string) $buyer->id,
            'merchant_source' => $resolved['source'],
        ], JSON_THROW_ON_ERROR)));

        $callbackUrl = (string) ($overrides['callback_url'] ?? $this->encodedReturnUrl());
        $lifetime = (string) ($overrides['lifetime'] ?? (int) config('services.aba_payway.qr_lifetime_minutes', 30));
        $qrTemplate = (string) ($overrides['qr_image_template'] ?? config('services.aba_payway.qr_image_template', 'template3_color'));
        $paymentOption = (string) ($overrides['payment_option'] ?? (
            in_array($merchant->payment_option, ['abapay_khqr', 'abapay_khqr_deeplink'], true)
                ? $merchant->payment_option
                : 'abapay_khqr'
        ));

        // QR API uses abapay_khqr (deeplink option still generates KHQR via same endpoint).
        if ($paymentOption === 'abapay_khqr_deeplink') {
            $paymentOption = 'abapay_khqr';
        }

        $request = [
            'req_time' => now('UTC')->format('YmdHis'),
            'merchant_id' => $merchant->merchant_id,
            'tran_id' => $tranId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string) ($overrides['email'] ?? $buyer->email ?? ''),
            'phone' => (string) ($overrides['phone'] ?? ''),
            'amount' => $amountValue,
            'purchase_type' => (string) ($overrides['purchase_type'] ?? 'purchase'),
            'payment_option' => $paymentOption,
            'items' => $items,
            'callback_url' => $callbackUrl,
            'return_deeplink' => (string) ($overrides['return_deeplink'] ?? ''),
            'currency' => $currency,
            'custom_fields' => $customFields,
            'return_params' => (string) ($overrides['return_params'] ?? 'purchase_id=' . $purchase->id),
            'payout' => (string) ($overrides['payout'] ?? ''),
            'lifetime' => $lifetime,
            'qr_image_template' => $qrTemplate,
        ];

        $request['hash'] = $this->generateQrHash($merchant->api_key, $request);

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(30)
            ->post($merchant->generateQrEndpoint(), $this->jsonBody($request));

        $json = $response->json();
        if (! is_array($json)) {
            Log::warning('ABA PayWay generate-qr non-JSON response', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
                'merchant_id' => $merchant->merchant_id,
                'tran_id' => $tranId,
            ]);
            $json = null;
        }

        $statusCode = is_array($json) ? (string) data_get($json, 'status.code', '') : '';
        $statusMessage = is_array($json) ? (string) data_get($json, 'status.message', '') : '';

        if ($response->failed() || ($statusCode !== '' && $statusCode !== '0')) {
            Log::warning('ABA PayWay generate-qr failed', [
                'http_status' => $response->status(),
                'status_code' => $statusCode,
                'status_message' => $statusMessage,
                'merchant_id' => $merchant->merchant_id,
                'tran_id' => $tranId,
            ]);
        }

        $hosted = $this->buildBookPurchasePayload($buyer, $book, $purchase, [
            'payment_option' => $merchant->payment_option ?: 'abapay_khqr',
            'tran_id' => $tranId,
        ]);

        return [
            'merchant' => $merchant,
            'tran_id' => $tranId,
            'request' => $request,
            'qr_string' => is_array($json) ? (data_get($json, 'qrString') ?: data_get($json, 'qr_string')) : null,
            'qr_image' => is_array($json) ? (data_get($json, 'qrImage') ?: data_get($json, 'qr_image')) : null,
            'abapay_deeplink' => is_array($json) ? data_get($json, 'abapay_deeplink') : null,
            'app_store' => is_array($json) ? data_get($json, 'app_store') : null,
            'play_store' => is_array($json) ? data_get($json, 'play_store') : null,
            'amount' => is_array($json) ? data_get($json, 'amount', $amountValue) : $amountValue,
            'currency' => is_array($json) ? data_get($json, 'currency', $currency) : $currency,
            'status_code' => $statusCode !== '' ? $statusCode : null,
            'status_message' => $statusMessage !== '' ? $statusMessage : null,
            'raw' => $json,
            'hosted' => [
                'endpoint' => $hosted['endpoint'],
                'fields' => $hosted['fields'],
            ],
        ];
    }

    /**
     * Check ABA PayWay transaction status (for frontend polling after KHQR scan).
     *
     * @return array{http_status: int, status_code: ?string, status_message: ?string, raw: array<string, mixed>|null}
     */
    public function checkTransaction(AbaPaywayMerchant $merchant, string $tranId): array
    {
        $reqTime = now('UTC')->format('YmdHis');
        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => $merchant->merchant_id,
            'tran_id' => Str::limit($tranId, 20, ''),
        ];
        $payload['hash'] = base64_encode(hash_hmac(
            'sha512',
            $payload['req_time'] . $payload['merchant_id'] . $payload['tran_id'],
            $merchant->api_key,
            true
        ));

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(30)
            ->post($merchant->checkTransactionEndpoint(), $payload);

        $json = $response->json();
        if (! is_array($json)) {
            $json = null;
        }

        return [
            'http_status' => $response->status(),
            'status_code' => is_array($json) ? (string) (data_get($json, 'status.code') ?? data_get($json, 'payment_status') ?? '') : null,
            'status_message' => is_array($json) ? (string) (data_get($json, 'status.message') ?? data_get($json, 'description') ?? '') : null,
            'raw' => $json,
        ];
    }

    /**
     * Submit purchase to PayWay (KHQR deeplink / JSON responses).
     *
     * @param  array<string, mixed>  $fields
     * @return array{status: int, body: string, json: array<string, mixed>|null}
     */
    public function submitPurchase(AbaPaywayMerchant $merchant, array $fields): array
    {
        $response = Http::asMultipart()
            ->timeout(30)
            ->post($merchant->purchaseEndpoint(), $this->multipartBody($fields));

        $body = $response->body();
        $json = null;
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $json = is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            $json = null;
        }

        return [
            'status' => $response->status(),
            'body' => $body,
            'json' => $json,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function generatePurchaseHash(string $apiKey, array $fields): string
    {
        $concat = ($fields['req_time'] ?? '')
            . ($fields['merchant_id'] ?? '')
            . ($fields['tran_id'] ?? '')
            . ($fields['amount'] ?? '')
            . ($fields['items'] ?? '')
            . ($fields['shipping'] ?? '')
            . ($fields['firstname'] ?? '')
            . ($fields['lastname'] ?? '')
            . ($fields['email'] ?? '')
            . ($fields['phone'] ?? '')
            . ($fields['type'] ?? '')
            . ($fields['payment_option'] ?? '')
            . ($fields['return_url'] ?? '')
            . ($fields['cancel_url'] ?? '')
            . ($fields['continue_success_url'] ?? '')
            . ($fields['return_deeplink'] ?? '')
            . ($fields['currency'] ?? '')
            . ($fields['custom_fields'] ?? '')
            . ($fields['return_params'] ?? '')
            . ($fields['payout'] ?? '')
            . ($fields['lifetime'] ?? '')
            . ($fields['additional_params'] ?? '')
            . ($fields['google_pay_token'] ?? '')
            . ($fields['skip_success_page'] ?? '');

        return base64_encode(hash_hmac('sha512', $concat, $apiKey, true));
    }

    /**
     * Legacy alias used by older call sites / tests.
     *
     * @param  array<string, mixed>  $fields
     */
    public function generateHash(string $apiKey, array $fields): string
    {
        return $this->generatePurchaseHash($apiKey, $fields);
    }

    /**
     * Hash for ABA Generate QR API.
     *
     * @param  array<string, mixed>  $fields
     */
    public function generateQrHash(string $apiKey, array $fields): string
    {
        $concat = ($fields['req_time'] ?? '')
            . ($fields['merchant_id'] ?? '')
            . ($fields['tran_id'] ?? '')
            . ($fields['amount'] ?? '')
            . ($fields['items'] ?? '')
            . ($fields['first_name'] ?? '')
            . ($fields['last_name'] ?? '')
            . ($fields['email'] ?? '')
            . ($fields['phone'] ?? '')
            . ($fields['purchase_type'] ?? '')
            . ($fields['payment_option'] ?? '')
            . ($fields['callback_url'] ?? '')
            . ($fields['return_deeplink'] ?? '')
            . ($fields['currency'] ?? '')
            . ($fields['custom_fields'] ?? '')
            . ($fields['return_params'] ?? '')
            . ($fields['payout'] ?? '')
            . ($fields['lifetime'] ?? '')
            . ($fields['qr_image_template'] ?? '');

        return base64_encode(hash_hmac('sha512', $concat, $apiKey, true));
    }

    private function encodedReturnUrl(): string
    {
        $url = (string) config('services.aba_payway.return_url');

        return $url !== '' ? base64_encode($url) : '';
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function jsonBody(array $fields): array
    {
        $body = [];
        foreach ($fields as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $body[$name] = $value;
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<int, array{name: string, contents: string}>
     */
    private function multipartBody(array $fields): array
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = [
                'name' => (string) $name,
                'contents' => (string) $value,
            ];
        }

        return $parts;
    }
}
