@extends('layouts.app')
@section('title', 'Low Stock Report')
@section('content')
<div class="flex justify-end mb-4"><a href="{{ route('reports.export',['report'=>'low-stock']) }}" class="ui-btn ui-btn-secondary">Download Excel</a></div>
    <div class="mb-6">
        <h2 class="page-heading">Low Stock Report</h2>
        <p class="page-subheading">Items that have reached their minimum stock level.</p>
    </div>

    <div class="ui-card data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Item</th><th>Current Stock</th><th>Minimum</th></tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td class="font-semibold">{{ $item->name }}</td>
                        <td><span class="status-pill bg-red-50 text-red-700">{{ $item->stock_qty }} {{ $item->unit }}</span></td>
                        <td>{{ $item->min_stock }} {{ $item->unit }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty-state">No low-stock items right now.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
