<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::role('admin')->get();
        $users = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'admin');
        })->get();

        return Inertia::render('admin/AdminsIndex', [
            'admins' => $admins,
            'users' => $users,
        ]);
    }

    public function add(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::findOrFail($request->user_id);
        $user->assignRole('admin');
        return back()->with('success', 'Admin added!');
    }

    public function remove(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::findOrFail($request->user_id);
        $user->removeRole('admin');
        return back()->with('success', 'Admin removed!');
    }
}