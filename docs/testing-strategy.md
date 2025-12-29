# Testing Strategy for Virgosoft Limit Order Exchange

## Overview
This document outlines the comprehensive testing strategy for the limit order exchange, focusing on financial data integrity, race condition prevention, and system reliability.

## Testing Pyramid

```
    /\
   /  \  E2E Tests (5%)
  /____\
 /      \ Integration Tests (25%)
/__________\
Unit Tests (70%)
```

## Unit Testing

### 1. Model Tests
**Location**: `tests/Unit/Models/`

#### User Model Tests
```php
class UserTest extends TestCase
{
    public function test_user_can_have_balance()
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        $this->assertEquals(1000.00, $user->balance);
    }
    
    public function test_user_balance_cannot_be_negative()
    {
        $this->expectException(InvalidArgumentException::class);
        User::factory()->create(['balance' => -100.00]);
    }
}
```

#### Asset Model Tests
```php
class AssetTest extends TestCase
{
    public function test_asset_belongs_to_user()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $asset->user);
    }
    
    public function test_asset_calculates_available_amount()
    {
        $asset = Asset::factory()->create([
            'amount' => 10.0,
            'locked_amount' => 3.0
        ]);
        
        $this->assertEquals(7.0, $asset->available_amount);
    }
}
```

#### Order Model Tests
```php
class OrderTest extends TestCase
{
    public function test_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $order->user);
    }
    
    public function test_order_calculates_remaining_amount()
    {
        $order = Order::factory()->create([
            'amount' => 10.0,
            'filled_amount' => 3.0
        ]);
        
        $this->assertEquals(7.0, $order->remaining_amount);
    }
    
    public function test_order_scope_for_open_orders()
    {
        $openOrder = Order::factory()->create(['status' => 'open']);
        $filledOrder = Order::factory()->create(['status' => 'filled']);
        
        $openOrders = Order::open()->get();
        
        $this->assertCount(1, $openOrders);
        $this->assertEquals($openOrder->id, $openOrders->first()->id);
    }
}
```

### 2. Repository Tests
**Location**: `tests/Unit/Repositories/`

#### Order Repository Tests
```php
class OrderRepositoryTest extends TestCase
{
    private OrderRepository $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository();
    }
    
    public function test_find_open_orders_by_symbol()
    {
        $btcOrder = Order::factory()->create([
            'symbol' => 'BTC-USD',
            'status' => 'open'
        ]);
        $ethOrder = Order::factory()->create([
            'symbol' => 'ETH-USD',
            'status' => 'open'
        ]);
        $filledOrder = Order::factory()->create([
            'symbol' => 'BTC-USD',
            'status' => 'filled'
        ]);
        
        $openBtcOrders = $this->repository->findOpenOrders('BTC-USD');
        
        $this->assertCount(1, $openBtcOrders);
        $this->assertEquals($btcOrder->id, $openBtcOrders->first()->id);
    }
    
    public function test_create_order_persists_data()
    {
        $orderData = [
            'user_id' => User::factory()->create()->id,
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 45000.00,
            'amount' => 0.1
        ];
        
        $order = $this->repository->create($orderData);
        
        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', $orderData);
    }
}
```

### 3. Service Tests
**Location**: `tests/Unit/Services/`

#### Order Service Tests
```php
class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }
    
    public function test_create_buy_order_deducts_balance()
    {
        $user = User::factory()->create(['balance' => 10000.00]);
        
        $orderData = [
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 45000.00,
            'amount' => 0.1
        ];
        
        $order = $this->orderService->createOrder($user, $orderData);
        
        $this->assertEquals(5500.00, $user->fresh()->balance);
        $this->assertEquals('open', $order->status);
    }
    
    public function test_create_buy_order_fails_with_insufficient_balance()
    {
        $user = User::factory()->create(['balance' => 100.00]);
        
        $orderData = [
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 45000.00,
            'amount' => 0.1
        ];
        
        $this->expectException(InsufficientBalanceException::class);
        $this->orderService->createOrder($user, $orderData);
    }
    
    public function test_create_sell_order_locks_assets()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 1.0,
            'locked_amount' => 0.0
        ]);
        
        $orderData = [
            'symbol' => 'BTC-USD',
            'side' => 'sell',
            'price' => 45000.00,
            'amount' => 0.1
        ];
        
        $order = $this->orderService->createOrder($user, $orderData);
        
        $this->assertEquals(0.1, $asset->fresh()->locked_amount);
        $this->assertEquals('open', $order->status);
    }
}
```

