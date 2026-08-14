<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
        'views_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'views_count' => 'integer',
    ];

    /** Playlist belongs to one user (owner). */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** Alias for user — each playlist has a single owner. */
    public function owner()
    {
        return $this->user();
    }

    /** Playlist has many books (many-to-many via playlist_books). */
    public function books()
    {
        return $this->belongsToMany(Books::class, 'playlist_books', 'playlist_id', 'book_id')
            ->using(PlaylistBook::class)
            ->withPivot(['id', 'sort_order'])
            ->withTimestamps()
            ->orderBy('playlist_books.sort_order');
    }

    public function likes()
    {
        return $this->hasMany(PlaylistLike::class, 'playlist_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(PlaylistComment::class, 'playlist_id', 'id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeVisibleTo(Builder $query, $user = null): Builder
    {
        if (!$user) {
            return $query->public();
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('is_public', true)
                ->orWhere('user_id', $user->id);
        });
    }

    public function isOwnedBy($user): bool
    {
        return $user && $this->user_id === $user->id;
    }

    public function canBeEditedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->isOwnedBy($user)
            || $user->hasPermission('edit_playlists')
            || $user->isSuperAdmin();
    }

    public function canBeDeletedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->isOwnedBy($user)
            || $user->hasPermission('delete_playlists')
            || $user->isSuperAdmin();
    }

    public function canBeViewedBy($user = null): bool
    {
        if ($this->is_public) {
            return true;
        }

        return $this->isOwnedBy($user)
            || ($user && ($user->hasPermission('view_playlists') || $user->isSuperAdmin()));
    }
}
