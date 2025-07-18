# Production Security Checklist

## Environment Configuration

### ✅ Critical Settings for Production

1. **Debug Mode**
   - [ ] Set `APP_DEBUG=false` (CRITICAL - currently true in local)
   - Debug mode exposes sensitive application details in error messages

2. **Environment**
   - [ ] Set `APP_ENV=production`
   - Ensures production optimizations and security measures are active

3. **Application Key**
   - [ ] Generate strong `APP_KEY` using `php artisan key:generate`
   - Never share or commit the APP_KEY

4. **URL Configuration**
   - [ ] Set `APP_URL` to your actual domain (e.g., https://wewingames.com)
   - Important for URL generation and security

### ✅ Session Security

5. **Session Configuration**
   - [ ] `SESSION_SECURE_COOKIE=true` (already set)
   - [ ] `SESSION_ENCRYPT=true` (already set)
   - [ ] `SESSION_SAME_SITE=lax` (already set)
   - [ ] Consider `SESSION_DOMAIN` for subdomain access

### ✅ Database Security

6. **Database Credentials**
   - [ ] Use strong database password
   - [ ] Restrict database user permissions
   - [ ] Use SSL/TLS for remote database connections

### ✅ Email Configuration

7. **SendGrid Setup**
   - [ ] Set `SENDGRID_API_KEY`
   - [ ] Verify `MAIL_FROM_ADDRESS` domain
   - [ ] Configure proper SPF/DKIM records

### ✅ Third-Party Services

8. **Stripe**
   - [ ] Use production Stripe keys
   - [ ] Set `STRIPE_WEBHOOK_SECRET` for production endpoint
   - [ ] Enable webhook signature verification

9. **Google Services**
   - [ ] Set production `GOOGLE_ANALYTICS_TAG_ID`
   - [ ] Set production `GOOGLE_TAG_MANAGER_ID`

10. **Cloudflare**
    - [ ] Enable `CLOUDFLARE_TRUSTED_PROXIES=true` when behind CF
    - [ ] Configure `CLOUDFLARE_API_KEY` for cache purging
    - [ ] Consider `CLOUDFLARE_BLOCKED_COUNTRIES` for geo-blocking

### ✅ Security Headers

11. **HTTPS**
    - [ ] Force HTTPS in production
    - [ ] Enable HSTS headers (already configured in middleware)

12. **CORS**
    - [ ] Configure allowed origins if API is consumed externally

### ✅ Logging

13. **Log Level**
    - [ ] Set `LOG_LEVEL=error` or `warning` for production
    - [ ] Ensure logs don't contain sensitive data

### ✅ Additional Security Measures

14. **Rate Limiting**
    - [ ] Verify rate limits are appropriate for production load
    - [ ] Monitor for abuse patterns

15. **File Permissions**
    - [ ] Set proper file permissions (storage/ and bootstrap/cache/ writable)
    - [ ] Disable directory listing

16. **Backup Strategy**
    - [ ] Implement automated database backups
    - [ ] Test restore procedures

## Deployment Commands

```bash
# Before deployment
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# After deployment
php artisan migrate --force
php artisan queue:restart
```

## Security Issues Found

### High Priority
1. **XSS Vulnerabilities**: Multiple instances of `v-html` usage without sanitization
2. **SQL Injection**: Fixed in BetService, but review other dynamic queries
3. **Debug Mode**: Must be disabled in production

### Medium Priority
1. **File Upload Security**: Implement content validation and virus scanning
2. **API Rate Limiting**: Ensure proper limits for all endpoints
3. **Subscription Tier Checking**: BetPolicy uses old method names

### Low Priority
1. **Logging Verbosity**: Reduce verbose logging in production
2. **Error Pages**: Ensure custom error pages don't leak information

## Monitoring

- [ ] Set up application monitoring (e.g., Sentry, Bugsnag)
- [ ] Configure uptime monitoring
- [ ] Set up security scanning
- [ ] Enable Laravel Telescope for production debugging (with authentication)