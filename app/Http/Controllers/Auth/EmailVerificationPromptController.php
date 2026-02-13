<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $bypassCode = config('auth.email_verification_bypass_code');

        return Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
            'email' => $request->user()->email,
            'bypassEnabled' => ! empty($bypassCode) && $bypassCode !== false,
        ]);
    }

    /**
     * Verify email using the bypass code.
     */
    public function verifyWithCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $bypassCode = config('auth.email_verification_bypass_code');

        // Check if bypass code feature is enabled
        if (empty($bypassCode) || $bypassCode === false) {
            throw ValidationException::withMessages([
                'code' => 'Code verification is not available.',
            ]);
        }

        // Case-insensitive comparison
        if (strtoupper(trim($request->code)) !== strtoupper(trim($bypassCode))) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid.',
            ]);
        }

        // Mark as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Log the bypass code usage
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'method' => 'bypass_code',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Email verified using bypass code');

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    /**
     * Update the user's email address during verification.
     * Requires password confirmation for security.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $oldEmail = $user->email;
        $newEmail = $request->email;

        // Update email and reset verification
        $user->email = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        // Log the email change
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'old_email' => $oldEmail,
                'new_email' => $newEmail,
                'ip' => $request->ip(),
                'context' => 'verification_flow',
            ])
            ->log('Email changed during verification');

        // Send new verification email
        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-email-updated');
    }
}
