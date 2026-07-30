<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasUuids;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FREE_TRIAL = 'free_trial';

    protected $fillable = [
        'type',
        'book_id',
        'author_id',
        'created_by',
        'discount_percent',
        'trial_days',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'trial_days' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Books::class, 'book_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function accessTrials()
    {
        return $this->hasMany(UserAccessTrial::class, 'promotion_id', 'id');
    }

    public function isPercentage(): bool
    {
        return ($this->type ?: self::TYPE_PERCENTAGE) === self::TYPE_PERCENTAGE;
    }

    public function isFreeTrial(): bool
    {
        return $this->type === self::TYPE_FREE_TRIAL;
    }

    public function coversBook(Books $book): bool
    {
        if ($this->book_id && $this->book_id === $book->id) {
            return true;
        }

        if ($this->author_id && $book->author_id && $this->author_id === $book->author_id) {
            return true;
        }

        return false;
    }

    public function targetLabel(): string
    {
        if ($this->book_id) {
            return $this->book?->title ?? 'Book';
        }

        $name = $this->author?->user?->name;

        return $name ? "All books by {$name}" : 'All author books';
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopePercentage(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('type', self::TYPE_PERCENTAGE)->orWhereNull('type');
        });
    }

    public function scopeFreeTrial(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_FREE_TRIAL);
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    public function resolvedTrialDays(): int
    {
        return max(1, (int) ($this->trial_days ?: config('elibra.trial_days', 7)));
    }
}
