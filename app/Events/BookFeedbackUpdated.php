<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookFeedbackUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $bookId,
        public string $type,
        public array $payload,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('books.' . $this->bookId)];
    }

    public function broadcastAs(): string
    {
        return 'book.feedback';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'book_id' => $this->bookId,
            'data' => $this->payload,
        ];
    }
}
