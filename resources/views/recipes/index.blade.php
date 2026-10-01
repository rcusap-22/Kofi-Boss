@extends('layouts.app')
@section('title','Ingredients')
@section('content')
<div class="mb-6">
    <h2 class="page-heading">Ingredients</h2>
    <p class="page-subheading">Select a product card to view its ingredients and quantities.</p>
</div>

@foreach($products->groupBy('category') as $category => $group)
    <h3 class="text-xl font-bold mt-7 mb-3">{{ $category }}</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-7">
        @foreach($group as $product)
            <button type="button"
                    class="ui-card p-5 text-left hover:shadow-md hover:-translate-y-0.5 transition cursor-pointer"
                    onclick="openRecipeModal('recipe-{{ $product->id }}')">
                <div class="text-lg font-bold">{{ $product->name }}</div>
                <div class="text-sm text-stone-500 mt-1">View ingredients</div>
            </button>
        @endforeach
    </div>
@endforeach

@if($extraShot)
    <h3 class="text-xl font-bold mt-7 mb-3">Add-ons</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-7">
        <button type="button"
                class="ui-card p-5 text-left hover:shadow-md hover:-translate-y-0.5 transition cursor-pointer"
                onclick="openRecipeModal('extra-shot-recipe')">
            <div class="text-lg font-bold">{{ $extraShot->name }}</div>
            <div class="text-sm text-stone-500 mt-1">View ingredients</div>
        </button>
    </div>
@endif

@foreach($products as $product)
<div id="recipe-{{ $product->id }}" class="recipe-modal fixed inset-0 z-50 hidden items-center justify-center p-4" aria-hidden="true">
    <div class="absolute inset-0 bg-black/50" onclick="closeRecipeModal('recipe-{{ $product->id }}')"></div>
    <div class="ui-card relative z-10 w-full max-w-4xl max-h-[88vh] overflow-y-auto p-5 md:p-6">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <div class="text-sm text-stone-500">{{ $product->category }}</div>
                <h3 class="text-2xl font-bold">{{ $product->name }}</h3>
            </div>
            <button type="button"
                    class="rounded-xl border px-4 py-2 font-bold hover:bg-stone-50"
                    onclick="closeRecipeModal('recipe-{{ $product->id }}')">Close</button>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            @foreach($sizes as $size)
                <div class="rounded-xl border p-4">
                    <div class="mb-3 text-lg"><strong>{{ $size->name }}</strong></div>
                    @forelse($product->recipes->where('size_id', $size->id) as $line)
                        <div class="flex justify-between gap-4 text-sm border-b border-stone-100 py-2 last:border-b-0">
                            <span>{{ $line->item->name }}</span>
                            <strong class="whitespace-nowrap">@php $recipeUnit = str_contains(strtolower($line->item->name), 'cup') || str_contains(strtolower($line->item->name), 'lid') ? 'pc' : (str_contains(strtolower($line->item->name), 'beans') || str_contains(strtolower($line->item->name), 'powder') || strtolower($line->item->name) === 'ice' ? 'g' : 'ml'); @endphp{{ number_format((float)$line->qty_per_unit, $line->qty_per_unit == floor($line->qty_per_unit) ? 0 : 1) }} {{ $recipeUnit }}</strong>
                        </div>
                    @empty
                        <div class="text-sm text-stone-500">No recipe configured.</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

@if($extraShot)
<div id="extra-shot-recipe" class="recipe-modal fixed inset-0 z-50 hidden items-center justify-center p-4" aria-hidden="true">
    <div class="absolute inset-0 bg-black/50" onclick="closeRecipeModal('extra-shot-recipe')"></div>
    <div class="ui-card relative z-10 w-full max-w-xl max-h-[88vh] overflow-y-auto p-5 md:p-6">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <div class="text-sm text-stone-500">Add-on</div>
                <h3 class="text-2xl font-bold">{{ $extraShot->name }}</h3>
            </div>
            <button type="button" class="rounded-xl border px-4 py-2 font-bold hover:bg-stone-50" onclick="closeRecipeModal('extra-shot-recipe')">Close</button>
        </div>
        @forelse($extraShot->recipes as $line)
            <div class="flex justify-between gap-4 text-sm border-b border-stone-100 py-2 last:border-b-0">
                <span>{{ $line->item->name }}</span>
                <strong class="whitespace-nowrap">@php $recipeUnit = str_contains(strtolower($line->item->name), 'cup') || str_contains(strtolower($line->item->name), 'lid') ? 'pc' : (str_contains(strtolower($line->item->name), 'beans') || str_contains(strtolower($line->item->name), 'powder') || strtolower($line->item->name) === 'ice' ? 'g' : 'ml'); @endphp{{ number_format((float)$line->qty_per_unit, $line->qty_per_unit == floor($line->qty_per_unit) ? 0 : 1) }} {{ $recipeUnit }} per shot</strong>
            </div>
        @empty
            <div class="text-sm text-stone-500">No recipe configured.</div>
        @endforelse
    </div>
</div>
@endif

<script>
function openRecipeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeRecipeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.recipe-modal:not(.hidden)').forEach(function (modal) {
        closeRecipeModal(modal.id);
    });
});
</script>
@endsection
