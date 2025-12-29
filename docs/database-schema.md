# Database Schema Design

## Overview
This document outlines the database schema for the Virgosoft Limit Order Exchange, designed for financial data integrity, performance, and race condition prevention.

## Core Tables

### 1. users
Extends Laravel's default users table with balance information.

```sql
CREATE TABLE users (
    -- Default Laravel columns
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Exchange-specific columns
    balance DECIMAL(20, 8) DEFAULT 0.00000000 NOT NULL,
    
    -- Constraints
    CONSTRAINT users_balance_check CHECK (balance >= 0)
);

-- Indexes
CREATE INDEX idx_users_email ON users(email);
```

### 2. assets
Tracks user's cryptocurrency holdings and locked amounts.

```sql
CREATE TABLE assets (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    user_id BIGINT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    amount DECIMAL(20, 8) DEFAULT 0.00000000 NOT NULL,
    locked_amount DECIMAL(20, 8) DEFAULT 0.00000000 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT fk_assets_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Constraints
    CONSTRAINT assets_amount_check CHECK (amount >= 0),
    CONSTRAINT assets_locked_amount_check CHECK (locked_amount >= 0),
    CONSTRAINT assets_symbol_user_unique UNIQUE (user_id, symbol)
);

-- Indexes
CREATE INDEX idx_assets_user_id ON assets(user_id);
CREATE INDEX idx_assets_symbol ON assets(symbol);
CREATE INDEX idx_assets_user_symbol ON assets(user_id, symbol);
```

### 3. orders
Stores all limit orders in the system.

```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    user_id BIGINT NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    side VARCHAR(4) NOT NULL CHECK (side IN ('buy', 'sell')),
    price DECIMAL(20, 8) NOT NULL,
    amount DECIMAL(20, 8) NOT NULL,
    filled_amount DECIMAL(20, 8) DEFAULT 0.00000000 NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'filled', 'cancelled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Constraints
    CONSTRAINT orders_price_check CHECK (price > 0),
    CONSTRAINT orders_amount_check CHECK (amount > 0),
    CONSTRAINT orders_filled_amount_check CHECK (filled_amount >= 0 AND filled_amount <= amount)
);

-- Indexes for performance
CREATE INDEX idx_orders_symbol ON orders(symbol);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_side ON orders(side);
CREATE INDEX idx_orders_symbol_status ON orders(symbol, status);
CREATE INDEX idx_orders_symbol_side_status ON orders(symbol, side, status);
CREATE INDEX idx_orders_price ON orders(price);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- Composite index for order book queries
CREATE INDEX idx_orders_orderbook_buy ON orders(symbol, side, status, price DESC) 
WHERE side = 'buy' AND status = 'open';
CREATE INDEX idx_orders_orderbook_sell ON orders(symbol, side, status, price ASC) 
WHERE side = 'sell' AND status = 'open';
```

### 4. trades
Records all executed trades between orders.

```sql
CREATE TABLE trades (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    buy_order_id BIGINT NOT NULL,
    sell_order_id BIGINT NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    amount DECIMAL(20, 8) NOT NULL,
    commission DECIMAL(20, 8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT fk_trades_buy_order_id FOREIGN KEY (buy_order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    CONSTRAINT fk_trades_sell_order_id FOREIGN KEY (sell_order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    
    -- Constraints
    CONSTRAINT trades_price_check CHECK (price > 0),
    CONSTRAINT trades_amount_check CHECK (amount > 0),
    CONSTRAINT trades_commission_check CHECK (commission >= 0),
    CONSTRAINT trades_different_orders CHECK (buy_order_id != sell_order_id)
);

-- Indexes
CREATE INDEX idx_trades_buy_order_id ON trades(buy_order_id);
CREATE INDEX idx_trades_sell_order_id ON trades(sell_order_id);
CREATE INDEX idx_trades_symbol ON trades(symbol);
CREATE INDEX idx_trades_created_at ON trades(created_at);
CREATE INDEX idx_trades_symbol_created_at ON trades(symbol, created_at);
```

## Database Views

### 1. order_book_view
Provides optimized access to current order book.

