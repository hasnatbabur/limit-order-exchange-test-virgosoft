import { ref, computed, onMounted } from 'vue';
import authService from '../services/auth.js';

export function useAuth() {
    // State
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Computed properties
    const isAuthenticated = computed(() => !!user.value && !!authService.isAuthenticated());

    // Methods
    const login = async (credentials) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await authService.login(credentials);
            user.value = response.data.user;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const register = async (userData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await authService.register(userData);
            user.value = response.data.user;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const logout = async () => {
        loading.value = true;
        error.value = null;

        try {
            await authService.logout();
            user.value = null;
        } catch (err) {
            error.value = err.message;
            // Even if logout fails on server, clear local state
            user.value = null;
        } finally {
            loading.value = false;
        }
    };

    const fetchUser = async () => {
        if (!authService.isAuthenticated()) {
            return null;
        }

        loading.value = true;
        error.value = null;

        try {
            const userData = await authService.fetchUser();
            user.value = userData;
            return userData;
        } catch (err) {
            error.value = err.message;
            user.value = null;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const forgotPassword = async (email) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await authService.forgotPassword(email);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const resetPassword = async (resetData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await authService.resetPassword(resetData);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateProfile = async (profileData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await authService.updateProfile(profileData);

            // Update user data in state
            if (response.data && response.data.user) {
                user.value = response.data.user;
            }

            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const initAuth = async () => {
        if (authService.isAuthenticated()) {
            try {
                await fetchUser();
            } catch (err) {
                console.warn('Failed to initialize auth:', err.message);
                // Clear invalid token
                await authService.clearAuth();
            }
        }
    };

    const clearError = () => {
        error.value = null;
    };

    // Initialize auth on composable creation
    onMounted(() => {
        initAuth();
    });

    return {
        // State
        user,
        loading,
        error,

        // Computed
        isAuthenticated,

        // Methods
        login,
        register,
        logout,
        fetchUser,
        forgotPassword,
        resetPassword,
        updateProfile,
        initAuth,
        clearError
    };
}
