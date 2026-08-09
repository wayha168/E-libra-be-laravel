<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'url',
        'alt_text',
        'image_type',
    ];

    /**
     * Public URL for <img src> / API clients.
     * Local uploads resolve to root-relative /storage/... so host/port mismatches never break previews.
     * External http(s) URLs (no /storage/) are left unchanged.
     */
    public function getUrlAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            $storagePos = strpos($value, '/storage/');
            if ($storagePos !== false) {
                return substr($value, $storagePos);
            }

            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        return '/storage/'.ltrim($value, '/');
    }

    /**
     * Normalize values written to DB: prefer disk-relative paths for local storage files.
     */
    public function setUrlAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['url'] = $value;

            return;
        }

        $this->attributes['url'] = self::normalizeForStorage($value);
    }

    /**
     * Disk-relative path under storage/app/public (e.g. uploads/general/x.jpg), or null for external URLs.
     */
    public function diskPath(): ?string
    {
        return self::diskPathFromStored($this->attributes['url'] ?? null);
    }

    public static function normalizeForStorage(string $value): string
    {
        $diskPath = self::diskPathFromStored($value);

        return $diskPath ?? $value;
    }

    public static function diskPathFromStored(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $stored) === 1) {
            $storagePos = strpos($stored, '/storage/');
            if ($storagePos === false) {
                return null;
            }

            return ltrim(substr($stored, $storagePos + strlen('/storage/')), '/');
        }

        if (str_starts_with($stored, '/storage/')) {
            return ltrim(Str::after($stored, '/storage/'), '/');
        }

        return ltrim($stored, '/');
    }

    public function author()
    {
        return $this->hasOne(Author::class, 'image_id', 'id');
    }

    public function userProfile()
    {
        return $this->hasMany(User::class, 'profile_image_id', 'id');
    }

    public function category()
    {
        return $this->hasMany(Category::class, 'image_id', 'id');
    }

    public function categoryBanner()
    {
        return $this->hasMany(Category::class, 'banner_image_id', 'id');
    }

    public function books()
    {
        return $this->hasMany(Books::class, 'image_id', 'id');
    }

    public function bookGalleries()
    {
        return $this->belongsToMany(Books::class, 'book_images', 'image_id', 'book_id');
    }
}
