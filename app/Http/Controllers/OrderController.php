<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OrderController — HTTP layer for the Order_Manager module.
 *
 * Routes:
 *   POST   /api/customer/orders              → store
 *   GET    /api/staff/orders                 → index
 *   GET    /api/staff/orders/{order}         → show
 *   PATCH  /api/staff/orders/{order}/status  → updateStatus
 *   POST   /api/admin/orders/{order}/cancel  → cancel
 *
 * Validates: Requirements 5.5, 5.6, 7.2, 7.3
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    // -------------------------------------------------------------------------
    // 6.1 — store: Create order from cart
    // -------------------------------------------------------------------------

    /**
     * Create a new order from the customer's cart.
     *
     * POST /api/customer/orders
     *
     * Validates: Requirements 5.5, 5.6
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $order = $this->orderService->createOrder($request, $userId);

        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'data'    => $this->formatOrder($order),
        ], 201);
    }

    /**
     * List orders owned by the authenticated customer.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $query = Order::with(['orderItems.menu', 'table', 'rating'])
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('order_status', $request->input('status'));
        }

        $orders = $query->limit(50)->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order) => $this->formatOrder($order))->values(),
        ]);
    }

    /**
     * Show a customer order for local guest tracking.
     *
     * Public access is restricted by table_id to avoid exposing arbitrary orders
     * from simple ID enumeration.
     */
    public function customerShow(Request $request, Order $order): JsonResponse
    {
        $tableId = $request->integer('table_id');

        if (! $order->table_id || $tableId !== (int) $order->table_id) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        $order->load(['orderItems.menu', 'table', 'rating']);

        return response()->json([
            'data' => $this->formatOrder($order),
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.1 — index: List orders with filters
    // -------------------------------------------------------------------------

    /**
     * List orders with optional filters.
     *
     * GET /api/staff/orders
     *
     * Query params:
     *   - order_type   (dine_in|delivery)
     *   - order_status (Diterima|Diproses|Dimasak|Selesai|Disajikan|Dibatalkan)
     *   - table_id     (integer)
     *   - per_page     (integer, default 10)
     *
     * Validates: Requirement 7.6
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['orderItems.menu', 'table', 'user', 'rating'])
            ->latest();

        $orderType = $request->input('order_type', $request->input('type'));
        if ($orderType) {
            $query->where('order_type', $orderType);
        }

        $orderStatus = $request->input('order_status', $request->input('status'));
        if ($orderStatus === 'active_kitchen') {
            $query->whereIn('order_status', ['Diterima', 'Diproses', 'Dimasak']);
        } elseif ($orderStatus) {
            $query->where('order_status', $orderStatus);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhereHas('table', fn ($table) => $table->where('table_number', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders  = $query->paginate($perPage);

        return response()->json([
            'data' => $orders->map(fn (Order $o) => $this->formatOrder($o)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
                'from'         => $orders->firstItem(),
                'to'           => $orders->lastItem(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.1 — show: Single order with items
    // -------------------------------------------------------------------------

    /**
     * Show a single order with all its items.
     *
     * GET /api/staff/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['orderItems.menu', 'table', 'user', 'rating']);

        return response()->json([
            'data' => $this->formatOrder($order),
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.3 — updateStatus: State machine transition
    // -------------------------------------------------------------------------

    /**
     * Update an order's status (state machine enforced).
     *
     * PATCH /api/staff/orders/{order}/status
     *
     * Validates: Requirements 7.2, 7.3
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        $order = $this->orderService->updateStatus(
            $order,
            $request->input('status'),
            $user->id,
            $isAdmin,
        );

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui.',
            'data'    => $this->formatOrder($order),
        ]);
    }

    /**
     * Add items to an open order from the staff order screen.
     */
    public function addItems(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.menu_id'          => ['required', 'integer', 'exists:menu,id'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.variant_id'       => ['nullable', 'integer', 'exists:variant,id'],
            'items.*.variant_selected' => ['nullable', 'string', 'max:255'],
            'items.*.note'             => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->orderService->addItems($order, $validated['items']);

        return response()->json([
            'message' => 'Item pesanan berhasil ditambahkan.',
            'data'    => $this->formatOrder($order),
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.4 — cancel: Admin-only cancellation
    // -------------------------------------------------------------------------

    /**
     * Cancel an order (admin only).
     *
     * POST /api/admin/orders/{order}/cancel
     *
     * Validates: Requirement 7.3 (Dibatalkan transition, admin only)
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $order = $this->orderService->cancelOrder($order, $request->user()->id);

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan.',
            'data'    => $this->formatOrder($order),
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Format an order for API responses.
     *
     * @return array<string, mixed>
     */
    private function formatOrder(Order $order): array
    {
        // Show rating prompt when order is Disajikan and has not been rated yet (Requirement 16.1)
        $showRatingPrompt = $order->order_status === 'Disajikan'
            && ! ($order->relationLoaded('rating') ? $order->rating : $order->rating()->exists());

        return [
            'id'                 => $order->id,
            'order_number'       => $order->order_number,
            'user_id'            => $order->user_id,
            'table_id'           => $order->table_id,
            'order_status'       => $order->order_status,
            'payment_status'     => $order->payment_status,
            'payment_method'     => $order->payment_method,
            'order_type'         => $order->order_type,
            'total_price'        => $order->total_price,
            'discount_amount'    => $order->discount_amount,
            'tax_amount'         => $order->tax_amount,
            'service_charge'     => $order->service_charge,
            'voucher_code'       => $order->voucher_code,
            'notes'              => $order->notes,
            'show_rating_prompt' => $showRatingPrompt,
            'table'          => $order->relationLoaded('table') && $order->table ? [
                'id'           => $order->table->id,
                'table_number' => $order->table->table_number,
                'status'       => $order->table->status,
            ] : null,
            'user'           => $order->relationLoaded('user') && $order->user ? [
                'id'   => $order->user->id,
                'name' => $order->user->name,
            ] : null,
            'items'          => $order->relationLoaded('orderItems')
                ? $order->orderItems->map(fn ($item) => [
                    'id'               => $item->id,
                    'menu_id'          => $item->menu_id,
                    'menu_name'        => $item->relationLoaded('menu') && $item->menu ? $item->menu->name : null,
                    'menu'             => $item->relationLoaded('menu') && $item->menu ? [
                        'id'        => $item->menu->id,
                        'name'      => $item->menu->name,
                        'image_url' => $item->menu->image_url,
                    ] : null,
                    'quantity'         => $item->quantity,
                    'price_at_time'    => $item->price_at_time,
                    'variant_selected' => $item->variant_selected,
                    'note'             => $item->note,
                    'subtotal'         => round((float) $item->price_at_time * $item->quantity, 2),
                ])->values()
                : [],
            'created_at'     => $order->created_at,
            'updated_at'     => $order->updated_at,
        ];
    }
}
