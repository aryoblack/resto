<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate a user and return a Sanctum token.
     *
     * Validates credentials, enforces account lock after 5 failed attempts,
     * and resets the failed attempt counter on success.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Unknown email — return generic 401
        if (! $user) {
            return response()->json([
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        // Account locked
        if ($user->isLocked()) {
            return response()->json([
                'message' => 'Akun Anda dikunci sementara. Silakan coba lagi dalam 15 menit.',
            ], 423);
        }

        // Account inactive
        if (! $user->isActive()) {
            return response()->json([
                'message' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ], 403);
        }

        // Wrong password
        if (! Hash::check($request->password, $user->password)) {
            $attempts = $user->failed_login_attempts + 1;

            $update = ['failed_login_attempts' => $attempts];

            if ($attempts >= 5) {
                $update['locked_until'] = now()->addMinutes(15);
            }

            $user->update($update);

            if ($attempts >= 5) {
                return response()->json([
                    'message' => 'Akun Anda dikunci selama 15 menit karena terlalu banyak percobaan login yang gagal.',
                ], 423);
            }

            return response()->json([
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        // Successful login — reset failed attempts
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login berhasil.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
                'poin'  => $user->poin,
            ],
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Register a new customer account.
     *
     * Creates the user, assigns the `customer` role via Spatie,
     * and returns a Sanctum token so the user is immediately logged in.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
        ]);

        $user->assignRole('customer');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registrasi berhasil.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
                'poin'  => $user->poin,
            ],
        ], 201);
    }
}
