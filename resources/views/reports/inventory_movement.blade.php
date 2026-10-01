@extends('layouts.app')
@section('title', 'Inventory Movement Report')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="page-heading">Inventory Movement Report</h2>
            <p class="page-subheading">{{ $periodLabel }}</p>
        </div>
        <button type="button" onclick="window.print()" class="tap rounded-xl bg-green-700 px-4 py-3 text-sm font-semibold text-white hover:bg-green-800 print:hidden">Print / Save PDF</button>
    </div>

    <div class="ui-card p-4 print:hidden">
        <form method="GET" action="{{ route('reports.inventory-movement') }}" class="grid md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-sm font-semibold mb-1">Report Period</label>
                <select id="period" name="period" class="w-full rounded-xl border border-stone-300 px-3 py-3" onchange="togglePeriodFields()">
                    <option value="daily" @selected($period === 'daily')>Daily</option>
                    <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                    <option value="custom" @selected($period === 'custom')>Custom Range</option>
                </select>
            </div>
            <div id="dailyField"><label class="block text-sm font-semibold mb-1">Date</label><input type="date" name="date" value="{{ request('date', $start->toDateString()) }}" class="w-full rounded-xl border border-stone-300 px-3 py-3"></div>
            <div id="monthlyField"><label class="block text-sm font-semibold mb-1">Month</label><input type="month" name="month" value="{{ request('month', $start->format('Y-m')) }}" class="w-full rounded-xl border border-stone-300 px-3 py-3"></div>
            <div id="fromField"><label class="block text-sm font-semibold mb-1">From</label><input type="date" name="from" value="{{ request('from', $start->toDateString()) }}" class="w-full rounded-xl border border-stone-300 px-3 py-3"></div>
            <div id="toField"><label class="block text-sm font-semibold mb-1">To</label><input type="date" name="to" value="{{ request('to', $end->toDateString()) }}" class="w-full rounded-xl border border-stone-300 px-3 py-3"></div>
            <button class="tap rounded-xl bg-green-700 px-4 py-3 font-semibold text-white hover:bg-green-800">Generate Report</button>
        </form>
    </div>

    <div class="grid sm:grid-cols-3 gap-3">
        <div class="ui-card p-4"><div class="text-xs uppercase tracking-wide text-stone-500">Received Stock</div><div class="text-2xl font-bold mt-1">{{ number_format($totals->received_stock, 0) }}</div><div class="text-xs text-stone-500 mt-1">Recorded deliveries</div></div>
        <div class="ui-card p-4"><div class="text-xs uppercase tracking-wide text-stone-500">Count Increase</div><div class="text-2xl font-bold mt-1">{{ number_format($totals->reconciliation_in, 0) }}</div><div class="text-xs text-stone-500 mt-1">Actual count above expected</div></div>
        <div class="ui-card p-4"><div class="text-xs uppercase tracking-wide text-stone-500">Count Reduction</div><div class="text-2xl font-bold mt-1">{{ number_format($totals->reconciliation_out, 0) }}</div><div class="text-xs text-stone-500 mt-1">Actual count below expected</div></div>
    </div>

    <div class="ui-card data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Item</th><th>Beginning Stock</th><th>Received</th><th>Count Increase</th><th>Count Reduction</th><th>Ending Stock</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><div class="font-semibold">{{ $row->name }}</div><div class="text-xs text-stone-500">{{ $row->category }}</div></td>
                        <td>{{ number_format($row->beginning_stock, 0) }} {{ $row->unit }}</td>
                        <td class="font-semibold text-green-700">+{{ number_format($row->received_stock, 0) }} {{ $row->unit }}</td>
                        <td>{{ number_format($row->reconciliation_in, 0) }} {{ $row->unit }}</td>
                        <td>{{ number_format($row->reconciliation_out, 0) }} {{ $row->unit }}</td>
                        <td class="font-bold">{{ number_format($row->ending_stock, 0) }} {{ $row->unit }}</td>
                        <td>@php $statusClass = $row->status === 'Out of Stock' ? 'bg-red-50 text-red-700' : ($row->status === 'Low Stock' ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700'); @endphp<span class="status-pill {{ $statusClass }}">{{ $row->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">No inventory items found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-sm text-stone-500">Daily Stock Count is the authoritative closing inventory. Count increases or reductions show the difference between the system's expected quantity and the staff's physical count.</div>
</div>
<script>
function togglePeriodFields(){const p=document.getElementById('period').value;document.getElementById('dailyField').style.display=p==='daily'?'block':'none';document.getElementById('monthlyField').style.display=p==='monthly'?'block':'none';document.getElementById('fromField').style.display=p==='custom'?'block':'none';document.getElementById('toField').style.display=p==='custom'?'block':'none';}
document.addEventListener('DOMContentLoaded',togglePeriodFields);
</script>
<style>@media print{aside,header,.app-bottom-nav{display:none!important}.app-main{padding:0!important}body{background:white!important}}</style>
@endsection
