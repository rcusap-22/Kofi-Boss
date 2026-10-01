@extends('layouts.app')
@section('title', auth()->user()->isInventoryStaff() ? 'Daily Stock Count' : 'Daily Inventory')
@section('content')
<div class="max-w-6xl mx-auto">
<div class="mb-5"><h2 class="page-heading">{{ auth()->user()->isInventoryStaff() ? 'Daily Closing Inventory' : 'Daily Inventory Review' }}</h2><p class="page-subheading">{{ auth()->user()->isInventoryStaff() ? 'Enter the physical stock remaining. Submit once at closing; the submitted record is locked.' : 'Read-only closing inventory submitted by Inventory Staff.' }}</p></div>
@if(!auth()->user()->isInventoryStaff())
 <div class="ui-card p-4 mb-5 no-print"><form method="GET" class="flex flex-wrap items-end gap-3"><div class="flex-1 min-w-[12rem]"><label class="ui-label">View date</label><input type="date" name="date" value="{{ request('date', today()->toDateString()) }}" class="ui-input"></div><button class="ui-btn ui-btn-primary">View Inventory</button></form></div>
 @php $viewCount=$selectedCount ?: (request('date') ? null : $todayCount); @endphp
 @if($viewCount)
  @include('daily-counts._readonly',['dailyCount'=>$viewCount])
 @else
  <div class="ui-card p-7 text-center mb-5"><div class="text-xl font-bold text-slate-800">No closing inventory submitted for this date.</div><p class="text-slate-500 mt-1">Select another date or check again after staff completes closing inventory.</p></div>
 @endif
@else
 @if($todayCount)
  <div class="ui-card p-5 mb-5 flex flex-wrap items-center justify-between gap-3"><div><h3 class="text-xl font-bold text-green-800">✓ Today's count is complete</h3><p class="text-slate-600">Submitted {{ $todayCount->submitted_at?->format('g:i A') }} by {{ $todayCount->user->name ?? 'Staff' }}. This record is locked.</p></div><a href="{{ route('daily-counts.show',$todayCount) }}" class="ui-btn ui-btn-primary">View Count</a></div>
 @else
  <form method="POST" action="{{ route('daily-counts.store') }}" onsubmit="return confirm('Submit closing inventory? Submitted counts are locked and will become the current inventory.');">@csrf
  <div class="ui-card overflow-hidden"><div class="daily-count-list"><div class="daily-count-head"><span>Item</span><span>Unit</span><span>Expected</span><span>Actual Physical Count</span><span>Variance</span></div>
  @foreach($items as $item)<div class="daily-count-row" data-row data-expected="{{ $item->stock_qty }}"><div class="daily-count-item"><div class="font-semibold">{{ $item->name }}</div><div class="text-xs text-slate-500">{{ $item->category }}</div></div><div class="daily-count-field daily-unit"><span class="daily-mobile-label">Unit</span><span>{{ $item->unit }}</span></div><div class="daily-count-field daily-expected"><span class="daily-mobile-label">Expected</span><strong>{{ number_format($item->stock_qty,0) }}</strong></div><div class="daily-count-field daily-actual"><label class="daily-mobile-label" for="actual-{{ $item->id }}">Actual Physical Count</label><input id="actual-{{ $item->id }}" name="actual[{{ $item->id }}]" inputmode="numeric" type="number" min="0" step="1" required value="{{ old('actual.'.$item->id) }}" class="actual-input ui-input text-lg font-semibold" placeholder="Enter count"></div><div class="daily-count-field daily-variance"><span class="daily-mobile-label">Variance</span><strong class="variance">—</strong></div></div>@endforeach</div>
  <div class="p-5 bg-slate-50"><label class="ui-label">Closing notes (optional)</label><textarea name="notes" rows="2" class="ui-input" placeholder="Optional note about unusual stock changes">{{ old('notes') }}</textarea><button class="mt-4 w-full sm:w-auto ui-btn ui-btn-primary text-lg">Submit Closing Inventory</button></div></div></form>
 @endif
@endif
@if($recentCounts->isNotEmpty())<div class="mt-6 ui-card p-5 no-print"><div class="flex justify-between items-center mb-3"><h3 class="text-xl font-bold">Recent Closing Counts</h3><span class="text-sm text-slate-500">Tap once to view</span></div><div class="grid md:grid-cols-2 gap-2">@foreach($recentCounts as $count)<a class="tap flex justify-between rounded-xl border p-4 hover:bg-green-50" href="{{ route('daily-counts.show',$count) }}"><span><strong>{{ $count->count_date->format('M d, Y') }}</strong><br><span class="text-sm text-slate-500">{{ $count->user->name ?? 'Staff' }}</span></span><span class="self-center">{{ $count->items_count }} items →</span></a>@endforeach</div></div>@endif
</div>
@if(auth()->user()->isInventoryStaff())<script>document.querySelectorAll('[data-row]').forEach(row=>{const input=row.querySelector('.actual-input'),out=row.querySelector('.variance'),expected=parseFloat(row.dataset.expected);input.addEventListener('input',()=>{if(input.value===''){out.textContent='—';return}const v=parseFloat(input.value)-expected;out.textContent=(v>0?'+':'')+Math.round(v).toString();out.className='variance '+(v<0?'text-red-600':v>0?'text-amber-600':'text-green-700')})});</script>@endif
@endsection
