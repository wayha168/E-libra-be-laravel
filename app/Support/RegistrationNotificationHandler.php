<?php

namespace App\Support;

use App\Models\User;

/**
 * Records a user registration as an activity and fans out notifications:
 * a welcome to the new user and an alert to every staff admin. Both the
 * activity and the notifications broadcast over WebSockets via their events,
 * so the dashboard/bell update live (with the polling fallback covering the
 * case where Reverb is down).
 */
class RegistrationNotificationHandler
{
    public static function handle(User $user, ?User $creator = null): void
    {
        $user->loadMissing('role');

        $actor = $creator ?? $user;
        $roleName = $user->role?->role ?? 'user';
        $createdByOther = $creator !== null && $creator->id !== $user->id;

        $meta = [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $roleName,
            'created_by' => $createdByOther ? $creator->id : null,
        ];

        ActivityLogger::log(
            'user.registered',
            $createdByOther ? 'New account created' : 'New user registered',
            $createdByOther
                ? "{$actor->name} created an account for {$user->name} ({$user->email})"
                : "{$user->name} ({$user->email}) joined e-Libra",
            $user,
            $actor,
            $meta,
        );

        NotificationService::send(
            $user,
            'user.welcome',
            'Welcome to e-Libra',
            "Hi {$user->name}, your account is ready. Explore the library and start reading.",
            $meta,
        );

        foreach (NotificationService::staffUsers() as $admin) {
            if ($admin->id === $user->id) {
                continue;
            }

            NotificationService::send(
                $admin,
                'user.registered',
                'New user registered',
                "{$user->name} ({$user->email}) just registered as {$roleName}.",
                $meta,
            );
        }
    }
}
