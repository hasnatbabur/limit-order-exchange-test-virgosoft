import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';

// Import components
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
import ForgotPassword from '../components/auth/ForgotPassword.vue';
import ResetPassword from '../components/auth/ResetPassword.vue';
import Home from '../views/Home.vue';
import Dashboard from '../views/Dashboard.vue';

// Lazy load components for better performance
const Profile = () => import('../views/Profile.vue');
const Orders = () => import('../views/Orders.vue');

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
        meta: {
            title: 'Virgosoft Exchange - Home',
            requiresAuth: false
        }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: {
            title: 'Login - Virgosoft Exchange',
            requiresAuth: false,
            hideForAuth: true // Redirect authenticated users away from this page
        }
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: {
            title: 'Register - Virgosoft Exchange',
            requiresAuth: false,
            hideForAuth: true // Redirect authenticated users away from this page
        }
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPassword,
        meta: {
            title: 'Forgot Password - Virgosoft Exchange',
            requiresAuth: false,
            hideForAuth: true // Redirect authenticated users away from this page
        }
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: ResetPassword,
        meta: {
            title: 'Reset Password - Virgosoft Exchange',
            requiresAuth: false,
            hideForAuth: true // Redirect authenticated users away from this page
        }
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: {
            title: 'Dashboard - Virgosoft Exchange',
            requiresAuth: true
        }
    },
    {
        path: '/profile',
        name: 'profile',
        component: Profile,
        meta: {
            title: 'Profile - Virgosoft Exchange',
            requiresAuth: true
        }
    },
    {
        path: '/orders',
        name: 'orders',
        component: Orders,
        meta: {
            title: 'Orders - Virgosoft Exchange',
            requiresAuth: true
        }
    },
    // Catch all route for 404
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('../views/NotFound.vue'),
        meta: {
            title: 'Page Not Found - Virgosoft Exchange'
        }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    }
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const { isAuthenticated, initAuth } = useAuth();

    // Initialize auth if not already done
    await initAuth();

    // Update page title
    if (to.meta.title) {
        document.title = to.meta.title;
    }

    // Check if route requires authentication
    if (to.meta.requiresAuth && !isAuthenticated.value) {
        // Store the intended destination for redirect after login
        const redirectPath = to.fullPath;
        next({
            name: 'login',
            query: { redirect: redirectPath }
        });
        return;
    }

    // Redirect authenticated users away from auth pages
    if (to.meta.hideForAuth && isAuthenticated.value) {
        next({ name: 'dashboard' });
        return;
    }

    // Handle redirect query parameter after login
    if (to.name === 'login' && isAuthenticated.value && to.query.redirect) {
        next(to.query.redirect);
        return;
    }

    next();
});

// Global error handler for navigation
router.onError((error) => {
    console.error('Router error:', error);
    // You could redirect to an error page here if needed
});

export default router;
