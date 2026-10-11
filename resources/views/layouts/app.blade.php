<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#064e3b">
<title>KOFI BOSS - @yield('title','Inventory')</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
@php
$user=auth()->user();
// Count stock alerts using the same status values as the Low Stock page.
$stockAlertItems=\App\Models\InventoryItem::query()
    ->whereIn('status',['low_stock','out_of_stock'])
    ->orderBy('id')
    ->get(['id','status']);
$lowStockCount=$stockAlertItems->count();
$outOfStockCount=$stockAlertItems->where('status','out_of_stock')->count();
$onlyLowCount=$lowStockCount-$outOfStockCount;
$activeAlertKeys=$stockAlertItems->map(fn($item)=>$item->id.':'.$item->status)->all();
$dismissedAlertKeys=session('stock_banner_dismissed_keys',[]);
$showStockBanner=$lowStockCount>0 && count(array_diff($activeAlertKeys,$dismissedAlertKeys))>0;

// Show only purchase requests requiring action for the current role.
$purchaseActionCount=0;
if($user->isStoreManager()) {
    $purchaseActionCount=\App\Models\Purchase::where('status','pending')->count();
} elseif($user->isInventoryStaff()) {
    $awaiting=\App\Models\Purchase::with(['items','receiving.items'])
        ->where('status','approved')->get();
    $purchaseActionCount=$awaiting->filter(function($purchase){
        $received=$purchase->receiving->flatMap->items
            ->groupBy('item_id')->map(fn($rows)=>$rows->sum('qty_received'));
        return $purchase->items->contains(fn($line)=>(int)($received[$line->item_id]??0)<(int)$line->quantity);
    })->count();
}
// Pending daily-count correction/cancellation requests awaiting Manager action.
$dailyCountRequestActionCount = $user->isStoreManager()
    ? \App\Models\DailyCountRequest::where('status', 'pending')->count()
    : 0;
