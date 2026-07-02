<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VariantController extends Controller
{
    /**
     * List all variants for a given menu.
     */
    public function index(Menu $menu): JsonResponse
    {
        $variants = $menu->variants()->get();

        return response()->json(['data' => $variants]);
    }

    /**
     * Create a new variant for a given menu.
     */
    public function store(Request $request, Menu $menu): JsonResponse
    {
        $data = $request->validate([
            'variant_name' => ['required', 'string', 'max:255'],
            'extra_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $data['menu_id'] = $menu->id;

        $variant = Variant::create($data);
        $this->invalidateMenuCache();

        return response()->json([
            'message' => 'Varian berhasil dibuat.',
            'data'    => $variant,
        ], 201);
    }

    /**
     * Update an existing variant.
     */
    public function update(Request $request, Variant $variant): JsonResponse
    {
        $data = $request->validate([
            'variant_name' => ['sometimes', 'string', 'max:255'],
            'extra_price'  => ['sometimes', 'numeric', 'min:0'],
        ]);

        $variant->update($data);
        $this->invalidateMenuCache();

        return response()->json([
            'message' => 'Varian berhasil diperbarui.',
            'data'    => $variant,
        ]);
    }

    /**
     * Delete a variant.
     */
    public function destroy(Variant $variant): JsonResponse
    {
        $variant->delete();
        $this->invalidateMenuCache();

        return response()->json([
            'message' => 'Varian berhasil dihapus.',
        ]);
    }

    private function invalidateMenuCache(): void
    {
        Cache::increment('menu:generation');
    }
}
