<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\Receiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivingController extends Controller
{
    public function create(Purchase $purchase)
    {
        abort_unless($purchase->status === 'approved', 422, 'Only approved purchases can be received.');
        $purchase->load(['supplier','items.item','receiving.items']);
        return view('receiving.create', compact('purchase'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->status === 'approved', 422, 'Only approved purchases can be received.');
        $purchase->load(['items.item','receiving.items']);

        $data = $request->validate([
            'received_date'=>['required','date'], 'notes'=>['nullable','string'],
            'items'=>['required','array','min:1'], 'items.*.item_id'=>['required','exists:inventory_items,id'],
            'items.*.qty_received'=>['required','integer','min:0'],
        ]);

        $ordered = $purchase->items->keyBy('item_id');
        $already = $purchase->receiving->flatMap->items->groupBy('item_id')->map(fn($rows) => $rows->sum('qty_received'));
        $hasAny = false;
        foreach ($data['items'] as $line) {
            $poLine = $ordered->get((int)$line['item_id']);
            abort_unless($poLine, 422, 'An item does not belong to this purchase.');
            $remaining = max(0, $poLine->quantity - (int)($already[$poLine->item_id] ?? 0));
            abort_if($line['qty_received'] > $remaining, 422, "Received quantity for {$poLine->item->name} cannot exceed the remaining {$remaining}.");
            $hasAny = $hasAny || $line['qty_received'] > 0;
        }
        abort_unless($hasAny, 422, 'Enter at least one received quantity.');

        DB::transaction(function () use ($data, $purchase) {
            $receiving = Receiving::create([
                'purchase_id'=>$purchase->id, 'received_by'=>Auth::id(),
                'received_date'=>$data['received_date'], 'notes'=>$data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                if ($line['qty_received'] <= 0) continue;
                $poLine = $purchase->items->firstWhere('item_id', (int)$line['item_id']);
                $receiving->items()->create(['item_id'=>$line['item_id'],'qty_received'=>$line['qty_received']]);
                $stockQty = $line['qty_received'];
                $item = InventoryItem::lockForUpdate()->findOrFail($line['item_id']);
                $item->stock_qty += $stockQty;
                $item->refreshStatus();
                $item->stockTransactions()->create([
                    'user_id'=>Auth::id(), 'type'=>'in', 'quantity'=>$stockQty,
                    'reference'=>"Purchase #{$purchase->id} · {$line['qty_received']} {$poLine->package_unit}",
                    'date'=>$data['received_date'],
                ]);
            }

            $purchase->load('receiving.items');
            $receivedTotals = $purchase->receiving->flatMap->items->groupBy('item_id')->map(fn($rows) => $rows->sum('qty_received'));
            $complete = $purchase->items->every(fn($line) => (int)($receivedTotals[$line->item_id] ?? 0) >= $line->quantity);
            if ($complete) $purchase->update(['status'=>'completed']);
        });

        return redirect()->route('purchases.index')->with('success', 'Delivery recorded and inventory updated automatically.');
    }
}
