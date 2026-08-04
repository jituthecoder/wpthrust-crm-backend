<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Sales Executive List
     */
    public function salesExecutives()
    {
        $users = User::where('role', 'sales_executive')
            ->select(
                'id',
                'name',
                'email'
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}