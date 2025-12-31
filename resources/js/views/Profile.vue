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

                <!-- Crypto Assets Section -->
                <div class="lg:col-span-3">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Your Crypto Assets</h3>
                                <button
                                    @click="showAddAssetModal = true"
                                    class="text-sm bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded"
                                >
                                    + Add Asset
                                </button>
                            </div>

                            <!-- Assets Table -->
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Asset</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Available</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Locked</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-if="userAssets.length === 0">
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                No assets found. Click "Add Asset" to get started!
                                            </td>
                                        </tr>
                                        <tr v-for="asset in userAssets" :key="asset.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <div :class="[
                                                            'h-8 w-8 rounded-full flex items-center justify-center text-white text-xs font-bold',
                                                            asset.symbol === 'BTC' ? 'bg-orange-500' :
                                                            asset.symbol === 'ETH' ? 'bg-blue-500' : 'bg-gray-500'
                                                        ]">
                                                            {{ asset.symbol }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ asset.symbol }}</div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ asset.symbol === 'BTC' ? 'Bitcoin' : asset.symbol === 'ETH' ? 'Ethereum' : asset.symbol }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ formatCryptoAmount(asset.available_amount) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ formatCryptoAmount(asset.locked_amount) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ formatCryptoAmount(asset.amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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

            <!-- Add Test Assets Modal -->
            <div v-if="showAddAssetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Add Test Assets</h3>
                            <button @click="showAddAssetModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Error Message -->
                        <div v-if="assetForm.error" class="rounded-md bg-red-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        {{ assetForm.error }}
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <!-- Success Message -->
                        <div v-if="assetForm.success" class="rounded-md bg-green-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">
                                        {{ assetForm.success }}
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Asset Symbol</label>
                                <select v-model="assetForm.symbol" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="BTC">Bitcoin (BTC)</option>
                                    <option value="ETH">Ethereum (ETH)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount</label>
                                <input
                                    v-model.number="assetForm.amount"
                                    type="number"
                                    step="0.00000001"
                                    min="0.00000001"
                                    max="10000"
                                    class="mt-1 block w-full px-3 py-2.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="0.00000000"
                                >
                            </div>

                            <div class="flex space-x-3">
                                <button
                                    type="button"
                                    @click="addTestAssets"
                                    :disabled="assetForm.loading"
                                    class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <svg v-if="assetForm.loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ assetForm.loading ? 'Adding...' : 'Add Assets' }}
                                </button>
                                <button
                                    type="button"
                                    @click="showAddAssetModal = false"
                                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';
import assetService from '../services/assets.js';

const router = useRouter();
const { user, loading, error, logout, updateProfile, isAuthenticated } = useAuth();

const form = reactive({
    name: '',
    email: ''
});

const errors = reactive({
    name: '',
    email: ''
});

const success = ref('');
const showAddAssetModal = ref(false);

// Asset form data
const assetForm = reactive({
    symbol: 'BTC',
    amount: '',
    loading: false,
    error: null,
    success: null
});

// Make balance reactive to user changes
const balance = computed(() => {
    return user.value?.balance || 0.00;
});

const userAssets = ref([]);

// Load assets when user is available
const loadUserAssets = async () => {
    if (!user.value || !isAuthenticated.value) {
        return;
    }

    try {
        const assets = await assetService.getAssets();
        userAssets.value = assets || [];
    } catch (error) {
        console.error('Failed to load assets:', error);
        userAssets.value = [];
    }
};

// Initialize form with user data
onMounted(async () => {
    // Wait a tick for auth to initialize
    await nextTick();

    // Initialize form if user is available
    if (user.value) {
        form.name = user.value.name || '';
        form.email = user.value.email || '';
    }

    // Load assets
    loadUserAssets();
});

// Watch for authentication changes
watch([user, isAuthenticated], () => {
    if (user.value && isAuthenticated.value) {
        form.name = user.value.name || '';
        form.email = user.value.email || '';
        loadUserAssets();
    }
}, { immediate: true });


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

const formatCryptoAmount = (amount) => {
    const num = parseFloat(amount);
    if (num === 0) return '0.00000000';

    // For very small amounts, show more decimal places
    if (num < 0.001) {
        return num.toFixed(8);
    }

    // For larger amounts, show appropriate decimal places
    if (num < 1) {
        return num.toFixed(6);
    }

    // For amounts >= 1, show 4 decimal places
    return num.toFixed(4);
};

const addTestAssets = async () => {
    // Clear previous messages
    assetForm.error = null;
    assetForm.success = null;

    // Validate form
    if (!assetForm.amount || parseFloat(assetForm.amount) <= 0) {
        assetForm.error = 'Please enter a valid amount greater than 0';
        return;
    }

    try {
        assetForm.loading = true;

        // Make API call to add test assets
        const result = await assetService.addTestAssets(
            assetForm.symbol,
            parseFloat(assetForm.amount)
        );

        // Show success message
        assetForm.success = result.message;

        // Reset form
        assetForm.amount = '';

        // Close modal after a short delay
        setTimeout(() => {
            showAddAssetModal.value = false;
            assetForm.success = null;
        }, 2000);

        // Refresh assets data
        await loadUserAssets();

        // Also refresh user data to get updated balance
        try {
            await updateProfile({});
        } catch (error) {
            console.error('Failed to refresh user data:', error);
        }

    } catch (error) {
        console.error('Failed to add test assets:', error);
        assetForm.error = error.response?.data?.error || 'Failed to add test assets. Please try again.';
    } finally {
        assetForm.loading = false;
    }
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
