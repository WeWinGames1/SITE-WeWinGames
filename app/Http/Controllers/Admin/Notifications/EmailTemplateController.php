<?php

namespace App\Http\Controllers\Admin\Notifications;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of email templates.
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();

        return Inertia::render('admin/Notifications/EmailTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for editing the specified email template.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return Inertia::render('admin/Notifications/EmailTemplates/Edit', [
            'template' => $emailTemplate,
        ]);
    }

    /**
     * Update the specified email template.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'body_html' => 'required|string|min:1',
            'body_text' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Don't strip HTML tags from body_html - preserve rich content
        // The frontend Tiptap editor already sanitizes dangerous content

        $emailTemplate->update($validated);

        return redirect()->route('admin.notifications.email-templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Preview an email template with sample data.
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        // Generate sample data based on template key
        $sampleData = $this->getSampleData($emailTemplate->key);

        // Render the template with sample data
        $rendered = $emailTemplate->render($sampleData);

        return response()->json([
            'subject' => $rendered['subject'],
            'body_html' => $rendered['body_html'],
            'body_text' => $rendered['body_text'],
            'from_email' => $rendered['from_email'],
            'from_name' => $rendered['from_name'],
        ]);
    }

    /**
     * Get sample data for template preview
     */
    private function getSampleData(string $templateKey): array
    {
        $baseData = [
            'app_name' => config('app.name'),
            'user_name' => 'John Doe',
            'user_email' => 'john.doe@example.com',
        ];

        return match ($templateKey) {
            EmailTemplate::NEW_REGISTRATION => array_merge($baseData, [
                'login_url' => route('login'),
            ]),
            EmailTemplate::FORGOT_PASSWORD => array_merge($baseData, [
                'reset_url' => '#',
                'expire_hours' => '24',
            ]),
            EmailTemplate::TRIAL_EXPIRING => array_merge($baseData, [
                'days_remaining' => '3',
                'expiry_date' => now()->addDays(3)->format('F j, Y'),
                'upgrade_url' => route('billing'),
            ]),
            EmailTemplate::PLAN_RENEWAL => array_merge($baseData, [
                'plan_name' => 'Gold Monthly',
                'amount' => '$49.99',
                'next_renewal_date' => now()->addMonth()->format('F j, Y'),
            ]),
            EmailTemplate::PAYMENT_FAILED => array_merge($baseData, [
                'plan_name' => 'Gold Monthly',
                'update_payment_url' => route('billing'),
            ]),
            default => $baseData,
        };
    }

    /**
     * Reset a template to its default content
     */
    public function reset(EmailTemplate $emailTemplate)
    {
        $defaults = EmailTemplate::getDefaultTemplates();

        if (isset($defaults[$emailTemplate->key])) {
            $emailTemplate->update([
                'subject' => $defaults[$emailTemplate->key]['subject'],
                'body_html' => $defaults[$emailTemplate->key]['body_html'],
                'body_text' => $defaults[$emailTemplate->key]['body_text'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Template reset to default.');
        }

        return redirect()->back()->with('error', 'No default template found.');
    }

    /**
     * Send a test email to a specific customer
     */
    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);

        // Get the data for this specific template and user
        $templateData = $this->getTemplateDataForUser($emailTemplate->key, $user);

        // Render the template with actual user data
        $rendered = $emailTemplate->render($templateData);

        try {
            // Send the test email
            Mail::html($rendered['body_html'], function ($message) use ($rendered, $user, $emailTemplate) {
                $message->to($user->email, $user->name)
                    ->subject($rendered['subject'])
                    ->from($rendered['from_email'], $rendered['from_name']);

                // Add reply-to if different from from_email
                if (isset($rendered['reply_to_email']) && $rendered['reply_to_email'] !== $rendered['from_email']) {
                    $message->replyTo($rendered['reply_to_email'], $rendered['from_name']);
                }

                // Add headers for email logging
                $message->getHeaders()->addTextHeader('X-Template-Key', $emailTemplate->key);
                $message->getHeaders()->addTextHeader('X-Email-Metadata', json_encode([
                    'type' => 'test',
                    'template_id' => $emailTemplate->id,
                    'sent_by' => auth()->user()->email,
                    'sent_by_id' => auth()->user()->id,
                ]));
            });

            // Log the test email
            activity()
                ->causedBy(auth()->user())
                ->performedOn($emailTemplate)
                ->withProperties([
                    'template_key' => $emailTemplate->key,
                    'sent_to' => $user->email,
                    'subject' => $rendered['subject'],
                ])
                ->log('Admin sent test email');

            return response()->json([
                'success' => true,
                'message' => "Test email sent successfully to {$user->email}",
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send test email', [
                'template' => $emailTemplate->key,
                'user' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get template data for a specific user based on template type
     */
    private function getTemplateDataForUser(string $templateKey, User $user): array
    {
        $baseData = [
            'app_name' => config('app.name'),
            'user_name' => $user->name,
            'user_email' => $user->email,
        ];

        // Get active subscription if exists
        $subscription = $user->subscriptions()->active()->first();
        $planName = 'Free';
        $amount = '$0.00';

        if ($subscription && $subscription->stripe_price) {
            // Try to get from StripeProduct table first
            $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $subscription->stripe_price)
                ->where('is_active', true)
                ->first();

            if ($stripeProduct) {
                $planName = $stripeProduct->tier.' '.ucfirst($stripeProduct->billing_period);
                $amount = '$'.number_format($stripeProduct->price, 2);
            }
        }

        return match ($templateKey) {
            EmailTemplate::NEW_REGISTRATION => array_merge($baseData, [
                'login_url' => route('login'),
            ]),
            EmailTemplate::FORGOT_PASSWORD => array_merge($baseData, [
                'reset_url' => url('/password/reset/test-token'),
                'expire_hours' => '24',
            ]),
            EmailTemplate::TRIAL_EXPIRING => array_merge($baseData, [
                'days_remaining' => '3',
                'expiry_date' => now()->addDays(3)->format('F j, Y'),
                'upgrade_url' => route('subscription.checkout'),
            ]),
            EmailTemplate::PLAN_RENEWAL => array_merge($baseData, [
                'plan_name' => $planName,
                'amount' => $amount,
                'next_renewal_date' => $subscription && $subscription->current_period_end
                    ? \Carbon\Carbon::parse($subscription->current_period_end)->format('F j, Y')
                    : now()->addMonth()->format('F j, Y'),
            ]),
            EmailTemplate::PAYMENT_FAILED => array_merge($baseData, [
                'plan_name' => $planName,
                'update_payment_url' => route('billing'),
            ]),
            EmailTemplate::SUBSCRIPTION_CANCELLED => array_merge($baseData, [
                'plan_name' => $planName,
                'cancellation_date' => $subscription && $subscription->ends_at
                    ? \Carbon\Carbon::parse($subscription->ends_at)->format('F j, Y')
                    : now()->format('F j, Y'),
            ]),
            EmailTemplate::WELCOME_SUBSCRIBER => array_merge($baseData, [
                'plan_name' => $planName,
                'dashboard_url' => route('dashboard'),
            ]),
            default => $baseData,
        };
    }
}
