<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success'=> 'Account created successfully!'
        ]);
    }

    public function login(LoginRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid email or password.'
            ], 401);
        }

        $user->tokens()->delete();

        // Create token with expiration
        $tokenResult = $user->createToken('user_token');
        $token = $tokenResult->plainTextToken;

        $tokenModel = $tokenResult->accessToken;  // This is the DB model
        $tokenModel->expires_at = Carbon::now()->addHours(7);
        $tokenModel->save();

        $permissions = $user->getPermissionNames();
        $roles = $user->getRoleNames();
        return response()->json([
            "tokken" =>  $token ,
            "permissions" => $permissions,
            "roles" => $roles
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $permissions = $user->getPermissionNames();
        $roles = $user->getRoleNames();

        return response()->json([
            "user" => [
                "id"    => $user->id,
                "name"  => $user->name,
                "email" => $user->email,
            ],
            "permissions" => $permissions,
            "roles"       => $roles,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success'=>'You have been logged out.'
        ]);
    }

}
