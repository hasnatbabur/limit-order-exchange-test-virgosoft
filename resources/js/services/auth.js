import axios from 'axios';

const API_BASE_URL = '/api';

class AuthService {
    constructor() {
        this.token = localStorage.getItem('auth_token');
        this.csrfToken = null;
        if (this.token) {
            this.setAuthToken(this.token);
        }
    }

    async setAuthToken(token) {
        this.token = token;
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            localStorage.setItem('auth_token', token);
            // Get CSRF token for stateful requests
            await this.getCsrfToken();
        } else {
            delete axios.defaults.headers.common['Authorization'];
            delete axios.defaults.headers.common['X-XSRF-TOKEN'];
            localStorage.removeItem('auth_token');
            this.csrfToken = null;
        }
    }

    async getCsrfToken() {
        try {
            await axios.get('/sanctum/csrf-cookie');
            // The CSRF token is automatically set in cookies by Laravel
        } catch (error) {
            console.warn('Failed to get CSRF token:', error);
        }
    }

    async login(credentials) {
        try {
            // Get CSRF token first for stateful authentication
            await this.getCsrfToken();

            const response = await axios.post(`${API_BASE_URL}/auth/login`, credentials);

            if (response.data.success) {
                const { access_token } = response.data.data;
                await this.setAuthToken(access_token);
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
            // Get CSRF token first for stateful authentication
            await this.getCsrfToken();

            const response = await axios.post(`${API_BASE_URL}/auth/register`, userData);

            if (response.data.success) {
                const { access_token } = response.data.data;
                await this.setAuthToken(access_token);
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
                return response.data.data.user;
            }

            throw new Error('Failed to fetch user data');
        } catch (error) {
            this.clearAuth();
            throw this.handleError(error);
        }
    }

    async clearAuth() {
        await this.setAuthToken(null);
    }

    handleError(error) {
        if (error.response) {
            const { status, data } = error.response;

            if (status === 401) {
                this.clearAuth();
                return new Error('Session expired. Please login again.');
            }

            if (status === 419) {
                this.clearAuth();
                return new Error('CSRF token mismatch. Please login again.');
            }

            if (status === 422 && data.errors) {
                const validationErrors = Object.values(data.errors).flat();
                return new Error(validationErrors.join(', '));
            }

            if (status === 429) {
                return new Error('Too many requests. Please try again later.');
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
