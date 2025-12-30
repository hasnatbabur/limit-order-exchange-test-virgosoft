# SPA Authentication Improvements

This document outlines the improvements made to our Laravel Sanctum SPA authentication implementation to follow best practices from the official Laravel documentation.

## Overview

We've enhanced our authentication system to provide a more secure, robust, and user-friendly experience for our Single Page Application (SPA) built with Vue.js.

## Key Improvements

### 1. CORS Configuration

**File:** `config/cors.php`

- Created proper CORS configuration for SPA
- Added support for credentials (`supports_credentials: true`)
- Configured paths to include API routes and CSRF cookie endpoint
- Added proper headers for cross-origin requests

### 2. Sanctum Stateful Domains

**Files:** `config/sanctum.php`, `.env`, `.env.example`

- Updated stateful domains to include common development ports (3000, 5173)
- Added environment variables for easy configuration
- Configured token expiration (default: 1 year)

### 3. Token Management

**Files:** `app/Http/Controllers/Auth/AuthenticatedUserController.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`

- Added token abilities for fine-grained access control
- Implemented proper token revocation on logout (revokes all tokens)
- Enhanced security by using wildcard abilities for full access

### 4. CSRF Protection

**Files:** `routes/api.php`, `resources/js/services/auth.js`

- Added CSRF cookie endpoint for SPA
- Implemented automatic CSRF token retrieval in auth service
- Enhanced error handling for CSRF token mismatches

### 5. Enhanced Error Handling

**File:** `resources/js/services/auth.js`

- Added specific error handling for different HTTP status codes
- Improved user feedback for authentication errors
- Added handling for rate limiting (429) and CSRF mismatches (419)

### 6. Frontend Route Protection

**Files:** `resources/js/router/middleware/auth.js`, `resources/js/router/index.js`

- Created authentication middleware for frontend routes
- Enhanced existing router navigation guards
- Added proper redirect logic for authenticated/unauthenticated users

### 7. Environment Configuration

**Files:** `.env`, `.env.example`

- Added Sanctum-specific environment variables
- Configured stateful domains for development and production
- Set token expiration configuration

### 8. Comprehensive Testing

**File:** `tests/Feature/SPAAuthenticationTest.php`

- Created comprehensive test suite for SPA authentication
- Tests for CSRF protection, token management, and error handling
- Validates CORS headers and configuration

## Security Enhancements

1. **Token Revocation**: All user tokens are revoked on logout for better security
2. **CSRF Protection**: Implemented CSRF token handling for stateful requests
3. **Token Abilities**: Added abilities for fine-grained access control
4. **Error Handling**: Enhanced error responses prevent information leakage

## Frontend Improvements

1. **Automatic CSRF Handling**: Auth service automatically retrieves CSRF tokens
2. **Better Error Messages**: User-friendly error messages for different scenarios
3. **Route Protection**: Frontend routes are properly protected based on auth status
4. **Async Support**: Updated composables to handle async operations properly

## Configuration Details

### CORS Configuration

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'supports_credentials' => true,
```

### Sanctum Configuration

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '...')),
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 525600),
```

### Environment Variables

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:5173,127.0.0.1,127.0.0.1:8000,127.0.0.1:5173
SANCTUM_TOKEN_EXPIRATION=525600
```

## Usage Examples

### Frontend Authentication

```javascript
import { useAuth } from './composables/useAuth.js';

const { login, logout, isAuthenticated, user } = useAuth();

// Login
await login({ email, password });

// Logout
await logout();

// Check authentication status
if (isAuthenticated.value) {
    // User is authenticated
}
```

### Protected API Requests

```javascript
// Auth service automatically handles CSRF tokens and authorization headers
const response = await axios.get('/api/auth/me');
```

## Testing

Run the authentication tests to verify everything is working correctly:

```bash
ddev php artisan test tests/Feature/SPAAuthenticationTest.php
```

## Migration Notes

If migrating from a previous authentication setup:

1. Update your frontend to use the new auth service methods
2. Ensure your environment variables are properly configured
3. Run the new test suite to verify functionality
4. Update any custom middleware to work with the new authentication flow

## Best Practices Followed

1. **Stateful Authentication**: Using Sanctum's stateful authentication for SPAs
2. **CSRF Protection**: Implementing CSRF tokens for security
3. **Token Management**: Proper token creation, usage, and revocation
4. **Error Handling**: Comprehensive error handling for better UX
5. **Testing**: Thorough test coverage for authentication features

## Future Enhancements

1. **Token Refresh**: Implement automatic token refresh for long-lived sessions
2. **Rate Limiting**: Add rate limiting to authentication endpoints
3. **Two-Factor Authentication**: Add 2FA support for enhanced security
4. **Social Authentication**: Integrate OAuth providers for social login
