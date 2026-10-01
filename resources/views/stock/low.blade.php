@extends('layouts.app')
@section('title', 'Low Stock')
@section('content')
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="page-heading">Low Stock Alerts</h2>
            <p class="page-subheading">Items that need restocking before they run out.</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary tap w-full sm:w-auto">View Inventory</a>
    </div>

    @if($items->isEmpty())
        <div class="ui-card empty-state">
            <div class="text-4xl mb-3">✓</div>
            <div class="font-bold text-lg text-stone-800">Stock levels look good</div>
            <p class="mt-1">Nothing is below its minimum threshold right now.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($items as $item)
                @php $critical = $item->status === 'out_of_stock'; @endphp
                <div class="ui-card p-5 {{ $critical ? 'border-red-200 bg-red-50/50' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-bold text-lg truncate">{{ $item->name }}</div>
                            <div class="text-sm text-stone-500 mt-1">{{ $item->category }}</div>
                        </div>
                        <span class="status-pill shrink-0 {{ $critical ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ $critical ? 'Out of stock' : 'Low stock' }}
                        </span>
                    </div>

                    <div class="mt-5 flex items-end justify-between">
                        <div>
                            <div class="text-3xl font-extrabold {{ $critical ? 'text-red-700' : 'text-orange-700' }}">
                                {{ $item->stock_qty }} <span class="text-base font-semibold">{{ $item->unit }}</span>
                            </div>
                            <div class="text-xs text-stone-500 mt-1">Current stock</div>
                        </div>
                        <div class="text-right text-sm text-stone-500">
                            Minimum<br><strong class="text-stone-800">{{ $item->min_stock }} {{ $item->unit }}</strong>
                        </div>
                    </div>

                    @if(auth()->user()->isInventoryStaff())
                        <a href="{{ route('purchases.create', ['item' => $item->id]) }}" class="ui-btn ui-btn-primary tap w-full mt-5">Request Restock</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
