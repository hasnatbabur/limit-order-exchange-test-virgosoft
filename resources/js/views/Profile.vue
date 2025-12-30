<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900">Profile Settings</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Manage your account information and preferences.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Profile Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Profile Information</h3>

                            <form class="space-y-6" @submit.prevent="handleUpdateProfile">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                        <input
                                            id="name"
                                            v-model="form.name"
                                            name="name"
                                            type="text"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300 focus:ring-red-500': errors.name }"
                                        />
                                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            name="email"
                                            type="email"
                                            required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-gray-50"
                                            :class="{ 'border-red-300 focus:ring-red-500': errors.email }"
                                            readonly
                                        />
                                        <p class="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                                    </div>
                                </div>

                                <div>
                                    <button
                                        type="submit"
                                        :disabled="loading"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="!loading">Update Profile</span>
                                        <span v-else>Updating...</span>
                                    </button>
                                </div>

                                <div v-if="error" class="rounded-md bg-red-50 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">Update Failed</h3>
                                            <div class="mt-2 text-sm text-red-700">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="success" class="rounded-md bg-green-50 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-green-800">Profile Updated</h3>
                                            <div class="mt-2 text-sm text-green-700">{{ success }}</div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Information</h3>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">USD Balance</h4>
                                    <p class="text-2xl font-bold text-gray-900">${{ formatNumber(balance) }}</p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Account Status</h4>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>

                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Member Since</h4>
                                    <p class="text-sm text-gray-600">{{ formatDate(user?.created_at) }}</p>
                                </div>

                                <div class="pt-4 border-t border-gray-200">
                                    <button
                                        type="button"
                                        @click="handleLogout"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        Sign Out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';

const router = useRouter();
const { user, loading, error, logout, updateProfile } = useAuth();

const form = reactive({
    name: '',
    email: ''
});

const errors = reactive({
    name: '',
    email: ''
});

const success = ref('');
const balance = ref(0.00);

// Initialize form with user data
onMounted(() => {
    if (user.value) {
        form.name = user.value.name || '';
        form.email = user.value.email || '';
        balance.value = user.value.balance || 0.00;
    }
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const validateForm = () => {
    errors.name = '';
    errors.email = '';

    let isValid = true;

    if (!form.name) {
        errors.name = 'Name is required';
        isValid = false;
    } else if (form.name.length < 2) {
        errors.name = 'Name must be at least 2 characters';
        isValid = false;
    }

    if (!form.email) {
        errors.email = 'Email is required';
        isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(form.email)) {
        errors.email = 'Email is invalid';
        isValid = false;
    }

    return isValid;
};

const handleUpdateProfile = async () => {
    if (!validateForm()) {
        return;
    }

    try {
        await updateProfile({
            name: form.name
        });

        success.value = 'Profile updated successfully!';

        // Clear success message after 3 seconds
        setTimeout(() => {
            success.value = '';
        }, 3000);

    } catch (err) {
        // Error is already handled by useAuth composable
        console.error('Profile update error:', err);
    }
};

const handleLogout = async () => {
    try {
        await logout();
        router.push('/login');
    } catch (err) {
        console.error('Logout error:', err);
    }
};
</script>
