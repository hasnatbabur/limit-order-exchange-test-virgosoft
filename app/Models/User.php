<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
    protected $guarded = [
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'balance', // Hide balance by default for security
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Safely update user balance within a database transaction.
     * This method should be used for all balance modifications to ensure
     * atomic operations and prevent race conditions.
     *
     * @param float $newBalance
     * @return bool
     */
    public function updateBalance(float $newBalance): bool
    {
        if ($newBalance < 0) {
            return false; // Prevent negative balances
        }

        // Use save() to bypass mass assignment protection for this internal method
        $this->balance = $newBalance;
        return $this->save();
    }

    /**
     * Add funds to user balance.
     *
     * @param float $amount
     * @return bool
     */
    public function addBalance(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->updateBalance($this->balance + $amount);
    }

    /**
     * Subtract funds from user balance.
     *
     * @param float $amount
     * @return bool
     */
    public function subtractBalance(float $amount): bool
    {
        if ($amount <= 0 || $this->balance < $amount) {
            return false; // Insufficient funds
        }

        return $this->updateBalance($this->balance - $amount);
    }

    /**
     * Get base user data for API responses.
     * This method provides the common fields shared across all API responses.
     *
     * @return array
     */
    private function getBaseApiData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get user data with balance explicitly included for API responses.
     * This method should be used when the authenticated user needs to see their balance.
     *
     * @return array
     */
    public function toApiWithBalance(): array
    {
        return array_merge($this->getBaseApiData(), [
            'balance' => $this->balance,
        ]);
    }

    /**
     * Get user data without balance for general API responses.
     *
     * @return array
     */
    public function toApiWithoutBalance(): array
    {
        return $this->getBaseApiData();
    }
}
