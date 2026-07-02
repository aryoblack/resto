<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthController extends Controller
{
    /**
     * Show the staff login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route($this->routeForRole(Auth::user()->role));
        }

        return view('admin.login');
    }

    /**
     * Handle staff login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (! in_array($user->role, ['admin', 'waiter', 'chef'], true)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda tidak memiliki akses panel operasional.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $request->session()->put(
                'api_token',
                $user->createToken('staff_panel')->plainTextToken,
            );

            $targetRoute = $this->routeForRole($user->role);

            if ($user->role === 'admin') {
                return redirect()->intended(route($targetRoute));
            }

            return redirect()->route($targetRoute);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        if ($token = $request->session()->pull('api_token')) {
            PersonalAccessToken::findToken($token)?->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function routeForRole(string $role): string
    {
        return match ($role) {
            'waiter' => 'admin.orders',
            'chef' => 'kds.index',
            default => 'admin.dashboard',
        };
    }
}
