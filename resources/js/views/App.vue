<template>
    <div id="app" class="min-h-screen bg-gray-50">
        <!-- Navigation Header -->
        <nav v-if="showNavigation" class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <router-link to="/" class="text-xl font-bold text-blue-600">
                                Virgosoft Exchange
                            </router-link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <router-link
                                to="/dashboard"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="[
                                    $route.name === 'dashboard'
                                        ? 'border-blue-500 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                ]"
                            >
                                Dashboard
                            </router-link>
                            <router-link
                                to="/orders"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="[
                                    $route.name === 'orders'
                                        ? 'border-blue-500 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                ]"
                            >
                                Orders
                            </router-link>
                            <router-link
                                to="/profile"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="[
                                    $route.name === 'profile'
                                        ? 'border-blue-500 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                ]"
                            >
                                Profile
                            </router-link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <button
                                @click="handleLogout"
                                :disabled="loading"
                                class="relative inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                <svg v-if="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ loading ? 'Logging out...' : 'Logout' }}
                            </button>
                        </div>

                        <!-- User Avatar (placeholder) -->
                        <div class="ml-3 relative">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">
                                        {{ userInitials }}
                                    </span>
                                </div>
                                <span class="ml-2 text-sm font-medium text-gray-700">
                                    {{ user?.name || 'User' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <router-link
                        to="/dashboard"
                        class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium"
                        :class="[
                            $route.name === 'dashboard'
                                ? 'bg-blue-50 border-blue-500 text-blue-700'
                                : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'
                        ]"
                    >
                        Dashboard
                    </router-link>
                    <router-link
                        to="/orders"
                        class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium"
                        :class="[
                            $route.name === 'orders'
                                ? 'bg-blue-50 border-blue-500 text-blue-700'
                                : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'
                        ]"
                    >
                        Orders
                    </router-link>
                    <router-link
                        to="/profile"
                        class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium"
                        :class="[
                            $route.name === 'profile'
                                ? 'bg-blue-50 border-blue-500 text-blue-700'
                                : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'
                        ]"
                    >
                        Profile
                    </router-link>
                </div>
            </div>
        </nav>

        <!-- Public Navigation (for non-authenticated users) -->
        <nav v-else-if="showPublicNavigation" class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <router-link to="/" class="text-xl font-bold text-blue-600">
                                Virgosoft Exchange
                            </router-link>
                        </div>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center space-x-4">
                        <router-link
                            to="/login"
                            class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
                        >
                            Login
                        </router-link>
                        <router-link
                            to="/register"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                        >
                            Register
                        </router-link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main :class="{ 'pt-16': showNavigation || showPublicNavigation }">
            <router-view />
        </main>

        <!-- Global Loading Overlay -->
        <div
            v-if="globalLoading"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50"
        >
            <div class="bg-white p-8 rounded-lg shadow-xl">
                <div class="flex items-center">
                    <svg class="animate-spin h-8 w-8 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-lg font-medium text-gray-900">Loading...</span>
                </div>
            </div>
        </div>

        <!-- Global Notification Toast -->
        <div
            v-if="notification.show"
            class="fixed bottom-4 right-4 max-w-sm w-full bg-white rounded-lg shadow-lg p-4 z-50 transform transition-all duration-300"
            :class="{
                'border-l-4 border-green-500': notification.type === 'success',
                'border-l-4 border-red-500': notification.type === 'error',
                'border-l-4 border-yellow-500': notification.type === 'warning',
                'border-l-4 border-blue-500': notification.type === 'info'
            }"
        >
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <!-- Success Icon -->
                    <svg v-if="notification.type === 'success'" class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <!-- Error Icon -->
                    <svg v-else-if="notification.type === 'error'" class="h-6 w-6 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <!-- Warning Icon -->
                    <svg v-else-if="notification.type === 'warning'" class="h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <!-- Info Icon -->
                    <svg v-else class="h-6 w-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        {{ notification.title }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ notification.message }}
                    </p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button
                        @click="hideNotification"
                        class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';

const route = useRoute();
const router = useRouter();
const { user, isAuthenticated, logout, loading } = useAuth();

const globalLoading = ref(false);
const notification = ref({
    show: false,
    type: 'info',
    title: '',
    message: ''
});

// Computed properties
const showNavigation = computed(() => {
    return isAuthenticated.value && !route.meta.hideForAuth;
});

const showPublicNavigation = computed(() => {
    return !isAuthenticated.value && !route.meta.hideForAuth;
});

const userInitials = computed(() => {
    if (!user.value?.name) return 'U';
    return user.value.name
        .split(' ')
        .map(word => word.charAt(0).toUpperCase())
        .join('')
        .substring(0, 2);
});

// Methods
const handleLogout = async () => {
    try {
        await logout();
        showNotification('success', 'Logged Out', 'You have been successfully logged out.');
        router.push('/login');
    } catch (error) {
        showNotification('error', 'Logout Failed', error.message);
    }
};

const showNotification = (type, title, message) => {
    notification.value = {
        show: true,
        type,
        title,
        message
    };

    // Auto-hide after 5 seconds
    setTimeout(() => {
        hideNotification();
    }, 5000);
};

const hideNotification = () => {
    notification.value.show = false;
};

// Global notification method (can be called from other components)
const notify = (type, title, message) => {
    showNotification(type, title, message);
};

// Make notify available globally
onMounted(() => {
    window.notify = notify;
});
</script>

<style>
/* Custom animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-out;
}

/* Router transition styles */
.router-enter-active,
.router-leave-active {
    transition: opacity 0.3s ease;
}

.router-enter-from,
.router-leave-to {
    opacity: 0;
}
</style>
