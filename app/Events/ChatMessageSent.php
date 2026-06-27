<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $chatMessage) {}

    public function broadcastOn(): array
    {
        return [
            // Both user and admin listen on this per-conversation channel
            new PrivateChannel('chat.' . $this->chatMessage->conversation_id),
            // Admin global channel to notify about any new user message
            new PrivateChannel('admin.chats'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        $message = $this->chatMessage->load('sender.role', 'sender.profileImage');

        return [
            'message' => $message->toArray(),
        ];
    }
}
