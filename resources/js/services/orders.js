import api from './api.js';

class OrderService {
    /**
     * Get all orders for the authenticated user
     */
    async getOrders() {
        const response = await api.get('/orders');
        return response.data.orders;
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
