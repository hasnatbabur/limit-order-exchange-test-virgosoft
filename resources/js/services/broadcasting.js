import Pusher from 'pusher-js';

// Initialize Pusher
console.log('Initializing Pusher with config:', {
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    authEndpoint: '/broadcasting/auth'
});

const pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }
});

// Add connection state logging
pusher.connection.bind('connected', () => {
    console.log('Pusher connected successfully');
});

pusher.connection.bind('error', (error) => {
    console.error('Pusher connection error:', error);
});

pusher.connection.bind('disconnected', () => {
    console.log('Pusher disconnected');
});

// Subscribe to order book updates
export const subscribeToOrderBook = (symbol, callback) => {
    console.log('Subscribing to orderbook channel:', `orderbook.${symbol}`);
    const channel = pusher.subscribe(`orderbook.${symbol}`);

    channel.bind('orderbook.updated', (data) => {
        console.log('Order book updated event received:', data);
        callback(data);
    });

    channel.bind('pusher:subscription_succeeded', () => {
        console.log('Successfully subscribed to orderbook channel:', `orderbook.${symbol}`);
    });

    channel.bind('pusher:subscription_error', (error) => {
        console.error('Failed to subscribe to orderbook channel:', error);
    });

    return () => {
        channel.unbind_all();
        pusher.unsubscribe(`orderbook.${symbol}`);
    };
};

// Unsubscribe from order book updates
export const unsubscribeFromOrderBook = (symbol) => {
    pusher.unsubscribe(`orderbook.${symbol}`);
};

// Subscribe to user-specific updates
export const subscribeToUserUpdates = (userId, callback) => {
    console.log('Subscribing to private user channel:', `private-user.${userId}`);
    const channel = pusher.subscribe(`private-user.${userId}`);

    channel.bind('order.updated', (data) => {
        console.log('User order updated event received:', data);
        callback(data);
    });

    channel.bind('order.cancelled', (data) => {
        console.log('User order cancelled event received:', data);
        callback(data);
    });

    channel.bind('order.matched', (data) => {
        console.log('User order matched event received:', data);
        callback(data);
    });

    channel.bind('balance.updated', (data) => {
        console.log('User balance updated event received:', data);
        callback(data);
    });

    channel.bind('pusher:subscription_succeeded', () => {
        console.log('Successfully subscribed to private user channel:', `private-user.${userId}`);
    });

    channel.bind('pusher:subscription_error', (error) => {
        console.error('Failed to subscribe to private user channel:', error);
    });

    return () => {
        channel.unbind_all();
        pusher.unsubscribe(`private-user.${userId}`);
    };
};

// Unsubscribe from user-specific updates
export const unsubscribeFromUserUpdates = (userId) => {
    pusher.unsubscribe(`private-user.${userId}`);
};

export default pusher;
