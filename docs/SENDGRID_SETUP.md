# SendGrid Email Configuration

## Overview

This application is configured to use SendGrid as the email service provider. SendGrid provides reliable email delivery with detailed analytics and tracking.

## Configuration Steps

### 1. Get SendGrid API Key

1. Sign up for a SendGrid account at https://sendgrid.com
2. Navigate to Settings > API Keys
3. Click "Create API Key"
4. Give your key a name (e.g., "WeWinGames Production")
5. Select "Full Access" or "Restricted Access" with Mail Send permissions
6. Copy the generated API key (you won't be able to see it again)

### 2. Update Environment Variables

Add the following to your `.env` file:

```env
# Email Configuration
MAIL_MAILER=sendgrid
MAIL_FROM_ADDRESS="noreply@wewingames.com"
MAIL_FROM_NAME="WeWinGames"

# SendGrid API Key
SENDGRID_API_KEY=your-sendgrid-api-key-here
```

### 3. Verify Sender Authentication (Important!)

SendGrid requires sender authentication for better deliverability:

1. Go to Settings > Sender Authentication in SendGrid
2. Choose either:
   - **Domain Authentication** (Recommended): Verify your entire domain
   - **Single Sender Verification**: Verify individual email addresses

For domain authentication:
1. Add the provided DNS records to your domain
2. Wait for verification (usually takes a few minutes)
3. Your emails will now show as authenticated

### 4. Optional: Configure Email Templates

You can use SendGrid's dynamic templates:

1. Go to Email API > Dynamic Templates
2. Create templates for different email types
3. Use the template IDs in your Laravel notifications

### 5. Test Email Configuration

Run this artisan command to test your email configuration:

```bash
php artisan tinker
```

Then in the tinker console:

```php
Mail::raw('Test email from WeWinGames', function($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});
```

## Alternative SMTP Configuration

If you prefer to use SendGrid via standard SMTP instead of the API:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key-here
MAIL_ENCRYPTION=tls
```

## Monitoring and Analytics

1. **Email Activity**: View real-time email activity in SendGrid dashboard
2. **Suppressions**: Monitor bounces, blocks, and spam reports
3. **Statistics**: Track opens, clicks, and engagement metrics

## Troubleshooting

### Common Issues

1. **Emails not sending**
   - Verify API key is correct
   - Check sender authentication status
   - Ensure from address is verified

2. **Emails going to spam**
   - Complete domain authentication
   - Set up SPF and DKIM records
   - Monitor sender reputation

3. **Rate limiting**
   - SendGrid free tier: 100 emails/day
   - Check your plan limits in SendGrid dashboard

### Debug Mode

To debug email issues, temporarily set in `.env`:

```env
MAIL_MAILER=log
```

This will log emails to `storage/logs/laravel.log` instead of sending them.

## Production Checklist

- [ ] API key is set in production `.env`
- [ ] Domain authentication is complete
- [ ] From address is verified
- [ ] Email templates are configured
- [ ] Monitoring alerts are set up
- [ ] Backup email service is configured (optional)