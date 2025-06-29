# WeWinGames - Sports Betting Platform

## Overview
WeWinGames is a full-stack sports betting information and picks service built with Laravel 12 and Vue.js 3. The platform provides betting recommendations, game analysis, and subscription-based access to premium picks.

## Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.0 / SQLite (for local development)
- **Cache**: File/Redis
- **Queue**: Laravel Queue with database driver
- **Authentication**: Laravel Breeze with Inertia.js
- **Billing**: Laravel Cashier (Stripe integration)
- **SSR**: Inertia.js

### Frontend
- **Framework**: Vue.js 3 with TypeScript
- **Build Tool**: Vite
- **CSS**: Tailwind CSS v4
- **UI Components**: Reka UI (custom component library)
- **Rich Text**: TinyMCE and Tiptap editors
- **Charts**: Chart.js
- **3D Graphics**: Three.js

## Project Structure

```
.
├── app/                    # Laravel application logic
│   ├── Console/           # Artisan commands
│   ├── Events/            # Event classes
│   ├── Http/              # Controllers, middleware, requests
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic services
│   └── Policies/          # Authorization policies
├── resources/             # Frontend resources
│   ├── js/               # Vue application
│   │   ├── components/   # Reusable Vue components
│   │   ├── pages/        # Page components
│   │   ├── layouts/      # Layout components
│   │   └── composables/  # Vue composition utilities
│   └── css/              # Stylesheets
├── routes/               # Application routes
├── database/             # Migrations and seeds
├── tests/                # PHPUnit tests
└── docker/               # Docker configuration
```

## Key Features

### 1. Betting System
- **Models**: Bet, Game, Operator, Sport, Team
- **Features**:
  - Create and manage betting picks
  - Track bet performance and profit
  - Batch upload betting data
  - Real-time odds tracking

### 2. User Management
- **Authentication**: Email/password with social login support
- **Roles**: Admin, Subscriber, Free User
- **Permissions**: Granular permission system using Spatie Laravel Permission
- **Profile Management**: User settings and preferences

### 3. Subscription System
- **Billing**: Stripe integration via Laravel Cashier
- **Plans**: Multiple subscription tiers (Bronze, Silver, Gold, Platinum)
- **Features**: 
  - Subscription management
  - Payment method updates
  - Invoice history
  - Coupon/discount code support
  - Dynamic Stripe product management
  - Ambassador/gifted user privileges

### 4. Content Management
- **Pages**: Dynamic page creation and management
- **Landing Pages**: Marketing pages with customizable content
- **Blog**: Full blog system with categories and tags
- **Rich Text Editing**: TinyMCE and Tiptap support

### 5. Admin Dashboard
Located at `/admin`, provides:
- User management with ambassador/gifted privileges
- Bet and game management with CSV import/export
- Content editing (pages, blog posts, landing pages)
- Analytics and reporting
- System settings
- Stripe product management
- Tier-based notification system

## Development Commands

### Getting Started
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start development server
composer dev
```

### Available Scripts
- `composer dev` - Run all development services
- `composer dev:ssr` - Run with SSR enabled
- `npm run dev` - Start Vite dev server
- `npm run build` - Production build
- `npm run typecheck` - Run TypeScript checks
- `composer format` - Format PHP code with Pint
- `npm run format` - Format JS/TS code with Prettier

### Testing
```bash
# Run PHP tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## Database Schema

### Core Tables
- `users` - User accounts (with ambassador/gifted/override fields)
- `bets` - Betting picks and predictions
- `games` - Sporting events
- `teams` - Sports teams
- `sports` - Sport categories
- `operators` - Betting operators/bookmakers
- `subscriptions` - User subscriptions (Laravel Cashier)
- `stripe_products` - Stripe product configurations
- `coupon_usage` - Tracks coupon/discount usage
- `team_logos` - Team logo management
- `pages` - CMS pages
- `posts` - Blog posts
- `notifications` - Enhanced with tier targeting

## API Routes

### Authentication
- `POST /login` - User login
- `POST /register` - User registration
- `POST /logout` - User logout
- `POST /forgot-password` - Password reset

