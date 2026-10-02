<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Clear operational/history data first so inventory items can be removed safely.
            foreach ([
                'receiving_items', 'receiving', 'purchase_items', 'purchases',
                'daily_stock_count_items', 'daily_stock_counts', 'stock_transactions',
                'order_detail_addons', 'order_details', 'orders',
                'add_on_recipes', 'product_recipes',
            ] as $table) {
                if (Schema::hasTable($table)) DB::table($table)->delete();
            }

            // The live system starts with no stock master data. KOFI BOSS staff/manager
            // will encode the branch's actual inventory items after handover.
            if (Schema::hasTable('inventory_items')) DB::table('inventory_items')->delete();
        });
    }

    public function down(): void
    {
        // Intentionally irreversible: deleted demo/process data must not be restored.
    }
};
