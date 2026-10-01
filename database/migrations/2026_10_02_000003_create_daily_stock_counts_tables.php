<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('daily_stock_counts', function (Blueprint $table) {
            $table->id();
            $table->date('count_date')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_stock_count_id')->constrained('daily_stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('expected_qty', 12, 3);
            $table->decimal('actual_qty', 12, 3);
            $table->decimal('variance_qty', 12, 3);
            $table->timestamps();
            $table->unique(['daily_stock_count_id', 'item_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('daily_stock_count_items');
        Schema::dropIfExists('daily_stock_counts');
    }
};
