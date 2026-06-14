<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserBuyBook extends Model
{
    use HasUuids;

    protected $table = 'users_buys_book';

    protected $fillable = [
        'user_id',
        'book_id',
        'amount',
        'payment_method',
        'admin_commission_rate',
        'admin_commission_amount',
        'status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'purchased_at',
        'expires_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount' => 'float',
        'admin_commission_rate' => 'float',
        'admin_commission_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function book()
    {
        return $this->belongsTo(Books::class, 'book_id', 'id');
    }

    public function authorEarnings(): float
    {
        if ($this->status !== 'paid' || !$this->amount) {
            return 0.0;
        }

        $fee = (float) ($this->admin_commission_amount ?? 0);

        return max(0, round((float) $this->amount - $fee, 2));
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'khqr' => 'KHQR Scan',
            default => 'Card',
        };
    }
}
