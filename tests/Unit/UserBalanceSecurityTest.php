<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBalanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that balance cannot be mass assigned.
     */
    public function test_balance_cannot_be_mass_assigned(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'balance' => 9999.99, // Malicious attempt to set balance
        ];

        $user = User::create($userData);

        // Balance should be the default value (null or 0.00), not the malicious value
        $this->assertNotEquals(9999.99, $user->balance);
        $this->assertEquals(0.00, $user->balance);
    }

    /**
     * Test that balance can be updated through dedicated methods.
     */
    public function test_balance_can_be_updated_through_dedicated_methods(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        // Test successful balance update through dedicated method
        $result = $user->addBalance(50.00);
        $this->assertTrue($result);
        $this->assertEquals(150.00, $user->fresh()->balance);

        // Test successful balance subtraction
        $result = $user->subtractBalance(25.00);
        $this->assertTrue($result);
        $this->assertEquals(125.00, $user->fresh()->balance);

        // Test direct balance update through dedicated method
        $result = $user->updateBalance(200.00);
        $this->assertTrue($result);
        $this->assertEquals(200.00, $user->fresh()->balance);
    }

    /**
     * Test that mass assignment with balance is prevented during creation.
     */
    public function test_mass_assignment_prevention_during_creation(): void
    {
        // This test verifies that even if someone tries to use fill() with balance,
        // it won't work because balance is not in fillable
        $user = new User();
        $user->fill([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'balance' => 9999.99, // This should be ignored
        ]);

        $user->save();

        // Balance should be null/default, not the malicious value
        $this->assertNotEquals(9999.99, $user->balance);
    }

    /**
     * Test that negative balances are prevented.
     */
    public function test_negative_balances_are_prevented(): void
    {
        $user = User::factory()->create(['balance' => 50.00]);

        // Attempt to subtract more than available balance
        $result = $user->subtractBalance(100.00);
        $this->assertFalse($result);
        $this->assertEquals(50.00, $user->fresh()->balance);

        // Attempt to add negative amount
        $result = $user->addBalance(-10.00);
        $this->assertFalse($result);
        $this->assertEquals(50.00, $user->fresh()->balance);

        // Attempt to set negative balance directly
        $result = $user->updateBalance(-10.00);
        $this->assertFalse($result);
        $this->assertEquals(50.00, $user->fresh()->balance);
    }
}
