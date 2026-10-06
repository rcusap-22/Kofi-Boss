<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyStockCountController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\LowStockController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivingController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:owner,store_manager,inventory_staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
    Route::get('/daily-counts', [DailyStockCountController::class, 'index'])->name('daily-counts.index');
    Route::get('/daily-counts/{dailyCount}', [DailyStockCountController::class, 'show'])->name('daily-counts.show');
    Route::get('/low-stock', [LowStockController::class, 'index'])->name('stock.low');
});

Route::middleware(['auth', 'role:inventory_staff'])->group(function () {
    Route::post('/daily-counts', [DailyStockCountController::class, 'store'])->name('daily-counts.store');
    Route::get('/purchases/{purchase}/receive', [ReceivingController::class, 'create'])->name('receiving.create');
    Route::post('/purchases/{purchase}/receive', [ReceivingController::class, 'store'])->name('receiving.store');
});

Route::middleware(['auth', 'role:inventory_staff,store_manager'])->group(function () {
    Route::get('/inventory/create', [InventoryItemController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{item}/edit', [InventoryItemController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{item}', [InventoryItemController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{item}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
});

Route::middleware(['auth', 'role:store_manager'])->group(function () {
    Route::post('/purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::post('/purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');
});

Route::middleware(['auth', 'role:inventory_staff,store_manager,owner'])->group(function () {
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
});

Route::middleware(['auth', 'role:owner,store_manager'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/daily-inventory', [ReportController::class, 'dailyInventory'])->name('reports.daily-inventory');
    Route::get('/reports/stock-summary', [ReportController::class, 'stockSummary'])->name('reports.stock');
    Route::get('/reports/inventory-movement', [ReportController::class, 'inventoryMovement'])->name('reports.inventory-movement');
    Route::get('/reports/transactions', [ReportController::class, 'transactions'])->name('reports.transactions');
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.lowstock');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/export/{report}', [ReportController::class, 'exportExcel'])->name('reports.export');
});

Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
});
