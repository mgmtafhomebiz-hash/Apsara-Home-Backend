<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 25), 100));
        $search  = trim((string) $request->query('q', ''));
        $status  = $request->query('status', '');

        $query = Product::query()
            ->select([
                'pd_id', 'pd_name', 'pd_catid', 'pd_catsubid',
                'pd_price_srp', 'pd_price_dp', 'pd_qty',
                'pd_weight', 'pd_type', 'pd_musthave',
                'pd_bestseller', 'pd_salespromo', 'pd_status', 'pd_date',
                'pd_last_update', 'pd_parent_sku', 'pd_image',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('pd_name', 'ilike', $like)
                          ->orWhere('pd_parent_sku', 'ilike', $like);
                });
            })
            ->when($status !== '', function ($q) use ($status) {
                $q->where('pd_status', (int) $status);
            })
            ->orderByDesc('pd_id');

        $paginator = $query->paginate($perPage);

        $products = collect($paginator->items())->map(fn (Product $p) => [
            'id'          => (int)   $p->pd_id,
            'name'        => (string) ($p->pd_name ?? ''),
            'catid'       => (int)   $p->pd_catid,
            'catsubid'    => (int)   $p->pd_catsubid,
            'priceSrp'    => (float) $p->pd_price_srp,
            'priceDp'     => (float) $p->pd_price_dp,
            'qty'         => (float) $p->pd_qty,
            'weight'      => (int)   $p->pd_weight,
            'type'        => (int)   $p->pd_type,
            'musthave'    => (bool)  $p->pd_musthave,
            'bestseller'  => (bool)  $p->pd_bestseller,
            'salespromo'  => (bool)  $p->pd_salespromo,
            'status'      => (int)   $p->pd_status,
            'sku'         => (string) ($p->pd_parent_sku ?? ''),
            'image'       => $p->pd_image ?? null,
            'createdAt'   => $p->pd_date ? $p->pd_date->format('Y-m-d') : null,
            'updatedAt'   => $p->pd_last_update ? $p->pd_last_update->format('Y-m-d') : null,
        ])->values();

        return response()->json([
            'products' => $products,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pd_name'      => 'required|string|max:255',
            'pd_catid'     => 'required|integer',
            'pd_catsubid'  => 'nullable|integer',
            'pd_price_srp' => 'required|numeric|min:0',
            'pd_price_dp'  => 'nullable|numeric|min:0',
            'pd_qty'       => 'nullable|numeric|min:0',
            'pd_weight'    => 'nullable|integer|min:0',
            'pd_psweight'  => 'nullable|numeric|min:0',
            'pd_pslenght'  => 'nullable|numeric|min:0',
            'pd_psheight'  => 'nullable|numeric|min:0',
            'pd_description' => 'nullable|string',
            'pd_parent_sku'  => 'nullable|string|max:50',
            'pd_type'      => 'nullable|integer',
            'pd_musthave'    => 'nullable|boolean',
            'pd_bestseller'  => 'nullable|boolean',
            'pd_salespromo'  => 'nullable|boolean',
            'pd_status'      => 'nullable|integer|in:0,1',
            'pd_image'       => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $now = now();

        $product = Product::create([
            'pd_name'        => $request->pd_name,
            'pd_catid'       => $request->pd_catid ?? 0,
            'pd_catsubid'    => $request->pd_catsubid ?? 0,
            'pd_catsubid2'   => 0,
            'pd_shopid'      => 0,
            'pd_description' => $request->pd_description ?? '',
            'pd_supplier'    => 0,
            'pd_price_srp'   => $request->pd_price_srp ?? 0,
            'pd_price_dp'    => $request->pd_price_dp ?? 0,
            'pd_qty'         => $request->pd_qty ?? 0,
            'pd_weight'      => $request->pd_weight ?? 0,
            'pd_psweight'    => $request->pd_psweight ?? 0,
            'pd_pslenght'    => $request->pd_pslenght ?? 0,
            'pd_psheight'    => $request->pd_psheight ?? 0,
            'pd_preorder'    => '',
            'pd_preorder_value' => 0,
            'pd_parent_sku'  => $request->pd_parent_sku ?? '',
            'pd_type'        => $request->pd_type ?? 0,
            'pd_shoptype'    => 0,
            'pd_musthave'    => $request->boolean('pd_musthave') ? 1 : 0,
            'pd_bestseller'  => $request->boolean('pd_bestseller') ? 1 : 0,
            'pd_salespromo'  => $request->boolean('pd_salespromo') ? 1 : 0,
            'pd_user'        => 0,
            'pd_usertype'    => 0,
            'pd_date'        => $now,
            'pd_last_update' => $now,
            'pd_status'      => $request->pd_status ?? 0,
            'pd_image'       => $request->pd_image ?? null,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => [
                'id'       => $product->pd_id,
                'name'     => $product->pd_name,
                'priceSrp' => (float) $product->pd_price_srp,
                'status'   => (int) $product->pd_status,
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'pd_name'        => 'sometimes|required|string|max:255',
            'pd_catid'       => 'sometimes|required|integer',
            'pd_catsubid'    => 'nullable|integer',
            'pd_price_srp'   => 'sometimes|required|numeric|min:0',
            'pd_price_dp'    => 'nullable|numeric|min:0',
            'pd_qty'         => 'nullable|numeric|min:0',
            'pd_weight'      => 'nullable|integer|min:0',
            'pd_psweight'    => 'nullable|numeric|min:0',
            'pd_pslenght'    => 'nullable|numeric|min:0',
            'pd_psheight'    => 'nullable|numeric|min:0',
            'pd_description' => 'nullable|string',
            'pd_parent_sku'  => 'nullable|string|max:50',
            'pd_type'        => 'nullable|integer',
            'pd_musthave'    => 'nullable|boolean',
            'pd_bestseller'  => 'nullable|boolean',
            'pd_salespromo'  => 'nullable|boolean',
            'pd_status'      => 'nullable|integer|in:0,1',
            'pd_image'       => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fields = [
            'pd_name', 'pd_catid', 'pd_catsubid', 'pd_description',
            'pd_price_srp', 'pd_price_dp', 'pd_qty', 'pd_weight',
            'pd_psweight', 'pd_pslenght', 'pd_psheight',
            'pd_parent_sku', 'pd_type', 'pd_status', 'pd_image',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $product->$field = $request->$field;
            }
        }

        if ($request->has('pd_musthave')) {
            $product->pd_musthave = $request->boolean('pd_musthave') ? 1 : 0;
        }
        if ($request->has('pd_bestseller')) {
            $product->pd_bestseller = $request->boolean('pd_bestseller') ? 1 : 0;
        }
        if ($request->has('pd_salespromo')) {
            $product->pd_salespromo = $request->boolean('pd_salespromo') ? 1 : 0;
        }

        $product->pd_last_update = now();
        $product->save();

        return response()->json(['message' => 'Product updated successfully.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
