<?php

namespace App\Http\Controllers;

use App\Mail\ResumeSubmissionNotification;
use App\Models\ResumeSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ResumeSubmissionController extends Controller
{
    /**
     * Handle resume submission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'position' => 'required|string|max:255',
            'about' => 'required|string|min:50',
            'turnstile_token' => 'required_if:env.TURNSTILE_ENABLED,true',
        ]);

        // Verify Turnstile if enabled
        if (config('services.turnstile.enabled')) {
            $response = Http::post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $request->input('turnstile_token'),
                'remoteip' => $request->ip(),
            ]);

            if (! $response->json('success')) {
                return back()->withErrors(['turnstile_token' => 'Security verification failed. Please try again.']);
            }
        }

        // Create the submission
        $submission = ResumeSubmission::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'about' => $validated['about'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send email notification to HR
        try {
            Mail::to('jose@wewingames.com')
                ->send(new ResumeSubmissionNotification($submission));
        } catch (\Exception $e) {
            \Log::error('Failed to send resume submission notification', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Your application has been submitted successfully! We will review it and get back to you soon.');
    }
}
