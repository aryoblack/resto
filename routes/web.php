<?php

use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

// Customer PWA Routes
Route::get('/app', function () {
    return view('customer.app');
})->name('customer.app');



Route::get('/', function () {
    return view('welcome');
})->name('landing');

// Redirect default login route to admin login
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

/*
|--------------------------------------------------------------------------
| QR Code Scan Route (task 5.5)
|--------------------------------------------------------------------------
| Public route — no auth required. Validates the QR token, marks the table
| as occupied, and redirects to the Customer_App with table_id.
|
| Validates: Requirements 2.1, 2.2, 10.4
*/
Route::get('/scan/{qrCode}', [TableController::class, 'scan'])->name('qr.scan');

/*
|--------------------------------------------------------------------------
| Staff Auth Routes (Login / Logout)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'logout'])->name('admin.logout');
});

/*
|--------------------------------------------------------------------------
| Admin Web Routes (Protected by auth)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['web', 'auth', 'check.role:waiter,chef,admin'])->group(function () {
    Route::get('/orders', [\App\Http\Controllers\Admin\AdminWebController::class, 'orders'])->name('admin.orders');
});

Route::prefix('admin')->middleware(['web', 'auth', 'check.role:waiter,admin'])->group(function () {
    Route::get('/cashier', [\App\Http\Controllers\Admin\AdminWebController::class, 'cashier'])->name('admin.cashier');
});

Route::prefix('admin')->middleware(['web', 'auth', 'check.role:admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/menus', [\App\Http\Controllers\Admin\AdminWebController::class, 'menus'])->name('admin.menus');
    Route::get('/categories', [\App\Http\Controllers\Admin\AdminWebController::class, 'categories'])->name('admin.categories');
    Route::get('/tables', [\App\Http\Controllers\Admin\AdminWebController::class, 'tables'])->name('admin.tables');
    Route::get('/inventory', [\App\Http\Controllers\Admin\AdminWebController::class, 'inventory'])->name('admin.inventory');
    Route::get('/suppliers', [\App\Http\Controllers\Admin\AdminWebController::class, 'suppliers'])->name('admin.suppliers');
    Route::get('/promos', [\App\Http\Controllers\Admin\AdminWebController::class, 'promos'])->name('admin.promos');
    Route::get('/reservations', [\App\Http\Controllers\Admin\AdminWebController::class, 'reservations'])->name('admin.reservations');
    Route::get('/reports', [\App\Http\Controllers\Admin\AdminWebController::class, 'reports'])->name('admin.reports');
    Route::get('/staff', [\App\Http\Controllers\Admin\AdminWebController::class, 'staff'])->name('admin.staff');
    Route::get('/settings', [\App\Http\Controllers\Admin\AdminWebController::class, 'settings'])->name('admin.settings');
});

Route::get('/kds', function() {
    return view('kds.index');
})->middleware(['web', 'auth', 'check.role:chef,admin'])->name('kds.index');
