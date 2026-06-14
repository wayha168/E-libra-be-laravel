<?php

namespace App\Support;

use App\Events\UserPresenceUpdated;
use App\Models\User;
use Illuminate\Support\Collection;

class UserPresence
{
    public const ONLINE_MINUTES = 3;

    public static function isOnline(?User $user): bool
    {
        if (!$user?->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->gte(now()->subMinutes(self::ONLINE_MINUTES));
    }

    public static function touch(User $user): User
    {
        $wasOnline = self::isOnline($user);

        $user->forceFill(['last_seen_at' => now()])->save();

        $isOnline = self::isOnline($user);

        if ($wasOnline !== $isOnline || !$wasOnline) {
            event(new UserPresenceUpdated($user->fresh()));
        }

        return $user;
    }

    public static function snapshot(Collection $users): array
    {
        return $users->map(fn (User $user) => self::format($user))->values()->all();
    }

    public static function format(User $user): array
    {
        return [
            'user_id' => $user->id,
            'online' => self::isOnline($user),
            'last_seen_at' => $user->last_seen_at?->toIso8601String(),
        ];
    }
}
