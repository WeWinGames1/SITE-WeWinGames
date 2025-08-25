# WeWinGames - Sports Betting Platform

## Overview
WeWinGames is a comprehensive sports betting information and picks service built with Laravel 12 and Vue.js 3. The platform provides betting recommendations, game analysis, subscription-based access to premium picks, and a full suite of content management and user engagement features.

## Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.0 / SQLite (local development)
- **Cache**: File/Redis with Cloudflare integration
- **Queue**: Laravel Queue with database driver
- **Authentication**: Laravel Breeze with Inertia.js
- **Billing**: Laravel Cashier (Stripe integration)
- **SSR**: Inertia.js v2

### Frontend
- **Framework**: Vue.js 3 with TypeScript
- **Build Tool**: Vite 6
- **CSS**: Bootstrap 5 (migrated from Tailwind CSS)
- **UI Components**: Bootstrap 5 components with custom admin theme
- **Rich Text**: TinyMCE and Tiptap editors
- **Charts**: Chart.js with vue-chartjs
- **3D Graphics**: Three.js
- **Icons**: Bootstrap Icons, Lucide Vue, Heroicons

### Third-Party Services
- **Payment**: Stripe (with dynamic product management)
- **Push Notifications**: OneSignal & Web Push API
- **Analytics**: Google Analytics & Tag Manager
- **Security**: Cloudflare Turnstile
- **Email**: SendGrid (via LoggedMailChannel)
- **Monitoring**: Laravel Telescope
- **Media**: Spatie Media Library
- **Permissions**: Spatie Laravel Permission
- **Activity Logging**: Spatie Activity Log

## Project Structure

```
.
├── app/
│   ├── Console/           # Artisan commands
│   ├── Events/            # Event classes
│   ├── Http/
│   │   ├── Controllers/   # All controllers including Admin/
│   │   ├── Middleware/    # Custom middleware
│   │   └── Requests/      # Form requests
│   ├── Models/            # Eloquent models (30+ models)
│   ├── Services/          # Business logic services
│   ├── Policies/          # Authorization policies
│   └── Mail/              # Mailable classes
├── resources/
│   ├── js/
│   │   ├── components/    # Reusable Vue components
│   │   ├── pages/         # Page components
│   │   ├── layouts/       # Layout components
│   │   └── composables/   # Vue composition utilities
│   └── css/               # Bootstrap-based styles
├── routes/                # Application routes
├── database/              # Migrations, seeders, factories
├── tests/                 # PHPUnit & Feature tests
└── docker/                # Docker configuration
```

## Key Features

### 1. Betting System
- **Bet Management**: Create, edit, track betting picks with performance metrics
- **Parlay Support**: Multi-bet parlays with combined odds
- **Golf Betting**: Each-way bets with place fractions and dead heat rules
- **CSV Import/Export**: Wizard-based bulk data management
- **Mass Edit**: Batch updates for golf positions
- **Premium Notes**: Subscriber-only betting insights
- **Profit Tracking**: Detailed P&L calculations

### 2. User & Subscription System
- **User Types**: Regular, Ambassador, Gifted, Admin
- **Subscription Tiers**: Bronze, Silver, Gold, Platinum
- **Billing Periods**: Daily, Weekly, Monthly, Yearly
- **Dynamic Stripe Products**: Database-driven product management
- **Discount Codes**: Percentage/fixed with usage limits
- **Affiliate System**: Track and manage affiliates
- **Impersonation**: Admin user switching

### 3. Content Management
- **Blog System**: Full-featured with SEO, categories, view tracking
- **CMS Pages**: Dynamic page creation and management
- **Landing Pages**: Marketing-focused pages
- **FAQ System**: Categorized Q&A management
- **Knowledgebase**: Article-based help system
- **Media Library**: Centralized file management
- **Testimonials**: Customer reviews with Google integration

### 4. Communication Features
- **Email System**: 
  - Template management
  - Full logging with SendGrid
  - Customizable transactional emails
- **Push Notifications**:
  - OneSignal integration (NEW)
  - Web Push API fallback
  - Tier-based targeting
  - Notification history
- **Support Tickets**: Guest-accessible support system

### 5. Career/Jobs System
- **Job Positions**: Manage job listings
- **Resume Submissions**: Application tracking system
- **Admin Review**: Application management interface

### 6. Admin Dashboard
Located at `/admin`, provides:
- **Statistics Dashboard**: MRR, user growth, betting activity
- **User Management**: Complete user administration
- **Bet Management**: Full CRUD with import/export
- **Content Editing**: Pages, posts, FAQs, testimonials
- **Subscription Dashboard**: Customer & revenue tracking
- **System Settings**: Configuration management
- **Activity Logs**: User action tracking
- **Cache Management**: Clear Laravel & Cloudflare cache

### 7. Security & Performance
- **Middleware**: 
  - Admin security headers
  - Rate limiting
  - IP blacklisting
  - Spam prevention