$topNav=collect([
 ['route'=>'dashboard','active'=>['dashboard'],'label'=>'Dashboard','show'=>true],
 ['route'=>'daily-counts.index','active'=>['daily-counts.*'],'label'=>$user->isInventoryStaff()?'Daily Count':'Daily Inventory','show'=>true],
 ['route'=>'inventory.index','active'=>['inventory.*'],'label'=>'Inventory','show'=>true],
 ['route'=>'stock.low','active'=>['stock.low'],'label'=>'Low Stock','show'=>true],
 ['route'=>'purchases.index','active'=>['purchases.*','receiving.*'],'label'=>'Purchases','show'=>true],
 ['route'=>'reports.index','active'=>['reports.*'],'label'=>'Reports','show'=>$user->isOwner()||$user->isStoreManager()],
 ['route'=>'employees.index','active'=>['employees.*'],'label'=>'Employees','show'=>$user->isOwner()],
])->filter(fn($i)=>$i['show']);
$backLinks=[
 'inventory.create'=>['route'=>'inventory.index','label'=>'Inventory'],'inventory.edit'=>['route'=>'inventory.index','label'=>'Inventory'],
 'purchases.create'=>['route'=>'purchases.index','label'=>'Purchases'],'purchases.edit'=>['route'=>'purchases.index','label'=>'Purchases'],'receiving.create'=>['route'=>'purchases.index','label'=>'Purchases'],
 'reports.daily-inventory'=>['route'=>'reports.index','label'=>'Reports'],'reports.stock'=>['route'=>'reports.index','label'=>'Reports'],
 'reports.inventory-movement'=>['route'=>'reports.index','label'=>'Reports'],'reports.transactions'=>['route'=>'reports.index','label'=>'Reports'],
 'reports.lowstock'=>['route'=>'reports.index','label'=>'Reports'],'reports.purchases'=>['route'=>'reports.index','label'=>'Reports'],
 'employees.create'=>['route'=>'employees.index','label'=>'Employees'],
];
$currentRoute=request()->route()?->getName();
$backLink=$currentRoute&&isset($backLinks[$currentRoute])?$backLinks[$currentRoute]:null;
@endphp
<div class="app-shell">
    <aside class="app-sidebar no-print">
        <a href="{{ route('dashboard') }}" class="app-brand">
            <span class="app-brand-kicker">KOFI BOSS</span>
            <span class="app-brand-title">Inventory System</span>
        </a>
        <nav class="app-primary-nav" aria-label="Main navigation">
            @foreach($topNav as $item)
                @php $active=request()->routeIs(...$item['active']); @endphp
                <a href="{{ route($item['route']) }}" class="app-nav-link {{ $active?'is-active':'' }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <span>{{ $item['label'] }}</span>
                    @php $badgeCount=$item['route']==='stock.low'?$lowStockCount:($item['route']==='purchases.index'?$purchaseActionCount:($item['route']==='daily-counts.index'?$dailyCountRequestActionCount:0)); @endphp
                    @if($badgeCount>0)<span class="kb-nav-badge" aria-label="{{ $badgeCount }} items requiring attention">{{ $badgeCount>99?'99+':$badgeCount }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="app-sidebar-footer">
            <div class="sidebar-user"><strong>{{ $user->name }}</strong><span>{{ $user->isOwner()?'Owner':($user->isStoreManager()?'Store Manager':'Inventory Staff') }}</span></div>
            <button type="button" class="app-logout-button" id="logout-open">Logout</button>
        </div>
    </aside>

    <div class="app-workspace">
        <header class="app-mobile-header no-print">
            <a href="{{ route('dashboard') }}" class="mobile-brand"><strong>KOFI BOSS</strong><span>Inventory System</span></a>
            <button type="button" class="app-logout-button" id="logout-open-mobile">Logout</button>
            <nav class="mobile-primary-nav" aria-label="Main navigation">
                @foreach($topNav as $item)
                    @php $active=request()->routeIs(...$item['active']); @endphp
                    <a href="{{ route($item['route']) }}" class="app-nav-link {{ $active?'is-active':'' }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <span>{{ $item['label'] }}</span>
                    @php $badgeCount=$item['route']==='stock.low'?$lowStockCount:($item['route']==='purchases.index'?$purchaseActionCount:($item['route']==='daily-counts.index'?$dailyCountRequestActionCount:0)); @endphp
                    @if($badgeCount>0)<span class="kb-nav-badge" aria-label="{{ $badgeCount }} items requiring attention">{{ $badgeCount>99?'99+':$badgeCount }}</span>@endif
                </a>
                @endforeach
            </nav>
        </header>

        <div class="page-topbar no-print">
            <div class="page-topbar-title">
                @if($backLink)<a href="{{ route($backLink['route']) }}" class="page-back">← {{ $backLink['label'] }}</a>@endif
                <span>@yield('title','Inventory')</span>
            </div>
            <div class="page-role">{{ $user->isOwner()?'Owner':($user->isStoreManager()?'Store Manager':'Inventory Staff') }}</div>
        </div>

        <main class="app-main">
            @if(session('success'))<div class="system-alert system-alert-success no-print">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="system-alert system-alert-error no-print"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if($showStockBanner)
                <section id="kb-stock-banner" class="kb-stock-banner no-print {{ $outOfStockCount>0?'kb-stock-urgent':'kb-stock-warning' }}" role="status" aria-live="polite">
                    <span class="kb-stock-icon" aria-hidden="true">&#9888;</span>
                    <div class="kb-stock-banner-text">
                        <strong>{{ $outOfStockCount>0?'Inventory Stock Alert':'Low Stock Alert' }}</strong>
                        <span>
                            @if($outOfStockCount>0 && $onlyLowCount>0)
                                {{ $outOfStockCount }} out of stock and {{ $onlyLowCount }} low on stock.
                            @elseif($outOfStockCount>0)
                                {{ $outOfStockCount }} {{ \Illuminate\Support\Str::plural('item',$outOfStockCount) }} out of stock.
                            @else
                                {{ $onlyLowCount }} {{ \Illuminate\Support\Str::plural('item',$onlyLowCount) }} low on stock.
                            @endif
                        </span>
                        <a href="{{ route('stock.low') }}">View Low Stock →</a>
                    </div>
                    <button type="button" id="kb-stock-dismiss" aria-label="Dismiss low-stock notification" title="Dismiss until next login or new stock alert">×</button>
                </section>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<div id="logout-dialog" class="logout-dialog hidden no-print" role="dialog" aria-modal="true">
    <div class="logout-dialog-card"><h2 class="text-xl font-bold">Log out of KOFI BOSS?</h2><p class="mt-2 text-slate-600">You will return to the login screen.</p><div class="mt-5 grid grid-cols-2 gap-3"><button type="button" id="logout-cancel" class="ui-btn ui-btn-secondary">Cancel</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="ui-btn ui-btn-primary w-full">Log Out</button></form></div></div>
</div>
<script>(()=>{const d=document.getElementById('logout-dialog'),opens=[document.getElementById('logout-open'),document.getElementById('logout-open-mobile')].filter(Boolean),c=document.getElementById('logout-cancel');if(!d||!c||!opens.length)return;let last=null;const close=()=>{d.classList.add('hidden');last?.focus()};opens.forEach(o=>o.addEventListener('click',()=>{last=o;d.classList.remove('hidden');c.focus()}));c.addEventListener('click',close);d.addEventListener('click',e=>{if(e.target===d)close()});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!d.classList.contains('hidden'))close()})})();</script>

