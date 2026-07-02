<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStaffRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles')
            ->whereIn('role', ['admin', 'waiter', 'chef'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Create a new staff account (waiter or chef).
     *
     * Generates a random password, creates the user, assigns the Spatie role,
     * and sends the credentials to the staff member's email.
     */
    public function store(CreateStaffRequest $request): JsonResponse
    {
        // Generate a random initial password
        $plainPassword = Str::random(12);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($plainPassword),
            'role'     => $request->role,
            'is_active' => true,
        ]);

        // Assign Spatie role for middleware compatibility
        $user->assignRole($request->role);

        // Send credentials via email (uses the configured mail driver)
        $this->sendCredentialsEmail($user, $plainPassword);

        return response()->json([
            'message' => 'Akun karyawan berhasil dibuat. Kredensial telah dikirim ke email.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 201);
    }

    /**
     * Deactivate a staff account and revoke all active tokens.
     */
    public function deactivate(User $user): JsonResponse
    {
        $user->update(['is_active' => false]);

        // Revoke all Sanctum tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Akun karyawan berhasil dinonaktifkan dan semua sesi telah dicabut.',
        ]);
    }

    /**
     * Activate a staff account.
     */
    public function activate(User $user): JsonResponse
    {
        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'Akun karyawan berhasil diaktifkan.',
        ]);
    }

    /**
     * Send initial credentials to the staff member's email.
     */
    private function sendCredentialsEmail(User $user, string $plainPassword): void
    {
        $appName = config('app.name', 'Resto App');

        Mail::raw(
            "Halo {$user->name},\n\n" .
            "Akun Anda di {$appName} telah dibuat.\n\n" .
            "Email    : {$user->email}\n" .
            "Password : {$plainPassword}\n" .
            "Role     : {$user->role}\n\n" .
            "Silakan login dan segera ganti password Anda.\n\n" .
            "Salam,\n{$appName}",
            function ($message) use ($user, $appName) {
                $message->to($user->email, $user->name)
                    ->subject("Kredensial Akun {$appName}");
            }
        );
    }
}
