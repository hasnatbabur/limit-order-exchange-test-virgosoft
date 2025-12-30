<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * SPA Authentication Tests
 *
 * Tests for SPA-specific authentication features including CORS, CSRF protection,
 * token abilities, and stateful authentication.
 */
class SPAAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function csrf_cookie_endpoint_sets_cookie(): void
    {
        $response = $this->getJson('/api/sanctum/csrf-cookie');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'CSRF cookie set'
            ]);
    }

    #[Test]
    public function login_with_valid_credentials_returns_token_with_abilities(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'balance',
                        'created_at',
                        'updated_at',
                    ],
                    'access_token',
                    'token_type',
                ],
            ]);

        // Verify token was created with abilities
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth-token',
            'abilities' => json_encode(['*']),
        ]);
    }

    #[Test]
    public function registration_creates_user_with_token_and_abilities(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'balance',
                        'created_at',
                        'updated_at',
                    ],
                    'access_token',
                    'token_type',
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Verify token was created with abilities
        $user = User::where('email', 'test@example.com')->first();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth-token',
            'abilities' => json_encode(['*']),
        ]);
    }

    #[Test]
    public function logout_revokes_all_user_tokens(): void
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $token1 = $user->createToken('token1');
        $token2 = $user->createToken('token2');
        $token3 = $user->createToken('token3');

        $this->assertEquals(3, $user->tokens()->count());

        // Logout with one token should revoke all
        $response = $this->actingAs($user)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        // All tokens should be revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function protected_routes_require_authentication(): void
    {
        // Test without token
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);

        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    #[Test]
    public function protected_routes_work_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token', ['*'])->plainTextToken;

        // Test user profile endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

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
    public function cors_headers_are_present_for_api_requests(): void
    {
        $response = $this->optionsJson('/api/auth/login');

        // CORS headers should be present
        $response->assertHeader('Access-Control-Allow-Origin');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    #[Test]
    public function sanctum_configuration_is_loaded_correctly(): void
    {
        // Verify Sanctum configuration exists
        $this->assertIsArray(config('sanctum'));
        $this->assertArrayHasKey('stateful', config('sanctum'));
        $this->assertArrayHasKey('guard', config('sanctum'));
        $this->assertArrayHasKey('expiration', config('sanctum'));
        $this->assertArrayHasKey('middleware', config('sanctum'));

        // Verify stateful domains include localhost variants
        $statefulDomains = config('sanctum.stateful');
        $this->assertContains('localhost', $statefulDomains);
        $this->assertContains('localhost:3000', $statefulDomains);
        $this->assertContains('localhost:5173', $statefulDomains);
        $this->assertContains('127.0.0.1', $statefulDomains);
        $this->assertContains('127.0.0.1:8000', $statefulDomains);
        $this->assertContains('127.0.0.1:5173', $statefulDomains);
    }

    #[Test]
    public function token_expiration_is_configured(): void
    {
        $expiration = config('sanctum.expiration');
        // In Laravel 12 Sanctum, expiration can be null (no expiration) or integer
        $this->assertTrue($expiration === null || is_int($expiration));
    }

    #[Test]
    public function authentication_error_responses_are_consistent(): void
    {
        // Test invalid credentials
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        // Test validation errors
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    #[Test]
    public function user_balance_is_included_in_auth_responses(): void
    {
        $user = User::factory()->create([
            'balance' => 1000.50,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.balance', '1000.50');
    }
}
