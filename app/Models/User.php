<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Author;
use App\Models\Image;
use App\Models\UserBuyBook;
use App\Models\BookComment;
use App\Models\BankAccount;
use App\Models\AppNotification;
use App\Models\UserActivity;

use Database\Factories\UserFactory;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'confirm_password', 'role_id', 'status', 'profile_image_id', 'payway_account', 'bakong_account', 'last_seen_at', 'google_id', 'trial_ends_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, HasApiTokens;

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
            'user_subscribe' => 'boolean',
            'last_seen_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (is_null($user->trial_ends_at) && self::isTrialEligible($user)) {
                $user->trial_ends_at = now()->addDays((int) config('elibra.trial_days', 7));
            }
        });
    }

    protected static function isTrialEligible(User $user): bool
    {
        if (!$user->role_id) {
            return true;
        }

        $role = Role::find($user->role_id);

        return !$role || $role->role === 'user';
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role && $this->role->role === $role;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role && $this->role->permissions()->where('permissions.name', $permission)->exists();
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

    public function getDisplayStatusAttribute(): string
    {
        return ucfirst((string) ($this->status ?? 'active'));
    }

    public function getDisplayRoleAttribute(): string
    {
        return $this->role?->display_name ?? '-';
    }

    public function bookPurchases()
    {
        return $this->hasMany(UserBuyBook::class, 'user_id', 'id');
    }

    public function bookComments()
    {
        return $this->hasMany(BookComment::class, 'user_id', 'id');
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function isStaff(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isAuthor();
    }

    public function isOnline(): bool
    {
        return \App\Support\UserPresence::isOnline($this);
    }
}