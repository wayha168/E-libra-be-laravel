<?php

namespace App\Support;

use App\Events\AppNotificationCreated;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public static function send(User|string $user, string $type, string $title, ?string $body = null, array $data = []): AppNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = AppNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
        ]);

        event(new AppNotificationCreated($notification));

        return $notification;
    }

    public static function sendMany(Collection|array $users, string $type, string $title, ?string $body = null, array $data = []): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                self::send($user, $type, $title, $body, $data);
            }
        }
    }

    public static function unreadCount(User $user): int
    {
        return AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    }

    public static function staffUsers(): Collection
    {
        return User::whereHas('role', fn ($q) => $q->whereIn('role', ['super_admin', 'admin']))->get();
    }
}
