@extends('layouts.app')
@section('title', auth()->user()->isInventoryStaff() ? 'Daily Stock Count' : 'Daily Inventory')
@section('content')
@php
    $dailyCountPendingRequests = auth()->user()->isStoreManager()
        ? \App\Models\DailyCountRequest::where('status', 'pending')->count()
        : 0;
@endphp
<style>
/* Closing entry only: align column headings with their corresponding values. */
@media (min-width: 768px) {
  .closing-entry-table .daily-count-head > span:not(:first-child),
  .closing-entry-table .daily-count-field { text-align: center; }
  .closing-entry-table .daily-actual .actual-input {
    display: block; width: min(100%, 13rem); margin-inline: auto;
    text-align: center;
  }
}
</style>
<div class="max-w-6xl mx-auto">
<div class="mb-6">
    <h2 class="page-heading">{{ auth()->user()->isInventoryStaff() ? 'Daily Closing Inventory' : 'Daily Inventory Review' }}</h2>
    <p class="page-subheading mt-2">{{ auth()->user()->isInventoryStaff() ? 'Enter the physical stock remaining. Submit once at closing; the submitted record is locked.' : 'Read-only closing inventory submitted by Inventory Staff.' }}</p>
    <div class="no-print flex flex-wrap items-center gap-3 mt-5">
        @if(!auth()->user()->isInventoryStaff())
        <a class="ui-btn ui-btn-secondary inline-flex items-center justify-center gap-2" href="{{ route('daily-counts.requests.index') }}">
            Requests
            @if(auth()->user()->isStoreManager() && $dailyCountPendingRequests > 0)
                <span class="inline-flex items-center justify-center rounded-full bg-red-600 text-white text-xs font-bold min-w-[1.5rem] h-6 px-1.5">{{ $dailyCountPendingRequests > 99 ? '99+' : $dailyCountPendingRequests }}</span>
            @endif
        </a>
        @endif
        <a href="{{ route('daily-counts.comparison') }}" class="ui-btn ui-btn-primary">Compare Inventory</a>
    </div>
</div>
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
  @include('daily-counts._notice',['dailyCount'=>$todayCount])
  <div class="ui-card p-5 mb-5 flex flex-wrap items-center justify-between gap-3"><div><h3 class="text-xl font-bold text-green-800">✓ Today's count is complete</h3><p class="text-slate-600">Submitted {{ $todayCount->submitted_at?->format('g:i A') }} by {{ $todayCount->user->name ?? 'Staff' }}. This record is locked.</p></div><a href="{{ route('daily-counts.show',$todayCount) }}" class="ui-btn ui-btn-primary">View Count</a></div>
 @else
  <form method="POST" action="{{ route('daily-counts.store') }}" @if($items->count() > 10) novalidate @endif>@csrf
  <div class="ui-card overflow-hidden"><div class="daily-count-list closing-entry-table"><div class="daily-count-head"><span>Item</span><span>Unit</span><span>Start of Day</span><span>End of Day</span><span>Variance</span></div>
  @foreach($items as $item)<div class="daily-count-row" data-row data-expected="{{ $item->stock_qty }}"><div class="daily-count-item"><div class="font-semibold">{{ $item->name }}</div><div class="text-xs text-slate-500">{{ $item->category }}</div></div><div class="daily-count-field daily-unit"><span class="daily-mobile-label">Unit</span><span>{{ $item->unit }}</span></div><div class="daily-count-field daily-expected"><span class="daily-mobile-label">Start of Day</span><strong>{{ number_format($item->stock_qty,0) }}</strong></div><div class="daily-count-field daily-actual"><label class="daily-mobile-label" for="actual-{{ $item->id }}">End of Day</label><input id="actual-{{ $item->id }}" name="actual[{{ $item->id }}]" inputmode="numeric" type="number" min="0" step="1" required value="{{ old('actual.'.$item->id) }}" class="actual-input ui-input text-lg font-semibold" placeholder="Enter count"></div><div class="daily-count-field daily-variance"><span class="daily-mobile-label">Variance</span><strong class="variance">—</strong></div></div>@endforeach</div>
  <div id="count-pagination" class="flex flex-wrap justify-center items-center gap-2 p-3" aria-label="Daily count pages"></div>
  <div class="p-5 bg-slate-50"><label class="ui-label">Closing notes (optional)</label><textarea name="notes" rows="2" class="ui-input" placeholder="Optional note about unusual stock changes">{{ old('notes') }}</textarea><button class="mt-4 w-full sm:w-auto ui-btn ui-btn-primary text-lg">Submit Closing Inventory</button></div></div></form>
 @endif
