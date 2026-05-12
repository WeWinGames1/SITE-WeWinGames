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
   
   # Google Analytics & Tag Manager
   GOOGLE_ANALYTICS_TAG_ID=G-ZTJTTQP72Q
   GOOGLE_TAG_MANAGER_ID=GTM-PQDDCG6L
   
   # Cloudflare Turnstile
   TURNSTILE_SITE_KEY=0x4AAAAAABjA9oaFF9BSsznw
   TURNSTILE_SECRET_KEY=0x4AAAAAABjA9iC5axcso_Tat1vZ1G-JsZc
   TURNSTILE_ENABLED=true
   
   # Push Notifications (Web Push)
   VAPID_PUBLIC_KEY=your_vapid_public_key
   VAPID_PRIVATE_KEY=your_vapid_private_key
   VAPID_SUBJECT=mailto:admin@wewingames.com
   
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

### Support Ticket System
- Guest and authenticated user support
- Ticket categories and priority levels
- Admin ticket management and responses
- Email notifications for ticket updates

## Default Users

After seeding, you can login with:

- **Admin**: admin@wewingames.test / password
- **Subscriber**: subscriber@wewingames.test / password

## Cloudflare Cache Management

The admin panel includes integrated Cloudflare cache purging. When you click "Clear All Cache" in the admin panel:

1. **Laravel caches are cleared**: config, routes, views, and application cache
2. **Cloudflare cache is purged**: If enabled, all cached content on Cloudflare is purged

### Setting up Cloudflare API:
1. Get your API credentials from Cloudflare dashboard:
   - Global API Key from My Profile > API Tokens
   - Zone ID from your domain's overview page
2. Add to your `.env` file:
   ```env
   CLOUDFLARE_ENABLED=true
   CLOUDFLARE_EMAIL=your@email.com
   CLOUDFLARE_API_KEY=your_global_api_key
   CLOUDFLARE_ZONE_ID=your_zone_id
   ```

## Push Notifications Setup

### Generate VAPID Keys
```bash
# Install web-push library globally or locally
npm install -g web-push

# Generate VAPID keys
web-push generate-vapid-keys

# Or if installed locally
npx web-push generate-vapid-keys
```

Add the generated keys to your `.env` file:
```env
VAPID_PUBLIC_KEY=your_generated_public_key
VAPID_PRIVATE_KEY=your_generated_private_key
VAPID_SUBJECT=mailto:admin@yourdomain.com
```

## SpringBig Integration

SpringBig syncs user subscription tiers for marketing automation.

### Basic Setup (Custom Group List)
```env
SPRINGBIG_ENABLED=true
SPRINGBIG_BASE_URL=https://production.api.springbig.technology/pos/v1
SPRINGBIG_API_KEY=your_api_key
SPRINGBIG_AUTH_TOKEN=your_merchant_id
```

### External Group Setup (Optional)
For segment-based tier management:

```bash
# Setup external group and segments
php artisan springbig:setup-external-group --all

# Or step by step:
php artisan springbig:setup-external-group --list          # View existing
php artisan springbig:setup-external-group --create-group  # Create group
php artisan springbig:setup-external-group --create-segments # Create segments
```

Command outputs env values to copy:
```env
SPRINGBIG_EXTERNAL_GROUP_ENABLED=true
SPRINGBIG_EXTERNAL_GROUP_ID=123
SPRINGBIG_SEGMENT_FREE=456
SPRINGBIG_SEGMENT_GOLD=457
SPRINGBIG_SEGMENT_PLATINUM=458
# etc.
```

See CLAUDE.md for detailed API documentation and service methods.

## Migration Best Practices

When creating new migrations, follow these guidelines to avoid common issues:

### Key Rules:
1. **Always name your indexes** - Auto-generated names can exceed MySQL's 64-character limit
2. **Check column existence** before adding/modifying columns
3. **Write proper rollback logic** in the down() method
4. **Consider migration order** - ensure dependencies exist first
5. **Avoid composite indexes on string columns** - they can exceed key length limits

