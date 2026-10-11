<?php
namespace App\Http\Controllers;

use App\Models\DailyStockCount;
use App\Models\InventoryItem;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyStockCountController extends Controller
{
    public function index(Request $request)
    {
        $items = InventoryItem::active()->orderBy('category')->orderBy('name')->get();
        $todayCount = DailyStockCount::with(['items.item','user'])->whereDate('count_date', today())->where('review_status', '!=', 'canceled')->latest('id')->first();
        $historyStatus = $request->query('history_status', 'all');
        if (!in_array($historyStatus, ['all', 'submitted', 'edited', 'canceled'], true)) {
            $historyStatus = 'all';
        }
        $historyQuery = DailyStockCount::with('user')->withCount('items');
        if ($historyStatus === 'canceled') {
            $historyQuery->where('review_status', 'canceled');
        } elseif ($historyStatus === 'edited') {
            $historyQuery->where('review_status', '!=', 'canceled')->where('was_corrected', true);
        } elseif ($historyStatus === 'submitted') {
            $historyQuery->where('review_status', '!=', 'canceled')->where(function ($query) {
                $query->where('was_corrected', false)->orWhereNull('was_corrected');
            });
        }
        $recentCounts = $historyQuery->latest('count_date')->latest('id')->paginate(10, ['*'], 'history_page')->withQueryString();
        $selectedCount = null;
        if ($request->filled('date')) {
            $selectedCount = DailyStockCount::with(['items.item','user'])->whereDate('count_date', $request->date)->where('review_status', '!=', 'canceled')->latest('id')->first();
        }
        return view('daily-counts.index', compact('items','todayCount','recentCounts','selectedCount'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isInventoryStaff(), 403);
        if (DailyStockCount::whereDate('count_date', today())->where('review_status', '!=', 'canceled')->exists()) {
            return back()->withErrors(['count' => 'Closing inventory for today has already been submitted.']);
        }
        $items = InventoryItem::active()->orderBy('id')->get();
        $rules = ['notes'=>'nullable|string|max:1000'];
        foreach ($items as $item) $rules['actual.'.$item->id] = 'required|integer|min:0';
        $data = $request->validate($rules);

        DB::transaction(function () use ($request, $items, $data) {
            $count = DailyStockCount::create([
                'count_date'=>today(), 'review_status'=>'submitted', 'user_id'=>auth()->id(), 'submitted_at'=>now(), 'notes'=>$data['notes'] ?? null,
            ]);
            foreach ($items as $item) {
                $expected = (int)$item->stock_qty;
                $actual = (int)$request->input('actual.'.$item->id);
                $variance = $actual - $expected;
                $count->items()->create(['item_id'=>$item->id, 'expected_qty'=>$expected, 'actual_qty'=>$actual, 'variance_qty'=>$variance]);
                if ($variance !== 0) {
                    StockTransaction::create([
                        'item_id'=>$item->id, 'user_id'=>auth()->id(), 'type'=>$variance > 0 ? 'in' : 'out',
                        'quantity'=>abs($variance), 'reference'=>'Daily Count #'.$count->id,
                        'notes'=>'End-of-day reconciliation. Expected: '.$expected.'; Actual: '.$actual, 'date'=>today(),
                    ]);
                }
                $item->stock_qty = $actual;
                $item->save();
                $item->refreshStatus();
            }
        });
        return redirect()->route('daily-counts.index')->with('success','Closing inventory submitted. Physical counts are now the current stock levels.');
    }

    public function show(DailyStockCount $dailyCount)
    {
        $dailyCount->load(['items.item','user']);
        return view('daily-counts.show', compact('dailyCount'));
    }
}