@endif
<div class="mt-6 ui-card p-5 no-print">
  <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
    <h3 class="text-xl font-bold">Recent Closing Counts</h3>
    <form method="GET" action="{{ route('daily-counts.index') }}" class="flex flex-wrap items-center gap-2">
      @if(request()->filled('date'))<input type="hidden" name="date" value="{{ request('date') }}">@endif
      <label for="history-status" class="font-semibold text-sm text-slate-700">Status</label>
      <select id="history-status" name="history_status" class="ui-input" onchange="this.form.submit()" aria-label="Filter closing counts by status">
        <option value="all" @selected(request('history_status', 'all') === 'all')>All</option>
        <option value="submitted" @selected(request('history_status') === 'submitted')>Submitted</option>
        <option value="edited" @selected(request('history_status') === 'edited')>Edited</option>
        <option value="canceled" @selected(request('history_status') === 'canceled')>Canceled</option>
      </select>
      <noscript><button type="submit" class="ui-btn ui-btn-secondary">Filter</button></noscript>
    </form>
  </div>
  @if($recentCounts->isNotEmpty())
  <div class="grid md:grid-cols-2 gap-3">@foreach($recentCounts as $count)<a class="tap flex justify-between rounded-xl border p-4 hover:bg-green-50" href="{{ route('daily-counts.show',$count) }}"><span><strong>{{ $count->count_date->format('M d, Y') }}</strong><br><span class="text-sm text-slate-500">{{ $count->user->name ?? 'Staff' }}</span></span><span class="self-center">{{ $count->items_count }} items @if($count->review_status==='canceled') · Canceled @elseif($count->was_corrected) · Edited @endif →</span></a>@endforeach</div><div class="mt-4">{{ $recentCounts->onEachSide(1)->links() }}</div>
  @else
    <div class="rounded-xl border border-slate-200 p-6 text-center text-slate-500">No closing counts found for this status.</div>
  @endif
</div>
</div>
@if(auth()->user()->isInventoryStaff())
<script>
(() => {
    const rows = Array.from(document.querySelectorAll('[data-row]'));
    const pagination = document.getElementById('count-pagination');
    const form = pagination?.closest('form');
    if (!pagination || !form || rows.length <= 10) return;
    let page = 0;
    const pages = Math.ceil(rows.length / 10);
    const render = (target) => {
        page = target;
        rows.forEach((row, i) => { row.style.display = Math.floor(i / 10) === page ? '' : 'none'; });
        pagination.replaceChildren();
        const addButton = (label, targetPage, active) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.className = 'ui-btn ' + (active ? 'ui-btn-primary' : 'ui-btn-secondary');
            button.style.cssText = 'min-width:40px;min-height:36px;padding:6px 10px';
            button.addEventListener('click', () => render(targetPage));
            pagination.appendChild(button);
        };
        for (let i = 0; i < pages; i++) addButton(String(i + 1), i, i === page);
    };
    form.addEventListener('submit', (event) => {
        const missing = rows.find(row => {
            const input = row.querySelector('.actual-input');
            return input && (input.value.trim() === '' || !input.checkValidity());
        });
        if (missing) {
            event.preventDefault();
            render(Math.floor(rows.indexOf(missing) / 10));
            missing.querySelector('.actual-input')?.focus();
        }
    });
    render(0);
})();
</script>
<script>document.querySelectorAll('[data-row]').forEach(row=>{const input=row.querySelector('.actual-input'),out=row.querySelector('.variance'),expected=parseFloat(row.dataset.expected);input.addEventListener('input',()=>{if(input.value===''){out.textContent='—';return}const v=parseFloat(input.value)-expected;out.textContent=Math.abs(Math.round(v)).toString();out.className='variance '+(v<0?'text-red-600':v>0?'text-amber-600':'text-green-700')})});</script>@endif
@endsection
