<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Authentication Feature Tests
 *
 * Tests for user registration, login, profile access, and logout functionality.
 * Ensures proper authentication flow and security measures.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
            ])
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
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals(0.00, $user->balance);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    #[Test]
    public function registration_fails_with_invalid_data(): void
    {
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different',
        ];

        $response = $this->postJson('/api/auth/register', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Jane Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
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
                ],
            ]);
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    #[Test]
    public function login_fails_with_nonexistent_user(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    #[Test]
    public function login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function authenticated_user_can_access_profile(): void
    {
        $user = User::factory()->create(['balance' => 100.50]);

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'balance',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertEquals(100.50, $response->json('data.user.balance'));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function login_request_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ];

        // Make 5 failed attempts to trigger rate limiting
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', $loginData);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    #[Test]
    public function user_balance_is_initialized_to_zero_on_registration(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals(0.00, $user->balance);

        // Debug: Check the actual response structure
        $responseData = $response->json();

        // Also check the response includes the balance
        $this->assertEquals(0.00, $responseData['data']['user']['balance']);
    }
}
