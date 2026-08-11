<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

   /**
     * User List
     */
    public function index(Request $request)
    {
        $query = User::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");

            });

        }

        /*
        |--------------------------------------------------------------------------
        | Role Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('role')) {

            $query->where('role', $request->role);

        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $users = $query
            ->select(
                'id',
                'name',
                'email',
                'role',
                'is_active',
                'created_at'
            )
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Create User
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => [
                'required',
                Rule::in([
                    'super_admin',
                    'sales_executive'
                ]),
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    /**
     * Update User
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],

            'role' => [
                'required',
                Rule::in([
                    'super_admin',
                    'sales_executive'
                ]),
            ],

            'is_active' => 'required|boolean',

        ]);

        $user->update($validated);

        return response()->json([

            'success' => true,

            'message' => 'User updated successfully.',

            'data' => $user,

        ]);
    }


    /**
     * Update User Password
     */
    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User password updated successfully.',
        ]);
    }

    /**
     * Delete User
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if (auth()->id() === $user->id) {

            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.'
            ], 422);

        }

        // Prevent deleting the last Super Admin
        if (
            $user->role === 'super_admin' &&
            User::where('role', 'super_admin')->count() <= 1
        ) {

            return response()->json([
                'success' => false,
                'message' => 'At least one Super Admin is required.'
            ], 422);

        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}