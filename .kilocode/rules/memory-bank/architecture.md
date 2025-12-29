# Virgosoft Limit Order Exchange - System Architecture

## System Overview
The limit-order exchange follows a client-server architecture with a Laravel API backend and Vue.js frontend, connected through RESTful APIs and real-time WebSocket connections.

## Database Schema Design

### Core Tables
1. **users**
   - Default Laravel columns
   - balance (decimal, USD funds)

2. **assets**
   - user_id (foreign key)
   - symbol (e.g., BTC, ETH)
   - amount (decimal, available asset)
   - locked_amount (decimal, reserved for open sell orders)

3. **orders**
   - user_id (foreign key)
   - symbol (trading pair)
   - side (buy/sell)
   - price (decimal)
   - amount (decimal)
   - status (open=1, filled=2, cancelled=3)
   - Timestamps

4. **trades** (optional)
   - buy_order_id (foreign key)
   - sell_order_id (foreign key)
   - symbol
   - price
   - amount
   - commission (decimal)

## API Endpoints Structure

### Authentication & Profile
- GET /api/profile - Returns authenticated user's USD balance + asset balances

### Order Management
- GET /api/orders?symbol=BTC - Returns all open orders for orderbook (buy & sell)
- POST /api/orders - Creates a limit order
- POST /api/orders/{id}/cancel - Cancels an open order and releases locked USD or assets

### Order Matching
- Internal matching or job-based match trigger - Matches new orders with the first valid counter order

## Frontend Architecture

### Vue.js Components Structure
```
src/
├── components/
│   ├── OrderForm.vue          # Limit order placement form
│   ├── OrderBook.vue          # Order book display
│   ├── BalanceDisplay.vue     # User balance and assets
│   ├── OrderHistory.vue       # Order history list
│   └── TradeNotifications.vue # Real-time trade updates
├── composables/
│   ├── useOrders.js           # Order management logic
│   ├── useBalance.js          # Balance and asset management
│   └── useWebSocket.js        # Real-time connection handling
├── services/
│   ├── api.js                 # API client
│   └── broadcasting.js        # Pusher integration
└── views/
    ├── Dashboard.vue          # Main trading interface
    └── Login.vue              # Authentication
```

## Order Matching Engine Logic

### Matching Rules (Full Match Only)
- New BUY → match with first SELL where sell.price <= buy.price
- New SELL → match with first BUY where buy.price >= sell.price

### Order Processing Flow
1. **Buy Order Creation**
   - Check if users.balance >= amount * price
   - Deduct amount * price from users.balance
   - Mark order as open with locked USD value

2. **Sell Order Creation**
   - Check if assets.amount >= amount
   - Move amount into assets.locked_amount
   - Mark order as open

3. **Order Matching**
   - Find first valid counter order
   - Execute atomic transaction
   - Calculate and deduct commission (1.5% of matched USD value)
   - Update balances and assets
   - Broadcast OrderMatched event

## Real-time Architecture

### Broadcasting System
- Laravel Broadcasting with Pusher
- Private channels: private-user.{id}
- Events: OrderMatched, OrderCreated, OrderCancelled, BalanceUpdated

### Frontend Real-time Updates
- Listen for OrderMatched event
- Patch new trade into UI
- Update balance and asset displays
- Update order status in list

## Security & Race Condition Prevention

### Database Transactions
- All financial operations wrapped in database transactions
- Use of row-level locking for balance updates
- Atomic operations for order matching

### Concurrency Control
- Database-level constraints for balance integrity
- Optimistic locking for order status updates
- Queue-based order processing to handle race conditions

## Commission System
- Commission = 1.5% of the matched USD value
- Deducted from buyer (sender) and/or asset fee from seller
- Consistent application across all trades
