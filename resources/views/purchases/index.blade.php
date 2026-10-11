
@extends('layouts.app')
@section('title', 'Purchases')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="page-heading">Purchases</h2>
        <p class="page-subheading">
            Request, approve and receive restock items without duplicate stock entry.
        </p>
    </div>

    @if(auth()->user()->isInventoryStaff() || auth()->user()->isStoreManager())
        <a href="{{ route('purchases.create') }}"
           class="ui-btn ui-btn-primary tap w-full sm:w-auto">
            ＋ New Request
        </a>
    @endif
</div>

{{-- Compact Purchase Status Filter --}}
<form method="GET"
      action="{{ route('purchases.index') }}"
      class="mb-5 flex items-center gap-3">

    <label for="purchase-status-filter"
           class="text-sm font-semibold text-white">
        Status
    </label>

    <select
        id="purchase-status-filter"
        name="status"
        onchange="this.form.submit()"
        class="min-h-11 rounded-xl bg-white text-emerald-950 border border-amber-200 px-3 cursor-pointer"
        style="width: 190px; max-width: 190px; flex: 0 0 190px;"
        aria-label="Filter purchases by status"
    >
        @foreach([
            'pending' => 'Pending',
            'approved' => 'Approved',
            'partial' => 'Partially Received',
            'received' => 'Received',
            'rejected' => 'Rejected',
            'all' => 'Show All'
        ] as $key => $label)
            <option value="{{ $key }}" @selected($status === $key)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</form>

{{-- Tablet / Mobile View --}}
<div class="tablet-only space-y-3">
    @forelse($purchases as $p)
        @php
            $received = $p->receiving
                ->flatMap->items
                ->groupBy('item_id')
                ->map(fn($r) => $r->sum('qty_received'));

            $hasReceiving = $received->sum() > 0;

            $displayStatus = $p->status === 'completed'
                ? 'Received'
                : ($p->status === 'approved' && $hasReceiving
                    ? 'Partially Received'
                    : ucfirst($p->status));
        @endphp

        <article class="ui-card p-4">
            <div class="flex justify-between gap-3">
                <div>
                    <div class="font-bold text-lg">
                        {{ $p->supplier->name }}
                    </div>

                    <div class="text-sm text-slate-500">
                        {{ $p->order_date->format('M j, Y') }}
                        · Requested by {{ $p->requestedBy->name }}
                    </div>
                </div>

                <span class="status-pill {{
                    $p->status === 'pending'
                        ? 'bg-yellow-50 text-yellow-700'
                        : ($p->status === 'approved'
                            ? 'bg-blue-50 text-blue-700'
                            : ($p->status === 'completed'
                                ? 'bg-green-50 text-green-700'
                                : 'bg-red-50 text-red-700'))
                }}">
                    {{ $displayStatus }}
                </span>
            </div>

            <div class="mt-4 rounded-xl bg-stone-50 p-3 space-y-2">
                @foreach($p->items as $line)
                    @php
                        $got = (int) ($received[$line->item_id] ?? 0);
                    @endphp

                    <div class="flex justify-between gap-4 text-sm">
                        <strong>{{ $line->item->name }}</strong>

                        <span class="text-right">
                            {{ $line->quantity }}
                            {{ $line->package_unit ?: $line->item->unit }}

                            @if($got > 0)
                                <span class="text-stone-500">
                                    · {{ $got }}/{{ $line->quantity }} received
                                </span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>

            @if($p->status === 'rejected' && $p->rejection_reason)
                <div class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                    <strong>Reason:</strong>
                    {{ $p->rejection_reason }}
                </div>
            @endif

            {{-- Staff: Edit / Delete Own Pending Requests --}}
            @if($p->status === 'pending'
                && auth()->user()->isInventoryStaff()
                && (int) $p->requested_by === (int) auth()->id())

                <div class="flex items-center justify-center gap-3 mt-4">
                    <a href="{{ route('purchases.edit', $p) }}"
                       class="ui-btn ui-btn-secondary text-sm text-center"
                       style="width: 96px; min-height: 36px; padding: 6px 12px;">
                        Edit
                    </a>

                    <form method="POST"
                          action="{{ route('purchases.destroy', $p) }}"
                          class="flex justify-center">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="ui-btn ui-btn-danger text-sm"
                                style="width: 96px; min-height: 36px; padding: 6px 12px;"
                                data-confirm="Delete this pending purchase request?">
                            Delete
                        </button>
                    </form>
                </div>

            {{-- Manager: Approve / Reject --}}
            @elseif($p->status === 'pending' && auth()->user()->isStoreManager())

                <div class="grid grid-cols-2 gap-2 mt-4">
                    <form method="POST" action="{{ route('purchases.approve', $p) }}">
                        @csrf

                        <button class="ui-btn ui-btn-primary w-full min-h-12">
                            Approve
                        </button>
                    </form>

                    <button
                        type="button"
                        class="ui-btn ui-btn-danger w-full min-h-12"
                        onclick="document.getElementById('reject-{{ $p->id }}').classList.remove('hidden')">
                        Reject
                    </button>
                </div>

                <div id="reject-{{ $p->id }}"
                     class="hidden mt-3 rounded-xl border border-red-200 bg-red-50 p-3">

                    <form method="POST" action="{{ route('purchases.reject', $p) }}">
                        @csrf

                        <label class="ui-label">
                            Reason for rejection
                        </label>

                        <textarea
                            name="rejection_reason"
                            required
                            maxlength="500"
                            rows="2"
                            class="ui-input"
                            placeholder="e.g. Quantity too high or item not needed yet"
                        ></textarea>

                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button
                                type="button"
                                class="ui-btn ui-btn-secondary"
                                onclick="document.getElementById('reject-{{ $p->id }}').classList.add('hidden')">
                                Cancel
                            </button>

                            <button class="ui-btn ui-btn-danger">
                                Confirm Reject
                            </button>
                        </div>
                    </form>
                </div>

            {{-- Staff: Receive Approved Requests --}}
            @elseif($p->status === 'approved' && auth()->user()->isInventoryStaff())

                <a href="{{ route('receiving.create', $p) }}"
                   class="ui-btn ui-btn-primary w-full mt-4 min-h-12">
                    {{ $hasReceiving ? 'Receive Remaining Items' : 'Receive Delivery' }}
                </a>
            @endif
        </article>

    @empty
        <div class="ui-card empty-state">
            No purchase requests match this filter.
        </div>
    @endforelse
