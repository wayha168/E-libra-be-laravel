<?php

namespace App\Services;

use App\Models\AbaPaywayMerchant;
use App\Models\Books;
use App\Models\User;
use App\Models\UserBuyBook;
use App\Support\BookPricing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AbaPayWayService
{
    public function forUser(User|string $user): ?AbaPaywayMerchant
    {
        $userId = $user instanceof User ? $user->id : $user;

        return AbaPaywayMerchant::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    public function isConfiguredFor(User|string $user): bool
    {
        $merchant = $this->forUser($user);

        return $merchant !== null
            && filled($merchant->merchant_id)
            && filled($merchant->api_key);
    }

    /**
     * Build the multipart purchase payload + HMAC hash for ABA PayWay.
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
        if (!$merchant->is_active) {
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
            'payment_option' => (string) ($overrides['payment_option'] ?? $merchant->payment_option ?? ''),
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

        $fields['hash'] = $this->generateHash($merchant->api_key, $fields);

        return [
            'endpoint' => $merchant->purchaseEndpoint(),
            'fields' => $fields,
            'merchant' => $merchant,
        ];
    }

    /**
     * Create a book purchase payload using the book author's ABA PayWay merchant.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{endpoint: string, fields: array<string, mixed>, merchant: AbaPaywayMerchant}
     */
    public function buildBookPurchasePayload(
        User $buyer,
        Books $book,
        UserBuyBook $purchase,
        array $overrides = [],
    ): array {
        $authorUserId = $book->author?->user_id;
        if (!$authorUserId) {
            throw new RuntimeException('Book has no author user for ABA PayWay.');
        }

        $merchant = $this->forUser($authorUserId);
        if (!$merchant) {
            throw new RuntimeException('ABA PayWay is not configured for this author.');
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
        ], JSON_THROW_ON_ERROR));

        return $this->buildPurchasePayload($merchant, $buyer, (float) $amount, $tranId, array_merge([
            'items' => $items,
            'custom_fields' => $customFields,
            'return_params' => 'purchase_id=' . $purchase->id,
        ], $overrides));
    }

    /**
     * Submit purchase to PayWay (KHQR deeplink / JSON responses).
     * Hosted checkout usually returns HTML — callers should prefer form POST redirect for that mode.
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
    public function generateHash(string $apiKey, array $fields): string
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

    private function encodedReturnUrl(): string
    {
        $url = (string) config('services.aba_payway.return_url');

        return $url !== '' ? base64_encode($url) : '';
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