- **Under Construction Mode**: Site-wide maintenance
- **Cloudflare Integration**: CDN & cache management
- **Session Security**: CSRF protection

## Development Commands

### Quick Start
```bash
# Install dependencies
composer install && npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Start development
composer dev
```

### Available Scripts
```bash
# Development
composer dev              # All services (recommended)
composer dev:ssr          # With SSR
npm run dev              # Vite only
npm run build            # Production build

# Code Quality
composer format          # Format PHP (Pint)
npm run format          # Format JS/TS (Prettier)
npm run lint            # ESLint
npm run typecheck       # TypeScript check

# Testing
php artisan test         # Run all tests
php artisan test --filter TestName
php artisan test --coverage

# Database
php artisan migrate:fresh --seed  # Reset database
php artisan db:seed --class=ProductionSeeder  # Production data only
```

## Database Schema

### Core Tables
- `users` - User accounts with ambassador/gifted fields
- `bets` - Betting picks and predictions
- `games` - Sporting events
- `teams` - Sports teams with aliases
- `sports` - Sport categories
- `operators` - Betting operators/bookmakers
- `leagues` - Team leagues
- `subscriptions` - Laravel Cashier subscriptions
- `stripe_products` - Dynamic Stripe configuration
- `discount_codes` - Coupon management
- `discount_redemptions` - Usage tracking

### Content Tables
- `pages` - CMS pages
- `landing_pages` - Marketing pages
- `posts` - Blog posts
- `blog_categories` - Blog categorization
- `faqs` - FAQ entries
- `faq_categories` - FAQ organization
- `testimonials` - Customer reviews
- `knowledgebase_articles` - Help articles
- `knowledgebase_categories` - KB organization

### Communication Tables
- `support_tickets` - Support system
- `support_ticket_replies` - Ticket responses
- `notifications` - System notifications
- `push_subscriptions` - Web Push subscriptions
- `push_notification_logs` - Push history
- `email_logs` - Email tracking
- `email_templates` - Email customization

### Career Tables
- `job_positions` - Job listings
- `resume_submissions` - Applications

### System Tables
- `activity_log` - User activity tracking
- `media` - Spatie media library
- `affiliates` - Affiliate management
- `sport_user` - User sport preferences
- `team_logos` - Team branding

## API Routes

### Public API
```
POST   /login                    - User authentication
POST   /register                 - User registration  
POST   /logout                   - User logout
POST   /forgot-password          - Password reset

GET    /api/bets                 - List bets
POST   /api/bets                 - Create bet
GET    /api/games                - List games
GET    /api/sports               - List sports
GET    /api/user                 - Current user
PUT    /api/user/profile         - Update profile
POST   /api/user/subscription    - Manage subscription

POST   /api/push/subscribe       - Subscribe to push
DELETE /api/push/unsubscribe     - Unsubscribe from push
```

### Admin API
```
POST   /admin/cache/clear        - Clear all caches
GET    /admin/api/customers/search - Search customers
POST   /admin/notifications/push/send - Send push notification
POST   /admin/notifications/push/test - Test notification
```

## Environment Variables

### Required
```env
APP_NAME=WeWinGames
APP_ENV=local
APP_URL=http://wewingames.test
APP_DEBUG=false  # MUST be false in production

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wewingames
DB_USERNAME=sail
DB_PASSWORD=password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp  # or 'log' for development
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@wewingames.com

# Stripe
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Admin
ADMIN_PASSWORD=YourSecurePassword123!  # For production seeder
```

### Optional Services
```env
# Push Notifications
VAPID_PUBLIC_KEY=your_public_key
VAPID_PRIVATE_KEY=your_private_key
VAPID_SUBJECT=mailto:admin@wewingames.com

# Analytics
GOOGLE_ANALYTICS_TAG_ID=G-ZTJTTQP72Q
GOOGLE_TAG_MANAGER_ID=GTM-PQDDCG6L

# Security
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=0x4AAAAAABjA9oaFF9BSsznw
TURNSTILE_SECRET_KEY=0x4AAAAAABjA9iC5axcso_Tat1vZ1G-JsZc

# Cloudflare
CLOUDFLARE_ENABLED=true
CLOUDFLARE_EMAIL=your_email
CLOUDFLARE_API_KEY=your_api_key
CLOUDFLARE_ZONE_ID=your_zone_id

# Notifications (Optional)
SLACK_BOT_USER_OAUTH_TOKEN=xoxb-xxx
SLACK_BOT_USER_DEFAULT_CHANNEL=#alerts
POSTMARK_TOKEN=xxx
RESEND_KEY=xxx
```

## Deployment

### Production Build & Deploy
```bash
# Fix npm PATH if needed
export PATH="/opt/nvm/versions/node/v22.17.0/bin:$PATH"

# Install with reduced concurrency (prevents EMFILE errors)
npm install --maxsockets 1

# Build assets
npm run build

# Optimize Laravel
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Run migrations
php artisan migrate --force

# Seed production data
php artisan db:seed --class=ProductionSeeder
```

