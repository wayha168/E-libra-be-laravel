<?php

namespace App\Support;

use App\Models\Books;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookPublishService
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    /**
     * Map publish_mode + schedule fields onto book attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyFromRequest(array $data, ?Books $existing = null): array
    {
        $mode = $data['publish_mode'] ?? null;
        unset($data['publish_mode']);

        // Keep existing status when editing without an explicit mode
        if ($mode === null && $existing) {
            return $data;
        }

        $mode = $mode ?: 'now';

        if ($mode === 'draft') {
            $data['status'] = self::STATUS_DRAFT;
            $data['scheduled_at'] = null;
            if (! $existing?->isPublished()) {
                $data['published_at'] = null;
            }

            return $data;
        }

        if ($mode === 'schedule') {
            $scheduledAt = self::resolveScheduleAt($data);

            if (! $scheduledAt) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'Choose a date/time to schedule this book.',
                ]);
            }

            if ($scheduledAt->lte(now())) {
                // Past/now schedule → publish immediately
                $data['status'] = self::STATUS_PUBLISHED;
                $data['published_at'] = $scheduledAt;
                $data['scheduled_at'] = null;
                $data['public_date'] = $data['public_date'] ?? $scheduledAt->toDateString();

                return $data;
            }

            $data['status'] = self::STATUS_SCHEDULED;
            $data['scheduled_at'] = $scheduledAt;
            $data['published_at'] = null;
            $data['public_date'] = $data['public_date'] ?? $scheduledAt->toDateString();

            return $data;
        }

        // publish now
        $data['status'] = self::STATUS_PUBLISHED;
        $data['published_at'] = now();
        $data['scheduled_at'] = null;
        $data['public_date'] = $data['public_date'] ?? now()->toDateString();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveScheduleAt(array $data): ?Carbon
    {
        if (! empty($data['scheduled_at'])) {
            return Carbon::parse($data['scheduled_at']);
        }

        if (! empty($data['public_date'])) {
            return Carbon::parse($data['public_date'])->startOfDay();
        }

        return null;
    }

    public static function publishNow(Books $book, bool $notify = true): Books
    {
        $wasPublished = $book->isPublished();

        $book->fill([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => $book->published_at ?? now(),
            'scheduled_at' => null,
            'public_date' => $book->public_date ?? now()->toDateString(),
        ])->save();

        if ($notify && ! $wasPublished) {
            self::notifyReleased($book->fresh());
        }

        return $book->fresh();
    }

    public static function notifyReleased(Books $book): void
    {
        $book->loadMissing(['category', 'author.user']);
        BookReleaseNotificationHandler::handle($book);
    }

    public static function afterSaved(Books $book, ?string $previousStatus): void
    {
        if ($book->status === self::STATUS_PUBLISHED && $previousStatus !== self::STATUS_PUBLISHED) {
            self::notifyReleased($book);
        }
    }
}
