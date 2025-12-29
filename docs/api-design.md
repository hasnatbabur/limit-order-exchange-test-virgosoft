# API Design Documentation

## Overview
This document outlines the RESTful API design for the Virgosoft Limit Order Exchange, focusing on clear endpoints, consistent data structures, and proper HTTP semantics.

## Base Configuration

### Base URL
```
https://api.virgosoft-exchange.com/api/v1
```

### Authentication
- Method: Bearer Token (Laravel Sanctum)
- Header: `Authorization: Bearer {token}`
- Token endpoint: `POST /auth/login`

### Response Format
All responses follow a consistent structure:

```json
{
    "success": true,
    "data": {},
    "message": "Operation completed successfully",
    "timestamp": "2025-12-29T20:18:00Z"
}
```

Error responses:
```json
{
    "success": false,
    "error": {
        "code": "INSUFFICIENT_BALANCE",
        "message": "Insufficient balance for this operation",
        "details": {}
    },
    "timestamp": "2025-12-29T20:18:00Z"
}
```

## Authentication Endpoints

### POST /auth/login
Authenticates a user and returns an access token.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "access_token": "1|abc123...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        }
    }
}
```

### POST /auth/logout
Revokes the current access token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

### GET /auth/me
Returns the current authenticated user information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "balance": "10000.00000000"
    }
}
```

## Profile Endpoints

### GET /profile
Returns the user's complete profile including balances and assets.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        },
        "balances": {
            "USD": "10000.00000000"
        },
        "assets": [
            {
                "symbol": "BTC",
                "amount": "0.50000000",
                "locked_amount": "0.10000000",
                "available_amount": "0.40000000"
            },
            {
                "symbol": "ETH",
                "amount": "10.00000000",
                "locked_amount": "2.00000000",
                "available_amount": "8.00000000"
            }
        ]
    }
}
```

## Order Management Endpoints

### GET /orders
Retrieves orders with optional filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `symbol` (optional): Filter by trading pair (e.g., BTC-USD)
- `status` (optional): Filter by status (open, filled, cancelled)
- `side` (optional): Filter by side (buy, sell)
- `limit` (optional): Number of results per page (default: 50)
- `offset` (optional): Pagination offset (default: 0)

**Response:**
```json
{
    "success": true,
    "data": {
        "orders": [
            {
                "id": 1,
                "symbol": "BTC-USD",
                "side": "buy",
                "price": "45000.00000000",
                "amount": "0.10000000",
                "filled_amount": "0.00000000",
                "status": "open",
                "created_at": "2025-12-29T20:18:00Z",
                "updated_at": "2025-12-29T20:18:00Z"
            }
        ],
        "pagination": {
            "total": 1,
            "limit": 50,
            "offset": 0,
            "has_more": false
        }
    }
}
```

### POST /orders
Creates a new limit order.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request:**
```json
{
    "symbol": "BTC-USD",
    "side": "buy",
    "price": "45000.00000000",
    "amount": "0.10000000"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "symbol": "BTC-USD",
        "side": "buy",
        "price": "45000.00000000",
        "amount": "0.10000000",
        "filled_amount": "0.00000000",
        "status": "open",
        "created_at": "2025-12-29T20:18:00Z",
        "updated_at": "2025-12-29T20:18:00Z"
    },
    "message": "Order created successfully"
}
```

### GET /orders/{id}
Retrieves a specific order by ID.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "symbol": "BTC-USD",
        "side": "buy",
        "price": "45000.00000000",
        "amount": "0.10000000",
        "filled_amount": "0.00000000",
        "status": "open",
        "created_at": "2025-12-29T20:18:00Z",
        "updated_at": "2025-12-29T20:18:00Z"
    }
}
```

