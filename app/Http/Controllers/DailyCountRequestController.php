<?php
namespace App\Http\Controllers;
use App\Models\DailyCountRequest;
use App\Models\DailyStockCount;
use App\Models\InventoryItem;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class DailyCountRequestController extends Controller {
 public function index(Request $request){
  $status = in_array($request->query('status'), ['pending','approved','rejected','withdrawn'], true) ? $request->query('status') : 'all';
  $type = in_array($request->query('type'), ['correction','cancellation'], true) ? $request->query('type') : 'all';
  $read = in_array($request->query('read'), ['unread','read'], true) ? $request->query('read') : 'all';

  $query = DailyCountRequest::with(['count.user','requester','reviewer'])
   ->withExists(['readers as is_read' => fn($q) => $q->where('users.id', auth()->id())]);

  if ($status !== 'all') $query->where('status', $status);
  if ($type !== 'all') $query->where('type', $type);
  if ($read === 'unread') $query->whereDoesntHave('readers', fn($q) => $q->where('users.id', auth()->id()));
  if ($read === 'read') $query->whereHas('readers', fn($q) => $q->where('users.id', auth()->id()));

  $requests = $query->latest()->paginate(10)->withQueryString();
  return view('daily-counts.requests', compact('requests','status','type','read'));
 }
 public function open(DailyCountRequest $dailyCountRequest){
  abort_unless($dailyCountRequest->count()->exists(),404);
  $dailyCountRequest->readers()->syncWithoutDetaching([auth()->id()]);
  return redirect()->route('daily-counts.show', ['dailyCount' => $dailyCountRequest->daily_stock_count_id, 'from' => 'requests']);
 }
 public function edit(DailyStockCount $dailyCount){
  abort_unless(auth()->user()->isInventoryStaff() && (int)$dailyCount->user_id === (int)auth()->id(),403);
  $dailyCount->load(['items.item','requests']);
  if($dailyCount->review_status==='canceled' || $dailyCount->requests->contains('status','pending')){
   return redirect()->route('daily-counts.show',$dailyCount)->withErrors(['request'=>'This submission is canceled or already has a pending request.']);
  }
  return view('daily-counts.correction',compact('dailyCount'));
 }
 public function store(Request $request,DailyStockCount $dailyCount){
  abort_unless(auth()->user()->isInventoryStaff() && $dailyCount->user_id===auth()->id(),403);
  $data=$request->validate(['type'=>'required|in:correction,cancellation','reason'=>'required|string|min:5|max:1000','corrected'=>'required_if:type,correction|array','corrected.*'=>'nullable|integer|min:0']);
  DB::transaction(function()use($dailyCount,$data){
   $count=DailyStockCount::whereKey($dailyCount->id)->lockForUpdate()->firstOrFail();
   if($count->review_status==='canceled'||$count->requests()->where('status','pending')->exists())throw ValidationException::withMessages(['request'=>'This submission is canceled or already has a pending request.']);
   $r=$count->requests()->create(['requested_by'=>auth()->id(),'type'=>$data['type'],'reason'=>$data['reason'],'status'=>'pending']);
   if($data['type']==='correction'){
    $changed=0;
    foreach($count->items as $item){$v=$data['corrected'][$item->id]??null;if($v===null||$v==='')continue;
     if((int)$v===(int)$item->actual_qty)continue;
     $r->items()->create(['daily_stock_count_item_id'=>$item->id,'previous_qty'=>(int)$item->actual_qty,'requested_qty'=>(int)$v]);$changed++;
    }
    if(!$changed)throw ValidationException::withMessages(['corrected'=>'Enter at least one different corrected quantity.']);
   }
  });
  return redirect()->route('daily-counts.show',$dailyCount)->with('success','Request submitted for Manager review.');
 }
 public function withdraw(DailyCountRequest $dailyCountRequest){
  abort_unless(auth()->user()->isInventoryStaff() && $dailyCountRequest->requested_by===auth()->id(),403);
  $updated=DailyCountRequest::whereKey($dailyCountRequest->id)->where('status','pending')->update(['status'=>'withdrawn']);
  if(!$updated)return back()->withErrors(['request'=>'Only pending requests can be withdrawn.']);
  return back()->with('success','Request withdrawn.');
 }
 public function review(Request $request,DailyCountRequest $dailyCountRequest){
  abort_unless(auth()->user()->isStoreManager(),403);
  $data=$request->validate(['decision'=>'required|in:approved,rejected','review_note'=>'nullable|string|max:1000']);
  DB::transaction(function()use($dailyCountRequest,$data){
   $r=DailyCountRequest::with('items')->whereKey($dailyCountRequest->id)->lockForUpdate()->firstOrFail();
   if($r->status!=='pending')throw ValidationException::withMessages(['review'=>'Request already processed.']);
   $count=DailyStockCount::whereKey($r->daily_stock_count_id)->lockForUpdate()->firstOrFail();
   if($count->review_status==='canceled')throw ValidationException::withMessages(['review'=>'This count has already been canceled.']);
   if($data['decision']==='approved'){
    // Only the latest active count can be changed. No stock transactions after its submission.
    $laterCount=DailyStockCount::where('review_status','!=','canceled')->where('id','>',$count->id)->exists();
    if($laterCount)throw ValidationException::withMessages(['review'=>'A newer closing inventory exists. This historical count cannot be changed.']);
    $affectedIds=$r->type==='cancellation'?$count->items()->pluck('item_id')->all():$r->items->map(fn($x)=>$x->countItem->item_id)->all();
    $laterMovement=StockTransaction::whereIn('item_id',$affectedIds)->where('created_at','>',$count->submitted_at)->where('reference','!=','Daily Count #'.$count->id)->where('reference','not like','Daily Count Correction #%')->where('reference','not like','Daily Count Cancellation #%')->exists();
    if($laterMovement)throw ValidationException::withMessages(['review'=>'Stock transactions occurred after submission. Resolve those movements before approving this request.']);
    if($r->type==='correction'){
     foreach($r->items as $change){
      $line=$change->countItem;$stock=InventoryItem::whereKey($line->item_id)->lockForUpdate()->firstOrFail();
      if((int)$line->actual_qty!==(int)$change->previous_qty)throw ValidationException::withMessages(['review'=>'The count has changed since the request was made.']);
      if((int)$stock->stock_qty!==(int)$line->actual_qty)throw ValidationException::withMessages(['review'=>'Current stock no longer matches this count.']);
      $delta=$change->requested_qty-$change->previous_qty;
      $this->movement($stock,$delta,'Daily Count Correction #'.$r->id,'Approved correction for closing count #'.$count->id);
      $line->actual_qty=$change->requested_qty;$line->variance_qty=$line->actual_qty-$line->expected_qty;$line->save();
     }
     $count->was_corrected=true;$count->review_status='corrected';$count->save();
    }else{
     foreach($count->items as $line){
      $stock=InventoryItem::whereKey($line->item_id)->lockForUpdate()->firstOrFail();
      if((int)$stock->stock_qty!==(int)$line->actual_qty)throw ValidationException::withMessages(['review'=>'Current stock no longer matches the submitted count.']);
      $this->movement($stock,(int)$line->expected_qty-(int)$line->actual_qty,'Daily Count Cancellation #'.$r->id,'Reversal of canceled closing count #'.$count->id);
     }
     $count->review_status='canceled';$count->canceled_at=now();$count->save();
    }
   }
   $r->status=$data['decision'];$r->review_note=$data['review_note']??null;$r->reviewed_by=auth()->id();$r->reviewed_at=now();$r->save();
  });
  return back()->with('success','Request '.$data['decision'].'.');
 }
 private function movement(InventoryItem $item,int $delta,string $reference,string $notes):void{
  if($delta!==0){StockTransaction::create(['item_id'=>$item->id,'user_id'=>auth()->id(),'type'=>$delta>0?'in':'out','quantity'=>abs($delta),'reference'=>$reference,'notes'=>$notes,'date'=>today()]);}
  $item->stock_qty=(int)$item->stock_qty+$delta;$item->save();$item->refreshStatus();
 }
}
