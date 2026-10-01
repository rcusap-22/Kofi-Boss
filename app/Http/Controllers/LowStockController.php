<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;

// Use case: Inventory Staff -> "Monitor stock levels and alerts"
class LowStockController extends Controller
{
    public function index()
    {
        $items = InventoryItem::whereIn('status', ['low_stock', 'out_of_stock'])
            ->orderBy('stock_qty')
            ->get();

        return view('stock.low', compact('items'));
    }
}
