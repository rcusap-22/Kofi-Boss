<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        if (!in_array($status, ['all', 'in_stock', 'low_stock', 'out_of_stock', 'archived'], true)) {
            $status = 'all';
        }

        $items = InventoryItem::with('supplier')
            ->when($status === 'archived', fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->active())
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%');
            })
            ->when(!in_array($status, ['all', 'archived'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.index', compact('items', 'status'));
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
        $this->checkDuplicateName($data['name']);
        $data['purchase_unit'] = null;
        $data['purchase_unit_size'] = 1;
        $data['expiry_date'] = null;

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
        abort_if($item->archived_at !== null, 403, 'Restore this item before editing it.');
        return view('inventory.form', compact('item', 'suppliers'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $this->validated($request);
        abort_if($item->archived_at !== null, 403, 'Restore this item before editing it.');
        $this->checkDuplicateName($data['name'], $item->id);
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

    private function normalizedName(string $name): string
    {
        return strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    private function checkDuplicateName(string $name, ?int $exceptId = null): void
    {
        $key = $this->normalizedName($name);
        $matches = InventoryItem::query()->select('id', 'name', 'archived_at')->get()
            ->filter(fn ($item) => (int) $item->id !== (int) $exceptId && $this->normalizedName($item->name) === $key);
        if ($matches->isNotEmpty()) {
            $existing = $matches->first();
            throw ValidationException::withMessages(['name' => 'An inventory item with this name already exists'.($existing->archived_at ? ' (archived)' : '').'. Use the existing item instead. Include the size or variant for a different product.']);
        }
    }

    public function archive(InventoryItem $item)
    {
        abort_unless(auth()->user()->isStoreManager(), 403);
        if ($item->archived_at) return back()->withErrors(['item' => 'This item is already archived.']);
        if ((int) $item->stock_qty !== 0) return back()->withErrors(['item' => 'Settle the remaining stock before archiving this item.']);
        if (DB::table('purchase_items')->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.item_id', $item->id)->whereIn('purchases.status', ['pending','approved'])->exists()) {
            return back()->withErrors(['item' => 'This item has an active purchase request. Complete or reject it before archiving.']);
        }
        $item->update(['archived_at' => now()]);
        return redirect()->route('inventory.index')->with('success', 'Item archived. Historical records are preserved.');
    }

    public function restore(InventoryItem $item)
    {
        abort_unless(auth()->user()->isStoreManager(), 403);
        $this->checkDuplicateName($item->name, $item->id);
        $item->update(['archived_at' => null]);
        return redirect()->route('inventory.index', ['status' => 'archived'])->with('success', 'Item restored.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:Ingredients,Packaging,Beverages,Cleaning Supplies,Others'],
            'unit' => ['required', 'string', 'max:20'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);
        $data['name'] = preg_replace('/\s+/u', ' ', trim($data['name']));
        $data['name'] = ucwords(strtolower($data['name']));
        $data['name'] = preg_replace_callback('/\b(\d+)Oz\b/i', fn ($m) => $m[1].'oz', $data['name']);
        return $data;
    }
}
