<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_on_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_id')->constrained('add_ons')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->decimal('qty_per_unit', 10, 3);
            $table->timestamps();

            $table->unique(['addon_id', 'item_id'], 'addon_recipe_unique_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_recipes');
    }
};
