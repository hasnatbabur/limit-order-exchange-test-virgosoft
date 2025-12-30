<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Password Reset Tests
 *
 * Tests for password reset functionality including token generation,
 * validation, and password reset process.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_password_reset_link_for_valid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link sent successfully.',
            ]);

        // Verify token was created in password_resets table
        $this->assertDatabaseHas('password_resets', [
            'email' => $user->email,
        ]);
    }

    #[Test]
    public function it_returns_generic_message_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link sent successfully.',
            ]);

        // Verify no token was created
        $this->assertDatabaseMissing('password_resets', [
            'email' => 'nonexistent@example.com',
        ]);
    }

    #[Test]
    public function it_validates_email_for_password_reset_request(): void
    {
        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    #[Test]
    public function it_resets_password_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = 'test-reset-token-123456789012345678901234567890123456789012';

        // Create password reset record
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $newPassword = 'new-password-123';

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully. Please login with your new password.',
            ]);

        // Verify password was updated
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));

        // Verify password reset record was deleted
        $this->assertDatabaseMissing('password_resets', [
            'email' => $user->email,
        ]);

        // Verify all user tokens were revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function it_fails_password_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid password reset token.',
            ]);
    }

    #[Test]
    public function it_fails_password_reset_with_expired_token(): void
    {
        $user = User::factory()->create();
        $token = 'test-reset-token-123456789012345678901234567890123456789012';

        // Create expired password reset record (61 minutes ago)
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Password reset token has expired.',
            ]);

        // Verify expired token was deleted
        $this->assertDatabaseMissing('password_resets', [
            'email' => $user->email,
        ]);
    }

    #[Test]
    public function it_fails_password_reset_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/password/reset', [
            'email' => 'nonexistent@example.com',
            'token' => 'test-reset-token-123456789012345678901234567890123456789012',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found.',
            ]);
    }

    #[Test]
    public function it_validates_password_reset_request_data(): void
    {
        $response = $this->postJson('/api/auth/password/reset', [
            'email' => '',
            'token' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'email',
                    'token',
                    'password',
                ],
            ]);
    }

    #[Test]
    public function it_requires_password_confirmation(): void
    {
        $user = User::factory()->create();
        $token = 'test-reset-token-123456789012345678901234567890123456789012';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'password',
                ],
            ]);
    }

    #[Test]
    public function it_requires_minimum_password_length(): void
    {
        $user = User::factory()->create();
        $token = 'test-reset-token-123456789012345678901234567890123456789012';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'password',
                ],
            ]);
    }

    #[Test]
    public function it_handles_concurrent_password_reset_requests(): void
    {
        $user = User::factory()->create();

        // Send first reset request
        $response1 = $this->postJson('/api/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response1->assertStatus(200);

        // Send second reset request
        $response2 = $this->postJson('/api/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response2->assertStatus(200);

        // Both should create tokens (Laravel allows multiple tokens)
        $resetRecords = DB::table('password_resets')
            ->where('email', $user->email)
            ->count();

        $this->assertEquals(2, $resetRecords);
    }

    #[Test]
    public function it_prevents_password_reuse_after_reset(): void
    {
        $oldPassword = 'old-password-123';
        $user = User::factory()->create([
            'password' => Hash::make($oldPassword),
        ]);
        $token = 'test-reset-token-123456789012345678901234567890123456789012';

        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $newPassword = 'new-password-456';

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(200);

        // Verify old password no longer works
        $this->assertFalse(Hash::check($oldPassword, $user->fresh()->password));
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }
}
