<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>KOFI BOSS - @yield('title','Inventory')</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-green-50 text-slate-800">
@php
$user=auth()->user();
$topNav=collect([
 ['route'=>'dashboard','active'=>['dashboard'],'label'=>'Dashboard','show'=>true],
 ['route'=>'daily-counts.index','active'=>['daily-counts.*'],'label'=>$user->isInventoryStaff()?'Daily Count':'Daily Inventory','show'=>true],
 ['route'=>'inventory.index','active'=>['inventory.*'],'label'=>'Inventory','show'=>true],
 ['route'=>'stock.low','active'=>['stock.low'],'label'=>'Low Stock','show'=>true],
 ['route'=>'purchases.index','active'=>['purchases.*','receiving.*'],'label'=>'Purchases','show'=>!$user->isOwner()],
 ['route'=>'reports.index','active'=>['reports.*'],'label'=>'Reports','show'=>$user->isOwner()||$user->isStoreManager()],
 ['route'=>'employees.index','active'=>['employees.*'],'label'=>'Employees','show'=>$user->isOwner()],
])->filter(fn($i)=>$i['show']);
$backLinks=[
 'inventory.create'=>['route'=>'inventory.index','label'=>'Inventory'],'inventory.edit'=>['route'=>'inventory.index','label'=>'Inventory'],
 'purchases.create'=>['route'=>'purchases.index','label'=>'Purchases'],'receiving.create'=>['route'=>'purchases.index','label'=>'Purchases'],
 'reports.daily-inventory'=>['route'=>'reports.index','label'=>'Reports'],'reports.stock'=>['route'=>'reports.index','label'=>'Reports'],
 'reports.inventory-movement'=>['route'=>'reports.index','label'=>'Reports'],'reports.transactions'=>['route'=>'reports.index','label'=>'Reports'],
 'reports.lowstock'=>['route'=>'reports.index','label'=>'Reports'],'reports.purchases'=>['route'=>'reports.index','label'=>'Reports'],
 'employees.create'=>['route'=>'employees.index','label'=>'Employees'],
];
$currentRoute=request()->route()?->getName(); $backLink=$currentRoute&&isset($backLinks[$currentRoute])?$backLinks[$currentRoute]:null;
@endphp
<div class="min-h-screen flex flex-col">
<header class="app-header sticky top-0 z-20 border-b border-green-800 no-print">
 <div class="app-header-inner"><div class="app-header-row">
  <a href="{{ route('dashboard') }}" class="app-brand"><span class="app-brand-kicker">KOFI BOSS</span><span class="app-brand-title">Inventory System</span></a>
  <nav class="app-primary-nav" style="--nav-count: {{ $topNav->count() }}" aria-label="Main navigation">
   @foreach($topNav as $item) @php $active=request()->routeIs(...$item['active']); @endphp
   <a href="{{ route($item['route']) }}" class="app-nav-link {{ $active?'is-active':'' }}">{{ $item['label'] }}</a>
   @endforeach
  </nav>
  <button type="button" class="app-logout-button" id="logout-open">↪ <span>Logout</span></button>
 </div></div>
</header>
<div id="logout-dialog" class="logout-dialog hidden no-print" role="dialog" aria-modal="true"><div class="logout-dialog-card"><h2 class="text-xl font-bold">Log out of KOFI BOSS?</h2><p class="mt-2 text-slate-600">You will return to the login screen.</p><div class="mt-5 grid grid-cols-2 gap-3"><button type="button" id="logout-cancel" class="ui-btn ui-btn-secondary">Cancel</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="ui-btn ui-btn-primary w-full">Log Out</button></form></div></div></div>
<div class="bg-green-900 text-white border-b border-green-800 px-4 sm:px-6 lg:px-8 py-3 no-print"><div class="flex items-center gap-3">@if($backLink)<a href="{{ route($backLink['route']) }}" class="tap inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-green-100">← {{ $backLink['label'] }}</a><span class="h-6 w-px bg-green-700"></span>@endif<h1 class="font-semibold text-lg truncate">@yield('title','Inventory')</h1></div></div>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-8">@if(session('success'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 no-print">{{ session('success') }}</div>@endif @if($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 no-print"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</main>
</div>
<script>(()=>{const d=document.getElementById('logout-dialog'),o=document.getElementById('logout-open'),c=document.getElementById('logout-cancel');if(!d||!o||!c)return;const close=()=>{d.classList.add('hidden');o.focus()};o.addEventListener('click',()=>{d.classList.remove('hidden');c.focus()});c.addEventListener('click',close);d.addEventListener('click',e=>{if(e.target===d)close()});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!d.classList.contains('hidden'))close()})})();</script>
@stack('scripts')
</body></html>
