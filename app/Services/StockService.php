<?php

namespace App\Services;

use App\Events\StockCritical;
use App\Models\Inventory;
use App\Models\MenuIngredientMap;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * StockService — Core business logic for the Stock_Manager module.
 *
 * Responsibilities:
 *  - Add stock (type `in`) with DB transaction
 *  - Deduct stock (type `out`) with DB transaction
 *  - Auto-deduct stock when an order is created
 *  - Detect and broadcast critical stock events
 *  - Validate stock input (reject negative / zero / non-numeric)
 *  - Retrieve critical stock items
 *  - Retrieve stock movement history
 *
 * Validates: Requirements 9.2, 9.3, 9.4, 9.6, 9.7
 */
class StockService
{
    // -------------------------------------------------------------------------
    // Input Validation
    // -------------------------------------------------------------------------

    /**
     * Validate that a stock quantity is a positive number.
     *
     * Returns true when valid, throws ValidationException otherwise.
     *
     * @param  mixed  $quantity
     * @return bool
     *
     * @throws ValidationException  when quantity is not a positive number
     *
     * Validates: Requirement 9.7
     */
    public function validateStockInput(mixed $quantity): bool
    {
        if (! is_numeric($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah stok harus berupa angka.',
            ]);
        }

        $qty = (float) $quantity;

        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah stok harus lebih dari 0.',
            ]);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Add Stock (type: in)
    // -------------------------------------------------------------------------

    /**
     * Add stock to an ingredient and record a `stock_movements` entry of type `in`.
     *
     * Uses a DB transaction to ensure both the inventory update and the movement
     * record are persisted atomically.
     *
     * After the update, broadcasts a StockCritical event if the ingredient is
     * still at or below its minimum stock level.
     *
     * @param  int         $ingredientId
     * @param  float       $quantity      Must be > 0
     * @param  string      $note
     * @param  int|null    $createdBy     User ID of the admin performing the action
     * @return StockMovement
     *
     * @throws ValidationException  when quantity ≤ 0 or non-numeric
     *
     * Validates: Requirements 9.2, 9.7
     */
    public function addStock(int $ingredientId, float $quantity, string $note, ?int $createdBy = null): StockMovement
    {
        $this->validateStockInput($quantity);

        $movement = DB::transaction(function () use ($ingredientId, $quantity, $note, $createdBy) {
            /** @var Inventory $ingredient */
            $ingredient = Inventory::lockForUpdate()->findOrFail($ingredientId);

            $ingredient->increment('current_stock', $quantity);
            $ingredient->refresh();

            $movement = StockMovement::create([
                'ingredient_id'   => $ingredientId,
                'quantity_change' => $quantity,
                'type'            => 'in',
                'note'            => $note,
                'order_id'        => null,
                'created_by'      => $createdBy,
            ]);

            return $movement;
        });

        // Check critical stock after transaction (outside lock)
        $ingredient = Inventory::find($ingredientId);
        if ($ingredient && $ingredient->isCriticalStock()) {
            $this->broadcastStockCriticalSafely($ingredient);
        }

        return $movement;
    }

    // -------------------------------------------------------------------------
    // Deduct Stock (type: out)
    // -------------------------------------------------------------------------

    /**
     * Deduct stock from an ingredient and record a `stock_movements` entry of type `out`.
     *
     * Uses a DB transaction to ensure both the inventory update and the movement
     * record are persisted atomically.
     *
     * After the update, broadcasts a StockCritical event if the ingredient has
     * become critical (current_stock ≤ min_stock).
     *
     * @param  int         $ingredientId
     * @param  float       $quantity      Must be > 0
     * @param  string      $note
     * @param  int|null    $orderId       Order that triggered this deduction
     * @param  int|null    $createdBy     User ID performing the action
     * @return StockMovement
     *
     * @throws ValidationException  when quantity ≤ 0 or non-numeric
     *
     * Validates: Requirements 9.3, 9.7
     */
    public function deductStock(
        int $ingredientId,
        float $quantity,
        string $note,
        ?int $orderId = null,
        ?int $createdBy = null
    ): StockMovement {
        $this->validateStockInput($quantity);

        $movement = DB::transaction(function () use ($ingredientId, $quantity, $note, $orderId, $createdBy) {
            /** @var Inventory $ingredient */
            $ingredient = Inventory::lockForUpdate()->findOrFail($ingredientId);

            if ((float) $ingredient->current_stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$ingredient->ingredient_name} tidak cukup.",
                ]);
            }

            $ingredient->decrement('current_stock', $quantity);
            $ingredient->refresh();

            $movement = StockMovement::create([
                'ingredient_id'   => $ingredientId,
                'quantity_change' => $quantity,
                'type'            => 'out',
                'note'            => $note,
                'order_id'        => $orderId,
                'created_by'      => $createdBy,
            ]);

            return $movement;
        });

        // Check critical stock after transaction (outside lock)
        $ingredient = Inventory::find($ingredientId);
        if ($ingredient && $ingredient->isCriticalStock()) {
            $this->broadcastStockCriticalSafely($ingredient);
        }

        return $movement;
    }

    // -------------------------------------------------------------------------
    // Auto-deduct for Order
    // -------------------------------------------------------------------------

    /**
     * Automatically deduct stock for all ingredients used in an order.
     *
     * For each order item, looks up the MenuIngredientMap to find which
     * ingredients are needed and how much per portion. Deducts
     * (quantity_used × order_item.quantity) for each ingredient.
     *
     * Skips menu items that have no ingredient mapping.
     *
     * This method is called inside the same DB transaction as order creation
     * (from OrderService::createOrder).
     *
     * @param  Order  $order
     * @return void
     *
     * Validates: Requirement 9.3
     */
    public function deductStockForOrder(Order $order, ?array $orderItemIds = null): void
    {
        $order->loadMissing('orderItems');

        $orderItems = $order->orderItems;
        if ($orderItemIds !== null) {
            $orderItems = $orderItems->whereIn('id', $orderItemIds);
        }

        foreach ($orderItems as $orderItem) {
            // Find all ingredient mappings for this menu item
            $mappings = MenuIngredientMap::where('menu_id', $orderItem->menu_id)->get();

            foreach ($mappings as $mapping) {
                $totalDeduction = (float) $mapping->quantity_used * (int) $orderItem->quantity;

                if ($totalDeduction <= 0) {
                    continue;
                }

                $this->deductStock(
                    ingredientId: (int) $mapping->ingredient_id,
                    quantity: $totalDeduction,
                    note: "Auto-deduct untuk pesanan #{$order->id}",
                    orderId: $order->id,
                    createdBy: null,
                );
            }
        }
    }

    private function broadcastStockCriticalSafely(Inventory $ingredient): void
    {
        try {
            broadcast(new StockCritical($ingredient));
        } catch (Throwable $e) {
            Log::warning('Stock critical realtime broadcast failed.', [
                'ingredient_id' => $ingredient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Critical Stock
    // -------------------------------------------------------------------------

    /**
     * Get all ingredients that are at or below their minimum stock level.
     *
     * @return Collection<int, Inventory>
     *
     * Validates: Requirement 9.4, 9.5
     */
    public function getCriticalStockItems(): Collection
    {
        return Inventory::whereColumn('current_stock', '<=', 'min_stock')->get();
    }

    // -------------------------------------------------------------------------
    // Stock History
    // -------------------------------------------------------------------------

    /**
     * Get stock movement history for a specific ingredient.
     *
     * @param  int        $ingredientId
     * @param  array|null $dateRange     Optional ['from' => Carbon, 'to' => Carbon]
     * @return Collection<int, StockMovement>
     *
     * Validates: Requirement 9.6
     */
    public function getStockHistory(int $ingredientId, ?array $dateRange = null): Collection
    {
        $query = StockMovement::where('ingredient_id', $ingredientId)
            ->with(['creator', 'order'])
            ->orderByDesc('created_at');

        if (! empty($dateRange['from'])) {
            $query->where('created_at', '>=', $dateRange['from']);
        }

        if (! empty($dateRange['to'])) {
            $query->where('created_at', '<=', $dateRange['to']);
        }

        return $query->get();
    }
}
