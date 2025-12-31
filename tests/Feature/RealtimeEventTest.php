<?php

namespace Tests\Feature;

use App\Features\Balance\Events\BalanceUpdated;
use App\Features\Orders\Events\OrderCancelled;
use App\Features\Orders\Events\OrderMatched;
use App\Features\Orders\Models\Order;
use App\Features\Orders\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeEventTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create(['balance' => 10000.00]);

        // Create test order
        $this->order = new Order([
            'user_id' => $this->user->id,
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.5,
            'status' => 'filled',
            'filled_amount' => 0.5,
        ]);
        $this->order->save();

        // Create test trade
        $sellOrder = new Order([
            'user_id' => User::factory()->create()->id,
            'symbol' => 'BTC-USD',
            'side' => 'sell',
            'price' => 50000.00,
            'amount' => 0.5,
            'status' => 'filled',
            'filled_amount' => 0.5,
        ]);
        $sellOrder->save();

        $this->trade = new Trade([
            'buy_order_id' => $this->order->id,
            'sell_order_id' => $sellOrder->id,
            'symbol' => 'BTC-USD',
            'price' => 50000.00,
            'amount' => 0.5,
            'commission' => 375.00,
        ]);
        $this->trade->save();
    }

    /**
     * Test OrderMatched event broadcasting.
     */
    public function test_order_matched_event_broadcasts_correctly(): void
    {
        Event::fake();

        // Dispatch event
        $event = new OrderMatched(
            $this->trade,
            $this->order->toArray(),
            [],
            $this->user->toArray(),
            []
        );
        event($event);

        // Assert event was dispatched
        Event::assertDispatched(OrderMatched::class);

        // Assert event broadcasts with correct name
        $this->assertEquals('order.matched', $event->broadcastAs());
    }

    /**
     * Test OrderCancelled event broadcasting.
     */
    public function test_order_cancelled_event_broadcasts_correctly(): void
    {
        Event::fake();

        // Dispatch event
        $event = new OrderCancelled(
            $this->order,
            $this->user->toArray(),
            'User requested cancellation'
        );
        event($event);

        // Assert event was dispatched
        Event::assertDispatched(OrderCancelled::class);

        // Assert event broadcasts with correct name
        $this->assertEquals('order.cancelled', $event->broadcastAs());
    }

    /**
     * Test BalanceUpdated event broadcasting.
     */
    public function test_balance_updated_event_broadcasts_correctly(): void
    {
        Event::fake();

        // Dispatch event
        $event = new BalanceUpdated(
            $this->user->id,
            9500.00,
            'BTC',
            0.5,
            0.0,
            'Trade execution'
        );
        event($event);

        // Assert event was dispatched
        Event::assertDispatched(BalanceUpdated::class);

        // Assert event broadcasts with correct name
        $this->assertEquals('balance.updated', $event->broadcastAs());
    }

    /**
     * Test OrderMatched event contains correct data.
     */
    public function test_order_matched_event_contains_correct_data(): void
    {
        // Dispatch event
        $event = new OrderMatched(
            $this->trade,
            $this->order->toArray(),
            [],
            $this->user->toArray(),
            []
        );

        // Assert event contains correct data
        $data = $event->broadcastWith();

        $this->assertTrue(isset($data['trade']));
        $this->assertEquals($this->trade->id, $data['trade']['id']);
        $this->assertEquals($this->trade->symbol, $data['trade']['symbol']);
        $this->assertEquals($this->trade->price, $data['trade']['price']);
        $this->assertEquals($this->trade->amount, $data['trade']['amount']);
    }

    /**
     * Test BalanceUpdated event contains correct data.
     */
    public function test_balance_updated_event_contains_correct_data(): void
    {
        // Dispatch event
        $event = new BalanceUpdated(
            $this->user->id,
            9500.00,
            'BTC',
            0.5,
            0.0,
            'Trade execution'
        );

        // Assert event contains correct data
        $data = $event->broadcastWith();

        $this->assertTrue(isset($data['user_id']));
        $this->assertEquals($this->user->id, $data['user_id']);
        $this->assertTrue(isset($data['new_balance']));
        $this->assertEquals(9500.00, $data['new_balance']);
        $this->assertTrue(isset($data['reason']));
        $this->assertEquals('Trade execution', $data['reason']);
    }
}
