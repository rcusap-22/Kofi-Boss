@extends('layouts.app')
@section('title','Daily Inventory Comparison')
@section('content')
<div class="max-w-6xl mx-auto">
<a href="{{ route('daily-counts.index') }}" class="ui-btn ui-btn-secondary mb-4">Back to Daily Inventory</a>
<h2 class="page-heading">Daily Inventory Comparison</h2>
<p class="page-subheading mb-5">Compare historical closing inventory counts. These are not live stock balances.</p>
<form method="GET" action="{{ route('daily-counts.comparison') }}" class="ui-card p-5 mb-5 flex flex-wrap gap-3 items-end">
<div class="flex-1 min-w-[12rem]"><label class="ui-label">Historical Date</label><input class="ui-input" type="date" name="historical_date" value="{{ $historicalDate }}" required></div>
<div class="flex-1 min-w-[12rem]"><label class="ui-label">Comparison Date</label><input class="ui-input" type="date" name="comparison_date" value="{{ $comparisonDate }}" required></div>
<div class="flex-1 min-w-[10rem]"><label class="ui-label">Stock Change</label><select class="ui-input" name="change">@foreach(['all'=>'All Items','increased'=>'Increased','decreased'=>'Decreased','unchanged'=>'Unchanged'] as $k=>$v)<option value="{{ $k }}" @selected($change===$k)>{{ $v }}</option>@endforeach</select></div>
<button class="ui-btn ui-btn-primary">Compare</button>
</form>
@if(!$historicalCount || !$comparisonCount)
<div class="ui-card p-6 text-center">No valid closing inventory exists for one or both selected dates.</div>
@else
@if($historicalCount->was_corrected || $comparisonCount->was_corrected)
<div class="ui-card p-4 mb-4" style="background:#fff8e4;border:1px solid #d7b968">The {{ $historicalCount->was_corrected && $comparisonCount->was_corrected ? 'selected closing inventories have' : ($historicalCount->was_corrected ? 'historical closing inventory has' : 'comparison closing inventory has') }} been edited following an approved correction.</div>
@endif
<div class="ui-card overflow-hidden">
<div class="overflow-x-auto"><table class="w-full text-sm">
<thead style="background:#f0d28e"><tr><th class="p-4 text-left">Item</th><th class="p-4 text-center">Historical ({{ $historicalDate }})</th><th class="p-4 text-center">Comparison ({{ $comparisonDate }})</th><th class="p-4 text-center">Difference</th></tr></thead>
<tbody>@forelse($paginatedRows as $r)
<tr class="border-b"><td class="p-4"><strong>{{ $r['name'] }}</strong><div class="text-xs text-slate-500">{{ $r['unit'] }}</div></td><td class="p-4 text-center">{{ $r['previous']===null?'—':number_format($r['previous']) }}</td><td class="p-4 text-center">{{ $r['current']===null?'—':number_format($r['current']) }}</td>
<td class="p-4 text-center font-bold">@if($r['difference']===null)—@elseif($r['difference']>0)<span class="text-green-700">+{{ $r['difference'] }}</span>@elseif($r['difference']<0)<span class="text-red-600">{{ $r['difference'] }}</span>@else 0 @endif</td></tr>
@empty<tr><td colspan="4" class="p-6 text-center">No matching items.</td></tr>@endforelse</tbody></table></div>
<div class="p-4">{{ $paginatedRows->links() }}</div></div>
<p class="text-sm mt-3 text-slate-200">Difference = comparison count minus historical count. Receiving between dates can affect the difference.</p>
@endif</div>
@endsection
