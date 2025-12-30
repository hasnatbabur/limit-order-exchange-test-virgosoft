<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900">Trading Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Welcome back, {{ user?.name }}! Here's your trading overview.
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">USD Balance</dt>
                                    <dd class="text-lg font-medium text-gray-900">${{ formatNumber(balance) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Assets</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ totalAssets }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Open Orders</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ openOrders }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Trades</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ totalTrades }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Place Order</h3>

                            <!-- Order Type Tabs -->
                            <div class="mb-4">
                                <nav class="flex space-x-8">
                                    <button
                                        @click="orderType = 'buy'"
                                        :class="[
                                            orderType === 'buy'
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                                        ]"
                                    >
                                        Buy
                                    </button>
                                    <button
                                        @click="orderType = 'sell'"
                                        :class="[
                                            orderType === 'sell'
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                                        ]"
                                    >
                                        Sell
                                    </button>
                                </nav>
                            </div>

                            <!-- Order Form -->
                            <div class="space-y-4">
                                <!-- Error Message -->
                                <div v-if="orderForm.error" class="rounded-md bg-red-50 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">
                                                {{ orderForm.error }}
                                            </h3>
                                        </div>
                                        <div class="ml-auto pl-3">
                                            <div class="-mx-1.5 -my-1.5">
                                                <button @click="orderForm.error = null" class="inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600">
                                                    <span class="sr-only">Dismiss</span>
                                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Success Message -->
                                <div v-if="orderForm.success" class="rounded-md bg-green-50 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-green-800">
                                                {{ orderForm.success }}
                                            </h3>
                                        </div>
                                        <div class="ml-auto pl-3">
                                            <div class="-mx-1.5 -my-1.5">
                                                <button @click="orderForm.success = null" class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
                                                    <span class="sr-only">Dismiss</span>
                                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Symbol</label>
                                    <select v-model="orderForm.symbol" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="BTC-USD">BTC-USD</option>
                                        <option value="ETH-USD">ETH-USD</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price (USD)</label>
                                    <input
                                        v-model.number="orderForm.price"
                                        @input="calculateTotal"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full px-3 py-2.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                                    <input
                                        v-model.number="orderForm.amount"
                                        @input="calculateTotal"
                                        type="number"
                                        step="0.00000001"
                                        min="0"
                                        class="mt-1 block w-full px-3 py-2.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Total (USD)</label>
                                    <input
                                        :value="orderForm.total"
                                        readonly
                                        class="mt-1 block w-full px-3 py-2.5 bg-gray-50 border-gray-300 rounded-md shadow-sm sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>

                                <button
                                    type="button"
                                    @click="placeOrder"
                                    :disabled="orderForm.loading"
                                    :class="[
                                        orderType === 'buy'
                                            ? 'bg-green-600 hover:bg-green-700'
                                            : 'bg-red-600 hover:bg-red-700',
                                        orderForm.loading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                                        'w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2'
                                    ]"
                                >
                                    <svg v-if="orderForm.loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ orderForm.loading ? 'Placing Order...' : (orderType === 'buy' ? 'Place Buy Order' : 'Place Sell Order') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Book -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Order Book</h3>

                            <!-- Order Book Table (Placeholder) -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Buy Orders -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Buy Orders</h4>
                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Price</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-green-600">45,000.00</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">0.1000</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">4,500.00</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-green-600">44,950.00</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">0.2000</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">8,990.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Sell Orders -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Sell Orders</h4>
                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Price</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-red-600">45,100.00</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">0.1500</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">6,765.00</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-red-600">45,150.00</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">0.3000</td>
                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">13,545.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="mt-6">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Orders</h3>
                            <router-link to="/orders" class="text-sm text-blue-600 hover:text-blue-500">View all</router-link>
                        </div>

                        <!-- Orders Table (Placeholder) -->
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">BTC-USD</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Buy</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">45,000.00</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">0.1000</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Open</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 hours ago</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import orderService from '../services/orders.js';

const { user } = useAuth();

// Mock data - in real app, this would come from API
const balance = ref(10000.00);
const totalAssets = ref(2);
const openOrders = ref(3);
const totalTrades = ref(47);
const orderType = ref('buy');

// Order form data
const orderForm = ref({
    symbol: 'BTC-USD',
    price: '',
    amount: '',
    total: '0.00',
    loading: false,
    error: null,
    success: null
});

// Calculate total when price or amount changes
const calculateTotal = () => {
    const price = parseFloat(orderForm.value.price) || 0;
    const amount = parseFloat(orderForm.value.amount) || 0;
    orderForm.value.total = (price * amount).toFixed(2);

    // Clear any previous messages when user changes input
    orderForm.value.error = null;
    orderForm.value.success = null;
};

// Place order function
const placeOrder = async () => {
    // Clear previous messages
    orderForm.value.error = null;
    orderForm.value.success = null;

    // Validate form
    if (!orderForm.value.price || !orderForm.value.amount) {
        orderForm.value.error = 'Please enter both price and amount';
        return;
    }

    if (parseFloat(orderForm.value.price) <= 0 || parseFloat(orderForm.value.amount) <= 0) {
        orderForm.value.error = 'Price and amount must be greater than 0';
        return;
    }

    try {
        orderForm.value.loading = true;

        // Make API call to create order
        const order = await orderService.createOrder({
            symbol: orderForm.value.symbol,
            side: orderType.value,
            price: parseFloat(orderForm.value.price),
            amount: parseFloat(orderForm.value.amount)
        });

        // Show success message
        orderForm.value.success = `${orderType.value === 'buy' ? 'Buy' : 'Sell'} order placed successfully! Order ID: ${order.id}`;

        // Reset form
        orderForm.value.price = '';
        orderForm.value.amount = '';
        orderForm.value.total = '0.00';

        // Update stats (in real app, this would come from API)
        openOrders.value += 1;

    } catch (error) {
        console.error('Order creation failed:', error);
        orderForm.value.error = error.response?.data?.error || 'Failed to place order. Please try again.';
    } finally {
        orderForm.value.loading = false;
    }
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
};

// Load user data on mount
onMounted(async () => {
    // In a real app, we would load user's balance and orders here
    // For now, we'll use mock data
});
</script>