</div>

{{-- Desktop / Tablet Landscape View --}}
<div class="desktop-tablet-landscape ui-card data-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Supplier</th>
                <th>Items Requested</th>
                <th>Requested By</th>
                <th>Status</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($purchases as $p)
                @php
                    $received = $p->receiving
                        ->flatMap->items
                        ->groupBy('item_id')
                        ->map(fn($r) => $r->sum('qty_received'));

                    $hasReceiving = $received->sum() > 0;

                    $displayStatus = $p->status === 'completed'
                        ? 'Received'
                        : ($p->status === 'approved' && $hasReceiving
                            ? 'Partially Received'
                            : ucfirst($p->status));
                @endphp

                <tr>
                    <td class="whitespace-nowrap">
                        {{ $p->order_date->format('M j, Y') }}
                    </td>

                    <td class="font-semibold">
                        {{ $p->supplier->name }}
                    </td>

                    <td>
                        <div class="space-y-1">
                            @foreach($p->items as $line)
                                <div>
                                    <strong>{{ $line->item->name }}</strong>
                                    —
                                    {{ $line->quantity }}
                                    {{ $line->package_unit ?: $line->item->unit }}

                                    @php
                                        $got = (int) ($received[$line->item_id] ?? 0);
                                    @endphp

                                    @if($got > 0)
                                        <span class="text-xs text-stone-500">
                                            ({{ $got }}/{{ $line->quantity }} received)
                                        </span>
                                    @endif
                                </div>
                            @endforeach

                            @if($p->status === 'rejected' && $p->rejection_reason)
                                <div class="text-xs text-red-700 mt-2">
                                    <strong>Rejected:</strong>
                                    {{ $p->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    </td>

                    <td>
                        {{ $p->requestedBy->name }}
                    </td>

                    <td>
                        <span class="status-pill {{
                            $p->status === 'pending'
                                ? 'bg-yellow-50 text-yellow-700'
                                : ($p->status === 'approved'
                                    ? 'bg-blue-50 text-blue-700'
                                    : ($p->status === 'completed'
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-red-50 text-red-700'))
                        }}">
                            {{ $displayStatus }}
                        </span>
                    </td>

                    <td class="text-center">
                        {{-- Staff: Small Centered Edit / Delete Buttons --}}
                        @if($p->status === 'pending'
                            && auth()->user()->isInventoryStaff()
                            && (int) $p->requested_by === (int) auth()->id())

                            <div class="flex flex-col items-center justify-center gap-2">
                                <a href="{{ route('purchases.edit', $p) }}"
                                   class="ui-btn ui-btn-secondary text-sm text-center"
                                   style="width: 96px; min-height: 36px; padding: 6px 12px;">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="{{ route('purchases.destroy', $p) }}"
                                      class="flex justify-center">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="ui-btn ui-btn-danger text-sm"
                                            style="width: 96px; min-height: 36px; padding: 6px 12px;"
                                            data-confirm="Delete this pending purchase request?">
                                        Delete
                                    </button>
                                </form>
                            </div>

                        {{-- Manager: Approve / Reject --}}
                        @elseif($p->status === 'pending' && auth()->user()->isStoreManager())

                            <div class="flex flex-col items-center justify-center gap-2">
                                <form method="POST" action="{{ route('purchases.approve', $p) }}">
                                    @csrf

                                    <button class="ui-btn ui-btn-primary text-sm"
                                            style="width: 96px; min-height: 36px; padding: 6px 12px;">
                                        Approve
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    class="ui-btn ui-btn-danger text-sm"
                                    style="width: 96px; min-height: 36px; padding: 6px 12px;"
                                    onclick="document.getElementById('reject-d-{{ $p->id }}').classList.toggle('hidden')">
                                    Reject
                                </button>

                                <div id="reject-d-{{ $p->id }}" class="hidden w-full">
                                    <form method="POST" action="{{ route('purchases.reject', $p) }}">
                                        @csrf

                                        <textarea
                                            name="rejection_reason"
                                            required
                                            rows="2"
                                            class="ui-input text-sm"
                                            placeholder="Reason"
                                        ></textarea>

                                        <button class="ui-btn ui-btn-danger w-full mt-1 text-sm">
                                            Confirm
                                        </button>
                                    </form>
                                </div>
                            </div>

                        {{-- Staff: Receive Delivery --}}
                        @elseif($p->status === 'approved' && auth()->user()->isInventoryStaff())

                            <a href="{{ route('receiving.create', $p) }}"
                               class="ui-btn ui-btn-primary min-h-10 px-3 text-sm">
                                {{ $hasReceiving ? 'Receive Remaining' : 'Receive Delivery' }}
                            </a>

                        @else
                            <span class="text-stone-400">
                                {{ $p->status === 'approved' && auth()->user()->isStoreManager()
                                    ? 'Waiting for staff delivery'
                                    : '—' }}
                            </span>
                        @endif
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        No purchase requests match this filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $purchases->links() }}
</div>
@endsection