### Betting API
- `GET /api/bets` - List bets
- `POST /api/bets` - Create bet
- `GET /api/games` - List games
- `GET /api/sports` - List sports

### User API
- `GET /api/user` - Current user
- `PUT /api/user/profile` - Update profile
- `POST /api/user/subscription` - Manage subscription

## Environment Variables

Key environment variables:
```
APP_NAME=WeWinGames
APP_ENV=local
APP_URL=http://wewingames.test

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=wewingames
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
```

## Deployment

### Production Build
```bash
# Build frontend assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

### Server Requirements
- PHP 8.2+
- MySQL 8.0+
- Redis
- Node.js 18+
- Composer 2+

## Coding Standards

### PHP
- Follow PSR-12 coding standards
- Use Laravel Pint for formatting
- Type declarations required
- Service pattern for business logic

### JavaScript/TypeScript
- Use TypeScript for all new code
- Follow Vue 3 Composition API
- ESLint and Prettier for formatting
- Component-based architecture

### Git Workflow
- Feature branches from `main`
- Descriptive commit messages
- PR reviews required
- Run tests before merging

## Security Considerations

- All user input sanitized
- CSRF protection enabled
- XSS prevention via Vue
- SQL injection prevention via Eloquent
- API rate limiting configured
- Secure session management

## Performance Optimization

- Redis caching for frequently accessed data
- Database indexing on foreign keys
- Lazy loading for Vue components
- Image optimization with Vite
- Query optimization with eager loading
- SSR for improved SEO

## Monitoring and Debugging

- Laravel Telescope for local debugging
- Error logging to `storage/logs`
- Database query logging available
- Performance monitoring hooks ready

## Common Tasks

### Adding a New Page
1. Create route in `routes/web.php`
2. Create controller in `app/Http/Controllers`
3. Create Vue page in `resources/js/pages`
4. Add navigation link if needed

### Creating a New Model
1. Run `php artisan make:model ModelName -mfc`
2. Define database schema in migration
3. Set up relationships in model
4. Create policy for authorization
5. Add routes and controller logic

### Adding a New Admin Feature
1. Create controller in `app/Http/Controllers/Admin`
2. Add routes to `routes/admin.php`
3. Create Vue components in `resources/js/pages/Admin`
4. Add menu item in admin layout

## Troubleshooting

### Common Issues
1. **Vite not connecting**: Check that Vite server is running on correct port
2. **Database errors**: Ensure migrations are run and seeded
3. **Permission denied**: Check file permissions and ownership
4. **Redis connection**: Verify Redis is running in Docker

### Debug Mode
Enable debug mode in `.env`:
```
APP_DEBUG=true
APP_ENV=local
```

## Recent Updates and Best Practices

### Code Organization (December 2024)
1. **Route Organization**: 
   - All routes now use controller methods instead of closures
   - Route files are properly registered in `bootstrap/app.php` using Laravel 12's routing configuration
   - Admin routes are consistently grouped with proper middleware

2. **Database-Driven Content**:
   - Betting education content migrated from Vue components to database pages
   - All blog posts are now stored in the database
   - Dynamic content management through admin panel

3. **Environment Configuration**:
   - All third-party service keys properly configured in `.env.example`
   - Stripe, Slack, Postmark, and Resend integrations documented
   - Local development optimized for SQLite

### Critical Updates (December 2024)
1. **Security**: Debug mode disabled for production (`APP_DEBUG=false`)
2. **Ambassador/Gifted Users**: Fixed daily privilege resets with database fields
3. **Stripe Integration**: 
   - Dynamic product management system
   - Correct product/price mapping
   - Coupon support at checkout
4. **Notifications**: Tier-based targeting for Silver/Gold/Platinum users
5. **CSV Import/Export**: Consistent 16-column format
6. **Security Headers**: Comprehensive middleware implemented

### Laravel Best Practices Implemented
1. **Controllers**: All route logic moved to dedicated controllers
2. **Service Layer**: Business logic separated into service classes
3. **Consistent Middleware**: Admin routes use consistent middleware stack
4. **Clean Imports**: Removed unused imports and dependencies
5. **Proper Configuration**: All config values use env() with defaults

### Testing
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
```

