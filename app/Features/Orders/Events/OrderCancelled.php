<?php

namespace App\Features\Orders\Events;

use App\Features\Orders\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'broadcasts';

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Order $order,
        public array $userData,
        public string $reason = 'User requested cancellation'
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->order->user_id),
            new Channel('orderbook.' . $this->order->symbol),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.cancelled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'symbol' => $this->order->symbol,
                'side' => $this->order->side,
                'price' => $this->order->price,
                'amount' => $this->order->amount,
                'filled_amount' => $this->order->filled_amount,
                'remaining_amount' => $this->order->remaining_amount,
                'status' => $this->order->status,
                'cancelled_at' => now()->toISOString(),
                'reason' => $this->reason,
            ],
            'user' => $this->userData,
        ];
    }
}
