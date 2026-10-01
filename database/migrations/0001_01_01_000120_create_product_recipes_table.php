<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('size_id')
                ->constrained('sizes')
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('inventory_items')
                ->cascadeOnDelete();

            $table->decimal('qty_per_unit', 10, 2);

            $table->timestamps();

            $table->unique(
                ['product_id', 'size_id', 'item_id'],
                'product_recipe_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};