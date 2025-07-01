<?php

namespace App\Http\Controllers\Admin\Notifications;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
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
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

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

        return match($templateKey) {
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
}