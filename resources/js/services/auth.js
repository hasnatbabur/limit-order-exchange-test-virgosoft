import axios from 'axios';

const API_BASE_URL = '/api';

class AuthService {
    constructor() {
        this.token = localStorage.getItem('auth_token');
        if (this.token) {
            this.setAuthToken(this.token);
        }
    }

    setAuthToken(token) {
        this.token = token;
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            localStorage.setItem('auth_token', token);
        } else {
            delete axios.defaults.headers.common['Authorization'];
            localStorage.removeItem('auth_token');
        }
    }

    async login(credentials) {
        try {
            const response = await axios.post(`${API_BASE_URL}/auth/login`, credentials);

            if (response.data.success) {
                const { access_token } = response.data.data;
                this.setAuthToken(access_token);
                return response.data;
            }

            throw new Error(response.data.message || 'Login failed');
        } catch (error) {
            this.clearAuth();
            throw this.handleError(error);
        }
    }

    async register(userData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/auth/register`, userData);

            if (response.data.success) {
                return response.data;
            }

            throw new Error(response.data.message || 'Registration failed');
        } catch (error) {
            throw this.handleError(error);
        }
    }

    async logout() {
        try {
            if (this.token) {
                await axios.post(`${API_BASE_URL}/auth/logout`);
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.clearAuth();
        }
    }

    async fetchUser() {
        try {
            const response = await axios.get(`${API_BASE_URL}/auth/me`);

            if (response.data.success) {
                return response.data.data;
            }

            throw new Error('Failed to fetch user data');
        } catch (error) {
            this.clearAuth();
            throw this.handleError(error);
        }
    }

    clearAuth() {
        this.setAuthToken(null);
    }

    handleError(error) {
        if (error.response) {
            const { status, data } = error.response;

            if (status === 401) {
                this.clearAuth();
                return new Error('Session expired. Please login again.');
            }

            if (status === 422 && data.errors) {
                const validationErrors = Object.values(data.errors).flat();
                return new Error(validationErrors.join(', '));
            }

            return new Error(data.message || 'Request failed');
        }

        if (error.request) {
            return new Error('Network error. Please check your connection.');
        }

        return error;
    }

    isAuthenticated() {
        return !!this.token;
    }
}

export default new AuthService();
