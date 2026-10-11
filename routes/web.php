<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyStockCountController;
use App\Http\Controllers\DailyInventoryComparisonController;
use App\Http\Controllers\DailyCountRequestController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryStockHistoryController;
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
    Route::get('/inventory/{item}/history', [InventoryStockHistoryController::class, 'show'])->name('inventory.history');
    Route::get('/daily-counts', [DailyStockCountController::class, 'index'])->name('daily-counts.index');
    Route::get('/daily-count-requests', [DailyCountRequestController::class, 'index'])->name('daily-counts.requests.index');
    Route::get('/daily-count-requests/{dailyCountRequest}/open', [DailyCountRequestController::class, 'open'])->name('daily-counts.requests.open');
    Route::get('/daily-counts/comparison', [DailyInventoryComparisonController::class, 'index'])->name('daily-counts.comparison');
    Route::get('/daily-counts/{dailyCount}', [DailyStockCountController::class, 'show'])->name('daily-counts.show');
    Route::get('/low-stock', [LowStockController::class, 'index'])->name('stock.low');
    Route::post('/low-stock/banner/dismiss', function () {
        $keys=\App\Models\InventoryItem::active()->whereIn('status',['low_stock','out_of_stock'])
            ->orderBy('id')->get(['id','status'])
            ->map(fn($item)=>$item->id.':'.$item->status)->all();
        session(['stock_banner_dismissed_keys'=>$keys]);
        return response()->json(['dismissed'=>true]);
    })->name('stock.banner.dismiss');
});

Route::middleware(['auth', 'role:inventory_staff'])->group(function () {
    Route::post('/daily-counts', [DailyStockCountController::class, 'store'])->name('daily-counts.store');
    Route::get('/daily-counts/{dailyCount}/correction', [DailyCountRequestController::class, 'edit'])->name('daily-counts.correction.edit');
    Route::post('/daily-counts/{dailyCount}/requests', [DailyCountRequestController::class, 'store'])->name('daily-counts.requests.store');
    Route::post('/daily-count-requests/{dailyCountRequest}/withdraw', [DailyCountRequestController::class, 'withdraw'])->name('daily-counts.requests.withdraw');
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
    Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit')->middleware('role:inventory_staff');
    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update')->middleware('role:inventory_staff');
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy')->middleware('role:inventory_staff');
});

Route::middleware(['auth', 'role:store_manager'])->group(function () {
    Route::patch('/inventory/{item}/archive', [InventoryItemController::class, 'archive'])->name('inventory.archive');
    Route::patch('/inventory/{item}/restore', [InventoryItemController::class, 'restore'])->name('inventory.restore');
    Route::post('/daily-count-requests/{dailyCountRequest}/review', [DailyCountRequestController::class, 'review'])->name('daily-counts.requests.review');
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
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
});
