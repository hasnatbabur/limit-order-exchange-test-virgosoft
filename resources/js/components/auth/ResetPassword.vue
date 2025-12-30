<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Set new password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enter your email, reset token, and new password below.
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleResetPassword">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.email }"
                            placeholder="Enter your email address"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="token" class="block text-sm font-medium text-gray-700">Reset Token</label>
                        <input
                            id="token"
                            v-model="form.token"
                            name="token"
                            type="text"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.token }"
                            placeholder="Enter reset token"
                        />
                        <p v-if="errors.token" class="mt-1 text-sm text-red-600">{{ errors.token }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.password }"
                            placeholder="Create a new password"
                            @input="checkPasswordStrength"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>

                        <!-- Password strength indicator -->
                        <div v-if="form.password" class="mt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Password strength:</span>
                                <span class="text-xs font-medium" :class="passwordStrengthColor">
                                    {{ passwordStrengthText }}
                                </span>
                            </div>
                            <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="h-2 rounded-full transition-all duration-300"
                                    :class="passwordStrengthBarColor"
                                    :style="{ width: passwordStrengthPercentage + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.password_confirmation }"
                            placeholder="Confirm your new password"
                        />
                        <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ errors.password_confirmation }}</p>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg v-if="!loading" class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        {{ loading ? 'Resetting...' : 'Reset Password' }}
                    </button>
                </div>

                <div class="text-center">
                    <router-link to="/login" class="font-medium text-blue-600 hover:text-blue-500">
                        Back to login
                    </router-link>
                </div>

                <div v-if="error" class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Reset Failed
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                {{ error }}
                            </div>
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
                            <h3 class="text-sm font-medium text-green-800">
                                Password Reset Successful
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                {{ success }}
                            </div>
                            <div class="mt-4">
                                <div class="-mx-2 -my-1.5 flex">
                                    <router-link
                                        to="/login"
                                        class="bg-green-50 px-2 py-1.5 rounded-md text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600"
                                    >
                                        Go to login
                                    </router-link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '../../composables/useAuth.js';

const router = useRouter();
const route = useRoute();
const { resetPassword, loading, error } = useAuth();

const form = reactive({
    email: '',
    token: '',
    password: '',
    password_confirmation: ''
});

const errors = reactive({
    email: '',
    token: '',
    password: '',
    password_confirmation: ''
});

const success = ref('');
const passwordStrength = ref(0);

// Pre-fill email and token from query params if available
if (route.query.email) {
    form.email = route.query.email;
}
if (route.query.token) {
    form.token = route.query.token;
}

const passwordStrengthText = computed(() => {
    if (passwordStrength.value === 0) return 'Very Weak';
    if (passwordStrength.value <= 2) return 'Weak';
    if (passwordStrength.value <= 3) return 'Fair';
    if (passwordStrength.value <= 4) return 'Good';
    return 'Strong';
});

const passwordStrengthColor = computed(() => {
    if (passwordStrength.value <= 2) return 'text-red-600';
    if (passwordStrength.value <= 3) return 'text-yellow-600';
    return 'text-green-600';
});

const passwordStrengthBarColor = computed(() => {
    if (passwordStrength.value <= 2) return 'bg-red-500';
    if (passwordStrength.value <= 3) return 'bg-yellow-500';
    return 'bg-green-500';
});

const passwordStrengthPercentage = computed(() => {
    return (passwordStrength.value / 5) * 100;
});

const checkPasswordStrength = () => {
    let strength = 0;
    const password = form.password;

    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    passwordStrength.value = strength;
};

const validateForm = () => {
    // Reset errors
    Object.keys(errors).forEach(key => {
        errors[key] = '';
    });

    let isValid = true;

    if (!form.email) {
        errors.email = 'Email is required';
        isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(form.email)) {
        errors.email = 'Email is invalid';
        isValid = false;
    }

    if (!form.token) {
        errors.token = 'Reset token is required';
        isValid = false;
    } else if (form.token.length < 60) {
        errors.token = 'Invalid reset token';
        isValid = false;
    }

    if (!form.password) {
        errors.password = 'Password is required';
        isValid = false;
    } else if (form.password.length < 8) {
        errors.password = 'Password must be at least 8 characters';
        isValid = false;
    } else if (passwordStrength.value < 3) {
        errors.password = 'Password is too weak. Please include uppercase, lowercase, numbers, and special characters.';
        isValid = false;
    }

    if (!form.password_confirmation) {
        errors.password_confirmation = 'Password confirmation is required';
        isValid = false;
    } else if (form.password !== form.password_confirmation) {
        errors.password_confirmation = 'Passwords do not match';
        isValid = false;
    }

    return isValid;
};

const handleResetPassword = async () => {
    if (!validateForm()) {
        return;
    }

    try {
        const response = await resetPassword({
            email: form.email,
            token: form.token,
            password: form.password,
            password_confirmation: form.password_confirmation
        });

        success.value = response.message;

        // Redirect to login after 2 seconds
        setTimeout(() => {
            router.push('/login');
        }, 2000);

    } catch (err) {
        // Error is already handled by useAuth composable
        console.error('Reset password error:', err);
    }
};
</script>
