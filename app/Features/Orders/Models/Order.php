<?php

namespace App\Features\Orders\Models;

use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'filled_amount',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'filled_amount' => 'decimal:8',
        'side' => OrderSide::class,
        'status' => OrderStatus::class,
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the remaining amount to be filled.
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->filled_amount;
    }

    /**
     * Get the total value of the order (price * amount).
     */
    public function getTotalValueAttribute(): float
    {
        return $this->price * $this->amount;
    }

    /**
     * Get the filled value of the order (price * filled_amount).
     */
    public function getFilledValueAttribute(): float
    {
        return $this->price * $this->filled_amount;
    }

    /**
     * Check if the order is completely filled.
     */
    public function isCompletelyFilled(): bool
    {
        return $this->filled_amount >= $this->amount;
    }

    /**
     * Check if the order is partially filled.
     */
    public function isPartiallyFilled(): bool
    {
        return $this->filled_amount > 0 && $this->filled_amount < $this->amount;
    }

    /**
     * Scope a query to only include open orders.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', OrderStatus::OPEN->value);
    }

    /**
     * Scope a query to only include buy orders.
     */
    public function scopeBuy($query)
    {
        return $query->where('side', OrderSide::BUY->value);
    }

    /**
     * Scope a query to only include sell orders.
     */
    public function scopeSell($query)
    {
        return $query->where('side', OrderSide::SELL->value);
    }

    /**
     * Scope a query to only include orders for a specific symbol.
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope a query to order by price (ascending for sell orders, descending for buy orders).
     */
    public function scopeOrderByPrice($query)
    {
        return $query->orderByRaw("CASE WHEN side = 'buy' THEN price END DESC")
                    ->orderByRaw("CASE WHEN side = 'sell' THEN price END ASC");
    }
}
