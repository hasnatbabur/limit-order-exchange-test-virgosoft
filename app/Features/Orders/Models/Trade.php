<?php

namespace App\Features\Orders\Models;

use App\Features\Orders\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'symbol',
        'price',
        'amount',
        'commission',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'commission' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the buy order that belongs to the trade.
     */
    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    /**
     * Get the sell order that belongs to the trade.
     */
    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

    /**
     * Get the buyer (user who placed the buy order).
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the seller (user who placed the sell order).
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Calculate the total value of the trade (price * amount).
     */
    public function getTotalValueAttribute(): float
    {
        return $this->price * $this->amount;
    }

    /**
     * Get the net value received by the seller (total - commission).
     */
    public function getSellerNetValueAttribute(): float
    {
        return $this->getTotalValueAttribute() - $this->commission;
    }

    /**
     * Scope a query to only include trades for a specific symbol.
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope a query to only include trades for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereHas('buyOrder', function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId);
            })->orWhereHas('sellOrder', function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId);
            });
        });
    }

    /**
     * Scope a query to only include recent trades.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
