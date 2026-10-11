@extends('layouts.app')
@section('title', $item->exists ? 'Edit Item' : 'Add Item')
@section('content')
<div class="mb-6">
    <h2 class="page-heading">{{ $item->exists ? 'Edit Inventory Item' : 'Add Inventory Item' }}</h2>
    <p class="page-subheading">{{ $item->exists ? 'Update the item details and stock settings.' : 'Register an inventory item using its specific size, flavor, or variant.' }}</p>
</div>

<form method="POST" action="{{ $item->exists ? route('inventory.update', $item) : route('inventory.store') }}" class="ui-card p-5 sm:p-6 max-w-3xl">
    @csrf
    @if($item->exists) @method('PUT') @endif
    @php
        $categories = ['Ingredients', 'Packaging', 'Beverages', 'Cleaning Supplies', 'Others'];
        $units = ['pcs' => 'Pieces (pcs)', 'pack' => 'Packs', 'carton' => 'Cartons', 'bottle' => 'Bottles', 'can' => 'Cans', 'bag' => 'Bags', 'sack' => 'Sacks', 'tray' => 'Trays', 'box' => 'Boxes', 'pouch' => 'Pouches', 'tub' => 'Tubs'];
        $currentCategory = old('category', $item->category);
        $currentUnit = old('unit', $item->unit);
    @endphp
    <div class="grid sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <label for="item-name" class="ui-label">Item Name</label>
            <input id="item-name" type="text" name="name" value="{{ old('name', $item->name) }}" required maxlength="100" class="ui-input" placeholder="e.g. Plastic Cups 16oz">
            <p class="text-xs text-stone-500 mt-1">Use separate items for different sizes, flavors, or variants (e.g. Plastic Cups 16oz and Plastic Cups 22oz).</p>
        </div>

        <div>
            <label for="item-category" class="ui-label">Category</label>
            <select id="item-category" name="category" required class="ui-input">
                <option value="" disabled @selected(!$currentCategory)>Select category</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected($currentCategory === $category)>{{ $category }}</option>
                @endforeach
                @if($currentCategory && !in_array($currentCategory, $categories, true))
                    <option value="{{ $currentCategory }}" selected>{{ $currentCategory }} (existing)</option>
                @endif
            </select>
        </div>

        <div>
            <label for="item-unit" class="ui-label">Unit</label>
            <select id="item-unit" name="unit" required class="ui-input">
                <option value="" disabled @selected(!$currentUnit)>Select unit</option>
                @foreach($units as $value => $label)
                    <option value="{{ $value }}" @selected($currentUnit === $value)>{{ $label }}</option>
                @endforeach
                @if($currentUnit && !array_key_exists($currentUnit, $units))
                    <option value="{{ $currentUnit }}" selected>{{ $currentUnit }} (existing)</option>
                @endif
            </select>
        </div>

        <div>
            <label for="item-stock" class="ui-label">{{ $item->exists ? 'Current Stock Quantity' : 'Opening Stock Quantity' }}</label>
            <input id="item-stock" type="number" step="1" inputmode="numeric" name="stock_qty" value="{{ old('stock_qty', $item->stock_qty) }}" min="0" required class="ui-input" placeholder="e.g. 20">
        </div>

        <div>
            <label for="item-threshold" class="ui-label">Low-Stock Threshold</label>
            <input id="item-threshold" type="number" step="1" inputmode="numeric" name="min_stock" value="{{ old('min_stock', $item->min_stock) }}" min="0" required class="ui-input" placeholder="e.g. 5">
        </div>

        <div class="sm:col-span-2">
            <label for="item-supplier" class="ui-label">Supplier <span class="font-normal text-stone-400">(optional)</span></label>
            <select id="item-supplier" name="supplier_id" class="ui-input">
                <option value="">No supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-7 pt-5 border-t border-stone-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <a href="{{ route('inventory.index') }}" class="ui-btn ui-btn-secondary tap w-full sm:w-auto">Cancel</a>
        <button type="submit" class="ui-btn ui-btn-primary tap w-full sm:w-auto">{{ $item->exists ? 'Save Changes' : 'Save Item' }}</button>
    </div>
</form>
@endsection
