@extends('layouts.app')
@section('title','Stock Summary')
@section('content')
<div class="flex justify-end mb-4"><a href="{{ route('reports.export',array_merge(['report'=>'stock-summary'],request()->only(['search','status','type']))) }}" class="ui-btn ui-btn-secondary">Download Excel</a></div>
<div class="mb-6"><h2 class="page-heading">Stock Summary</h2><p class="page-subheading">Current inventory quantities and stock status.</p></div>
<form method="GET" action="{{ route('reports.stock') }}" class="ui-card p-4 mb-4 flex flex-wrap gap-3 items-end"><label class="flex-1 min-w-48"><span class="ui-label">Search item</span><input class="ui-input w-full" name="search" value="{{ request('search') }}" placeholder="Search item"></label><label><span class="ui-label">Status</span><select class="ui-input" name="status"><option value="all" @selected(request('status','all')==='all')>All statuses</option><option value="in_stock" @selected(request('status','all')==='in_stock')>In Stock</option><option value="low_stock" @selected(request('status','all')==='low_stock')>Low Stock</option><option value="out_of_stock" @selected(request('status','all')==='out_of_stock')>Out of Stock</option></select></label><button class="ui-btn ui-btn-primary" type="submit">Filter</button><a class="ui-btn ui-btn-secondary" href="{{ route('reports.stock') }}">Clear</a></form>
<div class="ui-card data-table-wrap"><table class="data-table"><thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Reorder Threshold</th><th>Status</th></tr></thead><tbody>
@forelse($items as $item)<tr><td class="font-semibold">{{ $item->name }}</td><td>{{ $item->category }}</td><td>{{ $item->stock_qty }} {{ $item->unit }}</td><td>{{ $item->min_stock }} {{ $item->unit }}</td><td>{{ str_replace('_',' ',ucfirst($item->status)) }}</td></tr>@empty<tr><td colspan="5" class="empty-state">No inventory items found.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
    <p class="text-white" aria-live="polite">Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} records</p>
    {{ $items->links('reports.pagination') }}
</div>
@endsection
