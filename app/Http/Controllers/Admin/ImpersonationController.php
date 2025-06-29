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
        // Prevent admin from impersonating another admin
        if ($user->is_admin) {
            return back()->with('error', 'You cannot impersonate another admin user.');
        }

        // Store the admin's ID in the session
        Session::put('impersonator_id', Auth::id());
        
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
        
        if (!$impersonator) {
            Session::forget('impersonator_id');
            return redirect('/');
        }
        
        // Login back as the admin
        Auth::login($impersonator);
        
        // Clear the impersonation session
        Session::forget('impersonator_id');
        
        return redirect('/admin/customers')->with('success', 'Impersonation ended');
    }
}