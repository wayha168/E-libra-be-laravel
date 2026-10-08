<?php

namespace App\Models\DrunkDrive;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dd_driver_profiles';

    protected $fillable = [
        'user_id',
        'licence_number',
        'licence_class',
        'licence_country',
        'licence_issued_at',
        'licence_expires_at',
        'licence_scan_path',
        'licence_ocr_stub',
        'address_line',
        'city',
        'region',
        'postal_code',
        'country',
        'address_latitude',
        'address_longitude',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'is_available',
        'vehicle_type',
        'vehicle_plate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'licence_issued_at' => 'date',
            'licence_expires_at' => 'date',
            'licence_ocr_stub' => 'array',
            'address_latitude' => 'float',
            'address_longitude' => 'float',
            'current_latitude' => 'float',
            'current_longitude' => 'float',
            'location_updated_at' => 'datetime',
            'is_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isMatchable(): bool
    {
        return $this->is_available
            && $this->status === 'approved'
            && $this->current_latitude !== null
            && $this->current_longitude !== null;
    }
}
