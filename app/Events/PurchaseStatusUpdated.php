<?php

namespace App\Events;

use App\Models\UserBuyBook;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UserBuyBook $purchase)
    {
        $this->purchase->loadMissing(['user:id,name,email', 'book:id,title,price']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('purchases.' . $this->purchase->user_id),
            new PrivateChannel('dashboard.overview'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'purchase.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'purchase' => [
                'id' => $this->purchase->id,
                'user_id' => $this->purchase->user_id,
                'book_id' => $this->purchase->book_id,
                'amount' => $this->purchase->amount,
                'admin_commission_rate' => $this->purchase->admin_commission_rate,
                'admin_commission_amount' => $this->purchase->admin_commission_amount,
                'status' => $this->purchase->status,
                'purchased_at' => $this->purchase->purchased_at?->toIso8601String(),
                'user' => $this->purchase->user,
                'book' => $this->purchase->book,
            ],
        ];
    }
}
