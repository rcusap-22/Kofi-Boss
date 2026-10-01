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
            $table->decimal('stock_qty', 12, 3)->default(0)->change();
            $table->decimal('min_stock', 12, 3)->default(0)->change();
        });

        $formats = [
            'Coffee Beans'=>['bag','1 kg bag',1000], 'Fresh Milk'=>['carton','1 L carton',1000],
            'Condensed Milk'=>['can','390 g can',390], 'Caramel Syrup'=>['bottle','750 ml bottle',750],
            'Chocolate Syrup'=>['bottle','750 ml bottle',750], 'Matcha Powder'=>['pouch','500 g pouch',500],
            'Strawberry Syrup'=>['bottle','750 ml bottle',750], 'Green Apple Syrup'=>['bottle','750 ml bottle',750],
            'Blueberry Syrup'=>['bottle','750 ml bottle',750], 'Mango Syrup'=>['bottle','750 ml bottle',750],
            'Passion Fruit Syrup'=>['bottle','750 ml bottle',750], 'Simple Syrup'=>['bottle','750 ml bottle',750],
            'Frappe Powder / Base'=>['bag','1 kg bag',1000], 'Soda Water'=>['bottle','1.5 L bottle',1500],
            'Ice'=>['bag','5 kg bag',5000],
            '16 oz Cup'=>['pcs','individual cup',1], '22 oz Cup'=>['pcs','individual cup',1],
            '16 oz Lid'=>['pcs','individual lid',1], '22 oz Lid'=>['pcs','individual lid',1],
        ];

        foreach ($formats as $name => [$unit,$description,$divisor]) {
            $row = DB::table('inventory_items')->where('name',$name)->first();
            if (!$row) continue;
            // Convert only legacy base-unit records. This keeps the migration safe if data was already practical.
            if (in_array(strtolower((string)$row->unit), ['g','kg','ml','l','liter','litre'])) {
                DB::table('inventory_items')->where('id',$row->id)->update([
                    'stock_qty'=>round(((float)$row->stock_qty)/$divisor,3),
                    'min_stock'=>round(((float)$row->min_stock)/$divisor,3),
                    'unit'=>$unit, 'purchase_unit'=>$description, 'purchase_unit_size'=>1,
                ]);
            } else {
                DB::table('inventory_items')->where('id',$row->id)->update([
                    'unit'=>$unit, 'purchase_unit'=>$description, 'purchase_unit_size'=>1,
                ]);
            }
        }

        // Existing open purchase lines are package quantities already. From now on one received
        // purchase quantity equals one practical inventory unit; no ml/g conversion is performed.
        DB::table('purchase_items')->update(['package_size'=>1]);
    }

    public function down(): void
    {
        // Intentionally does not recreate ml/g inventory quantities. Rolling back would risk
        // corrupting physical counts entered after this migration.
    }
};
