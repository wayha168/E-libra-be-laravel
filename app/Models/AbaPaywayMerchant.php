<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbaPaywayMerchant extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'api_key',
        'merchant_name',
        'environment',
        'currency',
        'payment_option',
        'is_active',
        'is_platform',
        'notes',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
        'is_platform' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSandbox(): bool
    {
        return $this->environment !== 'production';
    }

    public function checkoutBaseUrl(): string
    {
        return $this->isSandbox()
            ? (string) config('services.aba_payway.sandbox_base_url')
            : (string) config('services.aba_payway.production_base_url');
    }

    public function purchaseEndpoint(): string
    {
        return rtrim($this->checkoutBaseUrl(), '/') . '/api/payment-gateway/v1/payments/purchase';
    }

    public function generateQrEndpoint(): string
    {
        return rtrim($this->checkoutBaseUrl(), '/') . '/api/payment-gateway/v1/payments/generate-qr';
    }

    public function checkTransactionEndpoint(): string
    {
        return rtrim($this->checkoutBaseUrl(), '/') . '/api/payment-gateway/v1/payments/check-transaction';
    }

    public function maskedApiKey(): string
    {
        $key = (string) $this->api_key;
        if ($key === '') {
            return '—';
        }

        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }

        return substr($key, 0, 4) . str_repeat('•', max(4, strlen($key) - 8)) . substr($key, -4);
    }

    public function paymentOptionLabel(): string
    {
        return match ($this->payment_option) {
            'cards' => 'Cards',
            'abapay_khqr' => 'ABA Pay / KHQR',
            'abapay_khqr_deeplink' => 'ABA Pay / KHQR (deeplink)',
            'alipay' => 'Alipay',
            'wechat' => 'WeChat',
            'google_pay' => 'Google Pay',
            default => 'All enabled methods',
        };
    }
}
