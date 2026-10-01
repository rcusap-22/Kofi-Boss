<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove old Daily Count audit rows without changing the current inventory.
        if (Schema::hasTable('stock_transactions')) {
            DB::table('stock_transactions')
                ->where('reference', 'like', 'Daily Count #%')
                ->delete();
        }

        if (Schema::hasTable('daily_stock_count_items')) {
            DB::table('daily_stock_count_items')->delete();
        }

        if (Schema::hasTable('daily_stock_counts')) {
            DB::table('daily_stock_counts')->delete();
        }
    }

    public function down(): void
    {
        // Test history cannot be reconstructed after it has been cleared.
    }
};