### Quick Reference:
```php
// GOOD: Named index under 64 chars
$table->unique(['user_id', 'product_id'], 'user_product_unique');

// GOOD: Check before adding
if (!Schema::hasColumn('users', 'avatar')) {
    $table->string('avatar')->nullable();
}

// GOOD: Separate indexes for strings
$table->index('email', 'email_idx');
$table->index('username', 'username_idx');
```

See CLAUDE.md for comprehensive migration patterns and examples.

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

# Clear caches (useful after updates)
php artisan view:clear && php artisan config:clear && php artisan route:clear && php artisan cache:clear

# Run linting commands
npm run lint
composer lint
```

## Production Deployment

### Prerequisites
- PHP 8.2+ with required extensions
- MySQL 8.0+ or compatible database
- Redis (recommended for caching)
- Web server (Apache/Nginx)
- SSL certificate

### Deployment Steps

1. **Clone and setup**:
   ```bash
   git clone [repository-url]
   cd SITE-WeWinGames
   
   # IMPORTANT: Fix npm PATH on production servers
   export PATH="/opt/nvm/versions/node/v22.17.0/bin:$PATH"
   
   composer install --optimize-autoloader --no-dev
   
   # Install npm dependencies with reduced concurrency for production servers
   npm ci --maxsockets 1
   
   # Or if updating packages
   npm update --maxsockets 1
   
   # Then build
   npm run build
   ```

2. **Environment configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure production environment** in `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=production_db
   DB_USERNAME=production_user
   DB_PASSWORD=secure_password
   
   # Stripe (Production)
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   
   # Redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   # Analytics
   GOOGLE_ANALYTICS_TAG_ID=your_ga_tag_id
   GOOGLE_TAG_MANAGER_ID=your_gtm_container_id
   
   # Security
   TURNSTILE_ENABLED=true
   TURNSTILE_SITE_KEY=your_site_key
   TURNSTILE_SECRET_KEY=your_secret_key
   
   # Push Notifications
   VAPID_PUBLIC_KEY=your_vapid_public_key
   VAPID_PRIVATE_KEY=your_vapid_private_key
   VAPID_SUBJECT=mailto:admin@yourdomain.com
   
   # Cloudflare API (for cache purging)
   CLOUDFLARE_ENABLED=true
   CLOUDFLARE_EMAIL=your_cloudflare_email
   CLOUDFLARE_API_KEY=your_cloudflare_api_key
   CLOUDFLARE_ZONE_ID=your_cloudflare_zone_id
   ```

4. **Database setup**:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=UserSeeder
   php artisan db:seed --class=StripeProductSeeder
   php artisan db:seed --class=TicketCategorySeeder
   php artisan db:seed --class=TestimonialSeeder
   ```

5. **Optimize for production**:
   ```bash
   # IMPORTANT: Always set npm PATH first on production servers
   export PATH="/opt/nvm/versions/node/v22.17.0/bin:$PATH"
   
   # Update npm packages with reduced concurrency
   npm update --maxsockets 1
   
   # Main production build commands
   php artisan optimize:clear && npm run build
   
   # Then cache configurations
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

6. **Set permissions**:
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 755 storage bootstrap/cache
   ```

### Performance Optimization

```bash
# Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1

# Queue workers (run as systemd service)
php artisan queue:work --daemon

# Scheduled tasks (add to crontab)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
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
- **Analytics Integration**: Google Analytics and Tag Manager for tracking
- **Bot Protection**: Cloudflare Turnstile for form protection
- **Support System**: Full-featured ticket system for customer support
- **Push Notifications**: Web Push API support with admin management
- **Testimonials**: Dynamic testimonials system with Google reviews integration

## NPM Commands for Production Servers

When working with npm on production servers with file descriptor limits, always use the `--maxsockets 1` flag:

```bash
# Install dependencies
npm install --maxsockets 1

# Update packages
npm update --maxsockets 1

# Install a specific package
npm install package-name --maxsockets 1

