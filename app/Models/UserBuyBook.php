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
        'status',
        'purchased_at',
        'expires_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function book()
    {
        return $this->belongsTo(Books::class, 'book_id', 'id');
    }
}
