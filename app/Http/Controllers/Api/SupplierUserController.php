<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Supplier\SupplierInviteMail;
use App\Models\Admin;
use App\Models\Supplier;
use App\Models\SupplierUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplierUserController extends Controller
{
    private const INVITE_TTL_MINUTES = 1440;

    public function store(Request $request)
    {
        $actor = $this->resolveAdmin($request);
        if (! $actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:tbl_supplier,s_id',
            'fullname' => 'required|string|max:85',
            'username' => 'required|string|max:45',
            'email' => 'required|email|max:255',
            'level_type' => 'nullable|integer|in:0,1',
        ]);

        $exists = SupplierUser::query()
            ->where('su_username', $validated['username'])
            ->orWhere('su_email', $validated['email'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => ['Supplier username or email already exists.'],
            ]);
        }

        $supplier = Supplier::query()->where('s_id', (int) $validated['supplier_id'])->firstOrFail();

        $message = $this->createAndSendInvite($validated, $supplier, (int) $actor->id);

        return response()->json(['message' => $message], 201);
    }

    public function showInvite(string $token)
    {
        $payload = $this->getInvitePayload($token);
        if (! $payload) {
            return response()->json(['message' => 'Invite link is invalid or expired.'], 404);
        }

        return response()->json([
            'invite' => [
                'fullname' => (string) $payload['fullname'],
                'username' => (string) $payload['username'],
                'email' => (string) $payload['email'],
                'supplier_id' => (int) $payload['supplier_id'],
                'supplier_name' => (string) $payload['supplier_name'],
                'expires_at' => (string) $payload['expires_at'],
            ],
        ]);
    }

    public function acceptInvite(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $payload = $this->getInvitePayload((string) $validated['token']);
        if (! $payload) {
            throw ValidationException::withMessages([
                'token' => ['Invite link is invalid or expired.'],
            ]);
        }

        $exists = SupplierUser::query()
            ->where('su_username', (string) $payload['username'])
            ->orWhere('su_email', (string) $payload['email'])
            ->exists();

        if ($exists) {
            Cache::forget($this->inviteCacheKey((string) $validated['token']));

            return response()->json([
                'message' => 'This supplier account has already been created or is no longer available.',
            ], 409);
        }

        $supplierUser = SupplierUser::query()->create([
            'su_level_type' => (int) ($payload['level_type'] ?? 0),
            'su_supplier' => (int) $payload['supplier_id'],
            'su_fullname' => trim((string) $payload['fullname']),
            'su_username' => trim((string) $payload['username']),
            'su_password' => Hash::make((string) $validated['password']),
            'su_email' => trim((string) $payload['email']),
            'su_date_created' => now(),
            'su_PIN' => 'N/A',
            'su_ASESSION_STAT' => '0',
            'su_last_ipadd' => '0',
            'su_last_loginloc' => '0',
        ]);

        Cache::forget($this->inviteCacheKey((string) $validated['token']));

        return response()->json([
            'message' => 'Your supplier account is now active. You may sign in.',
            'user' => [
                'id' => (int) $supplierUser->su_id,
                'supplier_id' => (int) $supplierUser->su_supplier,
                'username' => (string) $supplierUser->su_username,
                'email' => (string) ($supplierUser->su_email ?? ''),
            ],
        ]);
    }

    private function createAndSendInvite(array $validated, Supplier $supplier, int $actorId): string
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(self::INVITE_TTL_MINUTES);
        $payload = [
            'supplier_id' => (int) $supplier->s_id,
            'supplier_name' => (string) ($supplier->s_company ?: $supplier->s_name),
            'fullname' => trim((string) $validated['fullname']),
            'username' => trim((string) $validated['username']),
            'email' => trim((string) $validated['email']),
            'level_type' => (int) ($validated['level_type'] ?? 0),
            'created_by' => $actorId,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        Cache::put($this->inviteCacheKey($token), $payload, $expiresAt);

        $setupUrl = sprintf(
            '%s/supplier-setup?token=%s',
            rtrim((string) env('FRONTEND_URL', config('app.url')), '/'),
            urlencode($token)
        );

        Mail::to($payload['email'])->send(new SupplierInviteMail(
            name: $payload['fullname'],
            email: $payload['email'],
            supplierName: $payload['supplier_name'],
            setupUrl: $setupUrl,
            expiresAt: $expiresAt->toDayDateTimeString(),
        ));

        return 'Supplier invite sent successfully.';
    }

    private function resolveAdmin(Request $request): ?Admin
    {
        $user = $request->user();
        return $user instanceof Admin ? $user : null;
    }

    private function getInvitePayload(string $token): ?array
    {
        $payload = Cache::get($this->inviteCacheKey($token));
        return is_array($payload) ? $payload : null;
    }

    private function inviteCacheKey(string $token): string
    {
        return 'supplier:invite:' . $token;
    }
}
