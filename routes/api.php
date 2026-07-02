<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenOrderController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\MenuApiController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\VoucherValidateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated User Info
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Payment Webhook (public — no auth, no CSRF — API routes are CSRF-exempt)
|--------------------------------------------------------------------------
*/
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])
    ->name('payment.webhook');

/*
|--------------------------------------------------------------------------
| Push Notification — VAPID public key (public, no auth needed)
|--------------------------------------------------------------------------
*/
Route::get('/push/vapid-public-key', [NotificationController::class, 'vapidPublicKey'])
    ->name('push.vapid-public-key');

/*
|--------------------------------------------------------------------------
| Promo / Voucher — Public Routes (no auth required)
|--------------------------------------------------------------------------
*/
Route::post('/voucher/validate', [VoucherValidateController::class, 'validate'])
    ->name('voucher.validate');

/*
|--------------------------------------------------------------------------
| Table Availability — Public (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/tables/{table}/availability', [ReservationController::class, 'checkAvailability'])
    ->name('tables.availability');

Route::get('/promos/active', function () {
    /** @var \App\Services\PromoService $promoService */
    $promoService = app(\App\Services\PromoService::class);
    $promos = $promoService->getActivePromos();

    return response()->json([
        'message' => 'Promosi aktif berhasil diambil.',
        'data'    => $promos,
    ]);
})->name('promos.active');

Route::get('/settings/billing', [\App\Http\Controllers\SettingController::class, 'publicBilling'])
    ->name('settings.billing');

Route::get('/settings/receipt', [\App\Http\Controllers\SettingController::class, 'publicReceipt'])
    ->name('settings.receipt');

