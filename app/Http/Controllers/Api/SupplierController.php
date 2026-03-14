<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierUser;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $admin = $this->resolveAdmin($request);
        $supplierUser = $this->resolveSupplierUser($request);
        if (! $admin && ! $supplierUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Supplier::query()->orderBy('s_company')->orderBy('s_name');

        if ($supplierUser) {
            $query->where('s_id', (int) $supplierUser->su_supplier);
        } elseif ($this->roleFromLevel((int) $admin->user_level_id) === 'supplier_admin') {
            if (! $admin->supplier_id) {
                return response()->json(['suppliers' => []]);
            }

            $query->where('s_id', (int) $admin->supplier_id);
        }

        return response()->json([
            'suppliers' => $query->get()->map(fn (Supplier $supplier) => $this->transform($supplier))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->resolveAdmin($request);
        if (! $admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $supplier = Supplier::query()->create([
            's_name' => trim((string) $validated['name']),
            's_company' => trim((string) $validated['company']),
            's_email' => trim((string) ($validated['email'] ?? '')),
            's_contact' => trim((string) ($validated['contact'] ?? '')),
            's_address' => trim((string) ($validated['address'] ?? '')),
            's_status' => (int) ($validated['status'] ?? 1),
        ]);

        return response()->json([
            'message' => 'Supplier company created successfully.',
            'supplier' => $this->transform($supplier),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (! $admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $supplier = Supplier::query()->find($id);
        if (! $supplier) {
            return response()->json(['message' => 'Supplier company not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $supplier->fill([
            's_name' => trim((string) $validated['name']),
            's_company' => trim((string) $validated['company']),
            's_email' => trim((string) ($validated['email'] ?? '')),
            's_contact' => trim((string) ($validated['contact'] ?? '')),
            's_address' => trim((string) ($validated['address'] ?? '')),
            's_status' => (int) ($validated['status'] ?? 1),
        ]);
        $supplier->save();

        return response()->json([
            'message' => 'Supplier company updated successfully.',
            'supplier' => $this->transform($supplier->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $admin = $this->resolveAdmin($request);
        if (! $admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $supplier = Supplier::query()->find($id);
        if (! $supplier) {
            return response()->json(['message' => 'Supplier company not found.'], 404);
        }

        $linkedUserCount = SupplierUser::query()
            ->where('su_supplier', $supplier->s_id)
            ->count();
        if ($linkedUserCount > 0) {
            return response()->json([
                'message' => 'This supplier company still has linked supplier login accounts. Remove or reassign those accounts first.',
            ], 422);
        }

        $linkedProductCount = Product::query()
            ->where('pd_supplier', $supplier->s_id)
            ->count();
        if ($linkedProductCount > 0) {
            return response()->json([
                'message' => 'This supplier company still has linked products. Remove or reassign those products first.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier company deleted successfully.',
        ]);
    }

    private function resolveAdmin(Request $request): ?Admin
    {
        $user = $request->user();
        return $user instanceof Admin ? $user : null;
    }

    private function resolveSupplierUser(Request $request): ?SupplierUser
    {
        $user = $request->user();
        return $user instanceof SupplierUser ? $user : null;
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
            7 => 'merchant_admin',
            8 => 'supplier_admin',
            default => 'staff',
        };
    }

    private function transform(Supplier $supplier): array
    {
        return [
            'id' => (int) $supplier->s_id,
            'name' => (string) ($supplier->s_name ?? ''),
            'company' => (string) ($supplier->s_company ?? ''),
            'email' => (string) ($supplier->s_email ?? ''),
            'contact' => (string) ($supplier->s_contact ?? ''),
            'address' => (string) ($supplier->s_address ?? ''),
            'status' => (int) ($supplier->s_status ?? 0),
        ];
    }
}
