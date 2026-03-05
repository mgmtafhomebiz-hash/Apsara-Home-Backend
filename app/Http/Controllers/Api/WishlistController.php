<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Wishlist::with('product')
                ->where('cw_customer_id', Auth::id())
                ->orderByDesc('cw_id')
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:tbl_product,pd_id'],
            'product_name' => ['nullable', 'string', 'max:255'],
        ]);

        $productId = $request->integer('product_id');

        if (!$productId && $request->filled('product_name')) {
            $name = trim((string) $request->string('product_name'));
            $productId = Product::query()
                ->where('pd_name', $name)
                ->value('pd_id');
        }

        if (!$productId) {
            throw ValidationException::withMessages([
                'product_id' => ['Unable to resolve product. Provide a valid product_id or product_name.'],
            ]);
        }

        Wishlist::firstOrCreate([
            'cw_customer_id' => Auth::id(),
            'cw_product_id' => (int) $productId,
        ], [
            'cw_date' => now(),
        ]);

        return response()->json(['message' => 'Added to wishlist']);
    }

    public function destroy(int $productId)
    {
        Wishlist::where('cw_customer_id', Auth::id())
            ->where('cw_product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