/*
|--------------------------------------------------------------------------
| Customer Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->group(function () {
    Route::get('menus', [App\Http\Controllers\MenuApiController::class, 'index']);
    Route::get('categories', [App\Http\Controllers\CategoryController::class, 'index']);
    Route::get('tables', [App\Http\Controllers\TableController::class, 'customerIndex']);
    Route::get('orders/{order}', [App\Http\Controllers\OrderController::class, 'customerShow'])
        ->name('customer.orders.show.public');
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'check.role:customer'])
    ->prefix('customer')
    ->group(function () {
        // Push notification subscription management (task 9.8)
        Route::post('push-subscribe', [NotificationController::class, 'subscribe'])
            ->name('customer.push.subscribe');
        Route::delete('push-subscribe', [NotificationController::class, 'unsubscribe'])
            ->name('customer.push.unsubscribe');

        Route::post('orders', [OrderController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('customer.orders.store');
        Route::post('orders/{order}/payment/initiate', [PaymentController::class, 'initiate'])
            ->middleware('throttle:30,1')
            ->name('customer.payment.initiate');

        // Loyalty Engine — point balance, history, and redemption (task 11.1)
        Route::get('loyalty/balance', [LoyaltyController::class, 'balance'])
            ->name('customer.loyalty.balance');
        Route::get('loyalty/history', [LoyaltyController::class, 'history'])
            ->name('customer.loyalty.history');
        Route::post('loyalty/redeem', [LoyaltyController::class, 'redeem'])
            ->name('customer.loyalty.redeem');

        // Reservation — customer creates and manages own reservations (task 12.1)
        Route::post('reservations', [ReservationController::class, 'store'])
            ->name('customer.reservations.store');
        Route::get('reservations', [ReservationController::class, 'myReservations'])
            ->name('customer.reservations.index');
        Route::delete('reservations/{reservation}', [ReservationController::class, 'cancel'])
            ->name('customer.reservations.cancel');

        // Rating — customer submits rating for a completed order (task 14.1)
        Route::get('orders', [OrderController::class, 'myOrders'])
            ->name('customer.orders.index');
        Route::post('orders/{order}/rating', [RatingController::class, 'store'])
            ->name('customer.orders.rating.store');
    });

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/
// KDS — chef and admin only
Route::middleware(['auth:sanctum', 'check.role:chef,admin'])
    ->prefix('staff')
    ->group(function () {
        // KDS endpoint (task 6.9)
        Route::get('/kds', [KitchenOrderController::class, 'index']);
    });

// Orders — waiter, chef, and admin
Route::middleware(['auth:sanctum', 'check.role:waiter,chef,admin'])
    ->prefix('staff')
    ->group(function () {
        // Order management (task 6.1)
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    });

Route::middleware(['auth:sanctum', 'check.role:waiter,admin'])
    ->prefix('staff')
    ->group(function () {
        // Cash payment confirmation — waiter/admin (task 7.5)
        Route::get('/settings/receipt', [\App\Http\Controllers\SettingController::class, 'staffReceipt'])
            ->name('staff.settings.receipt');

        Route::post('/orders/{order}/items', [OrderController::class, 'addItems'])
            ->name('staff.orders.items.store');

        Route::post('/orders/{order}/payment/confirm', [PaymentController::class, 'confirm'])
            ->name('staff.payment.confirm');

        Route::post('/orders/{order}/payment/confirm-cash', [PaymentController::class, 'confirmCash'])
            ->name('staff.payment.confirm-cash');
    });

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'check.role:admin'])
    ->prefix('admin')
    ->group(function () {
        // Staff management
        Route::get('/staff', [StaffController::class, 'index']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::patch('/staff/{user}/deactivate', [StaffController::class, 'deactivate']);
        Route::patch('/staff/{user}/activate', [StaffController::class, 'activate']);

        // Menu management
        Route::apiResource('menus', MenuController::class);
        Route::patch('menus/{menu}/toggle-availability', [MenuController::class, 'toggleAvailability']);

        // Category management
        Route::post('categories/reorder', [CategoryController::class, 'reorder']);
        Route::apiResource('categories', CategoryController::class);

        // Variant management (nested under menus for index/store; standalone for update/destroy)
        Route::apiResource('menus/{menu}/variants', VariantController::class)
            ->only(['index', 'store']);
        Route::put('variants/{variant}', [VariantController::class, 'update'])->name('variants.update');
        Route::patch('variants/{variant}', [VariantController::class, 'update']);
        Route::delete('variants/{variant}', [VariantController::class, 'destroy'])->name('variants.destroy');

        // Table management (task 5.1)
        Route::apiResource('tables', TableController::class);
        Route::post('tables/{table}/regenerate-qr', [TableController::class, 'regenerateQr'])
            ->name('tables.regenerate-qr');
        Route::patch('tables/{table}/status', [TableController::class, 'updateStatus'])
            ->name('tables.update-status');

        // Order cancellation — admin only (task 6.4)
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->name('orders.cancel');

        // Payment history (task 7.7)
        Route::get('payments', [PaymentController::class, 'history'])
            ->name('admin.payments.history');

        // Inventory management (task 8.1, 8.2, 8.6)
        Route::get('inventory', [InventoryController::class, 'index'])
            ->name('admin.inventory.index');
        Route::post('inventory', [InventoryController::class, 'store'])
            ->name('admin.inventory.store');
        Route::get('inventory/{ingredient}', [InventoryController::class, 'show'])
            ->name('admin.inventory.show');
        Route::put('inventory/{ingredient}', [InventoryController::class, 'update'])
            ->name('admin.inventory.update');
        Route::patch('inventory/{ingredient}', [InventoryController::class, 'update']);
        Route::delete('inventory/{ingredient}', [InventoryController::class, 'destroy'])
            ->name('admin.inventory.destroy');
        Route::post('inventory/{ingredient}/add-stock', [InventoryController::class, 'addStock'])
            ->name('admin.inventory.add-stock');
        Route::get('inventory/{ingredient}/movements', [InventoryController::class, 'movements'])
            ->name('admin.inventory.movements');

        // Supplier master data
        Route::apiResource('suppliers', SupplierController::class);

        // Promo management (task 10.1)
        Route::apiResource('promos', PromoController::class);

        // Reservation management — admin (task 12.1)
        Route::get('reservations', [ReservationController::class, 'index'])
            ->name('admin.reservations.index');
        Route::post('reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])
            ->name('admin.reservations.confirm');
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
            ->name('admin.reservations.cancel');

        // Report Engine — laporan & analitik (task 13.1)
        Route::prefix('reports')->group(function () {
            Route::get('sales', [ReportController::class, 'salesReport'])
                ->name('admin.reports.sales');
            Route::get('stock', [ReportController::class, 'stockReport'])
                ->name('admin.reports.stock');
            Route::get('dashboard', [ReportController::class, 'dashboardMetrics'])
                ->name('admin.reports.dashboard');
            Route::get('hourly-revenue', [ReportController::class, 'hourlyRevenue'])
                ->name('admin.reports.hourly-revenue');
            Route::post('export/sales', [ReportController::class, 'exportSales'])
                ->name('admin.reports.export.sales');
            Route::post('export/stock', [ReportController::class, 'exportStock'])
                ->name('admin.reports.export.stock');
        });

        // Rating summary — admin views average rating and recent reviews (task 14.1)
        Route::get('ratings', [RatingController::class, 'index'])
            ->name('admin.ratings.index');
        // System settings (task 18.1)
        Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index'])
            ->name('admin.settings.index');
        Route::post('settings', [\App\Http\Controllers\SettingController::class, 'update'])
            ->name('admin.settings.update');
    });
