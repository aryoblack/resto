<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Services\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PromoController — Admin CRUD for promotions and vouchers.
 *
 * Routes (all under /api/admin/promos, protected by auth:sanctum + check.role:admin):
 *   GET    /              → index
 *   POST   /              → store
 *   GET    /{promo}       → show
 *   PUT    /{promo}       → update
 *   DELETE /{promo}       → destroy
 *
 * Validates: Requirements 12.1, 12.2
 */
class PromoController extends Controller
{
    public function __construct(private readonly PromoService $promoService)
    {
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * List all promos (admin).
     *
     * Validates: Requirement 12.2
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = Promo::orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'message' => 'Daftar promosi berhasil diambil.',
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Show a single promo.
     */
    public function show(Promo $promo): JsonResponse
    {
        return response()->json([
            'message' => 'Detail promosi berhasil diambil.',
            'data'    => $promo,
        ]);
    }

    /**
     * Create a new promo.
     *
     * Validates: Requirement 12.1, 12.2
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:100|unique:promo,code',
            'type'          => 'required|in:percentage,nominal',
            'value'         => 'required|numeric|min:0.01',
            'min_purchase'  => 'nullable|numeric|min:0',
            'max_discount'  => 'nullable|numeric|min:0',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'is_active'     => 'nullable|boolean',
            'usage_limit'   => 'nullable|integer|min:1',
        ]);

        // Defaults
        $validated['min_purchase'] = $validated['min_purchase'] ?? 0;
        $validated['is_active']    = $validated['is_active'] ?? true;
        $validated['usage_count']  = 0;

        $promo = Promo::create($validated);

        return response()->json([
            'message' => 'Promosi berhasil dibuat.',
            'data'    => $promo,
        ], 201);
    }

    /**
     * Update an existing promo.
     */
    public function update(Request $request, Promo $promo): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'code'         => 'sometimes|nullable|string|max:100|unique:promo,code,' . $promo->id,
            'type'         => 'sometimes|required|in:percentage,nominal',
            'value'        => 'sometimes|required|numeric|min:0.01',
            'min_purchase' => 'sometimes|nullable|numeric|min:0',
            'max_discount' => 'sometimes|nullable|numeric|min:0',
            'start_date'   => 'sometimes|required|date',
            'end_date'     => 'sometimes|required|date|after_or_equal:start_date',
            'is_active'    => 'sometimes|boolean',
            'usage_limit'  => 'sometimes|nullable|integer|min:1',
        ]);

        $promo->update($validated);

        return response()->json([
            'message' => 'Promosi berhasil diperbarui.',
            'data'    => $promo->fresh(),
        ]);
    }

    /**
     * Delete a promo.
     */
    public function destroy(Promo $promo): JsonResponse
    {
        $promo->delete();

        return response()->json([
            'message' => 'Promosi berhasil dihapus.',
        ]);
    }
}
