@extends('layouts.app')
@section('title', 'Stock History')
@section('content')
<div class="mb-5">
    <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary inline-flex items-center min-h-11 px-4">Back to Inventory</a>
</div>
<h2 class="page-heading">Stock History</h2>
<p class="page-subheading mb-5">{{ $item->name }} · {{ $item->unit }} · {{ $item->archived_at ? 'Archived item' : 'Inventory item' }}</p>
<div class="ui-card p-5 mb-5">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div><div class="text-sm text-slate-600">Current Stock</div><div class="text-3xl font-bold">{{ number_format($item->stock_qty) }} {{ $item->unit }}</div></div>
        <div><div class="text-sm text-slate-600">Total Recorded In</div><div class="text-2xl font-bold text-emerald-800">+{{ number_format($received) }}</div></div>
        <div><div class="text-sm text-slate-600">Total Recorded Out</div><div class="text-2xl font-bold text-red-700">-{{ number_format($removed) }}</div></div>
    </div>
    <p class="text-sm text-slate-600 mt-4">Requested or approved purchases do not change stock. Only recorded receiving and other completed stock transactions do. Historical balances are reconstructed from the current stock and available transactions; older records may be incomplete if transaction history was cleared.</p>
</div>
<form method="GET" action="{{ route('inventory.history', $item) }}" class="ui-card p-4 mb-5 flex flex-wrap gap-3 items-end">
    <div><label for="history-type" class="block text-sm font-semibold mb-1">Movement</label><select id="history-type" name="type" class="ui-input" style="min-width:170px">
        <option value="all" @selected($type === 'all')>All Movements</option>
        <option value="in" @selected($type === 'in')>Stock In</option>
        <option value="out" @selected($type === 'out')>Stock Out</option>
    </select></div>
    <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
    @if($type !== 'all')<a href="{{ route('inventory.history', $item) }}" class="ui-btn ui-btn-secondary">Clear</a>@endif
</form>
<div class="ui-card data-table-wrap">
<table class="data-table">
<thead><tr><th>Recorded</th><th>Activity / Reference</th><th>Employee</th><th>Change</th><th>Balance After</th></tr></thead>
<tbody>
@forelse($transactions as $tx)
<tr>
    <td class="whitespace-nowrap">{{ optional($tx->created_at)->format('M d, Y g:i A') ?? optional($tx->date)->format('M d, Y') }}</td>
    <td><div class="font-semibold">{{ $tx->reference ?: ($tx->type === 'in' ? 'Stock In' : 'Stock Out') }}</div>@if($tx->notes)<div class="text-sm text-slate-600">{{ $tx->notes }}</div>@endif<div class="text-xs text-slate-500">Transaction #{{ $tx->id }}</div></td>
    <td>{{ $tx->user?->name ?? 'Account unavailable' }}</td>
    <td class="font-bold {{ $tx->type === 'in' ? 'text-emerald-800' : 'text-red-700' }}">{{ $tx->type === 'in' ? '+' : '-' }}{{ number_format($tx->quantity) }}</td>
    <td class="font-semibold">{{ number_format($tx->balance_after) }}</td>
</tr>
@empty
<tr><td colspan="5" class="empty-state">No recorded stock movements match this filter.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-5 flex flex-wrap items-center justify-between gap-3">
    <span class="text-sm text-white">Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} records</span>
    {{ $transactions->links() }}
</div>
@endsection
