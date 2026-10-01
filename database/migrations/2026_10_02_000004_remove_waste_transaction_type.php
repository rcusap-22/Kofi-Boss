<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve any legacy records by treating them as ordinary stock-out
        // adjustments before removing the obsolete waste transaction type.
        DB::table('stock_transactions')->where('type', 'waste')->update(['type' => 'out']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_transactions MODIFY type ENUM('in','out') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_transactions MODIFY type ENUM('in','out','waste') NOT NULL");
        }
    }
};
