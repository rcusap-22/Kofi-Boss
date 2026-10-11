@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
    @php($canManage = auth()->user()->isInventoryStaff() || auth()->user()->isStoreManager())
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="page-heading">Inventory</h2>
            <p class="page-subheading">Add and maintain the actual stock items used by KOFI BOSS.</p>
        </div>
        @if($canManage)
            <a href="{{ route('inventory.create') }}" class="ui-btn ui-btn-primary tap w-full sm:w-auto">＋ Add Item</a>
        @endif
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm font-medium">{{ $errors->first() }}</div>
    @endif

    <div class="ui-card p-3 mb-5">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search inventory..." class="ui-input flex-1" aria-label="Search inventory">
            <label for="inventory-status-filter" class="text-sm font-semibold text-stone-700">Status</label>
            <select id="inventory-status-filter" name="status" onchange="this.form.submit()"
                class="min-h-11 rounded-xl border border-amber-200 bg-white text-emerald-950 px-3 cursor-pointer"
                style="width:190px;max-width:190px;flex:0 0 190px;">
                @foreach(['all'=>'All Items','in_stock'=>'In Stock','low_stock'=>'Low Stock','out_of_stock'=>'Out of Stock','archived'=>'Archived'] as $key=>$label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="ui-btn ui-btn-primary tap">Search</button>
            @if(request()->filled('search') || $status !== 'all')
                <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary tap">Clear</a>
            @endif
        </form>
    </div>

    <div class="tablet-only space-y-3">
        @forelse($items as $item)
            <div class="ui-card p-4">
                <div class="flex justify-between gap-3">
                    <div><div class="font-bold text-lg">{{ $item->name }}</div><div class="text-sm text-slate-500">{{ $item->category }}</div></div>
                    <span class="status-pill {{ $item->status === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ $item->archived_at ? 'Archived' : str_replace('_',' ',ucfirst($item->status)) }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div><div class="text-xs uppercase font-bold text-slate-500">Current Stock</div><div class="text-2xl font-extrabold">{{ $item->stock_qty }} <span class="text-sm font-semibold">{{ $item->unit }}</span></div></div>
                    @if($canManage)
                        <div class="flex items-end justify-end gap-2 flex-wrap">
                            @if(!$item->archived_at)<a href="{{ route('inventory.edit',$item) }}" class="ui-btn ui-btn-secondary inline-flex items-center justify-center w-24 min-h-11 px-3 py-2 text-sm font-semibold whitespace-nowrap text-center">Edit</a>@endif
                            @if(!$item->archived_at)<form method="POST" action="{{ route('inventory.destroy',$item) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui-btn ui-btn-secondary text-red-700 inline-flex items-center justify-center w-24 min-h-11 px-3 py-2 text-sm font-semibold whitespace-nowrap text-center">Delete</button>
                            </form>@endif
@if(auth()->user()->isStoreManager())
<form method="POST" action="{{ $item->archived_at ? route('inventory.restore',$item) : route('inventory.archive',$item) }}">
@csrf @method('PATCH')
<button type="submit" class="ui-btn ui-btn-secondary inline-flex items-center justify-center w-24 min-h-11 px-3 py-2 text-sm font-semibold whitespace-nowrap text-center">{{ $item->archived_at ? 'Restore' : 'Archive' }}</button>
</form>
@endif
                        </div>
                    @endif
                </div>
                <a href="{{ route('inventory.history', $item) }}" class="ui-btn ui-btn-secondary inline-flex items-center justify-center mt-3 min-h-11 px-4 py-2 text-sm font-semibold">View History</a>
            </div>
        @empty
            <div class="ui-card empty-state">Inventory is empty. Staff or Manager can add the branch's actual stock items.</div>
        @endforelse
    </div>

    <div class="desktop-tablet-landscape ui-card data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit</th><th>Status</th><th>History</th>@if($canManage)<th>Action</th>@endif</tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td class="font-semibold">{{ $item->name }}</td><td class="text-stone-600">{{ $item->category }}</td><td class="font-bold">{{ $item->stock_qty }}</td><td class="text-stone-600">{{ $item->unit }}</td>
                        <td><span class="status-pill {{ $item->status === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ $item->archived_at ? 'Archived' : str_replace('_', ' ', ucfirst($item->status)) }}</span></td>
                        <td><a href="{{ route('inventory.history', $item) }}" class="ui-btn ui-btn-secondary inline-flex items-center justify-center min-h-10 px-3 py-2 text-sm font-semibold whitespace-nowrap">View History</a></td>
                        @if($canManage)
                            <td><div class="flex gap-2 flex-wrap items-center">
                                @if(!$item->archived_at)<a href="{{ route('inventory.edit', $item) }}" class="ui-btn ui-btn-secondary tap inline-flex items-center justify-center w-24 min-h-11 px-3 py-2 text-sm font-semibold whitespace-nowrap text-center">Edit</a>@endif
                                @if(!$item->archived_at)<form method="POST" action="{{ route('inventory.destroy',$item) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui-btn ui-btn-secondary tap text-red-700 inline-flex items-center justify-center w-24 min-h-11 px-3 py-2 text-sm font-semibold whitespace-nowrap text-center">Delete</button>
                                </form>@endif
@if(auth()->user()->isStoreManager())
<form method="POST" action="{{ $item->archived_at ? route('inventory.restore',$item) : route('inventory.archive',$item) }}">
@csrf @method('PATCH')
<button type="submit" class="ui-btn ui-btn-secondary inline-flex items-center justify-center w-24 min-h-11 px-3 py-2 text-sm font-semibold whitespace-nowrap text-center">{{ $item->archived_at ? 'Restore' : 'Archive' }}</button>
</form>
@endif
                            </div></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="empty-state">Inventory is empty. Staff or Manager can add the branch's actual stock items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <span class="text-sm text-white">Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} items</span>
        {{ $items->links() }}
    </div>
@endsection
