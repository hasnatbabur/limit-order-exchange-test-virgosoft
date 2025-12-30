<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\PasswordResetTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link to the user.
     *
     * @param PasswordResetTokenRequest $request
     * @return JsonResponse
     */
    public function sendResetLink(PasswordResetTokenRequest $request): JsonResponse
    {
        try {
            $email = $request->email;

            // Check if user exists
            $user = User::where('email', $email)->first();
            if (!$user) {
                // Return success message to prevent email enumeration
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent successfully.',
                ], 200);
            }

            // Generate password reset token
            $token = Str::random(60);

            // Store token in password_resets table
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // In a real application, you would send an email here
            // For demo purposes, we'll return the token in the response
            // In production, remove the token from response and implement email sending

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent successfully.',
                'data' => [
                    'email' => $email,
                    'reset_token' => $token, // Remove this in production
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset the user's password.
     *
     * @param PasswordResetRequest $request
     * @return JsonResponse
     */
    public function reset(PasswordResetRequest $request): JsonResponse
    {
        try {
            $email = $request->email;
            $token = $request->token;
            $password = $request->password;

            // Find the user first
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Find the password reset record
            $resetRecord = DB::table('password_resets')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password reset token.',
                ], 422);
            }

            // Verify the token
            if (!Hash::check($token, $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password reset token.',
                ], 422);
            }

            // Check if token has expired (60 minutes)
            if (now()->subMinutes(60)->gt($resetRecord->created_at)) {
                // Delete expired token
                DB::table('password_resets')
                    ->where('email', $email)
                    ->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Password reset token has expired.',
                ], 422);
            }

            // Update password within a transaction
            DB::transaction(function () use ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                // Delete all user tokens to force re-login
                $user->tokens()->delete();
            });

            // Delete the password reset record
            DB::table('password_resets')
                ->where('email', $email)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. Please login with your new password.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
