import api from './api.js';

/**
 * Get user's trade history
 */
export const getUserTrades = async (symbol = null, limit = 50) => {
    const params = new URLSearchParams();
    if (symbol) params.append('symbol', symbol);
    params.append('limit', limit);

    const response = await api.get(`/trades?${params}`);
    return response.data.data;
};

/**
 * Get recent trades for a symbol (public)
 */
export const getRecentTrades = async (symbol, limit = 20) => {
    const params = new URLSearchParams();
    params.append('symbol', symbol);
    params.append('limit', limit);

    const response = await api.get(`/trades/recent?${params}`);
    return response.data.data;
};

export default {
    getUserTrades,
    getRecentTrades,
};
