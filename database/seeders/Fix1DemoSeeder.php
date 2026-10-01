<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Size;
use Illuminate\Database\Seeder;

// Run this AFTER your existing DatabaseSeeder (which creates the inventory items this
// seeder's recipes reference). Run with: php artisan db:seed --class=Fix1DemoSeeder
class Fix1DemoSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            '16oz' => Size::create(['name' => '16oz']),
            '24oz' => Size::create(['name' => '24oz']),
            '32oz' => Size::create(['name' => '32oz']),
        ];

        AddOn::insert([
            ['name' => 'Extra Shot', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pearls', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Extra Syrup', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Pull the ingredient items seeded earlier by DatabaseSeeder
        $beans = InventoryItem::where('name', 'Arabica Coffee Beans')->firstOrFail();
        $milk = InventoryItem::where('name', 'Milk')->firstOrFail();
        $choco = InventoryItem::where('name', 'Chocolate Powder')->firstOrFail();
        $cups = InventoryItem::where('name', 'Cups (16oz)')->firstOrFail();

        $coffee = Product::create(['name' => 'Kofi Classic', 'category' => 'Coffee', 'status' => 'active']);
        $chocolate = Product::create(['name' => 'Chocolate Delight', 'category' => 'Chocolate', 'status' => 'active']);

        // Recipe: Kofi Classic — coffee beans (kg) + milk (L) + 1 cup, scaling up with size
        $coffeeRecipe = [
            '16oz' => ['beans' => 0.020, 'milk' => 0.150],
            '24oz' => ['beans' => 0.025, 'milk' => 0.200],
            '32oz' => ['beans' => 0.030, 'milk' => 0.250],
        ];
        foreach ($coffeeRecipe as $sizeName => $amounts) {
            ProductRecipe::create(['product_id' => $coffee->id, 'size_id' => $sizes[$sizeName]->id, 'item_id' => $beans->id, 'qty_per_unit' => $amounts['beans']]);
            ProductRecipe::create(['product_id' => $coffee->id, 'size_id' => $sizes[$sizeName]->id, 'item_id' => $milk->id, 'qty_per_unit' => $amounts['milk']]);
            ProductRecipe::create(['product_id' => $coffee->id, 'size_id' => $sizes[$sizeName]->id, 'item_id' => $cups->id, 'qty_per_unit' => 1]);
        }

        // Recipe: Chocolate Delight — chocolate powder (kg) + milk (L) + 1 cup
        $chocoRecipe = [
            '16oz' => ['choco' => 0.030, 'milk' => 0.150],
            '24oz' => ['choco' => 0.040, 'milk' => 0.200],
            '32oz' => ['choco' => 0.050, 'milk' => 0.250],
        ];
        foreach ($chocoRecipe as $sizeName => $amounts) {
            ProductRecipe::create(['product_id' => $chocolate->id, 'size_id' => $sizes[$sizeName]->id, 'item_id' => $choco->id, 'qty_per_unit' => $amounts['choco']]);
            ProductRecipe::create(['product_id' => $chocolate->id, 'size_id' => $sizes[$sizeName]->id, 'item_id' => $milk->id, 'qty_per_unit' => $amounts['milk']]);
            ProductRecipe::create(['product_id' => $chocolate->id, 'size_id' => $sizes[$sizeName]->id, 'item_id' => $cups->id, 'qty_per_unit' => 1]);
        }
    }
}
