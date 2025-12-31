# Real-Time Order Book Setup

This document explains how to set up real-time order book updates using Pusher WebSockets.

## Overview

The limit order exchange now supports real-time order book updates using Laravel Broadcasting with Pusher. When orders are placed, matched, or cancelled, the order book is immediately updated for all connected users.

## Backend Setup

### 1. Environment Configuration

Add the following to your `.env` file:

```env
BROADCAST_CONNECTION=pusher

# Pusher Configuration
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_CLUSTER=mt1
```

### 2. Install Pusher PHP SDK

```bash
composer require pusher/pusher-php-server
```

### 3. Configure Broadcasting

The broadcasting configuration is already set up in `config/broadcasting.php`.

### 4. Enable Broadcasting

Uncomment the `BroadcastServiceProvider` in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\BroadcastServiceProvider::class,
    // ...
],
```

## Frontend Setup

### 1. Install Pusher JS SDK

The Pusher SDK is already added to `package.json` and installed via npm.

### 2. Environment Variables

The following Vite environment variables are automatically available:

```javascript
VITE_PUSHER_APP_KEY
VITE_PUSHER_APP_CLUSTER
```

### 3. WebSocket Connection

The `broadcasting.js` service handles WebSocket connections:

```javascript
import { subscribeToOrderBook } from '../services/broadcasting.js';

// Subscribe to order book updates
const unsubscribe = subscribeToOrderBook('BTC-USD', (data) => {
    // Update order book in real-time
    orderBook.value = {
        buy_orders: data.buyOrders,
        sell_orders: data.sellOrders
    };
});
```

## How It Works

### 1. Order Placement

When a user places an order:
1. Order is created in the database
2. Order matching engine processes the order
3. `OrderBookUpdated` event is broadcast with current order book state
4. All connected users receive the update immediately

### 2. Order Matching

When orders are matched:
1. Trade is executed with commission deduction
2. Order statuses are updated (filled/partially filled)
3. `OrderBookUpdated` event is broadcast with updated order book
4. Order book reflects remaining amounts after partial fills

### 3. Order Cancellation

When an order is cancelled:
1. Order status is updated to cancelled
2. Locked funds/assets are released back to user
3. `OrderBookUpdated` event is broadcast
4. Order book removes the cancelled order

## Real-Time Features

- **Instant Updates**: Order book updates are broadcast immediately
- **Race Condition Safe**: All operations use database transactions
- **Partial Fill Support**: Orders show remaining amounts after partial fills
- **Multi-Symbol Support**: Different symbols have separate channels
- **Automatic Cleanup**: Filled orders are removed from order book

## Testing

To test real-time updates:

1. Open two browser windows with different user accounts
2. Place an order in one window
3. Watch the order book update in real-time in the other window
4. Verify that matched orders are processed correctly

## Troubleshooting

### Common Issues

1. **WebSocket Connection Failed**
   - Check Pusher credentials in `.env`
   - Verify `VITE_PUSHER_APP_KEY` is accessible

2. **No Real-Time Updates**
   - Ensure `BroadcastServiceProvider` is registered
   - Check browser console for WebSocket errors

3. **Permission Errors**
   - Verify Pusher channel permissions
   - Check that events implement `ShouldBroadcast`

### Debug Mode

Enable debug mode in `.env` to see detailed logs:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Production Considerations

1. **Use HTTPS**: Pusher requires TLS in production
2. **Scale Channels**: Consider channel limits for high traffic
3. **Monitor Usage**: Track Pusher API usage and costs
4. **Fallback**: Implement polling fallback for WebSocket failures