@include('partials.system_dialog')

<!-- Global confirmation dialog for all inventory-changing actions. -->
<div id="global-confirm-dialog" class="logout-dialog hidden no-print" role="dialog" aria-modal="true" aria-labelledby="global-confirm-title">
 <div class="logout-dialog-card" style="max-width:440px">
  <h2 id="global-confirm-title" class="text-xl font-bold">Confirm action</h2>
  <p id="global-confirm-message" class="mt-2 text-slate-600">Do you want to continue?</p>
  <div class="mt-5 grid grid-cols-2 gap-3">
   <button type="button" id="global-confirm-cancel" class="ui-btn ui-btn-secondary">Cancel</button>
   <button type="button" id="global-confirm-yes" class="ui-btn ui-btn-primary">Confirm</button>
  </div>
 </div>
</div>
<script>
(()=>{
 const dialog=document.getElementById('global-confirm-dialog');
 const message=document.getElementById('global-confirm-message');
 const yes=document.getElementById('global-confirm-yes');
 const no=document.getElementById('global-confirm-cancel');
 let pending=null, previous=null;
 const close=()=>{dialog.classList.add('hidden');pending=null;previous?.focus()};
 const open=(msg,callback)=>{previous=document.activeElement;pending=callback;message.textContent=msg;dialog.classList.remove('hidden');no.focus()};
 no.addEventListener('click',close);
 yes.addEventListener('click',()=>{const action=pending;pending=null;close();if(action)action()});
 dialog.addEventListener('click',e=>{if(e.target===dialog)close()});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!dialog.classList.contains('hidden'))close()});
 document.addEventListener('submit',e=>{
  const form=e.target;
  if(!(form instanceof HTMLFormElement)||form.dataset.confirmed==='yes')return;
  if((form.getAttribute('method')||'GET').toUpperCase()==='GET')return;
  if(form.closest('#logout-dialog'))return;
  if(e.defaultPrevented)return;
  // Keep HTML validation, and preserve submitter name/value for button-specific actions.
  e.preventDefault();
  const submitter=e.submitter;
  const method=(form.querySelector('input[name="_method"]')?.value||form.method||'POST').toUpperCase();
  const label=(submitter?.textContent||submitter?.value||'').trim().replace(/\s+/g,' ').toLowerCase();
  const title=document.getElementById('global-confirm-title');
  let heading='Confirm Action';
  let msg='Are you sure you want to continue?';
  let buttonText='Confirm';
  if(label.includes('closing inventory')||label.includes('daily count')){
   heading='Confirm Closing Inventory';
   msg="Are you sure you want to submit today's closing inventory? Once submitted, it will be locked and cannot be edited.";
   buttonText='Submit Inventory';
  }else if(method==='DELETE'||label.includes('delete')||label.includes('remove')){
   heading='Confirm Deletion';msg='Are you sure you want to delete this record? This action cannot be undone.';buttonText='Delete';
  }else if(label.includes('approve')){
   heading='Confirm Approval';msg='Are you sure you want to approve this request?';buttonText='Approve';
  }else if(label.includes('reject')){
   heading='Confirm Rejection';msg='Are you sure you want to reject this request?';buttonText='Reject';
  }else if(label.includes('receiv')){
   heading='Confirm Receiving';msg='Are you sure you want to record this delivery? Received quantities will be added to inventory.';buttonText='Receive';
  }else if(method==='PUT'||method==='PATCH'||label.includes('update')||label.includes('save changes')){
   heading='Confirm Changes';msg='Are you sure you want to save these changes?';buttonText='Save Changes';
  }else if(label.includes('add')||label.includes('create')){
   heading='Confirm New Record';msg='Are you sure you want to add this record?';buttonText='Add Record';
  }else if(label.includes('submit')||label.includes('request')){
   heading='Confirm Submission';msg='Are you sure you want to submit this request?';buttonText='Submit';
  }
  title.textContent=heading;
  yes.textContent=buttonText;
  msg=submitter?.dataset.confirm || form.dataset.confirmMessage || form.dataset.confirm || msg;
  open(msg,()=>{
   form.dataset.confirmed='yes';
   if(submitter && submitter.isConnected)form.requestSubmit(submitter);
   else form.requestSubmit();
  });
 },true);
})();
</script>

