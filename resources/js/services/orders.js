import api from './api.js';
import authService from './auth.js';

class OrderService {
    /**
     * Get all orders for the authenticated user with pagination and filters
     */
    async getOrders(params = {}) {
        try {
            // Ensure we have a valid auth token
            if (!authService.isAuthenticated()) {
                throw new Error('Not authenticated');
            }

            const response = await api.get('/orders', { params });
            console.log('Orders API Response:', response.data);
            console.log('Orders API Status:', response.status);

            // Check if response has expected structure
            if (!response.data) {
                throw new Error('Invalid response structure: no data');
            }

            return {
                orders: response.data.orders || [],
                pagination: response.data.pagination || {
                    current_page: 1,
                    last_page: 1,
                    per_page: 10,
                    total: 0,
                    from: 0,
                    to: 0,
                }
            };
        } catch (error) {
            console.error('Orders service error:', error);
            console.error('Error response:', error.response);

            // If it's an auth error, clear the auth state
            if (error.response?.status === 401) {
                await authService.clearAuth();
            }

            throw error;
        }
    }

    /**
     * Create a new order
     */
    async createOrder(orderData) {
        const response = await api.post('/orders', orderData);
        return response.data.order;
    }

    /**
     * Get order book for a symbol
     */
    async getOrderBook(symbol, limit = 20) {
        const response = await api.get('/orderbook', { params: { symbol, limit } });
        return response.data;
    }

    /**
     * Cancel an order
     */
    async cancelOrder(orderId) {
        const response = await api.post(`/orders/${orderId}/cancel`);
        return response.data.order;
    }
}

export default new OrderService();
