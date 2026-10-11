<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $allowed = ['pending', 'approved', 'partial', 'received', 'rejected', 'all'];
        if (!in_array($status, $allowed, true)) $status = 'pending';

        $query = Purchase::with(['supplier','requestedBy','approvedBy','items.item','receiving.items']);
        if ($status === 'pending') $query->where('status', 'pending');
        elseif ($status === 'approved') $query->where('status', 'approved')->whereDoesntHave('receiving.items');
        elseif ($status === 'partial') $query->where('status', 'approved')->whereHas('receiving.items');
        elseif ($status === 'received') $query->where('status', 'completed');
        elseif ($status === 'rejected') $query->where('status', 'rejected');
        $purchases = $query->with(['supplier','requestedBy','approvedBy','items.item','receiving.items'])
            ->latest('order_date')->latest('id')->paginate(10)->withQueryString();
        return view('purchases.index', compact('purchases', 'status'));
    }

    public function create(Request $request)
    {
        Supplier::ensureMainSupplier();
        $suppliers = Supplier::orderByRaw("CASE WHEN name = 'Main Supplier' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $items = InventoryItem::active()->orderBy('name')->get();
        $preselectedItemId = $request->integer('item');
        $preselectedSupplierId = $preselectedItemId ? InventoryItem::whereKey($preselectedItemId)->value('supplier_id') : null;
        return view('purchases.create', compact('suppliers','items','preselectedItemId','preselectedSupplierId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'=>['required','exists:suppliers,id'], 'order_date'=>['required','date'],
            'notes'=>['nullable','string'], 'items'=>['required','array','min:1'],
            'items.*.item_id'=>['required','exists:inventory_items,id'],
            'items.*.quantity'=>['required','integer','min:1'],
        ]);

        $merged = false;
        DB::transaction(function () use ($data, &$merged) {
            $user = Auth::user();
            $managerSelfApproval = $user->isStoreManager();
            // Manager-created requests retain the existing auto-approval workflow.
            // Only pending requests by this same staff member and supplier are merged.
            $purchase = null;
            if (!$managerSelfApproval) {
                $purchase = Purchase::where('supplier_id', $data['supplier_id'])
                    ->where('requested_by', $user->id)
                    ->where('status', 'pending')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            }
            if ($purchase) {
                $merged = true;
                if (!empty($data['notes'])) {
                    $purchase->notes = trim(implode("\n", array_filter([$purchase->notes, $data['notes']])));
                    $purchase->save();
                }
            } else {
                $purchase = Purchase::create([
                    'supplier_id'=>$data['supplier_id'], 'requested_by'=>$user->id,
                    'approved_by'=>$managerSelfApproval ? $user->id : null,
                    'order_date'=>$data['order_date'], 'status'=>$managerSelfApproval ? 'approved' : 'pending',
                    'notes'=>$data['notes'] ?? null,
                ]);
            }
            foreach ($data['items'] as $line) {
                $item = InventoryItem::findOrFail($line['item_id']);
                $existing = $purchase->items()->where('item_id', $item->id)->lockForUpdate()->first();
                if ($existing) {
                    $existing->increment('quantity', (int) $line['quantity']);
                } else {
                    $purchase->items()->create([
                        'item_id'=>$item->id, 'quantity'=>$line['quantity'],
                        'package_unit'=>$item->unit, 'package_size'=>1,
                    ]);
                }
            }
        });

        return redirect()->route('purchases.index')->with('success', $merged
            ? 'Items added to your existing pending purchase request.'
            : (Auth::user()->isStoreManager()
                ? 'Purchase request created and approved. It is ready for delivery.'
                : 'Purchase request submitted for manager approval.'));
    }

    private function authorizePendingOwner(Purchase $purchase): void
    {
        abort_unless(Auth::user()->isInventoryStaff()
            && (int) $purchase->requested_by === (int) Auth::id(), 403);
        abort_unless($purchase->status === 'pending', 403, 'Only pending requests may be changed.');
    }

    public function edit(Purchase $purchase)
    {
        $this->authorizePendingOwner($purchase);
        Supplier::ensureMainSupplier();
        $suppliers = Supplier::orderByRaw("CASE WHEN name = 'Main Supplier' THEN 0 ELSE 1 END")
            ->orderBy('name')->get();
        $items = InventoryItem::active()->orderBy('name')->get();
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'items'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $this->authorizePendingOwner($purchase);
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:inventory_items,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($purchase, $data) {
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $this->authorizePendingOwner($locked);
            $locked->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
            ]);
            $locked->items()->delete();
            foreach ($data['items'] as $line) {
                $item = InventoryItem::findOrFail($line['item_id']);
                $locked->items()->create([
                    'item_id' => $item->id,
                    'quantity' => (int) $line['quantity'],
                    'package_unit' => $item->unit,
                    'package_size' => 1,
                ]);
            }
        });
        return redirect()->route('purchases.index', ['status' => 'pending'])
            ->with('success', 'Your pending purchase request was updated.');
    }

    public function destroy(Purchase $purchase)
    {
        $this->authorizePendingOwner($purchase);
        DB::transaction(function () use ($purchase) {
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $this->authorizePendingOwner($locked);
            $locked->items()->delete();
            $locked->delete();
        });
        return redirect()->route('purchases.index', ['status' => 'pending'])
            ->with('success', 'Your pending purchase request was deleted.');
    }

    public function approve(Purchase $purchase)
    {
        abort_unless($purchase->status === 'pending', 422, 'Only pending requests can be approved.');
        $purchase->update(['status'=>'approved','approved_by'=>Auth::id(),'rejection_reason'=>null]);
        return back()->with('success', 'Purchase request approved. Staff can now receive the delivery.');
    }

    public function reject(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->status === 'pending', 422, 'Only pending requests can be rejected.');
        $data = $request->validate(['rejection_reason'=>['required','string','max:500']]);
        $purchase->update(['status'=>'rejected','approved_by'=>Auth::id(),'rejection_reason'=>$data['rejection_reason']]);
        return back()->with('success', 'Purchase request rejected.');
    }
}
