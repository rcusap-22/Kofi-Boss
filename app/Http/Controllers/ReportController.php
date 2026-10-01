<?php
namespace App\Http\Controllers;

use App\Models\DailyStockCount;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index() { return view('reports.index'); }

    public function dailyInventory(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $count = DailyStockCount::with(['items.item','user'])->whereDate('count_date', $date)->first();
        return view('reports.daily_inventory', compact('count','date'));
    }

    public function stockSummary()
    {
        $items = InventoryItem::orderBy('category')->orderBy('name')->get();
        return view('reports.stock_summary', compact('items'));
    }

    public function inventoryMovement(Request $request)
    {
        $period = $request->input('period', 'daily');
        if ($period === 'monthly') {
            $month = $request->input('month', now()->format('Y-m'));
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth(); $end = $start->copy()->endOfMonth(); $periodLabel = $start->format('F Y');
        } elseif ($period === 'custom') {
            $validated = $request->validate(['from'=>['required','date'],'to'=>['required','date','after_or_equal:from']]);
            $start=Carbon::parse($validated['from'])->startOfDay(); $end=Carbon::parse($validated['to'])->endOfDay(); $periodLabel=$start->format('M j, Y').' - '.$end->format('M j, Y');
        } else {
            $period='daily'; $date=$request->input('date',now()->toDateString()); $start=Carbon::parse($date)->startOfDay(); $end=$start->copy()->endOfDay(); $periodLabel=$start->format('F j, Y');
        }
        $items=InventoryItem::orderBy('category')->orderBy('name')->get();
        $periodTransactions=StockTransaction::whereBetween('date',[$start->toDateString(),$end->toDateString()])->get()->groupBy('item_id');
        $afterTransactions=StockTransaction::where('date','>',$end->toDateString())->get()->groupBy('item_id');
        $rows=$items->map(function($item) use($periodTransactions,$afterTransactions){
            $tx=$periodTransactions->get($item->id,collect()); $after=$afterTransactions->get($item->id,collect());
            $isDailyCount=fn($t)=>str_starts_with((string)$t->reference,'Daily Count #');
            $received=(float)$tx->filter(fn($t)=>$t->type==='in'&&!$isDailyCount($t))->sum('quantity');
            $reconIn=(float)$tx->filter(fn($t)=>$t->type==='in'&&$isDailyCount($t))->sum('quantity');
            $reconOut=(float)$tx->filter(fn($t)=>$t->type==='out'&&$isDailyCount($t))->sum('quantity');
            $afterIn=(float)$after->where('type','in')->sum('quantity'); $afterOut=(float)$after->where('type','out')->sum('quantity');
            $ending=(float)$item->stock_qty-$afterIn+$afterOut; $allIn=(float)$tx->where('type','in')->sum('quantity'); $allOut=(float)$tx->where('type','out')->sum('quantity');
            $beginning=$ending-$allIn+$allOut;
            // Estimated reduction is the closing-count decrease. It is not labelled exact consumption because waste/spillage is not separately tracked.
            $estimatedReduction=$reconOut;
            $status=$ending<=0?'Out of Stock':($ending<=(float)$item->min_stock?'Low Stock':'In Stock');
            return (object)['name'=>$item->name,'category'=>$item->category,'unit'=>$item->unit,'beginning_stock'=>$beginning,'received_stock'=>$received,'reconciliation_in'=>$reconIn,'reconciliation_out'=>$reconOut,'estimated_reduction'=>$estimatedReduction,'ending_stock'=>$ending,'status'=>$status];
        });
        $totals=(object)['received_stock'=>$rows->sum('received_stock'),'reconciliation_in'=>$rows->sum('reconciliation_in'),'reconciliation_out'=>$rows->sum('reconciliation_out')];
        return view('reports.inventory_movement',compact('rows','totals','period','periodLabel','start','end'));
    }

    public function transactions(Request $request)
    {
        $from=$request->date('from')??now()->subDays(30); $to=$request->date('to')??now();
        $transactions=StockTransaction::with(['item','user'])->whereBetween('date',[$from,$to])->orderByDesc('date')->get();
        return view('reports.transactions',compact('transactions','from','to'));
    }
    public function lowStock(){ $items=InventoryItem::whereIn('status',['low_stock','out_of_stock'])->get(); return view('reports.low_stock',compact('items')); }
    public function purchases(Request $request)
    {
        $from=$request->date('from')??now()->subDays(90); $to=$request->date('to')??now();
        $purchases=Purchase::with(['supplier','requestedBy','approvedBy'])->whereBetween('order_date',[$from,$to])->orderByDesc('order_date')->get();
        return view('reports.purchases',compact('purchases','from','to'));
    }
}
