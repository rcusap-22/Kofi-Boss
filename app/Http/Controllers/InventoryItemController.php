<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Http\Request;

// Use case: Inventory Staff -> "Manage item reorder thresholds" + general item management.
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
        $suppliers = Supplier::orderBy('name')->get();
        return view('inventory.form', ['item' => new InventoryItem, 'suppliers' => $suppliers]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $item = InventoryItem::create($data);
        $item->refreshStatus();

        return redirect()->route('inventory.index')->with('success', 'Item added.');
    }

    public function edit(InventoryItem $item)
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('inventory.form', compact('item', 'suppliers'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $this->validated($request);
        $item->update($data);
        $item->refreshStatus();

        return redirect()->route('inventory.index')->with('success', 'Item updated.');
    }

    public function destroy(InventoryItem $item)
    {
        $item->delete();
        return back()->with('success', 'Item removed.');
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
