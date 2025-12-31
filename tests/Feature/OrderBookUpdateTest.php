<?php

namespace Tests\Feature;

use App\Features\Balance\Services\AssetService;
use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use App\Features\Orders\Models\Order;
use App\Features\Orders\Services\OrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderBookUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private OrderService $orderService;
    private AssetService $assetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = app(OrderService::class);
        $this->assetService = app(AssetService::class);

        // Create test users with initial balance
        $this->buyer = User::factory()->create(['balance' => 50000.00]);
        $this->seller = User::factory()->create(['balance' => 50000.00]);

        // Initialize assets for both users
        $this->assetService->initializeUserAssets($this->buyer->id);
        $this->assetService->initializeUserAssets($this->seller->id);

        // Give seller some BTC to sell
        $this->assetService->depositAsset($this->seller->id, 'BTC', 1.0);
    }

    /**
     * Test that order book is updated when a new order is placed.
     */
    public function test_order_book_updates_when_order_is_placed(): void
    {
        // Get initial order book
        $initialOrderBook = $this->orderService->getOrderBook('BTC-USD');
        $this->assertCount(0, $initialOrderBook['buy_orders']);
        $this->assertCount(0, $initialOrderBook['sell_orders']);

        // Place a buy order
        $buyOrder = $this->orderService->createOrder($this->buyer, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::BUY->value,
            'price' => 45000.00,
            'amount' => 0.1,
        ]);

        // Check that order was created
        $this->assertEquals(OrderStatus::OPEN, $buyOrder->status);
        $this->assertEquals('BTC-USD', $buyOrder->symbol);
        $this->assertEquals(OrderSide::BUY, $buyOrder->side);

        // Get updated order book
        $updatedOrderBook = $this->orderService->getOrderBook('BTC-USD');

        // Verify buy order appears in order book
        $this->assertCount(1, $updatedOrderBook['buy_orders']);
        $this->assertCount(0, $updatedOrderBook['sell_orders']);

        $buyOrderInBook = $updatedOrderBook['buy_orders']->first();
        $this->assertEquals($buyOrder->id, $buyOrderInBook->id);
        $this->assertEquals(45000.00, $buyOrderInBook->price);
        $this->assertEquals(0.1, $buyOrderInBook->amount);
    }

    /**
     * Test that order matching works and order book is updated accordingly.
     */
    public function test_order_matching_updates_order_book(): void
    {
        // First, place a sell order
        $sellOrder = $this->orderService->createOrder($this->seller, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::SELL->value,
            'price' => 45000.00,
            'amount' => 0.5,
        ]);

        // Check that sell order appears in order book
        $orderBookAfterSell = $this->orderService->getOrderBook('BTC-USD');
        $this->assertCount(0, $orderBookAfterSell['buy_orders']);
        $this->assertCount(1, $orderBookAfterSell['sell_orders']);

        // Now place a matching buy order
        $buyOrder = $this->orderService->createOrder($this->buyer, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::BUY->value,
            'price' => 45000.00,
            'amount' => 0.3, // Partial match
        ]);

        // Check final order book state
        $finalOrderBook = $this->orderService->getOrderBook('BTC-USD');

        // The buy order should be filled (no longer in order book)
        $this->assertCount(0, $finalOrderBook['buy_orders']);

        // The sell order should still be there but with reduced amount
        $this->assertCount(1, $finalOrderBook['sell_orders']);
        $remainingSellOrder = $finalOrderBook['sell_orders']->first();
        $this->assertEquals(0.2, $remainingSellOrder->remaining_amount); // 0.5 - 0.3 = 0.2

        // Verify order statuses
        $buyOrder->refresh();
        $sellOrder->refresh();
        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);
        $this->assertEquals(OrderStatus::OPEN, $sellOrder->status);
    }

    /**
     * Test that full order matching removes both orders from order book.
     */
    public function test_full_order_matching_removes_both_orders(): void
    {
        // Place a sell order
        $sellOrder = $this->orderService->createOrder($this->seller, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::SELL->value,
            'price' => 45000.00,
            'amount' => 0.3,
        ]);

        // Place a fully matching buy order
        $buyOrder = $this->orderService->createOrder($this->buyer, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::BUY->value,
            'price' => 45000.00,
            'amount' => 0.3,
        ]);

        // Check final order book state
        $finalOrderBook = $this->orderService->getOrderBook('BTC-USD');

        // Both orders should be filled and removed from order book
        $this->assertCount(0, $finalOrderBook['buy_orders']);
        $this->assertCount(0, $finalOrderBook['sell_orders']);

        // Verify order statuses
        $buyOrder->refresh();
        $sellOrder->refresh();
        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);
        $this->assertEquals(OrderStatus::FILLED, $sellOrder->status);
    }

    /**
     * Test that orders with different prices don't match.
     */
    public function test_orders_with_different_prices_dont_match(): void
    {
        // Place a sell order at 45000
        $sellOrder = $this->orderService->createOrder($this->seller, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::SELL->value,
            'price' => 45000.00,
            'amount' => 0.3,
        ]);

        // Place a buy order at lower price (44000)
        $buyOrder = $this->orderService->createOrder($this->buyer, [
            'symbol' => 'BTC-USD',
            'side' => OrderSide::BUY->value,
            'price' => 44000.00,
            'amount' => 0.3,
        ]);

        // Both orders should remain in order book (no match)
        $orderBook = $this->orderService->getOrderBook('BTC-USD');
        $this->assertCount(1, $orderBook['buy_orders']);
        $this->assertCount(1, $orderBook['sell_orders']);

        // Verify both orders are still open
        $buyOrder->refresh();
        $sellOrder->refresh();
        $this->assertEquals(OrderStatus::OPEN, $buyOrder->status);
        $this->assertEquals(OrderStatus::OPEN, $sellOrder->status);
    }
}
