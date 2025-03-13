<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validate request data
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'user_type' => 'required',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();
        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials or user type'
            ], 401);
        }

        // Check if the user_type matches the user's role
        if (($request->user_type === 'admin' && $user->role !== 'admin') ||
        ($request->user_type === 'incubatee' && $user->role !== 'incubatee')) {
        return response()->json([
            'message' => 'Invalid Credentials or user type'
        ], 401);
}

        // Generate API token using Sanctum with token expiration
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke the current user's token
        $request->user()->currentAccessToken()->delete();
    
        return response()->json([
            'message' => 'Logged out successfully',
        ], 204);
    }
}