# Clean install
npm ci --maxsockets 1
```

This prevents the "EMFILE: too many open files" error common on restricted production environments.

## Recent Updates (January 2025)

### Push Notifications
- **Web Push API**: Full support for browser push notifications
- **Admin Dashboard**: Send notifications from `/admin/notifications/push`
- **User Preferences**: Users can enable/disable in profile settings
- **Targeting Options**: Send to all users, push-enabled only, or by subscription tier
- **Service Worker**: Enhanced SW with notification click handling
- **Debug Tools**: Comprehensive debug page at `/admin/notifications/push/debug`

### Content Management
- **Testimonials System**: Dynamic testimonials with Google reviews
- **Blog Integration**: Betting education page merged with blog template
- **Conditional Content**: "Stay Updated" box shows based on auth/notification status

### Email System Improvements
- **SendGrid Integration**: Fixed LoggedMailChannel for email verification
- **Email Logging**: Comprehensive email activity tracking
- **CSP Headers**: Fixed Inter font loading from rsms.me

### Bug Fixes
- **419 CSRF Error**: Fixed on team image uploads
- **Email Verification**: Fixed 500 error on resend
- **Validation Errors**: Improved error handling for form submissions

### Analytics & Tracking
- **Google Analytics**: Integrated with automatic page view tracking
- **Google Tag Manager**: Full dataLayer integration for custom events
- **E-commerce Tracking**: Support for subscription and conversion tracking

### Security Enhancements
- **Cloudflare Turnstile**: Bot protection on registration forms
- **Environment-based Configuration**: All services configurable via .env
- **Secure Headers**: Enhanced security headers for production

### UI/UX Improvements
- **DraftKings Integration**: Affiliate link integration on home page
- **Support System**: Guest and authenticated user support with ticket tracking
- **Text Visibility**: Improved contrast ratios for better readability
- **Responsive Design**: Enhanced mobile experience across all pages

### Technical Enhancements
- **TypeScript Support**: Full TypeScript coverage for Vue components
- **Composables**: New composables for Google Analytics and Tag Manager
- **Route Organization**: Cleaned up route definitions and naming
- **Performance**: Optimized asset loading and caching strategies
- **Cache Management**: Integrated Cloudflare cache purging with admin panel

## Project Structure

```
├── app/                # Laravel application
│   ├── Http/          # Controllers, Middleware, Requests
│   ├── Models/        # Eloquent models
│   ├── Services/      # Business logic
│   └── Traits/        # Reusable traits
├── resources/         # Frontend resources
│   ├── js/           # Vue.js application
│   │   ├── composables/ # Vue composition utilities
│   │   ├── components/  # Reusable components
│   │   └── pages/      # Page components
│   └── css/          # Stylesheets
├── database/         # Migrations and seeders
├── routes/           # Application routes
├── config/           # Configuration files
└── tests/            # Test files
```

## Analytics Implementation

### Google Analytics
- Automatic page view tracking on route changes
- Custom event tracking via `useGoogleAnalytics` composable
- E-commerce tracking for subscriptions

### Google Tag Manager
- Full dataLayer support
- Custom event pushing via `useGoogleTagManager` composable
- Enhanced e-commerce tracking capabilities

Example usage:
```typescript
import { useGoogleAnalytics } from '@/composables/useGoogleAnalytics';
import { useGoogleTagManager } from '@/composables/useGoogleTagManager';

// Track custom events
const { trackEvent } = useGoogleAnalytics();
trackEvent('button_click', { category: 'engagement', label: 'header' });

// Push to dataLayer
const { pushToDataLayer } = useGoogleTagManager();
pushToDataLayer({ event: 'subscription_started', tier: 'gold' });
```

## Security Features

### Cloudflare Turnstile
- Enabled on registration forms
- Configurable via environment variables
- Backend validation for all submissions

### Environment Security
- All sensitive keys in .env file
- Proper validation and sanitization
- CSRF protection on all forms
- XSS prevention via Vue.js

## Documentation

For detailed documentation, see [CLAUDE.md](CLAUDE.md) which contains:
- Complete technology stack details
- Database schema
- API endpoints
- Development guidelines
- Deployment instructions
- Recent changes and best practices

## Contributing

1. Follow PSR-12 coding standards for PHP
2. Use TypeScript for all Vue components
3. Run tests before submitting PRs
4. Follow the existing code style and patterns
5. Ensure all analytics events are properly tracked
6. Maintain security best practices

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run JavaScript tests
npm run test
```

## License

This project is proprietary software. All rights reserved.

## Support

For questions or issues:
- Use the in-app support system at `/support`
- Contact the development team
- Check the detailed documentation in CLAUDE.md