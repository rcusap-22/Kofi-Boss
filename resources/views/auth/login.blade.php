<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#064e3b">
<title>Sign In — KOFI BOSS</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<div class="min-h-screen grid lg:grid-cols-[1.05fr_.95fr] bg-[#064e3b]">
    <section class="hidden lg:flex flex-col justify-between p-12 xl:p-16 text-white relative overflow-hidden" style="background:linear-gradient(145deg,#075b43,#033d2e 75%)">
        <div><div class="font-serif text-5xl xl:text-6xl font-bold">KOFI BOSS</div><div class="mt-2 text-sm uppercase tracking-[.24em] text-[#e7bd67] font-bold">Inventory System</div></div>
        <div class="max-w-xl"><p class="text-[#e7bd67] font-bold uppercase tracking-[.18em] text-xs mb-3">Coffee Shop Operations</p><h1 class="font-serif text-4xl xl:text-5xl font-bold leading-tight">Manage your coffee shop efficiently.</h1><p class="mt-5 text-green-100/80 text-lg leading-relaxed">Designed for quick, touch-friendly inventory work behind the counter.</p></div>
        <div class="text-sm text-green-100/55">KOFI BOSS Inventory Management System</div>
    </section>
    <main class="flex items-center justify-center p-5 sm:p-8" style="background:linear-gradient(135deg,#f1cf83,#d7a744)">
        <div class="w-full max-w-md">
            <div class="lg:hidden text-center mb-7"><h1 class="font-serif text-4xl font-bold text-[#2f2413]">KOFI BOSS</h1><p class="mt-1 text-sm font-bold uppercase tracking-[.15em] text-[#6b4a16]">Inventory Management System</p></div>
            <div class="ui-card p-6 sm:p-8 !bg-[#fffaf0] !border-[#f0cf87]">
                <div class="mb-7"><h2 class="font-serif text-3xl font-bold text-[#17231c]">Welcome back</h2><p class="text-stone-500 mt-1">Sign in to continue.</p></div>
                @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm font-medium">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login') }}" class="space-y-5">@csrf
                    <div><label class="ui-label">Username</label><input type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" class="ui-input text-lg" placeholder="Enter your username"></div>
                    <div><label class="ui-label">Password</label><input type="password" name="password" required autocomplete="current-password" class="ui-input text-lg" placeholder="Enter your password"></div>
                    <button type="submit" class="ui-btn ui-btn-primary tap w-full min-h-14 text-lg">Sign In</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body></html>
