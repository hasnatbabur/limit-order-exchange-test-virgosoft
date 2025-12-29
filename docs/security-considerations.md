# Security Considerations for Virgosoft Limit Order Exchange

## Overview
This document outlines the security measures and considerations for the limit order exchange, focusing on protecting financial assets, preventing fraud, and ensuring data integrity.

## Authentication & Authorization

### 1. Authentication Implementation

#### Laravel Sanctum Configuration
```php
// config/sanctum.php
'expiration' => 60, // Token expiration in minutes
'personal_access_tokens' => [
    'expire_at' => now()->addHours(24),
],
```

#### Secure Password Handling
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

public function boot(): void
{
    Password::defaults(function () {
        return Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    });
}
```

#### Rate Limiting for Authentication
```php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function store(LoginRequest $request): RedirectResponse
{
    RateLimiter::hit($request->throttleKey());
    
    if (RateLimiter::tooManyAttempts($request->throttleKey(), 5)) {
        throw new TooManyRequestsException;
    }
    
    // Authentication logic
}
```

### 2. Authorization Implementation

#### Policy-Based Authorization
```php
// app/Policies/OrderPolicy.php
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
    
    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id && 
               $order->status === OrderStatus::OPEN;
    }
    
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }
}
```

#### Middleware for API Protection
```php
// app/Http/Middleware/EnsureUserCanTrade.php
class EnsureUserCanTrade
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'message' => 'Email address must be verified to trade'
                ]
            ], 403);
        }
        
        if ($user->is_suspended) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_SUSPENDED',
                    'message' => 'Account is suspended'
                ]
            ], 403);
        }
        
        return $next($request);
    }
}
```

## Input Validation & Sanitization

### 1. Form Request Validation

#### Order Creation Validation
```php
// app/Features/Orders/Http/Requests/CreateOrderRequest.php
class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                'regex:/^[A-Z]{3,4}-[A-Z]{3,4}$/',
                Rule::in(['BTC-USD', 'ETH-USD'])
            ],
            'side' => [
                'required',
                'string',
                Rule::in(['buy', 'sell'])
            ],
            'price' => [
                'required',
                'numeric',
                'decimal:2,8',
                'min:0.01',
                'max:1000000'
            ],
            'amount' => [
                'required',
                'numeric',
                'decimal:2,8',
                'min:0.00000001',
                'max:1000'
            ]
        ];
    }
    
    public function sanitize(): array
    {
        $input = $this->all();
        
        // Sanitize numeric inputs
        $input['price'] = number_format($input['price'], 8, '.', '');
        $input['amount'] = number_format($input['amount'], 8, '.', '');
        
        return $input;
    }
}
```

### 2. SQL Injection Prevention

#### Parameterized Queries
```php
// app/Features/Orders/Repositories/OrderRepository.php
class OrderRepository implements OrderRepositoryInterface
{
    public function findOpenOrders(string $symbol): Collection
    {
        return Order::where('symbol', $symbol)
                   ->where('status', OrderStatus::OPEN->value)
                   ->orderBy('price', 'desc')
                   ->get();
    }
}
```

#### Raw Query Safety
```php
// When using raw queries, always use parameter binding
$orders = DB::select('
    SELECT * FROM orders 
    WHERE symbol = ? AND status = ? 
    ORDER BY price DESC
', [$symbol, 'open']);
```

## Financial Security

### 1. Balance & Asset Protection

#### Atomic Balance Updates
```php
// app/Features/Users/Services/BalanceService.php
class BalanceService
{
    public function updateBalance(User $user, float $amount, string $type): bool
    {
        return DB::transaction(function () use ($user, $amount, $type) {
            // Lock user row for update
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            
            if ($type === 'deduct' && $lockedUser->balance < $amount) {
                throw new InsufficientBalanceException();
            }
            
            $newBalance = $type === 'deduct' 
                ? $lockedUser->balance - $amount 
                : $lockedUser->balance + $amount;
            
            $lockedUser->update(['balance' => $newBalance]);
            
            // Create audit log
            BalanceAuditLog::create([
                'user_id' => $user->id,
                'old_balance' => $lockedUser->balance,
                'new_balance' => $newBalance,
                'amount' => $amount,
                'type' => $type
            ]);
            
            return true;
        });
    }
}
```

#### Double-Entry Accounting
```php
// app/Features/Trading/Services/AccountingService.php
class AccountingService
{
    public function recordTrade(Trade $trade): void
    {
        DB::transaction(function () use ($trade) {
            // Debit buyer's account
            AccountingEntry::create([
                'user_id' => $trade->buyOrder->user_id,
                'trade_id' => $trade->id,
                'debit' => $trade->amount * $trade->price + $trade->commission,
                'credit' => 0,
                'description' => "Buy {$trade->amount} {$trade->symbol}"
            ]);
            
            // Credit seller's account
            AccountingEntry::create([
                'user_id' => $trade->sellOrder->user_id,
                'trade_id' => $trade->id,
                'debit' => 0,
                'credit' => $trade->amount * $trade->price - $trade->commission,
                'description' => "Sell {$trade->amount} {$trade->symbol}"
            ]);
            
            // Record commission
            AccountingEntry::create([
                'user_id' => null, // System account
                'trade_id' => $trade->id,
                'debit' => 0,
                'credit' => $trade->commission * 2, // Both sides
                'description' => "Commission for trade {$trade->id}"
            ]);
        });
    }
}
```

### 2. Race Condition Prevention

#### Pessimistic Locking
```php
// app/Features/Orders/Services/OrderMatchingService.php
class OrderMatchingService
{
    public function matchOrder(Order $newOrder): ?Trade
    {
        return DB::transaction(function () use ($newOrder) {
            // Lock relevant rows
            $counterOrders = Order::where('symbol', $newOrder->symbol)
                                  ->where('side', $newOrder->getOppositeSide())
                                  ->where('status', OrderStatus::OPEN)
                                  ->where('price', $newOrder->getMatchingPriceCondition())
                                  ->orderBy('created_at')
                                  ->lockForUpdate()
                                  ->first();
            
            if (!$counterOrders) {
                return null;
            }
            
            // Execute trade with locked rows
            return $this->executeTrade($newOrder, $counterOrders);
        });
    }
}
```

#### Optimistic Locking
```php
// app/Models/Order.php
class Order extends Model
{
    protected $casts = [
        'version' => 'integer'
    ];
    
    public function updateWithLock(array $attributes): bool
    {
        $affected = DB::table('orders')
                     ->where('id', $this->id)
                     ->where('version', $this->version)
                     ->update(array_merge($attributes, [
                         'version' => $this->version + 1
                     ]));
        
        if ($affected === 0) {
            throw new OptimisticLockException();
        }
        
        $this->fill($attributes);
        $this->version++;
        
        return true;
    }
}
```

## API Security

### 1. Request/Response Security

#### CORS Configuration
```php
// config/cors.php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['https://virgosoft-exchange.com'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

#### Response Headers
```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        
        return $response;
    }
}
```

### 2. Rate Limiting

#### API Rate Limiting
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('orders', OrderController::class);
});

Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
});
```

#### Custom Rate Limiting
```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    RateLimiter::for('orders', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
    
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

## Data Protection

### 1. Encryption

#### Sensitive Data Encryption
```php
// app/Models/User.php
class User extends Model
{
    protected $casts = [
        'api_secret' => 'encrypted',
        'two_factor_secret' => 'encrypted',
    ];
}
```

#### Environment Variable Security
```php
// .env.example
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=virgosoft_exchange
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=mt1

# Encryption keys
JWT_SECRET=your_jwt_secret_key
API_ENCRYPTION_KEY=your_api_encryption_key
```

### 2. Audit Logging

#### Comprehensive Audit Trail
```php
// app/Observers/OrderObserver.php
class OrderObserver
{
    public function created(Order $order): void
    {
        AuditLog::create([
            'user_id' => $order->user_id,
            'action' => 'order_created',
            'model_type' => Order::class,
            'model_id' => $order->id,
            'old_values' => null,
            'new_values' => $order->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
    
    public function updated(Order $order): void
    {
        AuditLog::create([
            'user_id' => $order->user_id,
            'action' => 'order_updated',
            'model_type' => Order::class,
            'model_id' => $order->id,
            'old_values' => $order->getOriginal(),
            'new_values' => $order->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
```

## WebSocket Security

### 1. Authentication

#### WebSocket Authentication
```php
// app/Events/BroadcastOrderCreated.php
class BroadcastOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(public Order $order) {}
    
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('orders.' . $this->order->user_id);
    }
    
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'symbol' => $this->order->symbol,
            'side' => $this->order->side,
            'price' => $this->order->price,
            'amount' => $this->order->amount,
            'status' => $this->order->status,
            'timestamp' => $this->order->created_at->toISOString()
        ];
    }
}
```

### 2. Channel Authorization

#### Private Channel Authorization
```php
// routes/channels.php
Broadcast::channel('orders.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('orderbook.{symbol}', function ($user, $symbol) {
    // Only authenticated users can subscribe to order book
    return $user !== null;
});
```

## Infrastructure Security

### 1. Environment Configuration

#### Production Environment Settings
```php
// .env.production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.virgosoft-exchange.com

# Database
DB_SSLMODE=require
DB_SSL_CERT=/path/to/client-cert.pem
DB_SSL_KEY=/path/to/client-key.pem
DB_SSL_CA=/path/to/ca-cert.pem

# Session Security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=exchange_cache
```

### 2. Database Security

#### Database User Permissions
```sql
-- Application user with limited permissions
CREATE USER exchange_app WITH PASSWORD 'secure_password';

-- Grant necessary permissions
GRANT CONNECT ON DATABASE virgosoft_exchange TO exchange_app;
GRANT USAGE ON SCHEMA public TO exchange_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO exchange_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO exchange_app;

-- Revoke dangerous permissions
REVOKE CREATE ON SCHEMA public FROM exchange_app;
REVOKE ALL ON SCHEMA public FROM exchange_app;
```

## Monitoring & Alerting

### 1. Security Monitoring

#### Suspicious Activity Detection
```php
// app/Http/Middleware/DetectSuspiciousActivity.php
class DetectSuspiciousActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user) {
            // Check for rapid order creation
            $recentOrders = Order::where('user_id', $user->id)
                               ->where('created_at', '>', now()->subMinutes(5))
                               ->count();
            
            if ($recentOrders > 50) {
                SecurityAlert::create([
                    'user_id' => $user->id,
                    'type' => 'rapid_order_creation',
                    'details' => ['count' => $recentOrders],
                    'ip_address' => $request->ip()
                ]);
            }
        }
        
        return $next($request);
    }
}
```

### 2. Error Handling

#### Secure Error Responses
```php
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $exception->errors()
                ]
            ], 422);
        }
        
        // Don't expose internal errors in production
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An internal error occurred'
                ]
            ], 500);
        }
        
        return parent::render($request, $exception);
    }
}
```

## Compliance & Legal

### 1. Data Retention

#### GDPR Compliance
```php
// app/Http/Controllers/PrivacyController.php
class PrivacyController extends Controller
{
    public function exportUserData(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $userData = [
            'personal_info' => $user->only(['name', 'email', 'created_at']),
            'orders' => $user->orders()->with('trades')->get(),
            'trades' => $user->trades()->get(),
            'balance_audit' => $user->balanceAuditLogs()->get()
        ];
        
        return response()->json($userData);
    }
    
    public function deleteUserData(Request $request): JsonResponse
    {
        // Implement GDPR right to be forgotten
        // Anonymize user data instead of deleting for audit purposes
    }
}
```

### 2. Financial Regulations

#### AML/KYC Considerations
```php
// app/Http/Controllers/ComplianceController.php
class ComplianceController extends Controller
{
    public function checkTransactionLimits(User $user, float $amount): bool
    {
        $dailyTotal = $user->trades()
                          ->whereDate('created_at', today())
                          ->sum(DB::raw('amount * price'));
        
        if ($dailyTotal + $amount > config('exchange.daily_transaction_limit')) {
            ComplianceAlert::create([
                'user_id' => $user->id,
                'type' => 'daily_limit_exceeded',
                'amount' => $dailyTotal + $amount
            ]);
            
            return false;
        }
        
        return true;
    }
}
```

This comprehensive security framework ensures the protection of user assets, prevention of fraudulent activities, and compliance with financial regulations while maintaining system performance and usability.
