<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sanctum Token Tests
 *
 * Tests for Sanctum token creation, usage, and revocation.
 * Ensures proper token management for API authentication.
 */
class SanctumTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_create_multiple_tokens(): void
    {
        $user = User::factory()->create();

        // Create first token
        $token1 = $user->createToken('token1');
        $this->assertNotNull($token1);
        $this->assertNotNull($token1->plainTextToken);

        // Create second token
        $token2 = $user->createToken('token2');
        $this->assertNotNull($token2);
        $this->assertNotNull($token2->plainTextToken);

        // Verify user has 2 tokens
        $this->assertEquals(2, $user->tokens()->count());
    }

    #[Test]
    public function user_can_revoke_specific_token(): void
    {
        $user = User::factory()->create();

        // Create two tokens
        $token1 = $user->createToken('token1');
        $token2 = $user->createToken('token2');

        $this->assertEquals(2, $user->tokens()->count());

        // Revoke first token
        $token1->accessToken->delete();

        // Verify only one token remains
        $this->assertEquals(1, $user->tokens()->count());
        $this->assertTrue($user->tokens()->first()->name === 'token2');
    }

    #[Test]
    public function user_can_revoke_all_tokens(): void
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $user->createToken('token1');
        $user->createToken('token2');
        $user->createToken('token3');

        $this->assertEquals(3, $user->tokens()->count());

        // Revoke all tokens
        $user->tokens()->delete();

        // Verify all tokens are revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function token_can_be_used_for_authentication(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Make authenticated request with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'balance' => '0.00',
                    ]
                ]
            ]);
    }

    #[Test]
    public function token_can_be_used_with_actingas(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Use actingAs with token
        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'balance' => $user->balance,
                    ]
                ]
            ]);
    }

    #[Test]
    public function invalid_token_cannot_access_protected_routes(): void
    {
        // Make request with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function request_without_token_cannot_access_protected_routes(): void
    {
        // Make request without token
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function token_has_correct_abilities(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['server:update']);

        $this->assertEquals(['server:update'], $token->accessToken->abilities);
    }

    #[Test]
    public function token_expiration_works_correctly(): void
    {
        $user = User::factory()->create();

        // Create token that expires in 1 minute
        $expiresAt = now()->addMinute();
        $token = $user->createToken('test-token', ['*'], $expiresAt);

        // Compare with tolerance for microseconds
        $this->assertEqualsWithDelta($expiresAt, $token->accessToken->expires_at, 1);
    }

    #[Test]
    public function token_name_is_stored_correctly(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('my-custom-token');

        $this->assertEquals('my-custom-token', $token->accessToken->name);
    }

    #[Test]
    public function sanctum_configuration_is_loaded(): void
    {
        // Verify Sanctum configuration exists
        $this->assertIsArray(config('sanctum'));
        $this->assertArrayHasKey('stateful', config('sanctum'));
        $this->assertArrayHasKey('guard', config('sanctum'));
        $this->assertArrayHasKey('expiration', config('sanctum'));
        $this->assertArrayHasKey('middleware', config('sanctum'));
    }

    #[Test]
    public function sanctum_guard_is_configured(): void
    {
        // Verify sanctum guard exists in auth configuration
        $guards = config('auth.guards');
        $this->assertArrayHasKey('sanctum', $guards);

        $sanctumGuard = $guards['sanctum'];
        $this->assertEquals('sanctum', $sanctumGuard['driver']);
        $this->assertEquals('users', $sanctumGuard['provider']);
        $this->assertFalse($sanctumGuard['hash']);
    }

    #[Test]
    public function user_model_has_api_tokens_trait(): void
    {
        $user = User::factory()->create();

        // Verify User model uses HasApiTokens trait
        $this->assertTrue(method_exists($user, 'createToken'));
        $this->assertTrue(method_exists($user, 'tokens'));
        $this->assertTrue(method_exists($user, 'currentAccessToken'));
    }
}
