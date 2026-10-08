<?php

namespace App\Models\DrunkDrive;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dd_stores';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address_line',
        'city',
        'region',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'phone',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'store_id');
    }
}
