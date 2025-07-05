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
- **CSS**: Bootstrap 5 (converted from Tailwind CSS in December 2024)
- **UI Components**: Bootstrap 5 components with custom admin theme
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
# Fix npm PATH if needed (common on production servers)
export PATH="/opt/nvm/versions/node/v22.17.0/bin:$PATH"

# Install dependencies
npm install

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

## UI/UX Updates (December 2024)

### Bootstrap 5 Migration

The platform has been completely migrated from Tailwind CSS to Bootstrap 5 for improved consistency and maintainability:

1. **Admin Portal Redesign**:
   - Complete conversion of AdminLayout to Bootstrap 5
   - Dark sidebar with light main content area
   - Consistent navigation with parent/child expansion states
   - Improved hover effects and active states
   - Responsive design optimized for mobile and desktop

2. **Import System Overhaul**:
   - CSV import wizard completely redesigned with Bootstrap components
   - Multi-step process with progress indicators
   - Form validation with Bootstrap styling
   - Improved data tables and preview functionality
   - Better error handling and user feedback

3. **Navigation Improvements**:
   - Smart navigation expansion for current page parents
   - Enhanced hover states and visual feedback
   - Clean navigation structure with proper grouping
   - Removed duplicate elements and optimized layout

4. **Form and Table Consistency**:
   - All admin forms converted to Bootstrap form controls
   - Consistent table styling across all admin pages
   - Proper validation states and error messaging
   - Improved accessibility with ARIA labels

5. **Component Library**:
   - Standardized Bootstrap button variants
   - Consistent card layouts and spacing
   - Unified modal designs
   - Progress bars and status indicators

### Key Technical Improvements

1. **CSS Architecture**:
   - Removed Tailwind dependencies
   - Custom Bootstrap theme for admin area
   - Consistent color scheme and typography
   - Optimized bundle size

2. **Component Structure**:
   - Vue 3 components optimized for Bootstrap
   - TypeScript interfaces for props validation
   - Consistent naming conventions
   - Improved reusability

3. **Performance Optimizations**:
   - Reduced CSS bundle size
   - Improved loading times
   - Better caching strategies
   - Optimized build process

### Development Commands Updated

```bash
# Build with Bootstrap optimizations
npm run build

# Development with hot reload
npm run dev

# Type checking with Bootstrap types
npm run typecheck

# Format code (includes Vue templates)
npm run format
```

### Testing and Quality Assurance

1. **Cross-browser Testing**:
   - Chrome, Firefox, Safari compatibility
   - Mobile responsive design verification
   - Touch interface optimization

2. **Accessibility Improvements**:
   - WCAG 2.1 compliance
   - Keyboard navigation support
   - Screen reader compatibility
   - High contrast mode support

3. **Performance Metrics**:
   - Lighthouse score improvements
   - Core Web Vitals optimization
   - Bundle size reduction
   - Loading time improvements

## Recent Updates (January 2025)

### Analytics Integration

1. **Google Analytics**:
   - Tag ID: `G-ZTJTTQP72Q` (configurable via `GOOGLE_ANALYTICS_TAG_ID`)
   - Automatic page view tracking on route changes
   - Custom event tracking via `useGoogleAnalytics` composable
   - E-commerce tracking for subscriptions
   - Implementation in `app.blade.php` and `app.ts`

2. **Google Tag Manager**:
   - Container ID: `GTM-PQDDCG6L` (configurable via `GOOGLE_TAG_MANAGER_ID`)
   - Full dataLayer support
   - Custom event pushing via `useGoogleTagManager` composable
   - Enhanced e-commerce tracking
   - Proper placement in head and body tags

3. **Analytics Composables**:
   ```typescript
   // resources/js/composables/useGoogleAnalytics.ts
   - trackEvent(eventName, parameters)
   - trackPageView(path)
   - trackEcommerce(event, parameters)
   
   // resources/js/composables/useGoogleTagManager.ts
   - pushToDataLayer(data)
   - trackEvent(eventName, parameters)
   - trackEcommerce(eventType, data)
   - trackUserData(userData)
   ```

### Security Enhancements

