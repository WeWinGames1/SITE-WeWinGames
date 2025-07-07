<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index()
    {
        return Inertia::render('admin/AdminsIndex', [
            'admins' => $this->userService->getAllAdmins(),
            'users' => $this->userService->getNonAdminUsers(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $result = $this->userService->promoteToAdmin($request->user_id);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function remove(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $result = $this->userService->demoteFromAdmin($request->user_id);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }
}
