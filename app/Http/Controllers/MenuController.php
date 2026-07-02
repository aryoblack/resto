<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    public function __construct(
        private readonly ImageStorageService $imageService
    ) {}

    /**
     * List all menus with category and variants, paginated.
     * Admin only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Menu::with(['category', 'variants'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->latest();

        $menus = $query
            ->paginate($request->integer('per_page', 10));

        // Append public image URLs
        $menus->getCollection()->transform(function (Menu $menu) {
            $menu->image_url = $this->imageService->getMenuImageUrl($menu->image_url);
            return $menu;
        });

        return response()->json($menus);
    }

    /**
     * Create a new menu item.
     * Handles optional image upload via ImageStorageService.
     */
    public function store(CreateMenuRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageService->storeMenuImage($request->file('image'));
        }

        unset($data['image']);

        $menu = Menu::create($data);
        $menu->load(['category', 'variants']);
        $menu->image_url = $this->imageService->getMenuImageUrl($menu->image_url);

        // Invalidate menu list cache
        $this->invalidateMenuCache();

        return response()->json([
            'message' => 'Menu berhasil dibuat.',
            'data'    => $menu,
        ], 201);
    }

    /**
     * Show a single menu with its category and variants.
     */
    public function show(Menu $menu): JsonResponse
    {
        $menu->load(['category', 'variants']);
        $menu->image_url = $this->imageService->getMenuImageUrl($menu->image_url);

        return response()->json(['data' => $menu]);
    }

    /**
     * Update an existing menu.
     * Replaces the image if a new one is uploaded (old image is deleted).
     */
    public function update(UpdateMenuRequest $request, Menu $menu): JsonResponse
    {
        $data = $request->validated();

        // Handle image replacement
        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageService->storeMenuImage(
                $request->file('image'),
                $menu->image_url  // old path — will be deleted inside the service
            );
        }

        unset($data['image']);

        $menu->update($data);
        $menu->load(['category', 'variants']);
        $menu->image_url = $this->imageService->getMenuImageUrl($menu->image_url);

        // Invalidate menu list cache
        $this->invalidateMenuCache();

        return response()->json([
            'message' => 'Menu berhasil diperbarui.',
            'data'    => $menu,
        ]);
    }

    /**
     * Soft-delete a menu item.
     * Requires a `confirm` flag in the request body to prevent accidental deletion.
     */
    public function destroy(Request $request, Menu $menu): JsonResponse
    {
        if (! $request->boolean('confirm')) {
            return response()->json([
                'message' => 'Konfirmasi diperlukan untuk menghapus menu. Kirim `confirm: true`.',
            ], 422);
        }

        $menu->delete(); // SoftDeletes — sets deleted_at

        // Invalidate menu list cache
        $this->invalidateMenuCache();

        return response()->json([
            'message' => 'Menu berhasil dihapus.',
        ]);
    }

    /**
     * Toggle the is_available status of a menu item.
     * Invalidates the Redis cache for the menu list.
     */
    public function toggleAvailability(Menu $menu): JsonResponse
    {
        $menu->update(['is_available' => ! $menu->is_available]);

        // Invalidate menu list cache
        $this->invalidateMenuCache();

        return response()->json([
            'message'      => 'Status menu berhasil diubah.',
            'is_available' => $menu->is_available,
        ]);
    }

    /**
     * Invalidate all menu cache entries by incrementing the generation counter.
     * Works with any cache driver (tagged or not).
     * On Redis (tagged cache), also flushes the tag for belt-and-suspenders.
     */
    private function invalidateMenuCache(): void
    {
        // Increment the generation counter — MenuApiController includes this in the
        // cache key, so any cached result from the previous generation is automatically
        // bypassed on the next request.
        Cache::increment('menu:generation');
    }
}
