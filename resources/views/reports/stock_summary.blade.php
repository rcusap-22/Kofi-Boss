@extends('layouts.app')
@section('title','Stock Summary')
@section('content')
@if(auth()->user()->isOwner())<div class="flex justify-end mb-4 no-print"><button onclick="window.print()" class="ui-btn ui-btn-secondary">Print / Save PDF</button></div>@endif
<div class="mb-6"><h2 class="page-heading">Stock Summary</h2><p class="page-subheading">Current inventory quantities and stock status.</p></div>
<div class="ui-card data-table-wrap"><table class="data-table"><thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Reorder Threshold</th><th>Status</th></tr></thead><tbody>
@forelse($items as $item)<tr><td class="font-semibold">{{ $item->name }}</td><td>{{ $item->category }}</td><td>{{ $item->stock_qty }} {{ $item->unit }}</td><td>{{ $item->min_stock }} {{ $item->unit }}</td><td>{{ str_replace('_',' ',ucfirst($item->status)) }}</td></tr>@empty<tr><td colspan="5" class="empty-state">No inventory items found.</td></tr>@endforelse
</tbody></table></div>
@endsection
