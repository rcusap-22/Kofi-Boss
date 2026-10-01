<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert all existing practical-unit quantities to whole physical packages/items.
        DB::table('inventory_items')->update([
            'stock_qty' => DB::raw('ROUND(stock_qty)'),
            'min_stock' => DB::raw('ROUND(min_stock)'),
        ]);

        if (Schema::hasTable('daily_stock_count_items')) {
            DB::table('daily_stock_count_items')->update([
                'expected_qty' => DB::raw('ROUND(expected_qty)'),
                'actual_qty' => DB::raw('ROUND(actual_qty)'),
                'variance_qty' => DB::raw('ROUND(variance_qty)'),
            ]);
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedInteger('stock_qty')->default(0)->change();
            $table->unsignedInteger('min_stock')->default(0)->change();
        });

        if (Schema::hasTable('daily_stock_count_items')) {
            Schema::table('daily_stock_count_items', function (Blueprint $table) {
                $table->unsignedInteger('expected_qty')->change();
                $table->unsignedInteger('actual_qty')->change();
                $table->integer('variance_qty')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('stock_qty', 12, 3)->default(0)->change();
            $table->decimal('min_stock', 12, 3)->default(0)->change();
        });

        if (Schema::hasTable('daily_stock_count_items')) {
            Schema::table('daily_stock_count_items', function (Blueprint $table) {
                $table->decimal('expected_qty', 12, 3)->change();
                $table->decimal('actual_qty', 12, 3)->change();
                $table->decimal('variance_qty', 12, 3)->change();
            });
        }
    }
};
