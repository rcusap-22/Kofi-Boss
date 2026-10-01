@extends('layouts.app')
@section('title', 'New Purchase Request')

@section('content')

    <div class="mb-6">
        <h2 class="page-heading">New Purchase Request</h2>
        <p class="page-subheading">
            Prepare a restock request for manager approval.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('purchases.store') }}"
        class="max-w-4xl"
        id="purchaseForm"
    >
        @csrf

        {{-- PURCHASE INFORMATION --}}
        <section class="ui-card p-5 sm:p-6">

            <div class="grid md:grid-cols-2 gap-5">

                <div>
                    <label class="ui-label">
                        Supplier
                    </label>

                    <select
                        name="supplier_id"
                        required
                        class="ui-input"
                    >
                        <option value="">
                            Select supplier
                        </option>

                        @foreach($suppliers as $s)
                            <option
                                value="{{ $s->id }}"
                                @selected(old('supplier_id', $preselectedSupplierId ?? null) == $s->id)
                            >
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="ui-label">
                        Order Date
                    </label>

                    <input
                        type="date"
                        name="order_date"
                        value="{{ old('order_date', now()->format('Y-m-d')) }}"
                        required
                        class="ui-input"
                    >
                </div>

            </div>

        </section>


        {{-- REQUESTED ITEMS --}}
        <section class="ui-card mt-5 p-5 sm:p-6">

            <div
                class="
                    flex
                    items-end
                    justify-between
                    gap-3
                    mb-4
                "
            >

                <div>
                    <h3 class="font-bold text-lg">
                        Requested Items
                    </h3>

                    <p class="text-sm text-stone-500">
                        Choose normal supplier packs (cartons, bottles, bags or packs), not raw ml/g amounts.
                    </p>
                </div>


                <button
                    type="button"
                    id="addPurchaseRow"
                    class="
                        ui-btn
                        ui-btn-secondary
                        tap
                        shrink-0
                    "
                >
                    ＋ Add Item
                </button>

            </div>


            <div
                id="purchaseRows"
                class="space-y-3"
            ></div>

        </section>


        {{-- NOTES --}}
        <section class="ui-card mt-5 p-5 sm:p-6">

            <label class="ui-label">
                Notes

                <span class="font-normal text-stone-400">
                    (optional)
                </span>
            </label>

            <textarea
                name="notes"
                rows="3"
                class="ui-input"
                placeholder="Add delivery or purchasing notes..."
            >{{ old('notes') }}</textarea>

        </section>


        {{-- ACTIONS --}}
        <div
            class="
                mt-5
                flex
                flex-col-reverse
                sm:flex-row
                sm:justify-end
                gap-3
            "
        >

            <a
                href="{{ route('purchases.index') }}"
                class="
                    ui-btn
                    ui-btn-secondary
                    tap
                    w-full
                    sm:w-auto
                "
            >
                Cancel
            </a>


            <button
                type="submit"
                class="
                    ui-btn
                    ui-btn-primary
                    tap
                    w-full
                    sm:w-auto
                    min-h-14
                    px-8
                "
            >
                Submit Request
            </button>

        </div>

    </form>


    {{-- REQUESTED ITEM TEMPLATE --}}
    <template id="purchaseRowTemplate">

        <div
            class="
                purchase-row
                rounded-xl
                border-2
                border-stone-200
                bg-stone-50
                p-4
            "
        >

            <div
                class="
                    flex
                    items-center
                    justify-between
                    gap-3
                    mb-3
                "
            >

                <span class="font-bold">
                    Item
                    <span class="purchase-number"></span>
                </span>


                <button
                    type="button"
                    class="
                        remove-purchase
                        ui-btn
                        ui-btn-danger
                        tap
                        min-h-10
                        px-3
                        text-sm
                    "
                >
                    Remove
                </button>

            </div>


            <div
                class="
                    grid
                    md:grid-cols-[minmax(0,2fr)_1fr]
                    gap-3
                "
            >

                {{-- ITEM --}}
                <select
                    name="items[__i__][item_id]"
                    required
                    class="ui-input"
                >

                    <option value="">
                        Select item
                    </option>

                    @foreach($items as $i)

                        <option value="{{ $i->id }}" data-pack="{{ $i->unit }}" data-size="1" data-base="{{ $i->unit }}" @selected(($preselectedItemId ?? 0) == $i->id)>
                            {{ $i->name }} — {{ $i->unit }}@if($i->purchase_unit) ({{ $i->purchase_unit }})@endif
                        </option>

                    @endforeach

                </select>


                {{-- QUANTITY --}}
                <input
                    type="number"
                    name="items[__i__][quantity]"
                    placeholder="Quantity"
                    min="1"
                    required
                    inputmode="numeric"
                    class="ui-input purchase-quantity"
                >
                <div class="purchase-pack-hint md:col-span-2 text-sm text-stone-500 -mt-1"></div>

            </div>

        </div>

    </template>


    <script>

        (() => {

            let index = 0;

            const rows =
                document.getElementById('purchaseRows');

            const template =
                document.getElementById('purchaseRowTemplate');


            function renumberRows() {

                rows
                    .querySelectorAll('.purchase-number')
                    .forEach((element, i) => {

                        element.textContent =
                            `#${i + 1}`;

                    });

            }


            function addRow() {

                const fragment =
                    template.content.cloneNode(true);


                fragment
                    .querySelectorAll('[name*="__i__"]')
                    .forEach(element => {

                        element.name =
                            element.name.replaceAll(
                                '__i__',
                                index
                            );

                    });


                fragment
                    .querySelector('.purchase-number')
                    .textContent =
                    `#${index + 1}`;


                fragment
                    .querySelector('.remove-purchase')
                    .addEventListener(
                        'click',
                        event => {

                            const rowCount =
                                rows.querySelectorAll(
                                    '.purchase-row'
                                ).length;


                            if (rowCount === 1) {
                                return;
                            }


                            event
                                .currentTarget
                                .closest('.purchase-row')
                                .remove();


                            renumberRows();

                        }
                    );


                const select = fragment.querySelector('select[name*="[item_id]"]');
                const hint = fragment.querySelector('.purchase-pack-hint');
                const updateHint = () => {
                    const option = select.options[select.selectedIndex];
                    if (!option || !option.value) { hint.textContent = ''; return; }
                    hint.textContent = `Order by ${option.dataset.pack}. Each adds ${Number(option.dataset.size).toLocaleString()} ${option.dataset.base} to inventory when received.`;
                };
                select.addEventListener('change', updateHint);
                updateHint();

                rows.appendChild(fragment);

                index++;

            }


            document
                .getElementById('addPurchaseRow')
                .addEventListener(
                    'click',
                    addRow
                );


            addRow();

        })();

    </script>

@endsection