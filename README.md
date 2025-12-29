# Virgosoft Limit Order Exchange

A technical assessment project demonstrating a full-stack limit-order exchange with focus on financial data integrity, concurrency safety, and real-time systems.

## 🚀 Project Overview

This is a mini trading engine built with Laravel (backend) and Vue.js (frontend) that demonstrates:

- **Financial Data Integrity**: Atomic operations and race condition prevention
- **Real-time Trading**: Live order book updates and trade notifications
- **Commission System**: 1.5% commission on matched trades
- **Security**: Comprehensive security measures for financial applications
- **Scalability**: Feature-based architecture for maintainable growth

## 📋 Core Features

- User balance and cryptocurrency asset management
- Limit order placement (buy/sell) with price and amount specifications
- Real-time order matching engine (full matches only)
- Live order book updates via WebSocket
- Order history and status tracking
- Commission calculation and deduction
- Responsive trading interface

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 12.0
- **Language**: PHP 8.4
- **Database**: PostgreSQL 18
- **Authentication**: Laravel Sanctum
- **Real-time**: Laravel Broadcasting with Pusher
- **Queue**: Database queue for order processing

### Frontend
- **Framework**: Vue.js with Composition API
- **Styling**: Tailwind CSS 4.0
- **Build Tool**: Vite 7.0.7
- **HTTP Client**: Axios
- **Real-time**: Pusher.js

### Development Environment
- **Containerization**: DDEV
- **Testing**: PHPUnit
- **Code Style**: Laravel Pint

## 📁 Project Structure

```
virgosoft-limit-order/
├── app/
│   └── Features/           # Feature-based architecture
│       ├── Orders/         # Order management
│       ├── Trading/        # Trading engine
│       ├── Users/          # User management
│       └── Shared/         # Shared utilities
├── database/
│   ├── migrations/         # Database schema
│   └── seeders/           # Test data
├── resources/
│   └── js/
│       ├── components/     # Vue.js components
│       ├── composables/   # Reusable logic
│       └── services/      # API integration
├── routes/
│   └── api.php           # API endpoints
├── tests/                # Test suite
└── docs/                 # Documentation
    ├── feature-based-implementation-plan.md
    ├── feature-based-architecture.md
    ├── database-schema.md
    ├── api-design.md
    ├── testing-strategy.md
    └── security-considerations.md
```

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- DDEV installed locally
- Node.js 24+ (for frontend development)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/your-username/virgosoft-limit-order.git
cd virgosoft-limit-order
```

2. **Start DDEV environment**
```bash
ddev start
```

3. **Install dependencies**
```bash
ddev composer install
ddev npm install
```

4. **Configure environment**
```bash
ddev cp .env.example .env
ddev php artisan key:generate
```

5. **Run database migrations**
```bash
ddev php artisan migrate
```

6. **Build frontend assets**
```bash
ddev npm run build
```

7. **Start development servers**
```bash
# Backend server
ddev php artisan serve

# Frontend development server (in another terminal)
ddev npm run dev
```

8. **Access the application**
- Frontend: `https://virgosoft-limit-order.ddev.site`
- API: `https://virgosoft-limit-order.ddev.site/api`
- phpMyAdmin: Available via DDEV

## 📚 Documentation

### Architecture & Design
- [Feature-Based Implementation Plan](docs/feature-based-implementation-plan.md) - Feature-driven development approach
- [Feature-Based Architecture](docs/feature-based-architecture.md) - Code organization principles
- [Database Schema](docs/database-schema.md) - Database design and relationships