### POST /orders/{id}/cancel
Cancels an open order.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "status": "cancelled",
        "cancelled_at": "2025-12-29T20:18:00Z"
    },
    "message": "Order cancelled successfully"
}
```

## Order Book Endpoints

### GET /orderbook
Retrieves the current order book for a specific symbol.

**Query Parameters:**
- `symbol` (required): Trading pair (e.g., BTC-USD)
- `limit` (optional): Number of price levels (default: 20)

**Response:**
```json
{
    "success": true,
    "data": {
        "symbol": "BTC-USD",
        "timestamp": "2025-12-29T20:18:00Z",
        "bids": [
            {
                "price": "45000.00000000",
                "amount": "0.50000000",
                "total": "22500.00000000"
            },
            {
                "price": "44950.00000000",
                "amount": "0.30000000",
                "total": "13485.00000000"
            }
        ],
        "asks": [
            {
                "price": "45100.00000000",
                "amount": "0.20000000",
                "total": "9020.00000000"
            },
            {
                "price": "45150.00000000",
                "amount": "0.40000000",
                "total": "18060.00000000"
            }
        ]
    }
}
```

## Trade History Endpoints

### GET /trades
Retrieves trade history with optional filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `symbol` (optional): Filter by trading pair
- `limit` (optional): Number of results per page (default: 50)
- `offset` (optional): Pagination offset (default: 0)
- `from_date` (optional): Start date filter (ISO 8601)
- `to_date` (optional): End date filter (ISO 8601)

**Response:**
```json
{
    "success": true,
    "data": {
        "trades": [
            {
                "id": 1,
                "symbol": "BTC-USD",
                "price": "45000.00000000",
                "amount": "0.10000000",
                "commission": "67.50000000",
                "side": "buy",
                "created_at": "2025-12-29T20:18:00Z"
            }
        ],
        "pagination": {
            "total": 1,
            "limit": 50,
            "offset": 0,
            "has_more": false
        }
    }
}
```

## Market Data Endpoints

### GET /market/symbols
Retrieves available trading symbols.

**Response:**
```json
{
    "success": true,
    "data": {
        "symbols": [
            {
                "symbol": "BTC-USD",
                "base_asset": "BTC",
                "quote_asset": "USD",
                "status": "active"
            },
            {
                "symbol": "ETH-USD",
                "base_asset": "ETH",
                "quote_asset": "USD",
                "status": "active"
            }
        ]
    }
}
```

### GET /market/ticker
Retrieves 24hr ticker statistics for a symbol.

**Query Parameters:**
- `symbol` (required): Trading pair (e.g., BTC-USD)

**Response:**
```json
{
    "success": true,
    "data": {
        "symbol": "BTC-USD",
        "price_change": "500.00000000",
        "price_change_percent": "1.12",
        "weighted_avg_price": "44750.00000000",
        "prev_close_price": "44500.00000000",
        "last_price": "45000.00000000",
        "last_qty": "0.10000000",
        "bid_price": "44999.00000000",
        "bid_qty": "0.50000000",
        "ask_price": "45001.00000000",
        "ask_qty": "0.20000000",
        "open_price": "44500.00000000",
        "high_price": "45200.00000000",
        "low_price": "44300.00000000",
        "volume": "125.50000000",
        "quote_volume": "5623750.00000000",
        "open_time": "2025-12-28T20:18:00Z",
        "close_time": "2025-12-29T20:18:00Z",
        "count": 1250
    }
}
```

## WebSocket Events

### Connection
```
WebSocket URL: wss://api.virgosoft-exchange.com/ws
Authentication: Send auth token after connection
```

### Authentication Message
```json
{
    "event": "auth",
    "data": {
        "token": "1|abc123..."
    }
}
```

### Subscribe to Order Book
```json
{
    "event": "subscribe",
    "data": {
        "channel": "orderbook",
        "symbol": "BTC-USD"
    }
}
```

### Order Book Update Event
```json
{
    "event": "orderbook_update",
    "data": {
        "symbol": "BTC-USD",
        "timestamp": "2025-12-29T20:18:00Z",
        "bids": [
            {
                "price": "45000.00000000",
                "amount": "0.50000000"
            }
        ],
        "asks": [
            {
                "price": "45100.00000000",
                "amount": "0.20000000"
            }
        ]
    }
}
```

### Subscribe to User Orders
```json
{
    "event": "subscribe",
    "data": {
        "channel": "orders",
        "user_id": 1
    }
}
```

### Order Update Event
```json
{
    "event": "order_update",
    "data": {
        "id": 1,
        "symbol": "BTC-USD",
        "side": "buy",
        "price": "45000.00000000",
        "amount": "0.10000000",
        "filled_amount": "0.10000000",
        "status": "filled",
        "updated_at": "2025-12-29T20:18:00Z"
    }
}
```

### Trade Event
```json
{
    "event": "trade",
    "data": {
        "id": 1,
        "symbol": "BTC-USD",
        "price": "45000.00000000",
        "amount": "0.10000000",
        "side": "buy",
        "timestamp": "2025-12-29T20:18:00Z"
    }
}
```

### Balance Update Event
```json
{
    "event": "balance_update",
    "data": {
        "user_id": 1,
        "balances": {
            "USD": "9500.00000000"
        },
        "assets": [
            {
                "symbol": "BTC",
                "amount": "0.60000000",
                "locked_amount": "0.00000000"
            }
        ],
        "timestamp": "2025-12-29T20:18:00Z"
    }
}
```

## Error Codes

### Authentication Errors
- `UNAUTHORIZED` (401): Invalid or missing authentication token
- `TOKEN_EXPIRED` (401): Authentication token has expired
- `INVALID_CREDENTIALS` (401): Invalid email or password

### Validation Errors
- `VALIDATION_ERROR` (422): Request validation failed
- `INVALID_SYMBOL` (400): Invalid trading symbol
- `INVALID_ORDER_SIDE` (400): Invalid order side (must be buy or sell)
- `INVALID_PRICE` (400): Price must be greater than 0
- `INVALID_AMOUNT` (400): Amount must be greater than 0

### Business Logic Errors
- `INSUFFICIENT_BALANCE` (400): Insufficient USD balance for buy order
- `INSUFFICIENT_ASSETS` (400): Insufficient assets for sell order
- `ORDER_NOT_FOUND` (404): Order not found
- `ORDER_ALREADY_FILLED` (400): Cannot cancel filled order
- `ORDER_ALREADY_CANCELLED` (400): Order is already cancelled

### System Errors
- `INTERNAL_ERROR` (500): Internal server error
- `SERVICE_UNAVAILABLE` (503): Service temporarily unavailable
- `RATE_LIMIT_EXCEEDED` (429): Too many requests

## Rate Limiting

### Limits
- Authentication endpoints: 5 requests per minute
- Order creation: 100 requests per minute
- Order book queries: 1000 requests per minute
- Profile queries: 200 requests per minute
- Trade history: 100 requests per minute

### Headers
Rate limit information is included in response headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1640786280
```

## API Versioning

### Current Version
- Version: v1
- Base URL: `/api/v1`

### Versioning Strategy
- URL path versioning (`/api/v1`, `/api/v2`)
- Backward compatibility maintained for at least 6 months
- Deprecation warnings sent in response headers
```
X-API-Deprecated: true
X-API-Sunset: 2025-06-29T00:00:00Z
```

This API design provides a comprehensive, RESTful interface for the limit order exchange with clear semantics, proper error handling, and real-time capabilities through WebSocket events.
