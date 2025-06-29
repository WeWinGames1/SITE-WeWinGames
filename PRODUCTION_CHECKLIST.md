# Production Deployment Checklist - WeWinGames

## ✅ Completed Production Fixes

### 🔒 Security & Configuration
- [x] Updated `.env.example` with production-safe defaults
  - `APP_DEBUG=false`
  - `APP_ENV=production`
  - `SESSION_ENCRYPT=true`
  - `SESSION_SECURE_COOKIE=true`
  - `LOG_LEVEL=error`
- [x] Hidden sensitive user fields in API responses
  - Added IP addresses and user agent to `$hidden` array in User model
- [x] Removed all console statements from Vue components
  - 24 console statements commented out across 12 files

### 🚀 Performance Optimizations
- [x] Added comprehensive database indexes
  - Created migration `2025_06_29_add_performance_indexes.php`
  - Indexes on all foreign keys
  - Composite indexes for common query patterns
  - Indexes on date fields used for filtering
- [x] Implemented pagination for list endpoints
  - PageService: 20 items per page
  - CustomerController: 50 items per page
  - BetService: 50 items per page
- [x] Added eager loading to prevent N+1 queries
  - UserRepository: Added `with('roles')`
  - PageService: Added `with('author')`
  - AdminToolsController: Added `with('roles')`

## 🚨 Pre-Deployment Checklist

### 1. Environment Configuration
- [ ] Copy `.env.production.example` to `.env`
- [ ] Set unique `APP_KEY` using `php artisan key:generate`
- [ ] Configure database credentials
- [ ] Set up Redis connection
- [ ] Configure mail settings (Postmark/Resend)
- [ ] Set Stripe API keys
- [ ] Configure Slack webhook URL
- [ ] Set proper `APP_URL`
- [ ] Ensure `FORCE_HTTPS=true` in production

### 2. Database Setup
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Run performance indexes migration
- [ ] Seed initial data if needed: `php artisan db:seed`
- [ ] Verify database backups are configured

### 3. Optimization Commands
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Build frontend assets
npm run build

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 4. Server Configuration
- [ ] PHP 8.2+ installed
- [ ] Required PHP extensions enabled
- [ ] Web server configured (Nginx/Apache)
- [ ] SSL certificate installed
- [ ] Queue worker configured and supervised
- [ ] Cron job for Laravel scheduler
- [ ] File permissions set correctly

### 5. Security Checks
- [ ] Verify CSRF protection is enabled
- [ ] Check security headers middleware is active
- [ ] Validate Stripe webhook signatures
- [ ] Review API rate limiting settings
- [ ] Ensure admin routes are protected
- [ ] Check file upload restrictions

### 6. Monitoring Setup
- [ ] Configure error tracking (Sentry/Bugsnag)
- [ ] Set up application monitoring (New Relic/Datadog)
- [ ] Configure uptime monitoring
- [ ] Set up log aggregation
- [ ] Configure database query monitoring
- [ ] Set up alerts for critical errors

### 7. Testing
- [ ] Run test suite: `php artisan test`
- [ ] Perform manual testing of critical flows:
  - [ ] User registration and login
  - [ ] Subscription purchase
  - [ ] Bet viewing and filtering
  - [ ] Admin functions
  - [ ] Payment processing
- [ ] Load test API endpoints
- [ ] Verify email sending
- [ ] Test file uploads

### 8. Backup & Recovery
- [ ] Database backup strategy in place
- [ ] File backup configured
- [ ] Disaster recovery plan documented
- [ ] Rollback procedure defined

### 9. Documentation
- [ ] Update README with production deployment steps
- [ ] Document environment variables
- [ ] Create runbook for common operations
- [ ] Document API endpoints
- [ ] Update changelog

### 10. Post-Deployment
- [ ] Verify all services are running
- [ ] Check application logs for errors
- [ ] Monitor performance metrics
- [ ] Verify cron jobs are executing
- [ ] Test critical user flows
- [ ] Monitor error tracking dashboard

## 🔧 Maintenance Commands

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan queue:restart
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Queue Management
```bash
php artisan queue:work --daemon
php artisan queue:restart
php artisan queue:failed
```

### Database Maintenance
```bash
php artisan migrate:status
php artisan db:backup
```

## 📞 Emergency Contacts

- **System Administrator**: [Contact Info]
- **Database Administrator**: [Contact Info]
- **DevOps Lead**: [Contact Info]
- **On-Call Engineer**: [Contact Info]

## 🚀 Deployment Completed

- [ ] Date: _______________
- [ ] Deployed By: _______________
- [ ] Version: _______________
- [ ] All checks completed