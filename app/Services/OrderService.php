<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\OrderUpdated;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Promo;
use App\Models\SystemSetting;
use App\Models\VoucherUsage;
use App\Services\PushNotificationService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * OrderService — Core business logic for the Order_Manager module.
 *
 * Responsibilities:
 *  - Create orders from cart items (validate stock, snapshot price, calculate total)
 *  - Enforce state machine transitions
 *  - Cancel orders (admin only, restore stock placeholder)
 *  - Trigger table auto-release
 *  - Broadcast real-time events
 *
 * Validates: Requirements 5.5, 5.6, 6.3, 7.2, 7.3, 10.3
 */
class OrderService
{
    private const DEFAULT_TAX_PERCENTAGE = 11.0;
    private const DEFAULT_SERVICE_CHARGE_PERCENTAGE = 5.0;

    /**
     * Valid state machine transitions.
     *
     * Key   = current status
     * Value = allowed next statuses
     *
     * Validates: Requirement 7.3
     */
    private const TRANSITIONS = [
        'Diterima'   => ['Diproses', 'Dibatalkan'],
        'Diproses'   => ['Dimasak',  'Dibatalkan'],
        'Dimasak'    => ['Selesai',  'Dibatalkan'],
        'Selesai'    => ['Disajikan','Dibatalkan'],
        'Disajikan'  => ['Dibatalkan'],
        'Dibatalkan' => [],
    ];

    public function __construct(
        private readonly StockService $stockService,
        private readonly PushNotificationService $pushNotificationService,
    ) {
    }

    // -------------------------------------------------------------------------
    // Create Order
    // -------------------------------------------------------------------------

