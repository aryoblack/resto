<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * List all categories ordered by sort_order.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount('menus')->orderBy('sort_order');

        if (! $request->has('page') && ! $request->has('per_page')) {
            return response()->json(['data' => $query->get()]);
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
     * Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Default sort_order to the end of the list
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = Category::max('sort_order') + 1;
        }

        $data['name'] = $this->normalizeName($data['name']);

        $category = Category::create($data);

        return response()->json([
            'message' => 'Kategori berhasil dibuat.',
            'data'    => $category,
        ], 201);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (isset($data['name'])) {
            $data['name'] = $this->normalizeName($data['name']);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data'    => $category,
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    /**
     * Bulk-update sort_order for multiple categories.
     *
     * Expects: [{ "id": 1, "sort_order": 0 }, { "id": 2, "sort_order": 1 }, ...]
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'category'              => ['required', 'array'],
            'category.*.id'         => ['required', 'integer', 'exists:category,id'],
            'category.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('category') as $item) {
            Category::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'message' => 'Urutan kategori berhasil diperbarui.',
        ]);
    }

    private function normalizeName(string $name): string
    {
        return Str::ucfirst(trim($name));
    }
}
