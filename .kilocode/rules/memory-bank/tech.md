# Virgosoft Limit Order Exchange - Technology Stack

## Backend Technologies

### Laravel Framework
- Version: 12.0 (latest stable)
- PHP Version: 8.4
- Purpose: API backend, business logic, and data management

### Database
- Type: PostgreSQL
- Version: 18
- Hosted via DDEV environment
- Purpose: Persistent storage for users, assets, orders, and trades

### Real-time Communication
- Technology: Pusher via Laravel Broadcasting
- Purpose: Real-time order matching notifications
- Channels: Private channels for user-specific updates

## Frontend Technologies

### Vue.js
- Version: Latest stable
- Approach: Composition API
- Purpose: Reactive user interface and state management

### Tailwind CSS
- Version: 4.0 (latest)
- Purpose: Utility-first CSS framework for styling
- Integration: Via Vite build system

### Build Tools
- Vite: 7.0.7
- Purpose: Frontend asset bundling and development server
- Hot Module Replacement: Enabled for development

## Development Environment

### DDEV
- Type: Local development environment
- PHP Version: 8.4
- Database: PostgreSQL 18
- Node.js Version: 24
- Purpose: Containerized development environment

### Additional Development Tools
- Laravel Pint: Code formatting and style checking
- PHPUnit: Unit and feature testing
- Laravel Tinker: Interactive REPL for debugging

## Project Structure

### Backend Directory Organization
```
app/
├── Http/
│   ├── Controllers/          # API endpoint handlers
│   └── Requests/             # Form request validation
├── Models/                   # Eloquent models
├── Events/                   # Event definitions for broadcasting
├── Jobs/                     # Queueable jobs for order processing
└── Providers/                # Service providers for dependency injection
```

### Frontend Directory Organization
```
resources/
├── js/
│   ├── components/           # Vue.js components
│   ├── composables/          # Reusable composition functions
│   ├── services/             # API and external service integrations
│   └── views/                # Page-level Vue components
└── css/                      # Tailwind CSS configuration
```

## Key Dependencies

### Backend Dependencies
- laravel/framework: Core Laravel framework
- laravel/tinker: Debugging tool
- pusher/pusher-php-server: Pusher integration

### Frontend Dependencies
- vue: Vue.js framework
- axios: HTTP client for API requests
- pusher-js: Pusher client for real-time updates
- tailwindcss: CSS framework

## Development Workflow

### Commands
- `ddev start`: Start development environment
- `ddev php artisan serve`: Start Laravel development server
- `ddev npm run dev`: Start Vite development server
- `ddev php artisan migrate`: Run database migrations
- `ddev php artisan test`: Run test suite

### Environment Configuration
- Database: PostgreSQL via DDEV
- Broadcasting: Pusher (requires API keys)
- Session: Database driver
- Queue: Database driver (for order processing)

## Security Considerations
- Laravel's built-in CSRF protection
- API authentication via Laravel Sanctum
- Input validation using Form Requests
- Database transactions for financial operations
- Row-level locking for balance updates
