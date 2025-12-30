<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBalanceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that balance is hidden by default in JSON serialization.
     */
    public function test_balance_is_hidden_by_default(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $userArray = $user->toArray();

        // Balance should not be in the default array representation
        $this->assertArrayNotHasKey('balance', $userArray);
    }

    /**
     * Test that balance is explicitly included in API responses with balance.
     */
    public function test_balance_included_in_api_with_balance(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $userArray = $user->toApiWithBalance();

        // Balance should be included when explicitly requested
        $this->assertArrayHasKey('balance', $userArray);
        $this->assertEquals(100.00, $userArray['balance']);
    }

    /**
     * Test that balance is excluded in API responses without balance.
     */
    public function test_balance_excluded_in_api_without_balance(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $userArray = $user->toApiWithoutBalance();

        // Balance should not be included when explicitly excluded
        $this->assertArrayNotHasKey('balance', $userArray);
    }

    /**
     * Test that API with balance includes all expected fields.
     */
    public function test_api_with_balance_includes_all_fields(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $userArray = $user->toApiWithBalance();

        // Check all expected fields are present
        $expectedFields = ['id', 'name', 'email', 'balance', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $userArray);
        }
    }

    /**
     * Test that API without balance includes all expected fields except balance.
     */
    public function test_api_without_balance_includes_expected_fields(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $userArray = $user->toApiWithoutBalance();

        // Check expected fields are present
        $expectedFields = ['id', 'name', 'email', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $userArray);
        }

        // Balance should not be present
        $this->assertArrayNotHasKey('balance', $userArray);
    }
}