```sql
CREATE VIEW order_book_view AS
SELECT 
    o.id,
    o.user_id,
    o.symbol,
    o.side,
    o.price,
    o.amount,
    o.filled_amount,
    o.amount - o.filled_amount as remaining_amount,
    o.created_at,
    u.name as user_name
FROM orders o
JOIN users u ON o.user_id = u.id
WHERE o.status = 'open'
ORDER BY 
    CASE 
        WHEN o.side = 'buy' THEN o.price 
        ELSE NULL 
    END DESC,
    CASE 
        WHEN o.side = 'sell' THEN o.price 
        ELSE NULL 
    END ASC,
    o.created_at ASC;
```

### 2. user_balance_view
Provides comprehensive balance information.

```sql
CREATE VIEW user_balance_view AS
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    u.balance as usd_balance,
    COALESCE(SUM(CASE WHEN a.symbol = 'BTC' THEN a.amount ELSE 0 END), 0) as btc_amount,
    COALESCE(SUM(CASE WHEN a.symbol = 'BTC' THEN a.locked_amount ELSE 0 END), 0) as btc_locked,
    COALESCE(SUM(CASE WHEN a.symbol = 'ETH' THEN a.amount ELSE 0 END), 0) as eth_amount,
    COALESCE(SUM(CASE WHEN a.symbol = 'ETH' THEN a.locked_amount ELSE 0 END), 0) as eth_locked,
    u.created_at,
    u.updated_at
FROM users u
LEFT JOIN assets a ON u.id = a.user_id
GROUP BY u.id, u.name, u.email, u.balance, u.created_at, u.updated_at;
```

## Database Functions

### 1. calculate_commission()
Calculates commission for trades.

```sql
CREATE OR REPLACE FUNCTION calculate_commission(
    p_amount DECIMAL,
    p_price DECIMAL,
    p_rate DECIMAL DEFAULT 0.015
) RETURNS DECIMAL AS $$
BEGIN
    RETURN (p_amount * p_price) * p_rate;
END;
$$ LANGUAGE plpgsql;
```

### 2. update_user_balance()
Atomically updates user balance with locking.

```sql
CREATE OR REPLACE FUNCTION update_user_balance(
    p_user_id BIGINT,
    p_amount_change DECIMAL
) RETURNS BOOLEAN AS $$
DECLARE
    current_balance DECIMAL;
BEGIN
    -- Lock the user row for update
    SELECT balance INTO current_balance 
    FROM users 
    WHERE id = p_user_id 
    FOR UPDATE;
    
    -- Check if sufficient funds for withdrawal
    IF current_balance + p_amount_change < 0 THEN
        RETURN FALSE;
    END IF;
    
    -- Update balance
    UPDATE users 
    SET balance = balance + p_amount_change,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_user_id;
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;
```

### 3. update_asset_balance()
Atomically updates asset balance with locking.

```sql
CREATE OR REPLACE FUNCTION update_asset_balance(
    p_user_id BIGINT,
    p_symbol VARCHAR,
    p_amount_change DECIMAL,
    p_locked_change DECIMAL DEFAULT 0
) RETURNS BOOLEAN AS $$
DECLARE
    current_amount DECIMAL;
    current_locked DECIMAL;
BEGIN
    -- Lock the asset row for update
    SELECT amount, locked_amount INTO current_amount, current_locked 
    FROM assets 
    WHERE user_id = p_user_id AND symbol = p_symbol
    FOR UPDATE;
    
    -- Check if sufficient funds
    IF current_amount + p_amount_change < 0 THEN
        RETURN FALSE;
    END IF;
    
    IF current_locked + p_locked_change < 0 THEN
        RETURN FALSE;
    END IF;
    
    -- Update or insert asset
    INSERT INTO assets (user_id, symbol, amount, locked_amount)
    VALUES (p_user_id, p_symbol, p_amount_change, p_locked_change)
    ON CONFLICT (user_id, symbol) 
    DO UPDATE SET 
        amount = assets.amount + p_amount_change,
        locked_amount = assets.locked_amount + p_locked_change,
        updated_at = CURRENT_TIMESTAMP;
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;
```

## Triggers

