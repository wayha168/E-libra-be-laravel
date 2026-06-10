<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Author;
use App\Models\Image;

use Database\Factories\UserFactory;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    public function authorProfile()
    {
        return $this->hasOne(Author::class, 'user_id', 'id');
    }

    public function profileImage()
    {
        return $this->belongsTo(Image::class, 'profile_image_id', 'id');
    }




    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role && $this->role->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAuthor(): bool
    {
        return $this->hasRole('author');
    }

    public function isUser(): bool
    {
        return $this->hasRole('user');
    }
}
