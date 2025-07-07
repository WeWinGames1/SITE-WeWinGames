<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = EmailTemplate::getDefaultTemplates();

        foreach ($templates as $key => $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'subject' => $template['subject'],
                    'body_html' => $template['body_html'],
                    'body_text' => $template['body_text'] ?? null,
                    'available_variables' => $template['available_variables'],
                    'is_active' => true,
                ]
            );
        }

        // Add additional templates
        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::SUBSCRIPTION_CANCELLED],
            [
                'name' => 'Subscription Cancelled',
                'description' => 'Sent when a user cancels their subscription',
                'subject' => 'Your {{app_name}} Subscription Has Been Cancelled',
                'body_html' => '<h2>Subscription Cancelled</h2><p>Hi {{user_name}},</p><p>Your {{plan_name}} subscription has been cancelled as requested.</p><p>You will continue to have access until {{end_date}}.</p><p>We\'re sorry to see you go. If you have any feedback about your experience, we\'d love to hear from you.</p><p><a href="{{resubscribe_url}}" style="background-color: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Resubscribe</a></p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'plan_name', 'end_date', 'resubscribe_url', 'app_name'],
                'is_active' => true,
            ]
        );

        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::WELCOME_SUBSCRIBER],
            [
                'name' => 'Welcome Subscriber',
                'description' => 'Sent when a user starts a paid subscription',
                'subject' => 'Welcome to {{app_name}} {{plan_name}}!',
                'body_html' => '<h2>Welcome to {{plan_name}}!</h2><p>Hi {{user_name}},</p><p>Thank you for subscribing to {{app_name}}! Your {{plan_name}} subscription is now active.</p><p><strong>What\'s included:</strong></p><ul><li>Access to all premium betting picks</li><li>Expert analysis and insights</li><li>Real-time notifications</li><li>Exclusive member content</li></ul><p><a href="{{dashboard_url}}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Go to Dashboard</a></p><p>If you have any questions, our support team is here to help!</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'available_variables' => ['user_name', 'plan_name', 'dashboard_url', 'app_name'],
                'is_active' => true,
            ]
        );
    }
}
