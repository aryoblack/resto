<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Supplier;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * InventoryController — Admin CRUD for ingredients and stock management.
 *
 * Routes (all under /api/admin/inventory, protected by auth:sanctum + check.role:admin):
 *   GET    /                          → index
 *   POST   /                          → store
 *   GET    /{ingredient}              → show
 *   PUT    /{ingredient}              → update
 *   DELETE /{ingredient}              → destroy
 *   POST   /{ingredient}/add-stock    → addStock
 *   GET    /{ingredient}/movements    → movements
 *
 * Validates: Requirements 9.1, 9.2, 9.5, 9.6, 9.7
 */
class InventoryController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * List all ingredients with critical stock indicator.
     *
     * Validates: Requirement 9.1, 9.5
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with('suppliers')->orderBy('ingredient_name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('ingredient_name', 'like', "%{$search}%");
        }

        $paginator = $query->paginate($request->integer('per_page', 10));

        return response()->json([
            'message' => 'Daftar bahan baku berhasil diambil.',
            'data'    => $paginator->getCollection()->map(fn (Inventory $item) => $this->formatIngredient($item))->values(),
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
     * Show a single ingredient.
     */
    public function show(Inventory $ingredient): JsonResponse
    {
        return response()->json([
            'message' => 'Detail bahan baku berhasil diambil.',
            'data'    => $this->formatIngredient($ingredient->load('suppliers')),
        ]);
    }

    /**
     * Create a new ingredient.
     *
     * Validates: Requirement 9.1
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_name' => 'required|string|max:255',
            'unit'            => 'required|string|max:50',
            'current_stock'   => 'required|numeric|min:0',
            'min_stock'       => 'required|numeric|min:0',
            'supplier'        => 'nullable|string|max:255',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'last_price'      => 'nullable|numeric|min:0',
            'lead_time_days'  => 'nullable|integer|min:0',
        ]);

        $this->validateSupplierPurchaseFields($validated);

        $ingredient = Inventory::create(collect($validated)->except([
            'supplier_id',
            'last_price',
            'lead_time_days',
        ])->all());

        $this->syncPrimarySupplier($ingredient, $validated);

        return response()->json([
            'message' => 'Bahan baku berhasil ditambahkan.',
            'data'    => $this->formatIngredient($ingredient->load('suppliers')),
        ], 201);
    }

    /**
     * Update an existing ingredient.
     */
    public function update(Request $request, Inventory $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_name' => 'sometimes|required|string|max:255',
            'unit'            => 'sometimes|required|string|max:50',
            'current_stock'   => 'sometimes|required|numeric|min:0',
            'min_stock'       => 'sometimes|required|numeric|min:0',
            'supplier'        => 'nullable|string|max:255',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'last_price'      => 'nullable|numeric|min:0',
            'lead_time_days'  => 'nullable|integer|min:0',
        ]);

        $this->validateSupplierPurchaseFields($validated);

        $ingredient->update(collect($validated)->except([
            'supplier_id',
            'last_price',
            'lead_time_days',
        ])->all());

        $this->syncPrimarySupplier($ingredient, $validated);

        return response()->json([
            'message' => 'Bahan baku berhasil diperbarui.',
            'data'    => $this->formatIngredient($ingredient->fresh()->load('suppliers')),
        ]);
    }

    /**
     * Delete an ingredient.
     */
    public function destroy(Inventory $ingredient): JsonResponse
    {
        $ingredient->delete();

        return response()->json([
            'message' => 'Bahan baku berhasil dihapus.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Stock Operations
    // -------------------------------------------------------------------------

    /**
     * Add stock to an ingredient (type: in).
     *
     * POST /api/admin/inventory/{ingredient}/add-stock
     *
     * Validates: Requirements 9.2, 9.7
     */
    public function addStock(Request $request, Inventory $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'note'     => 'nullable|string|max:500',
        ]);

        // Delegate validation and business logic to StockService
        $movement = $this->stockService->addStock(
            ingredientId: $ingredient->id,
            quantity: (float) $validated['quantity'],
            note: $validated['note'] ?? '',
            createdBy: $request->user()?->id,
        );

        $ingredient->refresh();

        return response()->json([
            'message'    => 'Stok berhasil ditambahkan.',
            'data'       => [
                'movement'   => $this->formatMovement($movement),
                'ingredient' => $this->formatIngredient($ingredient),
            ],
        ], 201);
    }

    /**
     * Get stock movement history for an ingredient.
     *
     * GET /api/admin/inventory/{ingredient}/movements
     *
     * Validates: Requirement 9.6
     */
    public function movements(Request $request, Inventory $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $dateRange = [];

        if (! empty($validated['from'])) {
            $dateRange['from'] = Carbon::parse($validated['from'])->startOfDay();
        }

        if (! empty($validated['to'])) {
            $dateRange['to'] = Carbon::parse($validated['to'])->endOfDay();
        }

        $movements = $this->stockService->getStockHistory($ingredient->id, $dateRange);

        return response()->json([
            'message' => 'Riwayat pergerakan stok berhasil diambil.',
            'data'    => $movements->map(fn (mixed $m) => $this->formatMovement($m)),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Format an Inventory model for API response.
     *
     * @return array<string, mixed>
     */
    private function formatIngredient(Inventory $ingredient): array
    {
        if (! $ingredient->relationLoaded('suppliers')) {
            $ingredient->load('suppliers');
        }

        $primarySupplier = $ingredient->primarySupplier();

        return [
            'id'               => $ingredient->id,
            'ingredient_name'  => $ingredient->ingredient_name,
            'unit'             => $ingredient->unit,
            'current_stock'    => $ingredient->current_stock,
            'min_stock'        => $ingredient->min_stock,
            'supplier'         => $primarySupplier?->name ?? $ingredient->supplier,
            'supplier_id'      => $primarySupplier?->id,
            'last_price'       => $primarySupplier?->pivot?->last_price,
            'lead_time_days'   => $primarySupplier?->pivot?->lead_time_days,
            'suppliers'        => $ingredient->suppliers->map(fn (Supplier $supplier) => [
                'id'             => $supplier->id,
                'name'           => $supplier->name,
                'last_price'     => $supplier->pivot->last_price,
                'lead_time_days' => $supplier->pivot->lead_time_days,
                'is_primary'     => (bool) $supplier->pivot->is_primary,
            ])->values(),
            'is_critical'      => $ingredient->isCriticalStock(),
            'created_at'       => $ingredient->created_at,
            'updated_at'       => $ingredient->updated_at,
        ];
    }

    /**
     * Purchase metadata is stored on the supplier relationship, not directly on inventory.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateSupplierPurchaseFields(array $validated): void
    {
        if (! empty($validated['supplier_id'])) {
            return;
        }

        if (($validated['last_price'] ?? null) !== null || ($validated['lead_time_days'] ?? null) !== null) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Pilih supplier sebelum mengisi harga terakhir atau lead time.',
            ]);
        }
    }

    /**
     * Keep the new master supplier relation in sync while preserving the old text column.
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncPrimarySupplier(Inventory $ingredient, array $validated): void
    {
        if (! array_key_exists('supplier_id', $validated)) {
            return;
        }

        DB::table('inventory_supplier')
            ->where('inventory_id', $ingredient->id)
            ->update(['is_primary' => false, 'updated_at' => now()]);

        if (! $validated['supplier_id']) {
            $ingredient->suppliers()->detach();
            $ingredient->update(['supplier' => $validated['supplier'] ?? null]);
            return;
        }

        $supplier = Supplier::find($validated['supplier_id']);

        $ingredient->suppliers()->syncWithoutDetaching([
            $validated['supplier_id'] => [
                'last_price' => $validated['last_price'] ?? null,
                'lead_time_days' => $validated['lead_time_days'] ?? null,
                'is_primary' => true,
            ],
        ]);

        $ingredient->update(['supplier' => $supplier?->name]);
    }

    /**
     * Format a StockMovement model for API response.
     *
     * @return array<string, mixed>
     */
    private function formatMovement(mixed $movement): array
    {
        return [
            'id'              => $movement->id,
            'ingredient_id'   => $movement->ingredient_id,
            'quantity_change' => $movement->quantity_change,
            'type'            => $movement->type,
            'note'            => $movement->note,
            'order_id'        => $movement->order_id,
            'created_by'      => $movement->created_by,
            'creator'         => $movement->relationLoaded('creator') && $movement->creator ? [
                'id' => $movement->creator->id,
                'name' => $movement->creator->name,
            ] : null,
            'order'           => $movement->relationLoaded('order') && $movement->order ? [
                'id' => $movement->order->id,
                'order_status' => $movement->order->order_status,
            ] : null,
            'created_at'      => $movement->created_at,
        ];
    }
}
