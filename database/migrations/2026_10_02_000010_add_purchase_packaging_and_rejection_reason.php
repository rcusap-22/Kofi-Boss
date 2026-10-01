<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'purchase_unit')) {
                $table->string('purchase_unit', 40)->nullable()->after('unit');
            }
            if (!Schema::hasColumn('inventory_items', 'purchase_unit_size')) {
                $table->unsignedInteger('purchase_unit_size')->nullable()->after('purchase_unit');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'package_unit')) {
                $table->string('package_unit', 40)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('purchase_items', 'package_size')) {
                $table->unsignedInteger('package_size')->nullable()->after('package_unit');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }
        });

        // Practical Philippine café purchase packs. Inventory still stays in recipe-friendly
        // base units (ml/g/pcs); purchase requests use cartons, bottles, bags and packs.
        $packs = [
            'Coffee Beans' => ['bag', 1000],
            'Fresh Milk' => ['1 L carton', 1000],
            'Condensed Milk' => ['1 L bottle/pouch', 1000],
            'Caramel Syrup' => ['750 ml bottle', 750],
            'Chocolate Syrup' => ['750 ml bottle', 750],
            'Matcha Powder' => ['500 g pouch', 500],
            'Strawberry Syrup' => ['750 ml bottle', 750],
            'Green Apple Syrup' => ['750 ml bottle', 750],
            'Blueberry Syrup' => ['750 ml bottle', 750],
            'Mango Syrup' => ['750 ml bottle', 750],
            'Passion Fruit Syrup' => ['750 ml bottle', 750],
            'Simple Syrup' => ['750 ml bottle', 750],
            'Frappe Powder / Base' => ['1 kg bag', 1000],
            'Soda Water' => ['1.5 L bottle', 1500],
            'Ice' => ['5 kg bag', 5000],
            '16 oz Cup' => ['pack of 50', 50],
            '22 oz Cup' => ['pack of 50', 50],
            '16 oz Lid' => ['pack of 50', 50],
            '22 oz Lid' => ['pack of 50', 50],
        ];

        foreach ($packs as $name => [$purchaseUnit, $size]) {
            DB::table('inventory_items')->where('name', $name)->update([
                'purchase_unit' => $purchaseUnit,
                'purchase_unit_size' => $size,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'rejection_reason')) $table->dropColumn('rejection_reason');
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $cols = array_values(array_filter(['package_unit','package_size'], fn($c) => Schema::hasColumn('purchase_items', $c)));
            if ($cols) $table->dropColumn($cols);
        });
        Schema::table('inventory_items', function (Blueprint $table) {
            $cols = array_values(array_filter(['purchase_unit','purchase_unit_size'], fn($c) => Schema::hasColumn('inventory_items', $c)));
            if ($cols) $table->dropColumn($cols);
        });
    }
};
