# WeWinGames - Sports Betting Platform

WeWinGames is a comprehensive sports betting information and picks service built with Laravel 12 and Vue.js 3. The platform provides betting recommendations, game analysis, and subscription-based access to premium picks.

## Requirements

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18.x or higher
- MySQL 8.0 or SQLite (for local development)
- Redis (optional, for caching)

## Quick Start

1. **Clone the repository**
   ```bash
   git clone [repository-url]
   cd SITE-WeWinGames
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your database** in `.env`:
   - For SQLite (local development):
     ```
     DB_CONNECTION=sqlite
     DB_DATABASE=database/database.sqlite
     ```
   - For MySQL:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=wewingames
     DB_USERNAME=your_username
     DB_PASSWORD=your_password
     ```

5. **Run migrations and seeders**
   ```bash
   # Create the database file (SQLite only)
   touch database/database.sqlite
   
   # Run migrations
   php artisan migrate
   
   # Seed the database with test data
   php artisan db:seed
   ```

6. **Configure services** (optional)
   Add your API keys to `.env`:
   ```
   # Stripe Configuration
   STRIPE_KEY=your_stripe_publishable_key
   STRIPE_SECRET=your_stripe_secret_key
   STRIPE_WEBHOOK_SECRET=your_webhook_secret
   
   # Notification Services
   SLACK_BOT_USER_OAUTH_TOKEN=your_slack_token
   SLACK_BOT_USER_DEFAULT_CHANNEL=your_channel
   
   # Production Settings (CRITICAL)
   APP_DEBUG=false  # Must be false in production
   APP_ENV=production
   ```

7. **Start the development servers**
   ```bash
   # In one terminal - Laravel server
   php artisan serve
   
   # In another terminal - Vite dev server
   npm run dev
   ```

8. **Access the application**
   - Application: http://localhost:8000 (or http://site-wewingames.test if using Laravel Herd)
   - Admin area: http://localhost:8000/admin
   - Stripe Products: http://localhost:8000/admin/stripe-products

## Admin Features

### Stripe Product Management
Access at `/admin/stripe-products` to:
- Create and manage subscription products
- Connect to existing Stripe products or create new ones
- Set prices for each tier and billing period
- Manage product features and descriptions

### Enhanced User Management
- Grant ambassador or gifted user privileges
- Set override tiers and expiration dates
- Manage subscription overrides

### Notification System
- Send targeted notifications by tier (Silver, Gold, Platinum)
- Send to all users or specific user groups
- Track notification delivery status

## Default Users

After seeding, you can login with:

- **Admin**: admin@wewingames.test / password
- **Subscriber**: subscriber@wewingames.test / password

## Development Commands

```bash
# Run tests
php artisan test

# Format code
composer format        # PHP code with Laravel Pint
npm run format        # JavaScript/TypeScript with Prettier

# Type checking
npm run typecheck

# Build for production
npm run build

# Run with SSR
composer dev:ssr
```

## Key Features

- **Betting System**: Create and track sports betting picks with ROI calculations
- **Subscription Management**: Dynamic Stripe integration with product management
- **Content Management**: Dynamic pages and blog system
- **Admin Dashboard**: Comprehensive admin panel for managing users, bets, and content
- **Real-time Odds**: Integration with betting operators
- **Betting Education**: Comprehensive guides and educational content
- **Enhanced Security**: Production-ready security headers and configurations
- **Tier-Based Features**: Silver, Gold, and Platinum subscription tiers
- **Ambassador Program**: Support for gifted and ambassador users

## Project Structure

```
├── app/                # Laravel application
│   ├── Http/          # Controllers, Middleware, Requests
│   ├── Models/        # Eloquent models
│   └── Services/      # Business logic
├── resources/         # Frontend resources
│   ├── js/           # Vue.js application
│   └── css/          # Stylesheets
├── database/         # Migrations and seeders
├── routes/           # Application routes
└── tests/            # Test files
```

## Documentation

For detailed documentation, see [CLAUDE.md](CLAUDE.md) which contains:
- Complete technology stack details
- Database schema
- API endpoints
- Development guidelines
- Deployment instructions

## Contributing

1. Follow PSR-12 coding standards for PHP
2. Use TypeScript for all Vue components
3. Run tests before submitting PRs
4. Follow the existing code style and patterns

## License

This project is proprietary software. All rights reserved.

## Support

For questions or issues, please contact the development team.