### 1. validate_order_before_insert
Ensures order validity before insertion.

```sql
CREATE OR REPLACE FUNCTION validate_order_before_insert() RETURNS TRIGGER AS $$
BEGIN
    -- Validate price and amount
    IF NEW.price <= 0 OR NEW.amount <= 0 THEN
        RAISE EXCEPTION 'Price and amount must be positive';
    END IF;
    
    -- Validate side
    IF NEW.side NOT IN ('buy', 'sell') THEN
        RAISE EXCEPTION 'Invalid order side';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_order_before_insert
    BEFORE INSERT ON orders
    FOR EACH ROW
    EXECUTE FUNCTION validate_order_before_insert();
```

### 2. update_order_timestamp
Automatically updates timestamp on order changes.

```sql
CREATE OR REPLACE FUNCTION update_order_timestamp() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_order_timestamp
    BEFORE UPDATE ON orders
    FOR EACH ROW
    EXECUTE FUNCTION update_order_timestamp();
```

## Transaction Patterns

### 1. Order Creation Transaction
```sql
BEGIN;
-- Lock user balance
SELECT balance FROM users WHERE id = :user_id FOR UPDATE;

-- Create order
INSERT INTO orders (user_id, symbol, side, price, amount) VALUES (...);

-- Update balance or lock assets
CALL update_user_balance(:user_id, -:total_cost);
-- OR
CALL update_asset_balance(:user_id, :symbol, -:amount, :amount);

COMMIT;
```

### 2. Order Matching Transaction
```sql
BEGIN;
-- Lock both orders
SELECT * FROM orders WHERE id = :buy_order_id FOR UPDATE;
SELECT * FROM orders WHERE id = :sell_order_id FOR UPDATE;

-- Lock user balances
SELECT balance FROM users WHERE id = :buyer_id FOR UPDATE;
SELECT balance FROM users WHERE id = :seller_id FOR UPDATE;

-- Lock assets
SELECT * FROM assets WHERE user_id = :seller_id AND symbol = :symbol FOR UPDATE;

-- Execute trade
INSERT INTO trades (...) VALUES (...);

-- Update balances
CALL update_user_balance(:buyer_id, -:trade_amount - :commission);
CALL update_user_balance(:seller_id, :trade_amount - :commission);

-- Update assets
CALL update_asset_balance(:buyer_id, :symbol, :trade_amount, 0);
CALL update_asset_balance(:seller_id, :symbol, -:trade_amount, 0);

-- Update order statuses
UPDATE orders SET filled_amount = filled_amount + :trade_amount WHERE id = :buy_order_id;
UPDATE orders SET filled_amount = filled_amount + :trade_amount WHERE id = :sell_order_id;

COMMIT;
```

## Performance Considerations

### 1. Indexing Strategy
- Primary indexes on all foreign keys
- Composite indexes for common query patterns
- Partial indexes for order book queries
- Time-based indexes for historical queries

### 2. Partitioning
For high-volume scenarios, consider partitioning:
- `orders` table by symbol or date
- `trades` table by date
- `assets` table by user_id for very large user bases

### 3. Connection Pooling
Configure PostgreSQL connection pooling:
```ini
max_connections = 100
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB
```

## Backup Strategy

### 1. Regular Backups
```bash
# Daily full backup
pg_dump -h localhost -U postgres virgosoft_exchange > backup_$(date +%Y%m%d).sql

# Hourly transaction log backup
pg_receivewal -h localhost -U postgres -D /var/lib/postgresql/wal_archive/
```

### 2. Point-in-Time Recovery
Enable WAL archiving in postgresql.conf:
```ini
wal_level = replica
archive_mode = on
archive_command = 'cp %p /var/lib/postgresql/archive/%f'
```

## Monitoring

### 1. Key Metrics
- Order creation rate
- Trade execution rate
- Balance update latency
- Database connection pool usage
- Query performance metrics

### 2. Alerts
- Long-running transactions (> 5 seconds)
- Failed balance updates
- Database connection exhaustion
- Unusual order cancellation rates

This schema design ensures data integrity, supports high-performance trading operations, and provides the foundation for a scalable limit order exchange.
