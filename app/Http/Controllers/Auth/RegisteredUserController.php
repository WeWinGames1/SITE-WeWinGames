<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Affiliate;
use App\Models\User;
use App\Services\RegistrationSecurityService;
use App\Services\SendGridService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request, RegistrationSecurityService $securityService, SendGridService $sendGridService): RedirectResponse
    {
        // Debug logging
        \Log::info('Registration attempt', [
            'has_turnstile' => $request->has('cf-turnstile-response'),
            'turnstile_value' => $request->input('cf-turnstile-response') ? 'present' : 'missing',
            'all_inputs' => array_keys($request->all())
        ]);
        
        // Perform security checks
        $securityCheck = $securityService->canRegister($request);

        if (! $securityCheck['allowed']) {
            $securityService->logRegistrationAttempt($request, false);

            throw ValidationException::withMessages([
                'email' => [$securityCheck['reason']],
            ]);
        }

        // Use database transaction for atomicity
        try {
            $user = DB::transaction(function () use ($request) {
                // Check for affiliate cookie
                $affiliateCode = Cookie::get('affiliate_code');
                $affiliateId = null;

                if ($affiliateCode) {
                    $affiliate = Affiliate::where('code', $affiliateCode)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($affiliate) {
                        $affiliateId = $affiliate->id;
                    }
                }

                $user = User::create([
                    'name' => $request->name,
                    'email' => strtolower($request->email),
                    'password' => Hash::make($request->password),
                    'discord_username' => $request->discord_username,
                    'affiliate_id' => $affiliateId,
                    'registration_ip' => $request->ip(),
                    'registration_user_agent' => $request->userAgent(),
                ]);

                // Assign default role
                $user->assignRole('user');

                // Create Stripe customer
                $user->createAsStripeCustomer([
                    'metadata' => [
                        'registration_ip' => $request->ip(),
                        'registration_date' => now()->toDateTimeString(),
                        'affiliate_code' => $affiliateCode ?? '',
                        'discord_username' => $request->discord_username ?? '',
                    ],
                ]);

                return $user;
            });

            // Sync to SendGrid
            try {
                $sendGridService->syncContact($user);
            } catch (\Exception $e) {
                \Log::error('Failed to sync user to SendGrid: ' . $e->getMessage());
                // Don't fail registration if SendGrid sync fails
            }

            // Log successful registration
            $securityService->logRegistrationAttempt($request, true);

            // Fire registered event
            event(new Registered($user));

            // Login user
            Auth::login($user);

            return to_route('dashboard')->with('success', 'Welcome to WeWinGames!');

        } catch (\Exception $e) {
            $securityService->logRegistrationAttempt($request, false);
            
            \Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw ValidationException::withMessages([
                'email' => ['Registration failed. Please try again later. Error: ' . $e->getMessage()],
            ]);
        }
    }

    public function newSubscription(Request $request)
    {
        $request->validate([
            'subscription_name' => 'required|string',
            'subscription_price_id' => 'required|string',
            'coupon' => 'nullable|string',
        ]);

        $checkout = $request->user()
            ->newSubscription($request->subscription_name, $request->subscription_price_id);

        // Apply coupon if provided
        if ($request->filled('coupon')) {
            $checkout->withCoupon($request->coupon);
        }

        return $checkout->checkout([
            'success_url' => route('dashboard')->with('success', 'Subscription activated successfully!'),
            'cancel_url' => route('dashboard'),
            'allow_promotion_codes' => true, // Allow promotion codes at checkout
        ]);
    }
}