    /**
     * Create a new order from the customer's cart.
     *
     * Steps:
     *  1. Validate stock for every item
     *  2. Calculate total (subtotal + tax + service_charge - discount)
     *  3. Persist order + order_items inside a DB transaction
     *  4. Broadcast OrderCreated event
     *
     * @param  CreateOrderRequest  $request
     * @param  int|null            $userId   Authenticated user ID (null for walk-in)
     * @return Order
     *
     * @throws \Illuminate\Validation\ValidationException  when stock is insufficient
     *
     * Validates: Requirements 5.5, 5.6
     */
    public function createOrder(CreateOrderRequest $request, ?int $userId): Order
    {
        $items = $request->input('items');

        // --- 1. Validate stock ---
        $insufficientItems = $this->checkStock($items);

        if (! empty($insufficientItems)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'items' => 'Beberapa item tidak memiliki stok yang cukup.',
                'insufficient_items' => $insufficientItems,
            ]);
        }

        // --- 2. Persist inside a transaction and let the backend calculate totals ---
        $order = DB::transaction(function () use ($request, $userId, $items) {
            $order = $this->findOpenTableBill($request);
            $isNewOrder = ! $order;
            $requestedVoucherCode = $this->normalizeVoucherCode($request->input('voucher_code'));
            $shouldCountVoucherUsage = false;

            if ($isNewOrder) {
                $shouldCountVoucherUsage = (bool) $requestedVoucherCode;
                $order = Order::create([
                    'user_id'        => $userId,
                    'table_id'       => $request->input('table_id'),
                    'total_price'    => 0,
                    'discount_amount'=> 0,
                    'tax_amount'     => 0,
                    'service_charge' => 0,
                    'payment_method' => $request->input('payment_method'),
                    'payment_status' => 'pending',
                    'order_status'   => 'Diterima',
                    'order_type'     => $request->input('order_type', 'dine_in'),
                    'voucher_code'   => $requestedVoucherCode,
                    'notes'          => $request->input('notes'),
                ]);
            } elseif ($requestedVoucherCode && $order->voucher_code && $order->voucher_code !== $requestedVoucherCode) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Bill meja ini sudah memakai voucher lain.',
                ]);
            } elseif ($requestedVoucherCode && ! $order->voucher_code) {
                $order->voucher_code = $requestedVoucherCode;
                $shouldCountVoucherUsage = true;
            }

            $newOrderItemIds = [];
            foreach ($items as $item) {
                $menu = Menu::with('variants')->find($item['menu_id']);
                $variant = $menu ? $this->resolveVariant($item, $menu) : null;

                $orderItem = $order->orderItems()->create([
                    'menu_id'          => $item['menu_id'],
                    'quantity'         => $item['quantity'],
                    'variant_selected' => $variant?->variant_name ?? ($item['variant_selected'] ?? null),
                    'note'             => $item['note'] ?? null,
                    'price_at_time'    => $menu ? $this->unitPrice($menu, $variant) : 0,
                ]);
                $newOrderItemIds[] = $orderItem->id;
            }

            $order->load('orderItems');
            $subtotal = $this->subtotalFromOrderItems($order->orderItems);
            $voucher = $this->resolveBillVoucher($order, $subtotal, $userId, $shouldCountVoucherUsage);
            $mergedTotals = $this->totalsFromSubtotal($subtotal, $voucher['discount_amount']);
            $order->update([
                'total_price'    => $mergedTotals['total_price'],
                'tax_amount'     => $mergedTotals['tax_amount'],
                'service_charge' => $mergedTotals['service_charge'],
                'discount_amount'=> $mergedTotals['discount_amount'],
                'payment_method' => $order->payment_method ?: $request->input('payment_method'),
                'voucher_code'   => $voucher['code'],
                'notes'          => $this->mergeNotes($order->notes, $request->input('notes')),
            ]);

            // Auto-deduct ingredient stock based on MenuIngredientMap (Requirement 9.3)
            $this->stockService->deductStockForOrder($order, $newOrderItemIds);

            return $order;
        });

        // --- 4. Broadcast ---
        $this->broadcastSafely(new OrderCreated($order), 'Order created realtime broadcast failed.', [
            'order_id' => $order->id,
        ]);

        return $order->load(['orderItems.menu', 'table']);
    }

    /**
     * Add one or more items to an open order and recalculate the bill.
     *
     * @param  array<int, array{menu_id: int, quantity: int, variant_id?: int|null, variant_selected?: string|null, note?: string|null}>  $items
     */
    public function addItems(Order $order, array $items): Order
    {
        if (in_array($order->order_status, ['Disajikan', 'Dibatalkan'], true)) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan yang sudah disajikan atau dibatalkan tidak bisa ditambah item.',
            ]);
        }

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order' => 'Pesanan yang sudah lunas tidak bisa ditambah item. Buat tagihan baru untuk tambahan pesanan.',
            ]);
        }

        $insufficientItems = $this->checkStock($items);
        if (! empty($insufficientItems)) {
            throw ValidationException::withMessages([
                'items' => 'Beberapa item tidak memiliki stok yang cukup.',
                'insufficient_items' => $insufficientItems,
            ]);
        }

        $order = DB::transaction(function () use ($order, $items) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedOrder->order_status, ['Disajikan', 'Dibatalkan'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan yang sudah disajikan atau dibatalkan tidak bisa ditambah item.',
                ]);
            }

            if ($lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan yang sudah lunas tidak bisa ditambah item. Buat tagihan baru untuk tambahan pesanan.',
                ]);
            }

            $newOrderItemIds = [];
            foreach ($items as $item) {
                $menu = Menu::with('variants')->find($item['menu_id']);
                $variant = $menu ? $this->resolveVariant($item, $menu) : null;

                $orderItem = $lockedOrder->orderItems()->create([
                    'menu_id'          => $item['menu_id'],
                    'quantity'         => $item['quantity'],
                    'variant_selected' => $variant?->variant_name ?? ($item['variant_selected'] ?? null),
                    'note'             => $item['note'] ?? null,
                    'price_at_time'    => $menu ? $this->unitPrice($menu, $variant) : 0,
                ]);
                $newOrderItemIds[] = $orderItem->id;
            }

            $lockedOrder->load('orderItems');
            $subtotal = $this->subtotalFromOrderItems($lockedOrder->orderItems);
            $voucher = $this->resolveBillVoucher($lockedOrder, $subtotal, $lockedOrder->user_id, false);
            $totals = $this->totalsFromSubtotal($subtotal, $voucher['discount_amount']);

            $lockedOrder->update([
                'total_price'     => $totals['total_price'],
                'tax_amount'      => $totals['tax_amount'],
                'service_charge'  => $totals['service_charge'],
                'discount_amount' => $totals['discount_amount'],
                'voucher_code'    => $voucher['code'],
            ]);

            $this->stockService->deductStockForOrder($lockedOrder, $newOrderItemIds);

            return $lockedOrder;
        });

        $order = $order->fresh(['orderItems.menu', 'table', 'user', 'rating']);

        $this->broadcastSafely(new OrderUpdated($order), 'Order update realtime broadcast failed.', [
            'order_id' => $order->id,
        ]);

        return $order;
    }

    private function findOpenTableBill(CreateOrderRequest $request): ?Order
    {
        if ($request->input('order_type', 'dine_in') !== 'dine_in' || ! $request->filled('table_id')) {
            return null;
        }

        return Order::where('table_id', $request->input('table_id'))
            ->where('order_type', 'dine_in')
            ->where('payment_status', 'pending')
            ->whereNotIn('order_status', ['Disajikan', 'Dibatalkan'])
            ->lockForUpdate()
            ->oldest()
            ->first();
    }

    private function normalizeVoucherCode(mixed $code): ?string
    {
        $normalized = strtoupper(trim((string) $code));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array{code: string|null, discount_amount: float}
     */
    private function resolveBillVoucher(Order $order, float $subtotal, ?int $userId, bool $shouldCountUsage): array
    {
        $code = $this->normalizeVoucherCode($order->voucher_code);
        if (! $code) {
            return ['code' => null, 'discount_amount' => 0.0];
        }

        $promo = Promo::where('code', $code)->lockForUpdate()->first();
        if (! $promo) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Kode voucher tidak ditemukan.',
            ]);
        }

        if ($shouldCountUsage && $promo->usage_limit !== null && $promo->usage_count >= $promo->usage_limit) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Voucher sudah mencapai batas penggunaan.',
            ]);
        }

        $discountAmount = $this->validateLockedVoucherAndCalculateDiscount($promo, $subtotal);

        if ($shouldCountUsage) {
            $promo->increment('usage_count');
        }

        if ($userId) {
            VoucherUsage::updateOrCreate(
                [
                    'promo_id' => $promo->id,
                    'order_id' => $order->id,
                ],
                [
                    'user_id'          => $userId,
                    'discount_applied' => $discountAmount,
                ]
            );
        }

        return ['code' => $code, 'discount_amount' => $discountAmount];
    }

    private function validateLockedVoucherAndCalculateDiscount(Promo $promo, float $subtotal): float
    {
        if (! $promo->is_active) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Voucher tidak aktif.',
            ]);
        }

        $today = now()->toDateString();
        if ($promo->start_date > $today || $promo->end_date < $today) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Voucher sudah kedaluwarsa atau belum berlaku.',
            ]);
        }

        if ($subtotal < (float) $promo->min_purchase) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Total belanja minimum untuk voucher ini adalah Rp ' . number_format((float) $promo->min_purchase, 0, ',', '.') . '.',
            ]);
        }

        return app(PromoService::class)->calculateDiscount($promo, $subtotal);
    }

    private function mergeNotes(?string $currentNotes, ?string $newNotes): ?string
    {
        $newNotes = trim((string) $newNotes);
        if ($newNotes === '') {
            return $currentNotes;
        }

        $currentNotes = trim((string) $currentNotes);
        if ($currentNotes === '') {
            return $newNotes;
        }

        return $currentNotes . "\n" . $newNotes;
    }

    // -------------------------------------------------------------------------
    // Update Status
    // -------------------------------------------------------------------------

    /**
     * Update an order's status, enforcing the state machine.
     *
     * @param  Order   $order
     * @param  string  $newStatus
     * @param  int     $actorId    ID of the user performing the update
     * @param  bool    $isAdmin    Whether the actor is an admin (required for cancellation)
     * @return Order
     *
     * @throws \Illuminate\Validation\ValidationException  on invalid transition
     *
     * Validates: Requirements 7.2, 7.3
     */
    public function updateStatus(Order $order, string $newStatus, int $actorId, bool $isAdmin = false): Order
    {
        $currentStatus = $order->order_status;

        // Cancellation is admin-only
        if ($newStatus === 'Dibatalkan' && ! $isAdmin) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Hanya admin yang dapat membatalkan pesanan.',
            ]);
        }

        // Validate transition
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => "Transisi status dari '{$currentStatus}' ke '{$newStatus}' tidak diizinkan. "
                    . 'Status berikutnya yang valid: ' . implode(', ', $allowed),
            ]);
        }

        $previousStatus = $currentStatus;

        $order->update(['order_status' => $newStatus]);

        // Trigger table auto-release when order is served and paid
        if ($newStatus === 'Disajikan' && $order->table_id) {
            $order->table?->releaseIfAllOrdersComplete();
        }

        // Broadcast status change
        $this->broadcastSafely(new OrderStatusUpdated($order, $previousStatus, $newStatus), 'Order status realtime broadcast failed.', [
            'order_id' => $order->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ]);

        // Send push notification to the customer (Requirement 17.2, 8.3)
        if ($order->user_id && $order->user) {
            $this->pushNotificationService->sendToUser($order->user, [
                'title' => 'Status Pesanan Diperbarui',
                'body'  => 'Pesanan #' . ($order->order_number ?: $order->id) . " sekarang berstatus: {$newStatus}",
                'url'   => '/customer/orders/' . $order->id,
                'icon'  => '/icons/icon-192x192.png',
            ]);
        }

        return $order->fresh(['orderItems.menu', 'table']);
    }

    private function broadcastSafely(object $event, string $message, array $context = []): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (Throwable $e) {
            Log::warning($message, [
                ...$context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Cancel Order (admin only)
    // -------------------------------------------------------------------------

    /**
     * Cancel an order (admin only).
     *
     * Note: Stock restoration is handled by Stock_Manager (Task 8).
     * This method only updates the order status.
     *
     * @param  Order  $order
     * @param  int    $actorId
     * @return Order
     *
     * Validates: Requirement 7.3 (Dibatalkan transition)
     */
    public function cancelOrder(Order $order, int $actorId): Order
    {
        return $this->updateStatus($order, 'Dibatalkan', $actorId, isAdmin: true);
    }

    // -------------------------------------------------------------------------
    // Stock Validation
    // -------------------------------------------------------------------------

    /**
     * Check stock availability for all cart items.
     *
     * Returns an array of items with insufficient stock.
     * An empty array means all items are available.
     *
     * @param  array<int, array{menu_id: int, quantity: int}>  $items
     * @return array<int, array{menu_id: int, menu_name: string, requested: int, available: int}>
     *
     * Validates: Requirement 5.6
     */
    public function checkStock(array $items): array
    {
        $insufficient = [];

        // Aggregate quantities per menu_id (in case the same menu appears multiple times)
        $aggregated = [];
        foreach ($items as $item) {
            $menuId = $item['menu_id'];
            $aggregated[$menuId] = ($aggregated[$menuId] ?? 0) + (int) $item['quantity'];
        }

        $menus = Menu::whereIn('id', array_keys($aggregated))->get()->keyBy('id');

        foreach ($aggregated as $menuId => $totalQty) {
            $menu = $menus->get($menuId);

            if (! $menu) {
                $insufficient[] = [
                    'menu_id'   => $menuId,
                    'menu_name' => 'Unknown',
                    'requested' => $totalQty,
                    'available' => 0,
                ];
                continue;
            }

            if ($menu->stock < $totalQty) {
                $insufficient[] = [
                    'menu_id'   => $menuId,
                    'menu_name' => $menu->name,
                    'requested' => $totalQty,
                    'available' => $menu->stock,
                ];
            }
        }

        return $insufficient;
    }

    // -------------------------------------------------------------------------
    // Total Calculation
    // -------------------------------------------------------------------------

    /**
     * Calculate order totals.
     *
     * Formula:
     *   subtotal       = Σ(price_at_time × quantity)
     *   tax_amount     = subtotal × (tax_percentage / 100)
     *   service_charge = subtotal × (service_charge_percentage / 100)
     *   total_price    = subtotal + tax_amount + service_charge - discount_amount
     *
     * @param  array<int, array{menu_id: int, quantity: int}>  $items
     * @param  float  $discountAmount  Pre-calculated discount (from voucher/points)
     * @return array{subtotal: float, tax_amount: float, service_charge: float, discount_amount: float, total_price: float}
     *
     * Validates: Requirements 5.3, 5.5
     */
    public function calculateTotals(array $items, float $discountAmount = 0.0): array
    {
        // Fetch menu prices and variants
        $menuIds = array_column($items, 'menu_id');
        $menus   = Menu::with('variants')->whereIn('id', $menuIds)->get()->keyBy('id');

        $subtotal = 0.0;

        foreach ($items as $item) {
            $menu = $menus->get($item['menu_id']);
            if ($menu) {
                $variant = $this->resolveVariant($item, $menu);
                $subtotal += $this->unitPrice($menu, $variant) * (int) $item['quantity'];
            }
        }

        return $this->totalsFromSubtotal($subtotal, $discountAmount);
    }

    private function calculateTotalsFromOrderItems(iterable $orderItems, float $discountAmount = 0.0): array
    {
        return $this->totalsFromSubtotal($this->subtotalFromOrderItems($orderItems), $discountAmount);
    }

    private function subtotalFromOrderItems(iterable $orderItems): float
    {
        $subtotal = 0.0;

        foreach ($orderItems as $orderItem) {
            $subtotal += (float) $orderItem->price_at_time * (int) $orderItem->quantity;
        }

        return round($subtotal, 2);
    }

    private function totalsFromSubtotal(float $subtotal, float $discountAmount = 0.0): array
    {
        $discountAmount = min(max(0.0, $discountAmount), $subtotal);
        $taxableBase = max(0.0, $subtotal - $discountAmount);

        $taxPercentage           = $this->taxPercentage();
        $serviceChargePercentage = $this->serviceChargePercentage();

        $taxAmount     = $taxableBase * ($taxPercentage / 100);
        $serviceCharge = $taxableBase * ($serviceChargePercentage / 100);
        $totalPrice    = $taxableBase + $taxAmount + $serviceCharge;

        return [
            'subtotal'        => round($subtotal, 2),
            'tax_amount'      => round($taxAmount, 2),
            'service_charge'  => round($serviceCharge, 2),
            'discount_amount' => round($discountAmount, 2),
            'total_price'     => round($totalPrice, 2),
        ];
    }

    private function unitPrice(Menu $menu, mixed $variant = null): float
    {
        return (float) $menu->price + (float) ($variant?->extra_price ?? 0);
    }

    private function taxPercentage(): float
    {
        return (float) SystemSetting::getValue('tax_percentage', self::DEFAULT_TAX_PERCENTAGE);
    }

    private function serviceChargePercentage(): float
    {
        return (float) SystemSetting::getValue('service_charge_percentage', self::DEFAULT_SERVICE_CHARGE_PERCENTAGE);
    }

    private function resolveVariant(array $item, Menu $menu): mixed
    {
        if (! empty($item['variant_id'])) {
            $variant = $menu->variants->firstWhere('id', (int) $item['variant_id']);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'items' => "Varian yang dipilih tidak tersedia untuk menu {$menu->name}.",
                ]);
            }

            return $variant;
        }

        if (! empty($item['variant_selected'])) {
            return $menu->variants->firstWhere('variant_name', $item['variant_selected']);
        }

        return null;
    }
}
