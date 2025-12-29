import './bootstrap';
import { createApp } from 'vue';
import App from './views/App.vue';
import router from './router';

// Create Vue app instance
const app = createApp(App);

// Use router
app.use(router);

// Global error handler
app.config.errorHandler = (err, vm, info) => {
    console.error('Vue error:', err);
    console.error('Component:', vm);
    console.error('Info:', info);

    // Show user-friendly error notification
    if (window.notify) {
        window.notify('error', 'Application Error', 'Something went wrong. Please try again.');
    }
};

// Global warning handler
app.config.warnHandler = (msg, vm, trace) => {
    console.warn('Vue warning:', msg);
    console.warn('Component:', vm);
    console.warn('Trace:', trace);
};

// Mount the app
app.mount('#app');

// Make app available globally for debugging
if (process.env.NODE_ENV === 'development') {
    window.__VUE_APP__ = app;
}
