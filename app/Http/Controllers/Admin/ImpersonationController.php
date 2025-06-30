<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user
     */
    public function start(User $user)
    {
        // Security check: ensure the current user is an admin
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent admin from impersonating another admin
        if ($user->hasRole('admin')) {
            return back()->with('error', 'You cannot impersonate another admin user.');
        }

        // Log the impersonation action
        activity()
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties([
                'impersonator_id' => Auth::id(),
                'impersonator_email' => Auth::user()->email,
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Admin started impersonating user');

        // Store the admin's ID in the session
        Session::put('impersonator_id', Auth::id());
        Session::put('impersonation_started_at', now());
        
        // Login as the user
        Auth::login($user);
        
        return redirect('/dashboard')->with('success', "You are now impersonating {$user->name}");
    }
    
    /**
     * Stop impersonating and return to admin account
     */
    public function stop()
    {
        $impersonatorId = Session::get('impersonator_id');
        
        if (!$impersonatorId) {
            return redirect('/');
        }
        
        // Get the admin user
        $impersonator = User::find($impersonatorId);
        
        if (!$impersonator || !$impersonator->hasRole('admin')) {
            Session::forget('impersonator_id');
            Session::forget('impersonation_started_at');
            return redirect('/');
        }

        // Log the end of impersonation
        activity()
            ->causedBy($impersonator)
            ->performedOn(Auth::user())
            ->withProperties([
                'impersonator_id' => $impersonator->id,
                'impersonator_email' => $impersonator->email,
                'target_user_id' => Auth::id(),
                'target_user_email' => Auth::user()->email,
                'duration' => Session::has('impersonation_started_at') 
                    ? now()->diffInMinutes(Session::get('impersonation_started_at')) . ' minutes'
                    : 'unknown',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Admin stopped impersonating user');
        
        // Login back as the admin
        Auth::login($impersonator);
        
        // Clear the impersonation session
        Session::forget('impersonator_id');
        Session::forget('impersonation_started_at');
        
        return redirect('/admin/customers')->with('success', 'Impersonation ended');
    }
}