<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'subject',
        'from_email',
        'from_name',
        'body_html',
        'body_text',
        'available_variables',
        'is_active',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Template keys
     */
    const NEW_REGISTRATION = 'new_registration';

    const FORGOT_PASSWORD = 'forgot_password';

    const TRIAL_EXPIRING = 'trial_expiring';

    const PLAN_RENEWAL = 'plan_renewal';

    const PAYMENT_FAILED = 'payment_failed';

    const SUBSCRIPTION_CANCELLED = 'subscription_cancelled';

    const WELCOME_SUBSCRIBER = 'welcome_subscriber';
    
    const NEW_BET_PICK = 'new_bet_pick';
    
    const NEW_BATCH_UPLOAD = 'new_batch_upload';
    
    const CAREER_APPLICATION = 'career_application';

    /**
     * Get default templates
     */
    public static function getDefaultTemplates(): array
    {
        return [
            self::NEW_REGISTRATION => [
                'name' => 'New Registration',
                'description' => 'Sent when a new user registers an account',
                'subject' => 'Welcome to {{app_name}}!',
                'body_html' => '<h2>Welcome {{user_name}}!</h2><p>Thank you for registering at {{app_name}}. Your account has been created successfully.</p><p>You can now log in using your email: {{user_email}}</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'user_email', 'app_name', 'login_url'],
            ],
            self::FORGOT_PASSWORD => [
                'name' => 'Forgot Password',
                'description' => 'Sent when a user requests a password reset',
                'subject' => 'Reset Your Password - {{app_name}}',
                'body_html' => '<h2>Password Reset Request</h2><p>Hi {{user_name}},</p><p>We received a request to reset your password. Click the link below to reset it:</p><p><a href="{{reset_url}}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a></p><p>This link will expire in {{expire_hours}} hours.</p><p>If you didn\'t request this, please ignore this email.</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'user_email', 'reset_url', 'expire_hours', 'app_name'],
            ],
            self::TRIAL_EXPIRING => [
                'name' => 'Trial Expiring',
                'description' => 'Sent when a user\'s trial is about to expire',
                'subject' => 'Your {{app_name}} Trial is Expiring Soon',
                'body_html' => '<h2>Your Trial is Ending Soon</h2><p>Hi {{user_name}},</p><p>Your trial period will expire in {{days_remaining}} days on {{expiry_date}}.</p><p>To continue enjoying our premium features, please upgrade your subscription:</p><p><a href="{{upgrade_url}}" style="background-color: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Upgrade Now</a></p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'days_remaining', 'expiry_date', 'upgrade_url', 'app_name'],
            ],
            self::PLAN_RENEWAL => [
                'name' => 'Plan Renewal',
                'description' => 'Sent when a subscription is renewed',
                'subject' => 'Your {{app_name}} Subscription Has Been Renewed',
                'body_html' => '<h2>Subscription Renewed Successfully</h2><p>Hi {{user_name}},</p><p>Your {{plan_name}} subscription has been renewed successfully.</p><p><strong>Details:</strong></p><ul><li>Plan: {{plan_name}}</li><li>Amount: {{amount}}</li><li>Next Renewal: {{next_renewal_date}}</li></ul><p>Thank you for continuing with {{app_name}}!</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'plan_name', 'amount', 'next_renewal_date', 'app_name'],
            ],
            self::PAYMENT_FAILED => [
                'name' => 'Payment Failed',
                'description' => 'Sent when a payment attempt fails',
                'subject' => 'Payment Failed - Action Required',
                'body_html' => '<h2>Payment Failed</h2><p>Hi {{user_name}},</p><p>We were unable to process your payment for your {{plan_name}} subscription.</p><p>Please update your payment method to avoid service interruption:</p><p><a href="{{update_payment_url}}" style="background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Update Payment Method</a></p><p>If you need assistance, please contact our support team.</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'plan_name', 'update_payment_url', 'app_name'],
            ],
        ];
    }

    /**
     * Render template with variables
     */
    public function render(array $variables = []): array
    {
        $subject = $this->subject;
        $bodyHtml = $this->body_html;
        $bodyText = $this->body_text;

        // Add default variables
        $variables = array_merge([
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
        ], $variables);

        // Replace variables in subject and body
        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, $value, $subject);
            $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
            if ($bodyText) {
                $bodyText = str_replace($placeholder, $value, $bodyText);
            }
        }

        // Handle SendGrid subdomain replacement
        $fromEmail = $this->from_email ?: config('mail.from.address');
        $replyToEmail = $fromEmail; // Keep original for reply-to
        
        // If using SendGrid and MAIL_EHLO_DOMAIN is set, replace domain
        if (config('mail.default') === 'sendgrid' && config('mail.mailers.sendgrid.ehlo_domain')) {
            $sendgridDomain = config('mail.mailers.sendgrid.ehlo_domain');
            // Extract username from email
            if (strpos($fromEmail, '@') !== false) {
                list($username, $domain) = explode('@', $fromEmail, 2);
                $fromEmail = $username . '@' . $sendgridDomain;
            }
        }
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'from_email' => $fromEmail,
            'from_name' => $this->from_name ?: config('mail.from.name'),
            'reply_to_email' => $replyToEmail,
        ];
    }
}
