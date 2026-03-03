<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $actor = $this->resolveAdmin($request);
        if (!$actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->isSuperAdmin($actor)) {
            return response()->json(['message' => 'Forbidden: only super admin can access admin user management.'], 403);
        }

        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 20);

        $rows = Admin::query()
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($q) use ($search) {
                    $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('user_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'users' => collect($rows->items())->map(fn (Admin $admin) => $this->transform($admin))->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $actor = $this->resolveAdmin($request);
        if (!$actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->isSuperAdmin($actor)) {
            return response()->json(['message' => 'Forbidden: only super admin can create admin accounts.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:120|unique:tbl_admin,username',
            'email' => 'required|email|max:255|unique:tbl_admin,user_email',
            'password' => 'required|string|min:8',
            'user_level_id' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6])],
        ]);

        $admin = Admin::query()->create([
            'fname' => trim((string) $validated['name']),
            'username' => trim((string) $validated['username']),
            'user_email' => trim((string) $validated['email']),
            'passworde' => Hash::make((string) $validated['password']),
            'user_level_id' => (int) $validated['user_level_id'],
        ]);

        return response()->json([
            'message' => 'Admin account created successfully.',
            'user' => $this->transform($admin),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $actor = $this->resolveAdmin($request);
        if (!$actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->isSuperAdmin($actor)) {
            return response()->json(['message' => 'Forbidden: only super admin can update admin accounts.'], 403);
        }

        $admin = Admin::query()->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('tbl_admin', 'username')->ignore($admin->id, 'id'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('tbl_admin', 'user_email')->ignore($admin->id, 'id'),
            ],
            'password' => 'nullable|string|min:8',
            'user_level_id' => ['nullable', 'integer', Rule::in([1, 2, 3, 4, 5, 6])],
        ]);

        if (array_key_exists('name', $validated)) {
            $admin->fname = trim((string) $validated['name']);
        }
        if (array_key_exists('username', $validated)) {
            $admin->username = trim((string) $validated['username']);
        }
        if (array_key_exists('email', $validated)) {
            $admin->user_email = trim((string) $validated['email']);
        }
        if (array_key_exists('user_level_id', $validated)) {
            $admin->user_level_id = (int) $validated['user_level_id'];
        }
        if (!empty($validated['password'])) {
            $admin->passworde = Hash::make((string) $validated['password']);
        }

        $admin->save();

        return response()->json([
            'message' => 'Admin account updated successfully.',
            'user' => $this->transform($admin),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $actor = $this->resolveAdmin($request);
        if (!$actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (!$this->isSuperAdmin($actor)) {
            return response()->json(['message' => 'Forbidden: only super admin can delete admin accounts.'], 403);
        }

        if ((int) $actor->id === $id) {
            return response()->json(['message' => 'You cannot delete your own super admin account.'], 422);
        }

        $admin = Admin::query()->where('id', $id)->firstOrFail();
        $admin->delete();

        return response()->json([
            'message' => 'Admin account deleted successfully.',
        ]);
    }

    private function resolveAdmin(Request $request): ?Admin
    {
        $user = $request->user();
        return $user instanceof Admin ? $user : null;
    }

    private function isSuperAdmin(Admin $admin): bool
    {
        return (int) $admin->user_level_id === 1;
    }

    private function roleFromLevel(int $level): string
    {
        return match ($level) {
            1 => 'super_admin',
            2 => 'admin',
            3 => 'csr',
            4 => 'web_content',
            5 => 'accounting',
            6 => 'finance_officer',
            default => 'staff',
        };
    }

    private function transform(Admin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'name' => (string) ($admin->fname ?: $admin->username),
            'username' => (string) $admin->username,
            'email' => (string) $admin->user_email,
            'user_level_id' => (int) $admin->user_level_id,
            'role' => $this->roleFromLevel((int) $admin->user_level_id),
        ];
    }
}
