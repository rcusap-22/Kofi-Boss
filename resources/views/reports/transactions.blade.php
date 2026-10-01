@extends('layouts.app')
@section('title', 'Transaction Report')
@section('content')
@if(auth()->user()->isOwner())<div class="flex justify-end mb-4 no-print"><button onclick="window.print()" class="ui-btn ui-btn-secondary">Print / Save PDF</button></div>@endif
    <div class="mb-6">
        <h2 class="page-heading">Transaction Report</h2>
        <p class="page-subheading">{{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}</p>
    </div>

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
@endsection
