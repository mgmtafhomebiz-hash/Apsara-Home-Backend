<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->login);
        $password = (string) $request->password;

        $admin = Admin::query()
            ->where('user_email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'login' => ['Invalid email/username or password.'],
            ]);
        }

        $stored = (string) $admin->passworde;
        $hashMatch = false;
        if ($stored !== '' && password_get_info($stored)['algo'] !== null) {
            $hashMatch = Hash::check($password, $stored);
        }
        $legacyDirectMatch = hash_equals($stored, $password);

        if (! $hashMatch && ! $legacyDirectMatch) {
            throw ValidationException::withMessages([
                'login' => ['Invalid email/username or password.'],
            ]);
        }

        $token = $admin->createToken('admin_auth_token')->plainTextToken;
        $role = $this->mapRole((int) $admin->user_level_id);

        return response()->json([
            'user' => [
                'id' => (int) $admin->id,
                'name' => (string) ($admin->fname ?: $admin->username),
                'email' => (string) $admin->user_email,
                'role' => $role,
                'user_level_id' => (int) $admin->user_level_id,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Admin logged out successfully.']);
    }

    public function me(Request $request)
    {
        /** @var Admin|null $admin */
        $admin = $request->user();

        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'id' => (int) $admin->id,
            'name' => (string) ($admin->fname ?: $admin->username),
            'email' => (string) $admin->user_email,
            'role' => $this->mapRole((int) $admin->user_level_id),
            'user_level_id' => (int) $admin->user_level_id,
        ]);
    }

    private function mapRole(int $level): string
    {
        return match ($level) {
            1 => 'super_admin',
            2 => 'admin',
            3 => 'csr',
            4 => 'web_content',
            default => 'staff',
        };
    }
}