### Database Seeders
```bash
# Seed test users (admin and subscriber)
php artisan db:seed --class=UserSeeder

# Seed betting education pages
php artisan db:seed --class=BettingEducationSeeder

# Seed all blog posts
php artisan db:seed --class=BlogPostsSeeder

# Seed sample blog posts with rich content
php artisan db:seed --class=SampleBlogPostsSeeder

# Seed Stripe products (all tiers and billing periods)
php artisan db:seed --class=StripeProductSeeder
```

### Stripe Product Management

The platform now includes a comprehensive Stripe product management system:

1. **Access**: Navigate to `/admin/stripe-products`
2. **Features**:
   - Create local product configurations
   - Connect to existing Stripe products
   - Create new products in Stripe
   - Manage prices and features
   - Enable/disable products

3. **Workflow**:
   - Products are stored locally for fast access
   - Can be connected to Stripe products/prices
   - Automatically used for subscription checkout
   - Supports all tiers: Silver, Gold, Platinum
   - Supports all billing periods: Daily, Weekly, Monthly

### Blog System

A full-featured blog system with rich text editing and SEO optimization:

1. **Admin Features** (`/admin/blog-posts`):
   - Rich text editor (TinyMCE) with image upload
   - SEO fields (meta title, description, keywords)
   - Categories and tags
   - Draft/Published/Scheduled posts
   - Featured images
   - View statistics
   - Duplicate posts feature

2. **Public Blog** (`/blog`):
   - Responsive design
   - Category and tag filtering
   - Search functionality
   - Related posts
   - Social sharing
   - View count tracking
   - Reading time estimation

3. **Key Features**:
   - Automatic slug generation
   - SEO-friendly URLs
   - Rich content support
   - Media management
   - Performance optimized

### Subscription Dashboard

Comprehensive subscription management system:

1. **Access**: `/admin/subscriptions`
2. **Features**:
   - View all active subscriptions
   - Filter by status, tier, renewal period
   - Export customer data
   - Grant manual subscriptions
   - Cancel subscriptions
   - MRR calculations
   - Renewal forecasting

### Discount Code System

Full discount code management:

1. **Access**: `/admin/discounts`
2. **Features**:
   - Create discount codes (percentage or fixed amount)
   - Set usage limits (total and per customer)
   - Validity periods
   - Apply to first payment, forever, or specific months
   - Product-specific discounts
   - Stripe coupon integration
   - Redemption tracking

### Enhanced Admin Portal (December 2024)

The admin portal has been completely redesigned with improved UX and comprehensive features:

1. **Custom Admin Login** (`/admin/login`):
   - Professional admin-specific login page
   - Animated background with floating icons
   - Security-focused design
   - Quick stats preview

2. **Admin Dashboard** (`/admin`):
   - Comprehensive statistics overview
   - Real-time activity monitoring
   - System health indicators
   - Interactive charts (user growth, revenue, betting activity)
   - Subscription tier breakdown
   - Recent activity feed

3. **Dedicated Admin Layout**:
   - Dark sidebar navigation
   - Hierarchical menu structure
   - Quick search functionality
   - User profile dropdown
   - Responsive design

4. **Betting Management System**:
   - Full CRUD operations for bets
   - Advanced filtering (status, sport, date range)
   - Bulk status updates
   - Statistics and analytics
   - Import/Export functionality

5. **Admin Features Organization**:
   - **Betting**: Bets, Games, Teams, Sports, Operators
   - **Users**: Customers, Subscriptions, Admin Users
   - **Content**: Blog Posts, Pages, Landing Pages
   - **E-commerce**: Stripe Products, Discount Codes
   - **Communications**: Notifications, Email Templates
   - **Settings**: System configuration

6. **System Monitoring**:
   - Database size tracking
   - Storage usage monitoring
   - Queue status
   - Error log tracking
   - Performance metrics

## Contact and Support

For questions or issues:
- Check Laravel documentation: https://laravel.com/docs
- Vue.js documentation: https://vuejs.org/
- Inertia.js documentation: https://inertiajs.com/