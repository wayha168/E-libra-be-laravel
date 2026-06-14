<?php

namespace App\Events;

use App\Models\UserActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UserActivity $activity)
    {
        $this->activity->loadMissing(['user:id,name,email', 'actor:id,name,email']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.activities'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'activity.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'activity' => [
                'id' => $this->activity->id,
                'type' => $this->activity->type,
                'title' => $this->activity->title,
                'description' => $this->activity->description,
                'metadata' => $this->activity->metadata,
                'created_at' => $this->activity->created_at?->toIso8601String(),
                'user' => $this->activity->user,
                'actor' => $this->activity->actor,
            ],
        ];
    }
}
