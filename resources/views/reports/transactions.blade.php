@extends('layouts.app')
@section('title', 'Transaction Report')
@section('content')
<div class="flex justify-end mb-4"><a href="{{ route('reports.export',array_merge(['report'=>'transactions','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')],request()->only(['search','status','type']))) }}" class="ui-btn ui-btn-secondary">Download Excel</a></div>
    <div class="mb-6">
        <h2 class="page-heading">Transaction Report</h2>
        <p class="page-subheading">{{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}</p>
    </div>

    <form method="GET" action="{{ route('reports.transactions') }}" class="ui-card p-4 mb-4 flex flex-wrap gap-3 items-end"><label class="flex-1 min-w-48"><span class="ui-label">Search item</span><input class="ui-input w-full" name="search" value="{{ request('search') }}" placeholder="Search item"></label><label><span class="ui-label">Type</span><select class="ui-input" name="type"><option value="all" @selected(request('type','all')==='all')>All movements</option><option value="in" @selected(request('type','all')==='in')>Stock In</option><option value="out" @selected(request('type','all')==='out')>Stock Out</option></select></label><label><span class="ui-label">From</span><input class="ui-input" type="date" name="from" value="{{ request('from',$from->format('Y-m-d')) }}"></label><label><span class="ui-label">To</span><input class="ui-input" type="date" name="to" value="{{ request('to',$to->format('Y-m-d')) }}"></label><button class="ui-btn ui-btn-primary" type="submit">Filter</button><a class="ui-btn ui-btn-secondary" href="{{ route('reports.transactions') }}">Clear</a></form>
<div class="ui-card data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Quantity</th><th>Recorded By</th></tr></thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr>
                        <td class="whitespace-nowrap">{{ $tx->date->format('M j, Y') }}</td>
                        <td class="font-semibold">{{ $tx->item->name }}</td>
                        <td>
                            <span class="status-pill {{ $tx->type === 'in' ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700' }}">
                                {{ ucfirst($tx->type) }}
                            </span>
                        </td>
                        <td class="font-bold">{{ $tx->quantity }}</td>
                        <td>{{ $tx->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">No transactions found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
<div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
    <p class="text-white" aria-live="polite">Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} records</p>
    {{ $transactions->links('reports.pagination') }}
</div>
@endsection
