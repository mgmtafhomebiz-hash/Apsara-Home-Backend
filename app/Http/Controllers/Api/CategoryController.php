<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $categories = Category::select([
                'cat_id', 'cat_name', 'cat_description',
                'cat_url', 'cat_image', 'cat_order',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('cat_name', 'ilike', $like)
                          ->orWhere('cat_description', 'ilike', $like)
                          ->orWhere('cat_url', 'ilike', $like);
                });
            })
            ->orderBy('cat_order')
            ->orderByDesc('cat_id')
            ->get()
            ->map(fn (Category $c) => [
                'id'          => (int)    $c->cat_id,
                'name'        => (string) ($c->cat_name ?? ''),
                'description' => (string) ($c->cat_description ?? ''),
                'url'         => (string) ($c->cat_url ?? ''),
                'image'       => $c->cat_image ?? null,
                'order'       => (int)    $c->cat_order,
            ])
            ->values();

        return response()->json([
            'categories' => $categories,
            'total'      => $categories->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cat_name'        => 'required|string|max:50',
            'cat_description' => 'nullable|string|max:200',
            'cat_url'         => 'nullable|string|max:40',
            'cat_order'       => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $slug = $request->cat_url
            ? Str::slug($request->cat_url)
            : Str::slug($request->cat_name);

        $category = Category::create([
            'cat_name'        => trim($request->cat_name),
            'cat_description' => trim($request->cat_description ?? ''),
            'cat_url'         => $slug,
            'cat_image'       => '0',
            'cat_order'       => (int) ($request->cat_order ?? 0),
        ]);

        return response()->json([
            'message'  => 'Category created successfully.',
            'category' => [
                'id'   => $category->cat_id,
                'name' => $category->cat_name,
                'url'  => $category->cat_url,
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cat_name'        => 'sometimes|required|string|max:50',
            'cat_description' => 'nullable|string|max:200',
            'cat_url'         => 'nullable|string|max:40',
            'cat_order'       => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('cat_name')) {
            $category->cat_name = trim($request->cat_name);
        }

        if ($request->has('cat_description')) {
            $category->cat_description = trim($request->cat_description ?? '');
        }

        if ($request->has('cat_url') && $request->cat_url) {
            $category->cat_url = Str::slug($request->cat_url);
        } elseif ($request->has('cat_name') && ! $request->has('cat_url')) {
            $category->cat_url = Str::slug($request->cat_name);
        }

        if ($request->has('cat_order')) {
            $category->cat_order = (int) $request->cat_order;
        }

        $category->save();

        return response()->json(['message' => 'Category updated successfully.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
