<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'provider',
        'bank_name',
        'account_holder',
        'account_number',
        'branch',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'payway' => 'PayWay',
            'bakong' => 'Bakong',
            default => 'Bank',
        };
    }
}
