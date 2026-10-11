@php
    $countPage = max(1, (int) request()->query('count_page', 1));
    $countRows = $dailyCount->items;
    $countTotal = $countRows->count();
    $countPages = max(1, (int) ceil($countTotal / 10));
    $countPage = min($countPage, $countPages);
    $visibleCountRows = $countRows->slice(($countPage - 1) * 10, 10);
@endphp
<style>
    /* Submitted inventory only: align each value with its heading. */
    @media (min-width: 768px) {
        .submitted-inventory .record-head > span:not(:first-child),
        .submitted-inventory .record-row > div:not(:first-child) {
            text-align: center;
        }
    }
</style>

@include('daily-counts._notice',['dailyCount'=>$dailyCount])
<div class="ui-card overflow-hidden mb-5 submitted-inventory">
    <div class="p-5 bg-green-50 border-b flex flex-wrap justify-between gap-3">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-green-700">Completed Daily Inventory</div>
            <h3 class="text-2xl font-bold">{{ $dailyCount->count_date->format('F j, Y') }}</h3>
            <p class="text-slate-600">Submitted by {{ $dailyCount->user->name ?? 'Staff' }} · {{ $dailyCount->submitted_at?->format('g:i A') }}</p>
        </div>
        <span class="status-pill bg-green-100 text-green-800 self-start">✓ Read Only</span>
    </div>

    <div class="responsive-record-list">
        <div class="record-head">
            <span>Item</span>
            <span>Start of Day</span>
            <span>End of Day</span>
            <span>Variance</span>
        </div>
        @foreach($visibleCountRows as $row)
            <div class="record-row">
                <div>
                    <strong>{{ $row->item->name }}</strong>
                    <div class="text-xs text-slate-500">{{ $row->item->category }} · {{ $row->item->unit }}</div>
                </div>
                <div data-label="Start of Day">{{ number_format($row->expected_qty, 0) }}</div>
                <div data-label="End of Day"><strong>{{ number_format($row->actual_qty, 0) }}</strong></div>
                <div data-label="Variance" class="font-bold {{ $row->variance_qty == 0 ? 'text-green-700' : 'text-red-600' }}">
                    {{ number_format(abs($row->variance_qty), 0) }}
                </div>
            </div>
        @endforeach
    </div>

    @if($dailyCount->notes)
        <div class="p-5 bg-slate-50"><strong>Notes:</strong> {{ $dailyCount->notes }}</div>
    @endif
</div>

@if($countPages > 1)
    <nav aria-label="Closing inventory pages" class="flex flex-wrap items-center justify-center gap-2 mb-6">
        @for($page = 1; $page <= $countPages; $page++)
            <a href="{{ request()->fullUrlWithQuery(['count_page' => $page]) }}"
               class="ui-btn {{ $page === $countPage ? 'ui-btn-primary' : 'ui-btn-secondary' }} text-sm"
               style="min-width:40px;min-height:36px;padding:6px 10px;">
                {{ $page }}
            </a>
        @endfor
        <span class="text-sm text-white ml-2">{{ $countTotal }} items</span>
    </nav>
@endif
