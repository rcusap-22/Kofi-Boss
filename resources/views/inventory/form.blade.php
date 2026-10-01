@extends('layouts.app')
@section('title', $item->exists ? 'Edit Item' : 'Add Item')
@section('content')
    <div class="mb-6">
        <h2 class="page-heading">{{ $item->exists ? 'Edit Item' : 'Add Inventory Item' }}</h2>
        <p class="page-subheading">{{ $item->exists ? 'Update the item details and stock settings.' : 'Add an ingredient or supply to your inventory.' }}</p>
    </div>

    <form method="POST" action="{{ $item->exists ? route('inventory.update', $item) : route('inventory.store') }}"
          class="ui-card p-5 sm:p-6 max-w-3xl">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="grid sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="ui-label">Item Name</label>
                <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="ui-input" placeholder="e.g. Arabica Coffee Beans">
            </div>

            <div>
                <label class="ui-label">Category</label>
                <input type="text" name="category" value="{{ old('category', $item->category) }}" required class="ui-input" placeholder="e.g. Ingredients">
            </div>

            <div>
                <label class="ui-label">Unit</label>
                <select name="unit" required class="ui-input">
                    @foreach(['carton','bottle','bag','pouch','can','tub','pack','pcs'] as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', $item->unit) === $unit)>{{ ucfirst($unit) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="ui-label">Package Description</label>
                <input type="text" name="purchase_unit" value="{{ old('purchase_unit', $item->purchase_unit) }}" class="ui-input" placeholder="e.g. 1 L carton, 750 ml bottle, 1 kg bag">
                <div class="text-xs text-stone-500 mt-1">For reference only. Inventory is counted using the Unit above.</div>
            </div>
            <input type="hidden" name="purchase_unit_size" value="1">

            <div>
                <label class="ui-label">Stock Quantity</label>
                <input type="number" step="1" inputmode="numeric" name="stock_qty" value="{{ old('stock_qty', $item->stock_qty) }}" min="0" required class="ui-input">
            </div>

            <div>
                <label class="ui-label">Reorder Threshold</label>
                <input type="number" step="1" inputmode="numeric" name="min_stock" value="{{ old('min_stock', $item->min_stock) }}" min="0" required class="ui-input">
            </div>


            <div>
                <label class="ui-label">Expiry Date <span class="font-normal text-stone-400">(optional)</span></label>
                <input type="date" name="expiry_date" value="{{ old('expiry_date', optional($item->expiry_date)->format('Y-m-d')) }}" class="ui-input">
            </div>

            <div class="sm:col-span-2">
                <label class="ui-label">Supplier <span class="font-normal text-stone-400">(optional)</span></label>
                <select name="supplier_id" class="ui-input">
                    <option value="">No supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-7 pt-5 border-t border-stone-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary tap w-full sm:w-auto">Cancel</a>
            <button type="submit" class="ui-btn ui-btn-primary tap w-full sm:w-auto">Save Item</button>
        </div>
    </form>
@endsection
