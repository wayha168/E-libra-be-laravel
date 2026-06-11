<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_roles';

    protected $fillable = [
        'role',
    ];

    public $timestamps = true;

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    public function getDisplayNameAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', (string) $this->role));
    }
}
