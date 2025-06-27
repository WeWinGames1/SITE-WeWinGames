<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
/**
 * Class BetService.
 */
class UserService
{
    public function getAllUsers()
    {
        return User::all();
    }
    public function getUserById($id)
    {
        return User::find($id);
    }
}
