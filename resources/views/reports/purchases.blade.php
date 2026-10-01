@extends('layouts.app')
@section('title','Purchase Report')
@section('content')
@if(auth()->user()->isOwner())<div class="flex justify-end mb-4 no-print"><button onclick="window.print()" class="ui-btn ui-btn-secondary">Print / Save PDF</button></div>@endif
<div class="mb-6"><h2 class="page-heading">Purchase Report</h2><p class="page-subheading">{{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}</p></div>
<div class="ui-card data-table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Supplier</th><th>Requested By</th><th>Status</th></tr></thead><tbody>
@forelse($purchases as $p)<tr><td class="whitespace-nowrap">{{ $p->order_date->format('M j, Y') }}</td><td class="font-semibold">{{ $p->supplier->name }}</td><td>{{ $p->requestedBy->name }}</td><td><span class="status-pill {{ match($p->status){'pending'=>'bg-yellow-50 text-yellow-700','approved'=>'bg-blue-50 text-blue-700','completed'=>'bg-green-50 text-green-700',default=>'bg-red-50 text-red-700'} }}">{{ ucfirst($p->status) }}</span></td></tr>@empty<tr><td colspan="4" class="empty-state">No purchase records found for this period.</td></tr>@endforelse
</tbody></table></div>
@endsection
