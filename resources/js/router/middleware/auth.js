import { useAuth } from '../../composables/useAuth.js';

export function requireAuth(to, from, next) {
    const { isAuthenticated } = useAuth();

    if (!isAuthenticated.value) {
        // Redirect to login page or home page
        next({ name: 'home' });
    } else {
        next();
    }
}

export function guestOnly(to, from, next) {
    const { isAuthenticated } = useAuth();

    if (isAuthenticated.value) {
        // Redirect to dashboard if already authenticated
        next({ name: 'dashboard' });
    } else {
        next();
    }
}
