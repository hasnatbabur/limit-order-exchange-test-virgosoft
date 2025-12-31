<?php

namespace App\Features\Orders\Events;

use App\Features\Orders\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Trade $trade,
        public array $buyOrderData,
        public array $sellOrderData,
        public array $buyerData,
        public array $sellerData
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
            new PrivateChannel('user.' . $this->trade->buyOrder->user_id),
            new PrivateChannel('user.' . $this->trade->sellOrder->user_id),
            new Channel('orderbook.' . $this->trade->symbol),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'total_value' => $this->trade->getTotalValueAttribute(),
                'commission' => $this->trade->commission,
                'seller_net_value' => $this->trade->getSellerNetValueAttribute(),
                'created_at' => $this->trade->created_at->toISOString(),
            ],
            'buy_order' => $this->buyOrderData,
            'sell_order' => $this->sellOrderData,
            'buyer' => $this->buyerData,
            'seller' => $this->sellerData,
        ];
    }
}
