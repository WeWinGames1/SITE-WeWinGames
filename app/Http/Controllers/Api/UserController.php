<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get the authenticated user (legacy endpoint).
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}