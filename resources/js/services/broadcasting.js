import Pusher from 'pusher-js';

// Initialize Pusher
const pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }
});

// Subscribe to order book updates
export const subscribeToOrderBook = (symbol, callback) => {
    const channel = pusher.subscribe(`orderbook.${symbol}`);

    channel.bind('orderbook.updated', (data) => {
        console.log('Order book updated:', data);
        callback(data);
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
    const channel = pusher.subscribe(`private-user.${userId}`);

    channel.bind('order.updated', (data) => {
        console.log('User order updated:', data);
        callback(data);
    });

    channel.bind('order.cancelled', (data) => {
        console.log('User order cancelled:', data);
        callback(data);
    });

    channel.bind('order.matched', (data) => {
        console.log('User order matched:', data);
        callback(data);
    });

    channel.bind('balance.updated', (data) => {
        console.log('User balance updated:', data);
        callback(data);
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
