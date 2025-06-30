<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    /**
     * Display the admin login view.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming admin authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Apply rate limiting specifically for admin login
        $this->ensureIsNotRateLimited($request);
        
        try {
            $request->authenticate();

            // Check if the user has admin role
            if (!$request->user()->hasRole('admin')) {
                Auth::guard('web')->logout();
                
                // Log failed admin access attempt
                activity()
                    ->withProperties([
                        'email' => $request->email,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'reason' => 'User does not have admin role',
                    ])
                    ->log('Failed admin login attempt');
                
                return redirect()->route('admin.login')->withErrors([
                    'email' => 'You do not have permission to access the admin area.',
                ]);
            }

            // Clear rate limiter on successful login
            RateLimiter::clear($this->throttleKey($request));

            // Log successful admin login
            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Admin logged in successfully');

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard', absolute: false));
            
        } catch (\Exception $e) {
            // Log failed login attempt
            activity()
                ->withProperties([
                    'email' => $request->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'error' => $e->getMessage(),
                ])
                ->log('Failed admin login attempt');
                
            throw $e;
        }
    }
    
    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        $key = $this->throttleKey($request);
        
        if (! RateLimiter::tooManyAttempts($key, 5)) {
            RateLimiter::hit($key, 900); // 15 minutes
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
    
    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return 'admin_login_'.strtolower($request->input('email')).'|'.$request->ip();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Log admin logout
        if ($user = Auth::user()) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Admin logged out');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}