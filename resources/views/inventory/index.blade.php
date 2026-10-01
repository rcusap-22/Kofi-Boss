@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="page-heading">Inventory</h2>
            <p class="page-subheading">Monitor ingredients and supplies at a glance.</p>
        </div>
        @if(auth()->user()->isStoreManager())
            <a href="{{ route('inventory.create') }}" class="ui-btn ui-btn-primary tap w-full sm:w-auto">＋ Add Item</a>
        @endif
    </div>

    <div class="ui-card p-3 mb-5">
        <form method="GET" class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search inventory..."
                       class="ui-input pl-4" aria-label="Search inventory">
            </div>
            <button type="submit" class="ui-btn ui-btn-primary tap sm:min-w-28">Search</button>
            @if(request('search'))
                <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary tap">Clear</a>
            @endif
        </form>
    </div>

    <div class="tablet-only space-y-3">
        @forelse($items as $item)
        <div class="ui-card p-4"><div class="flex justify-between gap-3"><div><div class="font-bold text-lg">{{ $item->name }}</div><div class="text-sm text-slate-500">{{ $item->category }}</div></div><span class="status-pill {{ $item->status === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ str_replace('_',' ',ucfirst($item->status)) }}</span></div><div class="mt-4 grid grid-cols-2 gap-3"><div><div class="text-xs uppercase font-bold text-slate-500">Current Stock</div><div class="text-2xl font-extrabold">{{ $item->stock_qty }} <span class="text-sm font-semibold">{{ $item->unit }}</span></div></div>@if(auth()->user()->isStoreManager())<div class="flex items-end justify-end"><a href="{{ route('inventory.edit',$item) }}" class="ui-btn ui-btn-secondary">Edit Item</a></div>@endif</div></div>
        @empty<div class="ui-card empty-state">No inventory items found.</div>@endforelse
    </div>

    <div class="desktop-tablet-landscape ui-card data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Unit</th>
                    <th>Status</th>
                    @if(auth()->user()->isStoreManager()) <th>Action</th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td class="font-semibold">{{ $item->name }}</td>
                        <td class="text-stone-600">{{ $item->category }}</td>
                        <td class="font-bold">{{ $item->stock_qty }}</td>
                        <td class="text-stone-600">{{ $item->unit }}</td>
                        <td>
                            <span class="status-pill {{ $item->status === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                {{ str_replace('_', ' ', ucfirst($item->status)) }}
                            </span>
                        </td>
                        @if(auth()->user()->isStoreManager())
                            <td>
                                <a href="{{ route('inventory.edit', $item) }}" class="ui-btn ui-btn-secondary tap min-h-10 py-2 px-3 text-sm">Edit</a>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">No inventory items found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
@endsection
