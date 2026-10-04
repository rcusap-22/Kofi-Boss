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
                <a href="{{ route($item['route']) }}" class="app-nav-link {{ $active?'is-active':'' }}">{{ $item['label'] }}</a>
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
                    <a href="{{ route($item['route']) }}" class="app-nav-link {{ $active?'is-active':'' }}">{{ $item['label'] }}</a>
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
            @yield('content')
        </main>
    </div>
</div>

<div id="logout-dialog" class="logout-dialog hidden no-print" role="dialog" aria-modal="true">
    <div class="logout-dialog-card"><h2 class="text-xl font-bold">Log out of KOFI BOSS?</h2><p class="mt-2 text-slate-600">You will return to the login screen.</p><div class="mt-5 grid grid-cols-2 gap-3"><button type="button" id="logout-cancel" class="ui-btn ui-btn-secondary">Cancel</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="ui-btn ui-btn-primary w-full">Log Out</button></form></div></div>
</div>
<script>(()=>{const d=document.getElementById('logout-dialog'),opens=[document.getElementById('logout-open'),document.getElementById('logout-open-mobile')].filter(Boolean),c=document.getElementById('logout-cancel');if(!d||!c||!opens.length)return;let last=null;const close=()=>{d.classList.add('hidden');last?.focus()};opens.forEach(o=>o.addEventListener('click',()=>{last=o;d.classList.remove('hidden');c.focus()}));c.addEventListener('click',close);d.addEventListener('click',e=>{if(e.target===d)close()});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!d.classList.contains('hidden'))close()})})();</script>
@stack('scripts')
</body></html>