1. **Cloudflare Turnstile**:
   - Site Key: `0x4AAAAAABjA9oaFF9BSsznw`
   - Secret Key: `0x4AAAAAABjA9iC5axcso_Tat1vZ1G-JsZc`
   - Enabled on registration forms
   - Backend validation in `RegisterRequest.php`
   - Configuration via `TURNSTILE_ENABLED`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`

2. **Environment Configuration**:
   - All third-party services now configurable via `.env`
   - Proper validation and fallbacks
   - Secure key storage

### UI/UX Improvements

1. **DraftKings Integration**:
   - Affiliate link added to home page
   - Responsive card design
   - Proper tracking parameters
   - Located in subscription plans section

2. **Support System Enhancements**:
   - Fixed route conflicts (`/support` vs `/support/tickets`)
   - Guest support without authentication
   - Improved text visibility (fixed grey-on-grey issue)
   - Better contrast ratios throughout

3. **Home Page Updates**:
   - Slimmer DraftKings promotional section
   - Horizontal layout for better space utilization
   - Improved responsive design

### Technical Improvements

1. **Route Organization**:
   - Fixed duplicate route names
   - Proper route grouping and prefixes
   - Consistent naming conventions
   - Support routes properly separated

2. **Configuration Updates**:
   - New `config/google.php` for Google services
   - Updated `HandleInertiaRequests.php` to share analytics config
   - Environment variables properly documented

3. **Bug Fixes**:
   - Fixed PHP syntax error in `SubscriptionDashboardController.php`
   - Fixed missing closing braces in cache callback
   - Corrected undefined variable references
   - Fixed MRR calculation variable names

### Development Workflow

1. **New Commands**:
   ```bash
   # Clear and rebuild route cache
   php artisan route:clear && php artisan route:cache
   
   # Test configuration
   php artisan tinker --execute="echo config('google.analytics.tag_id');"
   ```

2. **Testing**:
   - Added `SupportAccessTest.php` for support system validation
   - All tests passing for guest and authenticated support access

3. **Documentation**:
   - Updated README.md with all new features
   - Added analytics usage examples
   - Documented all environment variables
   - Created composables documentation

## Contact and Support

For questions or issues:
- Use the in-app support system at `/support`
- Check Laravel documentation: https://laravel.com/docs
- Vue.js documentation: https://vuejs.org/
- Inertia.js documentation: https://inertiajs.com/

# Important Instruction Reminders

## Code Development Guidelines

1. **File Creation**: 
   - NEVER create files unless they're absolutely necessary
   - ALWAYS prefer editing existing files over creating new ones
   - NEVER proactively create documentation files (*.md) or README files unless explicitly requested

2. **Code Style**:
   - DO NOT add comments unless specifically asked
   - Follow existing code patterns and conventions
   - Use existing libraries and utilities rather than introducing new ones

3. **Security**:
   - Never expose or log secrets and keys
   - Never commit sensitive information
   - Always follow security best practices

4. **Task Management**:
   - Complete exactly what was asked - nothing more, nothing less
   - Mark todos as completed immediately after finishing tasks
   - Use the TodoWrite tool for complex multi-step tasks

5. **Testing and Validation**:
   - Run lint and typecheck commands after completing tasks
   - Verify solutions with appropriate tests
   - Check for and fix any syntax errors before marking tasks complete

6. **Communication**:
   - Keep responses concise (under 4 lines unless detail requested)
   - Answer user questions directly without elaboration
   - Avoid unnecessary preambles or summaries

## Environment Variables Summary

All sensitive configuration should be stored in `.env`:

```env
# Application
APP_DEBUG=false  # CRITICAL: Must be false in production
APP_ENV=production

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wewingames
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Stripe
STRIPE_KEY=your_publishable_key
STRIPE_SECRET=your_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret

# Google Services
GOOGLE_ANALYTICS_TAG_ID=G-ZTJTTQP72Q
GOOGLE_TAG_MANAGER_ID=GTM-PQDDCG6L

# Cloudflare Turnstile
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=0x4AAAAAABjA9oaFF9BSsznw
TURNSTILE_SECRET_KEY=0x4AAAAAABjA9iC5axcso_Tat1vZ1G-JsZc

# Notifications (Optional)
SLACK_BOT_USER_OAUTH_TOKEN=your_token
SLACK_BOT_USER_DEFAULT_CHANNEL=your_channel
POSTMARK_TOKEN=your_token
RESEND_KEY=your_key
```