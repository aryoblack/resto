<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class MenuApiController extends Controller
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly ImageStorageService $imageService
    ) {}

    /**
     * Get menus for customers.
     *
     * - Only returns menus that are not soft-deleted.
     * - Menus with is_available=false or stock=0 are included but flagged with
     *   `is_available=false` so the frontend can show a "Habis" label.
     * - Supports filtering by category_id and searching by name.
     * - Results are cached with a 5-minute TTL.
     *
     * Cache invalidation strategy:
     *   MenuController increments `menu:generation` on every write operation.
     *   The generation is embedded in the cache key, so any cached result from
     *   a previous generation is automatically bypassed on the next request —
     *   no tag support required, works with any cache driver.
     *
     *   Cache key: menu:list:{generation}:{category_id}:{search}
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());
            $user = $accessToken?->tokenable;

            if ($user && $user->role !== 'customer') {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $categoryId = $request->query('category_id', '');
        $search     = $request->query('search', '');

        // Read the current generation. MenuController increments this on every
        // create/update/delete/toggle, which changes the cache key and forces a
        // fresh DB query on the next request.
        $generation = (int) Cache::get('menu:generation', 0);
        $cacheKey   = "menu:list:{$generation}:{$categoryId}:{$search}";

        $menus = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryId, $search) {
            $query = Menu::with(['category', 'variants'])
                ->orderBy('name');

            if ($categoryId !== '') {
                $query->where('category_id', $categoryId);
            }

            if ($search !== '') {
                $query->where('name', 'like', "%{$search}%");
            }

            return $query->get();
        });

        // Append public image URLs (done outside cache to avoid storing full URLs)
        $menus = $menus->map(function (Menu $menu) {
            $menu->image_url = $this->imageService->getMenuImageUrl($menu->image_url);
            return $menu;
        });

        return response()->json(['data' => $menus]);
    }
}
