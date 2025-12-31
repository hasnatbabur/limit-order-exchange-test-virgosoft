<?php

namespace App\Features\Orders\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderBookUpdated implements ShouldBroadcast
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
        public string $symbol,
        public array $buyOrders,
        public array $sellOrders
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orderbook.' . $this->symbol),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'orderbook.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->symbol,
            'buyOrders' => $this->buyOrders,
            'sellOrders' => $this->sellOrders,
        ];
    }
}