### API & Integration
- [API Design](docs/api-design.md) - RESTful API documentation
- [WebSocket Events](docs/api-design.md#websocket-events) - Real-time event specifications

### Development & Testing
- [Testing Strategy](docs/testing-strategy.md) - Comprehensive testing approach
- [Security Considerations](docs/security-considerations.md) - Security best practices

## 🔧 Development Workflow

### Making Changes

1. **Create a feature branch**
```bash
git checkout -b feature/your-feature-name
```

2. **Make your changes**
- Follow the feature-based architecture
- Write tests for new functionality
- Ensure code follows Laravel conventions

3. **Run tests**
```bash
ddev php artisan test
```

4. **Commit changes**
```bash
git add .
git commit -m "feat: add your feature description"
```

5. **Push and create pull request**

### Code Quality

```bash
# Code formatting
ddev php artisan pint

# Run all tests
ddev php artisan test

# Run specific test
ddev php artisan test tests/Unit/Services/OrderServiceTest.php
```

## 🧪 Testing

The project includes comprehensive test coverage:

- **Unit Tests**: Individual component testing
- **Feature Tests**: API endpoint testing
- **Integration Tests**: Component interaction testing
- **Concurrency Tests**: Race condition prevention
- **Performance Tests**: Load and stress testing

```bash
# Run all tests
ddev php artisan test

# Run with coverage
ddev php artisan test --coverage

# Run specific test suite
ddev php artisan test tests/Unit/
ddev php artisan test tests/Feature/
```

## 🔐 Security

This application implements multiple security layers:

- **Authentication**: Laravel Sanctum with secure token management
- **Authorization**: Policy-based access control
- **Input Validation**: Comprehensive request validation
- **Rate Limiting**: API endpoint protection
- **Financial Security**: Atomic transactions and balance protection
- **Audit Logging**: Complete audit trail for all operations

See [Security Considerations](docs/security-considerations.md) for detailed security measures.

## 📊 API Endpoints

### Authentication
- `POST /api/auth/login` - User authentication
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Current user info

### Profile
- `GET /api/profile` - User balances and assets

### Orders
- `GET /api/orders` - List user orders
- `POST /api/orders` - Create new order
- `GET /api/orders/{id}` - Get order details
- `POST /api/orders/{id}/cancel` - Cancel order

### Market Data
- `GET /api/orderbook` - Current order book
- `GET /api/trades` - Trade history
- `GET /api/market/symbols` - Available trading pairs

## 🔄 Real-time Events

The application uses WebSocket connections for real-time updates:

- `order_update` - Order status changes
- `trade` - New trade executions
- `balance_update` - Balance changes
- `orderbook_update` - Order book changes

## 🎯 Core Business Logic

### Order Matching Rules
- **Full matches only** - Partial matches are not supported
- **Price-time priority** - Orders matched by price, then by creation time
- **Buy orders** match with sell orders at equal or lower prices
- **Sell orders** match with buy orders at equal or higher prices

### Commission Structure
- **Rate**: 1.5% of matched USD value
- **Deduction**: From both buyer and seller
- **Calculation**: Commission = Amount × Price × 0.015

### Balance Management
- **Buy orders**: USD balance locked when order is placed
- **Sell orders**: Cryptocurrency assets locked when order is placed
- **Order cancellation**: Locked funds/assets released immediately
- **Trade execution**: Balances updated atomically

## 🚀 Deployment

### Environment Configuration

1. **Production Environment Variables**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-key
PUSHER_APP_SECRET=your-pusher-secret
PUSHER_APP_CLUSTER=your-pusher-cluster
```

2. **Deployment Steps**
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start queue workers
php artisan queue:work --daemon
```

## 📈 Performance Considerations

### Database Optimization
- Indexed queries for order book
- Composite indexes for common query patterns
- Database connection pooling
- Query result caching

### Application Performance
- Queue-based order processing
- Real-time updates via WebSocket
- Lazy loading for frontend components
- Optimized asset bundling

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For questions or support:

- Create an issue in the GitHub repository
- Review the documentation in the `/docs` folder
- Check the test files for implementation examples

## 🎯 Evaluation Criteria

This project is evaluated on:

- ✅ Balance & asset race safety
- ✅ Atomic execution of operations
- ✅ Commission calculation accuracy
- ✅ Real-time listener stability
- ✅ Code quality and repository cleanliness
- ✅ Security validation
- ✅ Fast setup process
- ✅ Meaningful git commits

---

**Note**: This is a technical assessment project demonstrating full-stack development capabilities with focus on financial systems, real-time applications, and secure coding practices.
