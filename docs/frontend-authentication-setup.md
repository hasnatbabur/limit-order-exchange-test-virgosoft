# Frontend Authentication Setup

This document explains how to use the frontend authentication system that has been implemented for the Virgosoft Limit Order Exchange.

## Overview

The frontend authentication system includes:
- Login and registration pages
- Authentication state management
- Route guards for protected pages
- Responsive design with Tailwind CSS
- Vue.js 3 with Composition API

## File Structure

```
resources/js/
├── app.js                    # Main application entry point
├── components/
│   └── auth/
│       ├── Login.vue         # Login form component
│       └── Register.vue      # Registration form component
├── composables/
│   └── useAuth.js           # Authentication state management
├── services/
│   └── auth.js              # Authentication API service
├── router/
│   └── index.js             # Vue Router configuration
└── views/
    ├── App.vue               # Main application component
    ├── Home.vue              # Public home page
    ├── Dashboard.vue         # Protected dashboard
    ├── Profile.vue          # User profile page
    ├── Orders.vue           # Order management page
    └── NotFound.vue        # 404 error page
```

## Authentication Flow

### 1. Registration
- Navigate to `/register`
- Fill in name, email, password, and password confirmation
- Password strength indicator provides real-time feedback
- Upon successful registration, user is redirected to login page

### 2. Login
- Navigate to `/login`
- Enter email and password
- Optional "Remember me" checkbox
- Upon successful login, user is redirected to dashboard

### 3. Protected Routes
- Routes like `/dashboard`, `/profile`, `/orders` require authentication
- Unauthenticated users are redirected to login with intended destination stored
- Authenticated users are redirected away from login/register pages

### 4. Logout
- Click logout button in navigation header
- Clears authentication state and redirects to login page

## API Integration

The authentication system expects the following API endpoints (as defined in `docs/api-design.md`):

- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

## Usage Examples

### Using the useAuth Composable

```javascript
import { useAuth } from '@/composables/useAuth.js';

const { user, isAuthenticated, login, logout, loading, error } = useAuth();

// Login
try {
    await login({
        email: 'user@example.com',
        password: 'password123'
    });
} catch (error) {
    console.error('Login failed:', error.message);
}

// Logout
await logout();
```

### Authentication State

The `useAuth` composable provides:
- `user` - Current user object
- `isAuthenticated` - Boolean flag
- `loading` - Loading state for operations
- `error` - Error message
- `login()`, `register()`, `logout()`, `fetchUser()` methods

## Styling

The authentication pages use Tailwind CSS with:
- Responsive design for mobile and desktop
- Consistent color scheme (blue primary)
- Form validation states
- Loading indicators
- Error and success notifications

## Security Features

- Password strength validation
- Form validation on client and server
- Token-based authentication (Laravel Sanctum)
- Automatic token management
- Session persistence with localStorage

## Development

### Building Assets
```bash
npm run build
```

### Development Server
```bash
npm run dev
```

### File Watching
The Vite configuration includes hot module replacement for development.

## Testing

To test the authentication flow:

1. Start the development server
2. Navigate to `/register`
3. Create a new account
4. Verify redirect to login page
5. Login with new credentials
6. Verify access to protected routes
7. Test logout functionality

## Customization

### Adding New Protected Routes
Add routes to `resources/js/router/index.js` with `meta: { requiresAuth: true }`:

```javascript
{
    path: '/new-page',
    name: 'new-page',
    component: NewPage,
    meta: {
        title: 'New Page - Virgosoft Exchange',
        requiresAuth: true
    }
}
```

### Customizing Authentication Service
Modify `resources/js/services/auth.js` to:
- Change API endpoints
- Add custom error handling
- Implement token refresh logic
- Add additional authentication methods

### Styling Customization
- Modify Tailwind classes in Vue components
- Update color scheme in CSS variables
- Add custom animations and transitions

## Troubleshooting

### Common Issues

1. **Build fails with Vue template error**
   - Ensure `@vitejs/plugin-vue` is installed
   - Check Vite configuration includes Vue plugin

2. **Authentication not working**
   - Verify API endpoints are implemented
   - Check CORS configuration
   - Ensure Laravel Sanctum is properly configured

3. **Route guards not working**
   - Check router configuration
   - Verify `isAuthenticated` computed property
   - Ensure `initAuth()` is called

4. **Styling issues**
   - Verify Tailwind CSS is properly imported
   - Check Vite Tailwind plugin configuration
   - Ensure CSS build process is working

## Next Steps

The authentication system is ready for integration with the Laravel backend. The next phase would be:

1. Implement Laravel API endpoints
2. Set up Laravel Sanctum
3. Configure CORS for API requests
4. Add comprehensive testing
5. Implement additional security features

This frontend authentication system provides a solid foundation for the Virgosoft Limit Order Exchange application.
