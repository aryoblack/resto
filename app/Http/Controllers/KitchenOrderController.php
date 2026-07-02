<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * KitchenOrderController — KDS (Kitchen Display System) endpoint.
 *
 * Returns only orders with status `Dimasak`, including:
 *   - Order items with menu name, quantity, variant, note
 *   - Table number
 *   - Time elapsed since order creation
 *
 * Routes:
 *   GET /api/staff/kds
 *
 * Validates: Requirements 7.4, 17.1
 */
class KitchenOrderController extends Controller
{
    /**
     * Get all orders currently being cooked (status = Dimasak).
     *
     * GET /api/staff/kds
     *
     * Validates: Requirement 7.4 — "THE KDS SHALL display only orders with status Dimasak."
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['orderItems.menu', 'table'])
            ->where('order_status', 'Dimasak')
            ->oldest() // oldest first so kitchen processes in order
            ->get();

        $data = $orders->map(fn (Order $order) => $this->formatKitchenOrder($order));

        return response()->json(['data' => $data]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Format an order for the KDS display.
     *
     * @return array<string, mixed>
     */
    private function formatKitchenOrder(Order $order): array
    {
        $minutesElapsed = (int) $order->created_at->diffInMinutes(now());

        return [
            'id'              => $order->id,
            'order_number'    => $order->order_number,
            'order_type'      => $order->order_type,
            'order_status'    => $order->order_status,
            'notes'           => $order->notes,
            'table'           => $order->table ? [
                'id'           => $order->table->id,
                'table_number' => $order->table->table_number,
            ] : null,
            'minutes_elapsed' => $minutesElapsed,
            'created_at'      => $order->created_at,
            'items'           => $order->orderItems->map(fn ($item) => [
                'id'               => $item->id,
                'menu_id'          => $item->menu_id,
                'menu_name'        => $item->menu?->name,
                'quantity'         => $item->quantity,
                'variant_selected' => $item->variant_selected,
                'note'             => $item->note,
            ])->values(),
        ];
    }
}
