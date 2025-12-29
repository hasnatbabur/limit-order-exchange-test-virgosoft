<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Create your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <router-link to="/login" class="font-medium text-blue-600 hover:text-blue-500">
                        sign in to your existing account
                    </router-link>
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.name }"
                            placeholder="Enter your full name"
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
                            autocomplete="email"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.email }"
                            placeholder="Enter your email address"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.password }"
                            placeholder="Create a password"
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
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            :class="{ 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': errors.password_confirmation }"
                            placeholder="Confirm your password"
                        />
                        <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ errors.password_confirmation }}</p>
                    </div>
                </div>

                <div class="flex items-center">
                    <input
                        id="terms"
                        v-model="form.accept_terms"
                        name="terms"
                        type="checkbox"
                        required
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                        I agree to the
                        <a href="#" class="text-blue-600 hover:text-blue-500">Terms and Conditions</a>
                        and
                        <a href="#" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
                    </label>
                </div>
                <p v-if="errors.accept_terms" class="mt-1 text-sm text-red-600">{{ errors.accept_terms }}</p>

                <div>
                    <button
                        type="submit"
                        :disabled="loading || !form.accept_terms"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg v-if="!loading" class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        {{ loading ? 'Creating account...' : 'Create account' }}
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
                            <h3 class="text-sm font-medium text-red-800">
                                Registration failed
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
                                Registration successful!
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                Your account has been created successfully. You can now sign in.
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
import { useRouter } from 'vue-router';
import { useAuth } from '../../composables/useAuth.js';

const router = useRouter();
const { register, loading, error } = useAuth();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    accept_terms: false
});

const errors = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    accept_terms: ''
});

const success = ref(false);
const passwordStrength = ref(0);

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

    if (!form.accept_terms) {
        errors.accept_terms = 'You must accept the terms and conditions';
        isValid = false;
    }

    return isValid;
};

const handleRegister = async () => {
    if (!validateForm()) {
        return;
    }

    try {
        await register({
            name: form.name,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation
        });

        success.value = true;

        // Redirect to login after 2 seconds
        setTimeout(() => {
            router.push('/login');
        }, 2000);

    } catch (err) {
        // Error is already handled by useAuth composable
        console.error('Registration error:', err);
    }
};
</script>