<style>
.kb-nav-badge{display:inline-flex;align-items:center;justify-content:center;min-width:23px;height:23px;padding:0 6px;border-radius:999px;background:#9f2f20;color:white;font-size:12px;font-weight:800;line-height:1;flex-shrink:0}
.kb-stock-banner{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;padding:15px 18px;border-radius:12px;border:1px solid;border-left-width:7px;box-shadow:0 4px 12px rgba(0,0,0,.13)}
.kb-stock-warning{background:#ffedd5;border-color:#f97316;color:#9a3412}
.kb-stock-urgent{background:#fee2e2;border-color:#dc2626;color:#991b1b}
.kb-stock-icon{font-size:25px;font-weight:900;line-height:1;flex-shrink:0}
.kb-stock-banner-text{display:flex;flex:1;flex-wrap:wrap;align-items:center;gap:7px 12px;font-size:14px}
.kb-stock-banner-text strong{font-size:16px;font-weight:900}
.kb-stock-banner-text a{color:inherit;font-weight:800;text-decoration:underline;text-underline-offset:3px}
#kb-stock-dismiss{flex-shrink:0;border:0;background:transparent;color:inherit;font-size:25px;line-height:1;cursor:pointer;padding:5px 8px;border-radius:6px}
#kb-stock-dismiss:hover{background:rgba(0,0,0,.07)}
#kb-stock-dismiss:focus-visible{outline:2px solid currentColor;outline-offset:3px}
</style>
<script>
(()=>{
 const close=document.getElementById('kb-stock-dismiss');
 const banner=document.getElementById('kb-stock-banner');
 if(!close||!banner)return;
 close.addEventListener('click',async()=>{
  close.disabled=true;
  try{
   const response=await fetch(@json(route('stock.banner.dismiss')),{
    method:'POST',credentials:'same-origin',headers:{
     'X-CSRF-TOKEN':@json(csrf_token()),
     'Accept':'application/json',
     'X-Requested-With':'XMLHttpRequest'
    }
   });
   if(!response.ok)throw new Error('Could not dismiss notification');
   banner.remove();
  }catch(e){close.disabled=false;}
 });
})();
</script>
@stack('scripts')
</body></html>
