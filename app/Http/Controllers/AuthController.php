<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER — sends 6-digit email verification code
    | FIX: removed $user->assignRole() which triggered web guard lookup.
    |      Role is now assigned AFTER email verification.
    |--------------------------------------------------------------------------
    */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generate & store 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'code'       => $code,
                'expires_at' => Carbon::now()->addMinutes(15),
                'created_at' => Carbon::now(),
            ]
        );

        // Send verification email
        Mail::send([], [], function ($message) use ($user, $code) {
            $message->to($user->email, $user->name)
                ->subject('Your Ticket Center verification code')
                ->html(
                    view('emails.verify-code', [
                        'code' => $code,
                        'name' => $user->name,
                    ])->render()
                );
        });

        return response()->json([
            'message' => 'Account created. Please check your email for the 6-digit verification code.',
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY EMAIL — marks verified & assigns default role
    |--------------------------------------------------------------------------
    */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|digits:6',
        ]);

        $record = DB::table('email_verification_codes')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            return response()->json([
                'message' => 'Verification code has expired. Please request a new one.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Mark verified
        $user->update(['email_verified_at' => Carbon::now()]);

        // Assign default role NOW (safe — user exists & is verified)
        if (! $user->hasAnyRole(['employee', 'manager', 'admin', 'technician'])) {
            $user->assignRole('employee');
        }

        // Clean up code
        DB::table('email_verification_codes')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Email verified successfully. You can now log in.']);
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND VERIFICATION CODE
    |--------------------------------------------------------------------------
    */
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'code'       => $code,
                'expires_at' => Carbon::now()->addMinutes(15),
                'created_at' => Carbon::now(),
            ]
        );

        Mail::send([], [], function ($message) use ($user, $code) {
            $message->to($user->email, $user->name)
                ->subject('Your new Ticket Center verification code')
                ->html(
                    view('emails.verify-code', [
                        'code' => $code,
                        'name' => $user->name,
                    ])->render()
                );
        });

        return response()->json(['message' => 'A new verification code has been sent.']);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'error'            => 'Please verify your email before logging in.',
                'email_unverified' => true,
            ], 403);
        }

        // Revoke old tokens
        $user->tokens()->delete();

        $tokenResult             = $user->createToken('user_token');
        $token                   = $tokenResult->plainTextToken;
        $tokenModel              = $tokenResult->accessToken;
        $tokenModel->expires_at  = Carbon::now()->addHours(7);
        $tokenModel->save();

        return response()->json([
            'tokken'      => $token,
            'user'        => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'permissions' => $user->getPermissionNames(),
            'roles'       => $user->getRoleNames(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ME
    |--------------------------------------------------------------------------
    */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'permissions' => $user->getPermissionNames(),
            'roles'       => $user->getRoleNames(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    public function logout(Request $request)
    {
        $request->user()?->tokens()->delete();
        return response()->json(['success' => 'You have been logged out.']);
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => Carbon::now()]
        );

        $resetUrl = config('app.frontend_url', 'http://localhost:5173')
            . '/reset-password?token=' . $token
            . '&email=' . urlencode($request->email);

        Mail::send([], [], function ($message) use ($user, $resetUrl) {
            $message->to($user->email, $user->name)
                ->subject('Reset your Ticket Center password')
                ->html(
                    view('emails.reset-password', [
                        'url'  => $resetUrl,
                        'name' => $user->name,
                    ])->render()
                );
        });

        return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Reset link has expired. Please request a new one.'], 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully. You can now log in.']);
    }
}
