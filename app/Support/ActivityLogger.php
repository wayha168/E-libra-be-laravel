<?php

namespace App\Support;

use App\Events\UserActivityRecorded;
use App\Models\User;
use App\Models\UserActivity;

class ActivityLogger
{
    public static function log(
        string $type,
        string $title,
        ?string $description = null,
        ?User $subject = null,
        ?User $actor = null,
        array $metadata = [],
    ): UserActivity {
        $activity = UserActivity::create([
            'user_id' => $subject?->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);

        event(new UserActivityRecorded($activity));

        return $activity;
    }
}
