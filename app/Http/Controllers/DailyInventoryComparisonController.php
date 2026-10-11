<?php
namespace App\Http\Controllers;
use App\Models\DailyStockCount;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DailyInventoryComparisonController extends Controller {
 public function index(Request $request) {
  $v=$request->validate(['historical_date'=>'nullable|date','comparison_date'=>'nullable|date','change'=>'nullable|in:all,increased,decreased,unchanged']);
  $dates=DailyStockCount::where('review_status','!=','canceled')->orderByDesc('count_date')->pluck('count_date')->map(fn($d)=>\Illuminate\Support\Carbon::parse($d)->format('Y-m-d'))->unique()->values();
  $historicalDate=$v['historical_date']??($dates->get(1)??$dates->first());
  $comparisonDate=$v['comparison_date']??$dates->first();
  $change=$v['change']??'all';
  $getCount=fn($date)=>$date ? DailyStockCount::with('items.item')->whereDate('count_date',$date)->where('review_status','!=','canceled')->latest('id')->first() : null;
  $historicalCount=$getCount($historicalDate);$comparisonCount=$getCount($comparisonDate);
  $rows=collect();
  if($historicalCount && $comparisonCount){
   $old=$historicalCount->items->keyBy('item_id');$new=$comparisonCount->items->keyBy('item_id');
   $rows=$old->keys()->merge($new->keys())->unique()->map(function($id)use($old,$new){
    $a=$old->get($id);$b=$new->get($id);$item=$b?->item??$a?->item;
    $previous=$a?(int)$a->actual_qty:null;$current=$b?(int)$b->actual_qty:null;
    return ['name'=>$item?->name??'Item #'.$id,'unit'=>$item?->unit??'—','previous'=>$previous,'current'=>$current,'difference'=>($previous!==null&&$current!==null)?$current-$previous:null];
   })->sortBy('name')->values();
   if($change!=='all')$rows=$rows->filter(fn($r)=>$r['difference']!==null && match($change){'increased'=>$r['difference']>0,'decreased'=>$r['difference']<0,'unchanged'=>$r['difference']===0})->values();
  }
  $page=LengthAwarePaginator::resolveCurrentPage();
  $paginatedRows=new LengthAwarePaginator($rows->forPage($page,10)->values(),$rows->count(),10,$page,['path'=>$request->url(),'query'=>$request->query()]);
  return view('daily-counts.comparison',compact('historicalDate','comparisonDate','historicalCount','comparisonCount','change','paginatedRows'));
 }
}
