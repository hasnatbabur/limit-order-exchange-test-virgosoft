<?php

namespace App\Features\Balance\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class BalanceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $userId,
        public float $newBalance,
        public ?string $assetSymbol = null,
        public ?float $newAssetAmount = null,
        public ?float $newLockedAmount = null,
        public string $reason = 'Balance update'
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
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'balance.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $data = [
            'user_id' => $this->userId,
            'new_balance' => $this->newBalance,
            'reason' => $this->reason,
            'updated_at' => now()->toISOString(),
        ];

        if ($this->assetSymbol) {
            $data['asset'] = [
                'symbol' => $this->assetSymbol,
                'new_amount' => $this->newAssetAmount,
                'new_locked_amount' => $this->newLockedAmount,
            ];
        }

        return $data;
    }
}
