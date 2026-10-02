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
        <form method="GET" class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search inventory..." class="ui-input pl-4" aria-label="Search inventory">
            </div>
            <button type="submit" class="ui-btn ui-btn-primary tap sm:min-w-28">Search</button>
            @if(request('search'))
                <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary tap">Clear</a>
            @endif
        </form>
    </div>

    <div class="tablet-only space-y-3">
        @forelse($items as $item)
            <div class="ui-card p-4">
                <div class="flex justify-between gap-3">
                    <div><div class="font-bold text-lg">{{ $item->name }}</div><div class="text-sm text-slate-500">{{ $item->category }}</div></div>
                    <span class="status-pill {{ $item->status === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ str_replace('_',' ',ucfirst($item->status)) }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div><div class="text-xs uppercase font-bold text-slate-500">Current Stock</div><div class="text-2xl font-extrabold">{{ $item->stock_qty }} <span class="text-sm font-semibold">{{ $item->unit }}</span></div></div>
                    @if($canManage)
                        <div class="flex items-end justify-end gap-2">
                            <a href="{{ route('inventory.edit',$item) }}" class="ui-btn ui-btn-secondary">Edit</a>
                            <form method="POST" action="{{ route('inventory.destroy',$item) }}" onsubmit="return confirm('Delete {{ addslashes($item->name) }}? This is only allowed if the item has no inventory history.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui-btn ui-btn-secondary text-red-700">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="ui-card empty-state">Inventory is empty. Staff or Manager can add the branch's actual stock items.</div>
        @endforelse
    </div>

    <div class="desktop-tablet-landscape ui-card data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit</th><th>Status</th>@if($canManage)<th>Action</th>@endif</tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td class="font-semibold">{{ $item->name }}</td><td class="text-stone-600">{{ $item->category }}</td><td class="font-bold">{{ $item->stock_qty }}</td><td class="text-stone-600">{{ $item->unit }}</td>
                        <td><span class="status-pill {{ $item->status === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ str_replace('_', ' ', ucfirst($item->status)) }}</span></td>
                        @if($canManage)
                            <td><div class="flex gap-2">
                                <a href="{{ route('inventory.edit', $item) }}" class="ui-btn ui-btn-secondary tap min-h-10 py-2 px-3 text-sm">Edit</a>
                                <form method="POST" action="{{ route('inventory.destroy',$item) }}" onsubmit="return confirm('Delete {{ addslashes($item->name) }}? This is only allowed if the item has no inventory history.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui-btn ui-btn-secondary tap min-h-10 py-2 px-3 text-sm text-red-700">Delete</button>
                                </form>
                            </div></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="empty-state">Inventory is empty. Staff or Manager can add the branch's actual stock items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
@endsection
