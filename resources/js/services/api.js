import axios from 'axios';

// Create axios instance with base configuration
const api = axios.create({
    baseURL: '/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add request interceptor to include auth token and CSRF
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            config.headers['X-XSRF-TOKEN'] = csrfToken;
        }

        console.log('API Request:', config.method?.toUpperCase(), config.url, config.params);
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add request interceptor to include auth token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add response interceptor to handle common errors
api.interceptors.response.use(
    (response) => {
        console.log('API Response:', response.config.method?.toUpperCase(), response.config.url, response.status);
        return response;
    },
    (error) => {
        console.error('API Error:', error.config?.method?.toUpperCase(), error.config?.url, error.response?.status, error.response?.data);

        // Handle 401 unauthorized errors
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            // Redirect to login page or emit event
            window.location.href = '/login';
        }

        return Promise.reject(error);
    }
);

export default api;
