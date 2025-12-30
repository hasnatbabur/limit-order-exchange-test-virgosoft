<?php

namespace App\Features\Balance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'locked_amount' => 'decimal:8',
        ];
    }

    /**
     * Get the user that owns the asset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the available amount (total - locked).
     *
     * @return float
     */
    public function getAvailableAmountAttribute(): float
    {
        return $this->amount - $this->locked_amount;
    }

    /**
     * Check if the asset has sufficient available amount.
     *
     * @param float $requiredAmount
     * @return bool
     */
    public function hasAvailableAmount(float $requiredAmount): bool
    {
        return $this->available_amount >= $requiredAmount;
    }

    /**
     * Safely update asset amounts within a database transaction.
     * This method should be used for all asset modifications to ensure
     * atomic operations and prevent race conditions.
     *
     * @param float $newAmount
     * @param float $newLockedAmount
     * @return bool
     */
    public function updateAmounts(float $newAmount, float $newLockedAmount): bool
    {
        if ($newAmount < 0 || $newLockedAmount < 0 || $newLockedAmount > $newAmount) {
            return false; // Prevent invalid states
        }

        $this->amount = $newAmount;
        $this->locked_amount = $newLockedAmount;
        return $this->save();
    }

    /**
     * Add funds to the asset amount.
     *
     * @param float $amount
     * @return bool
     */
    public function addAmount(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->updateAmounts($this->amount + $amount, $this->locked_amount);
    }

    /**
     * Subtract funds from the asset amount.
     *
     * @param float $amount
     * @return bool
     */
    public function subtractAmount(float $amount): bool
    {
        if ($amount <= 0 || $this->amount < $amount) {
            return false; // Insufficient funds
        }

        return $this->updateAmounts($this->amount - $amount, $this->locked_amount);
    }

    /**
     * Lock funds for sell orders.
     *
     * @param float $amount
     * @return bool
     */
    public function lockAmount(float $amount): bool
    {
        if ($amount <= 0 || !$this->hasAvailableAmount($amount)) {
            return false; // Insufficient available funds
        }

        return $this->updateAmounts($this->amount, $this->locked_amount + $amount);
    }

    /**
     * Unlock funds from sell orders.
     *
     * @param float $amount
     * @return bool
     */
    public function unlockAmount(float $amount): bool
    {
        if ($amount <= 0 || $this->locked_amount < $amount) {
            return false; // Invalid unlock amount
        }

        return $this->updateAmounts($this->amount, $this->locked_amount - $amount);
    }

    /**
     * Convert locked amount to available amount (for order fulfillment).
     *
     * @param float $amount
     * @return bool
     */
    public function convertLockedToAvailable(float $amount): bool
    {
        if ($amount <= 0 || $this->locked_amount < $amount) {
            return false; // Invalid conversion amount
        }

        return $this->updateAmounts(
            $this->amount - $amount,
            $this->locked_amount - $amount
        );
    }

    /**
     * Get asset data for API responses.
     *
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'amount' => $this->amount,
            'locked_amount' => $this->locked_amount,
            'available_amount' => $this->available_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Scope to get assets for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get assets by symbol.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $symbol
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope to get assets with available balance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAvailableBalance($query)
    {
        return $query->whereRaw('amount > locked_amount');
    }
}
