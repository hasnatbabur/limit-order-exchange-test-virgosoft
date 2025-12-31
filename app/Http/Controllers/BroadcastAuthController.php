<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BroadcastAuthController extends Controller
{
    /**
     * Authenticate the request for channel access.
     */
    public function authenticate(Request $request): JsonResponse
    {
        // Log the authentication attempt for debugging
        Log::info('Broadcast authentication attempt', [
            'channel_name' => $request->channel_name,
            'socket_id' => $request->socket_id,
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
        ]);

        // For public channels (orderbook), always return true
        if (str_starts_with($request->channel_name, 'orderbook')) {
            return response()->json(['auth' => '']);
        }

        // For private channels, check if user is authenticated
        if (str_starts_with($request->channel_name, 'private-')) {
            if (!Auth::check()) {
                Log::warning('Broadcast auth failed: User not authenticated', [
                    'channel' => $request->channel_name,
                ]);
                return response()->json(['error' => 'Unauthenticated'], 403);
            }

            // Extract user ID from private-user.{id} channel
            $channelParts = explode('.', $request->channel_name);
            if (count($channelParts) >= 2 && $channelParts[0] === 'private-user') {
                $requestedUserId = $channelParts[1];
                $currentUserId = Auth::id();

                Log::info('Private user channel auth check', [
                    'requested_user_id' => $requestedUserId,
                    'current_user_id' => $currentUserId,
                    'authorized' => $currentUserId == $requestedUserId,
                ]);

                if ($currentUserId != $requestedUserId) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }

                // Return proper auth response for Pusher
                return response()->json(['auth' => '']);
            }
        }

        // Default deny
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
