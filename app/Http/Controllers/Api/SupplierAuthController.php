<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplierUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SupplierAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->login);
        $password = (string) $request->password;

        $supplierUser = SupplierUser::query()
            ->with('supplier')
            ->where('su_username', $login)
            ->orWhere('su_email', $login)
            ->first();

        if (! $supplierUser) {
            throw ValidationException::withMessages([
                'login' => ['Invalid username/email or password.'],
            ]);
        }

        $stored = (string) $supplierUser->su_password;
        $hashMatch = false;
        if ($stored !== '' && password_get_info($stored)['algo'] !== null) {
            $hashMatch = Hash::check($password, $stored);
        }
        $legacyDirectMatch = hash_equals($stored, $password);

        if (! $hashMatch && ! $legacyDirectMatch) {
            throw ValidationException::withMessages([
                'login' => ['Invalid username/email or password.'],
            ]);
        }

        $token = $supplierUser->createToken('supplier_auth_token')->plainTextToken;

        return response()->json([
            'user' => $this->transform($supplierUser),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Supplier logged out successfully.']);
    }

    public function me(Request $request)
    {
        $supplierUser = $request->user();
        if (! $supplierUser instanceof SupplierUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $supplierUser->loadMissing('supplier');

        return response()->json($this->transform($supplierUser));
    }

    private function transform(SupplierUser $supplierUser): array
    {
        return [
            'id' => (int) $supplierUser->su_id,
            'name' => (string) ($supplierUser->su_fullname ?: $supplierUser->su_username),
            'email' => (string) ($supplierUser->su_email ?? ''),
            'role' => 'supplier',
            'supplier_id' => (int) $supplierUser->su_supplier,
            'supplier_name' => $supplierUser->supplier?->s_company ?: $supplierUser->supplier?->s_name,
            'username' => (string) $supplierUser->su_username,
        ];
    }
}
