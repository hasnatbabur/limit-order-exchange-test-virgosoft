import api from './api.js';

/**
 * Asset service for managing cryptocurrency assets
 */
export default {
    /**
     * Get all assets for the authenticated user
     */
    async getAssets() {
        try {
            console.log('Fetching assets...');
            const response = await api.get('/assets');
            console.log('Assets response:', response);
            console.log('Response data:', response.data);
            return response.data.data;
        } catch (error) {
            console.error('Failed to fetch assets:', error);
            console.error('Error response:', error.response);
            throw error;
        }
    },

    /**
     * Get user's complete balance information
     */
    async getBalance() {
        try {
            const response = await api.get('/assets/balance');
            return response.data.data;
        } catch (error) {
            console.error('Failed to fetch balance:', error);
            throw error;
        }
    },

    /**
     * Get user's portfolio summary
     */
    async getPortfolio() {
        try {
            const response = await api.get('/assets/portfolio');
            return response.data.data;
        } catch (error) {
            console.error('Failed to fetch portfolio:', error);
            throw error;
        }
    },

    /**
     * Add test assets for development (only available in local environment)
     * @param {string} symbol - Asset symbol (BTC, ETH)
     * @param {number} amount - Amount to add
     */
    async addTestAssets(symbol, amount) {
        const response = await api.post('/assets/test-add', {
            symbol,
            amount
        });
        return response.data;
    }
};