#### Commission Service Tests
```php
class CommissionServiceTest extends TestCase
{
    private CommissionService $commissionService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = app(CommissionService::class);
    }
    
    public function test_calculate_commission()
    {
        $amount = 0.1;
        $price = 45000.00;
        $expectedCommission = 67.50; // 0.1 * 45000 * 0.015
        
        $commission = $this->commissionService->calculate($amount, $price);
        
        $this->assertEquals($expectedCommission, $commission);
    }
    
    public function test_commission_rate_is_constant()
    {
        $this->assertEquals(0.015, $this->commissionService->getRate());
    }
}
```

### 4. Job Tests
**Location**: `tests/Unit/Jobs/`

#### Order Matching Job Tests
```php
class ProcessOrderMatchingTest extends TestCase
{
    public function test_job_matches_buy_order_with_sell_order()
    {
        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create();
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 1.0
        ]);
        
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC-USD',
            'side' => 'sell',
            'price' => 45000.00,
            'amount' => 0.1,
            'status' => 'open'
        ]);
        
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 45100.00,
            'amount' => 0.1,
            'status' => 'open'
        ]);
        
        $job = new ProcessOrderMatching($buyOrder);
        $job->handle();
        
        $this->assertEquals('filled', $buyOrder->fresh()->status);
        $this->assertEquals('filled', $sellOrder->fresh()->status);
        $this->assertDatabaseHas('trades', [
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id
        ]);
    }
}
```

## Integration Testing

### 1. API Endpoint Tests
**Location**: `tests/Feature/`

#### Authentication Tests
```php
class AuthTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                        'user'
                    ]
                ]);
    }
    
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS'
                    ]
                ]);
    }
}
```

#### Order Management Tests
```php
class OrderTest extends TestCase
{
    private User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['balance' => 10000.00]);
        $this->actingAs($this->user);
    }
    
    public function test_user_can_create_buy_order()
    {
        $orderData = [
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 45000.00,
            'amount' => 0.1
        ];
        
        $response = $this->postJson('/api/orders', $orderData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'symbol',
                        'side',
                        'price',
                        'amount',
                        'status'
                    ]
                ]);
        
        $this->assertDatabaseHas('orders', $orderData);
        $this->assertEquals(5500.00, $this->user->fresh()->balance);
    }
    
    public function test_user_cannot_create_order_with_insufficient_balance()
    {
        $orderData = [
            'symbol' => 'BTC-USD',
            'side' => 'buy',
            'price' => 45000.00,
            'amount' => 1.0
        ];
        
        $response = $this->postJson('/api/orders', $orderData);
        
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INSUFFICIENT_BALANCE'
                    ]
                ]);
    }
}
```

### 2. Real-time Broadcasting Tests
```php
class BroadcastingTest extends TestCase
{
    public function test_order_created_event_is_broadcast()
    {
        Event::fake();
        
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        
        event(new OrderCreated($order));
        
        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }
}
```

## Concurrency Testing

### 1. Race Condition Tests
```php
class ConcurrencyTest extends TestCase
{
    public function test_concurrent_balance_updates_are_atomic()
    {
        $user = User::factory()->create(['balance' => 1000.00]);
        
        // Simulate concurrent balance deductions
        $processes = 10;
        $deductionAmount = 100.00;
        
        $promises = [];
        for ($i = 0; $i < $processes; $i++) {
            $promises[] = $this->async(function () use ($user, $deductionAmount) {
                return $this->orderService->deductBalance($user, $deductionAmount);
            });
        }
        
        $results = Promise::all($promises)->wait();
        
        // Only some should succeed due to insufficient balance
        $successfulDeductions = count(array_filter($results));
        $expectedFinalBalance = 1000.00 - ($successfulDeductions * $deductionAmount);
        
        $this->assertEquals($expectedFinalBalance, $user->fresh()->balance);
        $this->assertGreaterThanOrEqual(0, $user->fresh()->balance);
    }
    
    public function test_concurrent_order_matching_handles_race_conditions()
    {
        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create();
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 1.0
        ]);
        
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC-USD',
            'side' => 'sell',
            'price' => 45000.00,
            'amount' => 0.1,
            'status' => 'open'
        ]);
        
        // Create multiple buy orders simultaneously
        $promises = [];
        for ($i = 0; $i < 5; $i++) {
            $promises[] = $this->async(function () use ($buyer) {
                $orderData = [
                    'symbol' => 'BTC-USD',
                    'side' => 'buy',
                    'price' => 45100.00,
                    'amount' => 0.1
                ];
                return $this->orderService->createOrder($buyer, $orderData);
            });
        }
        
        $orders = Promise::all($promises)->wait();
        
        // Only one order should be matched
        $matchedOrders = Order::where('status', 'filled')->get();
        $this->assertCount(1, $matchedOrders);
        
        // Only one trade should be created
        $trades = Trade::all();
        $this->assertCount(1, $trades);
    }
}
```

