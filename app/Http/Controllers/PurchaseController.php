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
    public function index()
    {
        $purchases = Purchase::with(['supplier','requestedBy','approvedBy','items.item','receiving.items'])
            ->latest('order_date')->latest('id')->paginate(10);
        return view('purchases.index', compact('purchases'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = InventoryItem::orderBy('name')->get();
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

        DB::transaction(function () use ($data) {
            $user = Auth::user();
            $managerSelfApproval = $user->isStoreManager();
            $purchase = Purchase::create([
                'supplier_id'=>$data['supplier_id'], 'requested_by'=>$user->id,
                'approved_by'=>$managerSelfApproval ? $user->id : null,
                'order_date'=>$data['order_date'], 'status'=>$managerSelfApproval ? 'approved' : 'pending',
                'notes'=>$data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $line) {
                $item = InventoryItem::findOrFail($line['item_id']);
                $purchase->items()->create([
                    'item_id'=>$item->id, 'quantity'=>$line['quantity'],
                    'package_unit'=>$item->unit,
                    'package_size'=>1,
                ]);
            }
        });

        return redirect()->route('purchases.index')->with('success', Auth::user()->isStoreManager()
            ? 'Purchase request created and approved. It is ready for delivery.'
            : 'Purchase request submitted for manager approval.');
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
