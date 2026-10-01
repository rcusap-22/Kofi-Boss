<?php
namespace App\Http\Controllers;

use App\Models\DailyStockCount;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\StockTransaction;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $totalItems = InventoryItem::count();
        $lowStockCount = InventoryItem::where('stock_qty','>',0)->whereColumn('stock_qty','<=','min_stock')->count();
        $outOfStockCount = InventoryItem::where('stock_qty','<=',0)->count();
        $todayActivityCount = StockTransaction::whereDate('date',today())->count();
        $recentActivity = StockTransaction::with(['item','user'])->latest('date')->latest('id')->take(6)->get();
        $lowStockItems = InventoryItem::whereColumn('stock_qty','<=','min_stock')->orderBy('stock_qty')->take(6)->get();
        $todayCount = DailyStockCount::with('user')->whereDate('count_date', today())->first();
        $pendingPurchases = Purchase::where('status','pending')->count();
        $approvedPurchases = Purchase::where('status','approved')->count();
        $receivedToday = StockTransaction::whereDate('date', today())->where('type','in')->where('reference','like','Purchase Order #%')->count();
        $recentCounts = DailyStockCount::with('user')->latest('count_date')->take(5)->get();
        $recentPurchases = Purchase::with(['supplier','requestedBy','approvedBy'])->latest('updated_at')->take(5)->get();
        return view('dashboard', compact(
            'user','totalItems','lowStockCount','outOfStockCount','todayActivityCount','recentActivity','lowStockItems',
            'todayCount','pendingPurchases','approvedPurchases','receivedToday','recentCounts','recentPurchases'
        ));
    }
}
