<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockTransaction;
use Illuminate\Http\Request;

class InventoryStockHistoryController extends Controller
{
    public function show(Request $request, InventoryItem $item)
    {
        $type = $request->query('type', 'all');
        if (!in_array($type, ['all', 'in', 'out'], true)) $type = 'all';

        $base = StockTransaction::where('item_id', $item->id);
        $received = (clone $base)->where('type', 'in')->sum('quantity');
        $removed = (clone $base)->where('type', 'out')->sum('quantity');
        $query = (clone $base)->with('user');
        if ($type !== 'all') $query->where('type', $type);
        $transactions = $query->orderByDesc('id')->paginate(10)->withQueryString();

        // Running balances are reconstructed from current stock, not assumed to be
        // stored snapshots. Earlier balances may be incomplete if logs were cleared.
        $rows = $transactions->getCollection()->map(function ($transaction) use ($item) {
            $later = StockTransaction::where('item_id', $item->id)
                ->where('id', '>', $transaction->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) AS net")
                ->value('net');
            $transaction->balance_after = (int) $item->stock_qty - (int) $later;
            return $transaction;
        });
        $transactions->setCollection($rows);

        return view('inventory.history', compact('item', 'transactions', 'received', 'removed', 'type'));
    }
}