## Performance Testing

### 1. Load Testing
```php
class PerformanceTest extends TestCase
{
    public function test_order_book_query_performance()
    {
        // Create large dataset
        Order::factory()->count(10000)->create(['status' => 'open']);
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/orderbook?symbol=BTC-USD');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(0.5, $executionTime, 'Order book query should be under 500ms');
    }
    
    public function test_concurrent_order_creation_performance()
    {
        $users = User::factory()->count(100)->create(['balance' => 10000.00]);
        
        $startTime = microtime(true);
        
        $promises = [];
        foreach ($users as $user) {
            $promises[] = $this->async(function () use ($user) {
                $orderData = [
                    'symbol' => 'BTC-USD',
                    'side' => 'buy',
                    'price' => 45000.00,
                    'amount' => 0.1
                ];
                return $this->actingAs($user)->postJson('/api/orders', $orderData);
            });
        }
        
        $responses = Promise::all($promises)->wait();
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        $successCount = count(array_filter($responses, fn($r) => $r->status() === 201));
        
        $this->assertEquals(100, $successCount);
        $this->assertLessThan(5.0, $totalTime, '100 concurrent orders should be created within 5 seconds');
    }
}
```

## End-to-End Testing

### 1. Browser Tests
**Location**: `tests/Browser/`

```php
class TradingFlowTest extends DuskTestCase
{
    public function test_complete_trading_workflow()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->press('Login')
                    ->waitForLocation('/dashboard')
                    ->assertSee('Balance')
                    ->clickLink('Place Order')
                    ->waitFor('#order-form')
                    ->select('side', 'buy')
                    ->type('price', '45000')
                    ->type('amount', '0.1')
                    ->press('Submit Order')
                    ->waitFor('.order-success')
                    ->assertSee('Order created successfully')
                    ->clickLink('Order Book')
                    ->waitFor('.order-book')
                    ->assertSee('45000.00');
        });
    }
}
```

## Test Data Management

### 1. Factories
```php
// database/factories/OrderFactory.php
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => $this->faker->randomElement(['BTC-USD', 'ETH-USD']),
            'side' => $this->faker->randomElement(['buy', 'sell']),
            'price' => $this->faker->randomFloat(8, 1000, 50000),
            'amount' => $this->faker->randomFloat(8, 0.001, 10),
            'filled_amount' => 0,
            'status' => 'open'
        ];
    }
}
```

### 2. Test Database
```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Continuous Integration

### 1. GitHub Actions Workflow
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: test_db
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: pdo, pdo_pgsql, bcmath
        
    - name: Install dependencies
      run: composer install --no-progress --no-interaction
      
    - name: Run tests
      run: vendor/bin/phpunit
      
    - name: Run performance tests
      run: vendor/bin/phpunit tests/Performance
      
    - name: Generate coverage report
      run: vendor/bin/phpunit --coverage-html coverage
```

## Test Coverage Requirements

### Minimum Coverage Targets
- Unit Tests: 95% line coverage
- Integration Tests: 90% line coverage
- Overall Coverage: 90% line coverage

### Critical Path Coverage
- All financial operations: 100% coverage
- Order matching logic: 100% coverage
- Authentication and authorization: 100% coverage
- Input validation: 100% coverage

This comprehensive testing strategy ensures the reliability, security, and performance of the limit order exchange, with special focus on preventing race conditions and ensuring financial data integrity.
