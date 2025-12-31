<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Order book channel - public for all authenticated users
Broadcast::channel('orderbook', function ($user) {
    return true;
});

// Order book symbol-specific channels
Broadcast::channel('orderbook.{symbol}', function ($user) {
    return true;
});

// Private user channel for individual updates
Broadcast::channel('private-user.{id}', function ($user, $id) {
    \Log::info('Private user channel authorization', [
        'user_id' => $user ? $user->id : null,
        'requested_id' => $id,
        'authorized' => $user && (int) $user->id === (int) $id
    ]);
    return $user && (int) $user->id === (int) $id;
});