### Server Requirements
- PHP 8.2+ with extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- MySQL 8.0+ or MariaDB 10.3+
- Redis 6.0+
- Node.js 18+ & npm 8+
- Composer 2+
- Minimum 2GB RAM
- SSL certificate for HTTPS

## Coding Standards

### PHP
- PSR-12 coding standards
- Laravel Pint for formatting
- Type declarations required
- Service pattern for business logic
- Repository pattern for data access

### JavaScript/TypeScript
- TypeScript for all new code
- Vue 3 Composition API
- ESLint & Prettier formatting
- Component-based architecture
- Proper type interfaces

### Git Workflow
- Feature branches from `main`
- Descriptive commit messages
- PR reviews required
- Run tests before merging
- Squash merge for features

## Security Best Practices

- Input validation on all forms
- CSRF protection enabled
- XSS prevention via Vue
- SQL injection prevention via Eloquent
- API rate limiting configured
- Secure session management
- Environment variables for secrets
- Regular dependency updates

## Performance Optimization

- Redis caching for frequent queries
- Database indexing on foreign keys
- Lazy loading for Vue components
- Image optimization with Vite
- Query optimization with eager loading
- CDN for static assets
- Gzip compression enabled
- Browser caching headers

## Common Tasks

### Adding a New Feature
1. Create migration: `php artisan make:model ModelName -mfsc`
2. Define relationships and fillable
3. Create policy: `php artisan make:policy ModelPolicy`
4. Add routes in appropriate file
5. Create controller with CRUD actions
6. Build Vue components
7. Add to admin navigation if needed
8. Write tests

### Managing Subscriptions
1. Create/update Stripe products in `/admin/stripe-products`
2. Set pricing and features
3. Enable/disable products
4. Monitor in subscription dashboard

### Sending Push Notifications
1. Access `/admin/notifications/push`
2. Compose notification
3. Select target audience
4. Send or schedule
5. Monitor delivery

## Migration Best Practices

### Naming Conventions
- Use descriptive names: `2025_01_15_create_testimonials_table.php`
- Name indexes under 64 chars: `disc_redemptions_unique`
- Check column existence before adding
- Write proper rollback methods

### Common Pitfalls to Avoid
- Long auto-generated index names
- Composite indexes on string columns exceeding key length
- Missing down() methods
- Raw SQL for enum changes
- Incorrect migration ordering

## Testing

### Run Tests
```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage

# Specific test
php artisan test --filter=SubscriptionTest
```

### Test Database
```bash
# Use separate test database
DB_CONNECTION=mysql_test
DB_DATABASE=wewingames_test
```

## Troubleshooting

### Common Issues

1. **419 CSRF Error**
   ```bash
   php artisan optimize:clear
   # Clear browser cookies
   # Check SESSION_DOMAIN
   ```

2. **Vite Connection Error**
   ```bash
   npm run build
   # Or restart: npm run dev
   ```

3. **Migration Errors**
   ```bash
   # Check migration status
   php artisan migrate:status
   
   # Fix key length for old MySQL
   # Already handled in AppServiceProvider
   ```

4. **Email Not Sending**
   ```bash
   # Check MAIL_MAILER in .env
   # Use 'log' for testing
   # Check storage/logs/laravel.log
   ```

5. **Stripe Webhook Errors**
   ```bash
   # Verify webhook secret
   # Check Stripe logs
   # Test with Stripe CLI
   ```

## Recent Updates (January 2025)

### New Features
1. **OneSignal Integration**: Push notifications for non-admin pages
2. **Enhanced Media Library**: Centralized file management
3. **Knowledgebase System**: Help articles and categories
4. **Job Board**: Career opportunities management
5. **Affiliate System**: Commission tracking
6. **Activity Logging**: Comprehensive user tracking

### Improvements
1. **Performance**: Reduced query counts with eager loading
2. **Security**: Enhanced middleware and validation
3. **UX**: Bootstrap 5 migration for consistency
4. **Admin**: Redesigned dashboard with better analytics
5. **SEO**: Improved meta tags and structured data

### Bug Fixes
1. Fixed 419 CSRF errors on file uploads
2. Resolved email verification 500 errors
3. Corrected TypeScript null checks
4. Fixed admin cache clearing 403 error
5. Improved form validation messages

## Support & Documentation

- Laravel Docs: https://laravel.com/docs/12.x
- Vue.js Docs: https://vuejs.org/
- Inertia Docs: https://inertiajs.com/
- Bootstrap Docs: https://getbootstrap.com/docs/5.3/
- Stripe Docs: https://stripe.com/docs

## Contact

For questions or issues:
- Use in-app support system at `/support`
- Check activity logs in admin dashboard
- Review Laravel Telescope for debugging