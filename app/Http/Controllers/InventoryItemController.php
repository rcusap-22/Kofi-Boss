<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $items = InventoryItem::with('supplier')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        Supplier::ensureMainSupplier();
        $suppliers = Supplier::orderByRaw("CASE WHEN name = 'Main Supplier' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        return view('inventory.form', ['item' => new InventoryItem, 'suppliers' => $suppliers]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $item = InventoryItem::create($data);
            $item->refreshStatus();

            if ((int) $item->stock_qty > 0) {
                StockTransaction::create([
                    'item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'quantity' => (int) $item->stock_qty,
                    'reference' => 'Opening Balance',
                    'notes' => 'Initial stock entered when the inventory item was created.',
                    'date' => today(),
                ]);
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Item added.');
    }

    public function edit(InventoryItem $item)
    {
        Supplier::ensureMainSupplier();
        $suppliers = Supplier::orderByRaw("CASE WHEN name = 'Main Supplier' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        return view('inventory.form', compact('item', 'suppliers'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $this->validated($request);
        $oldStock = (int) $item->stock_qty;

        DB::transaction(function () use ($item, $data, $oldStock) {
            $item->update($data);
            $item->refreshStatus();

            $newStock = (int) $item->stock_qty;
            $difference = $newStock - $oldStock;
            if ($difference !== 0) {
                StockTransaction::create([
                    'item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'type' => $difference > 0 ? 'in' : 'out',
                    'quantity' => abs($difference),
                    'reference' => 'Inventory Adjustment',
                    'notes' => "Stock quantity edited from {$oldStock} to {$newStock}.",
                    'date' => today(),
                ]);
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Item updated.');
    }

    public function destroy(InventoryItem $item)
    {
        $hasHistory = $item->stockTransactions()->where(function ($q) {
                $q->whereNull('reference')->orWhere('reference', '!=', 'Opening Balance');
            })->exists()
            || DB::table('daily_stock_count_items')->where('item_id', $item->id)->exists()
            || DB::table('purchase_items')->where('item_id', $item->id)->exists()
            || DB::table('receiving_items')->where('item_id', $item->id)->exists();

        if ($hasHistory) {
            return back()->withErrors(['item' => 'This item already has inventory history and cannot be permanently deleted.']);
        }

        $item->delete();
        return back()->with('success', 'Item deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:20'],
            'purchase_unit' => ['nullable', 'string', 'max:60'],
            'purchase_unit_size' => ['nullable'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);
    }
}
