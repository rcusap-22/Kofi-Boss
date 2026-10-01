<?php

namespace Database\Seeders;

use App\Models\{AddOn, AddOnRecipe, InventoryItem, Product, ProductRecipe, Size};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $inventory = [
                ['Coffee Beans','Ingredient','g'], ['Fresh Milk','Ingredient','ml'],
                ['Condensed Milk','Ingredient','ml'], ['Caramel Syrup','Ingredient','ml'],
                ['Chocolate Syrup','Ingredient','ml'], ['Matcha Powder','Ingredient','g'],
                ['Strawberry Syrup','Ingredient','ml'], ['Green Apple Syrup','Ingredient','ml'],
                ['Blueberry Syrup','Ingredient','ml'], ['Mango Syrup','Ingredient','ml'],
                ['Passion Fruit Syrup','Ingredient','ml'], ['Simple Syrup','Ingredient','ml'],
                ['Frappe Powder / Base','Ingredient','g'], ['Soda Water','Ingredient','ml'],
                ['Ice','Ingredient','g'], ['16 oz Cup','Supply','pcs'], ['22 oz Cup','Supply','pcs'],
                ['16 oz Lid','Supply','pcs'], ['22 oz Lid','Supply','pcs'],
            ];

            foreach ($inventory as [$name,$category,$unit]) {
                InventoryItem::firstOrCreate(
                    ['name' => $name],
                    ['category'=>$category,'unit'=>$unit,'stock_qty'=>0,'min_stock'=>0]
                );
            }

            $sizes = [
                'Grande Boss (16oz)' => Size::firstOrCreate(['name'=>'Grande Boss (16oz)']),
                'Big Boss (22oz)' => Size::firstOrCreate(['name'=>'Big Boss (22oz)']),
            ];

            // Working 16 oz recipes for inventory deduction. The menu board gives product names
            // and sizes, not proprietary recipe measurements. Big Boss scales consumable
            // ingredients by the exact cup-size ratio: 22 / 16 = 1.375.
            $menu = [
                ['Caramel Macchiato','Coffee Best Sellers',['Coffee Beans'=>16,'Fresh Milk'=>180,'Caramel Syrup'=>20,'Ice'=>180]],
                ['Spanish Latte','Coffee Best Sellers',['Coffee Beans'=>16,'Fresh Milk'=>160,'Condensed Milk'=>30,'Ice'=>180]],

                ['Creamy Chocolate','Delights - Non-Coffee',['Fresh Milk'=>200,'Chocolate Syrup'=>30,'Ice'=>180]],
                ['Matcha','Delights - Non-Coffee',['Fresh Milk'=>200,'Matcha Powder'=>4,'Simple Syrup'=>15,'Ice'=>180]],
                ['Strawberry Pink Boss','Delights - Non-Coffee',['Fresh Milk'=>200,'Strawberry Syrup'=>25,'Ice'=>180]],
                ['Choco Matcha','Delights - Non-Coffee',['Fresh Milk'=>180,'Matcha Powder'=>4,'Chocolate Syrup'=>20,'Ice'=>180]],
                ['Strawberry Matcha','Delights - Non-Coffee',['Fresh Milk'=>180,'Matcha Powder'=>4,'Strawberry Syrup'=>20,'Ice'=>180]],

                ['Creamy Mocha','Delights - Coffee',['Coffee Beans'=>16,'Fresh Milk'=>180,'Chocolate Syrup'=>20,'Ice'=>180]],
                ['Black Pink Boss','Delights - Coffee',['Coffee Beans'=>16,'Fresh Milk'=>180,'Strawberry Syrup'=>20,'Ice'=>180]],
                ['Dirty Matcha','Delights - Coffee',['Coffee Beans'=>16,'Fresh Milk'=>180,'Matcha Powder'=>4,'Ice'=>180]],

                ['Caramel Macchiato Frost','Frost Crush - Coffee',['Coffee Beans'=>16,'Fresh Milk'=>150,'Caramel Syrup'=>20,'Frappe Powder / Base'=>20,'Ice'=>220]],
                ['Spanish Latte Frost','Frost Crush - Coffee',['Coffee Beans'=>16,'Fresh Milk'=>140,'Condensed Milk'=>30,'Frappe Powder / Base'=>20,'Ice'=>220]],

                ['Chocolate Frost','Frost Crush - Non-Coffee',['Fresh Milk'=>170,'Chocolate Syrup'=>30,'Frappe Powder / Base'=>20,'Ice'=>220]],
                ['Strawberry Frost','Frost Crush - Non-Coffee',['Fresh Milk'=>170,'Strawberry Syrup'=>30,'Frappe Powder / Base'=>20,'Ice'=>220]],
                ['Matcha Frost','Frost Crush - Non-Coffee',['Fresh Milk'=>170,'Matcha Powder'=>5,'Frappe Powder / Base'=>20,'Ice'=>220]],

                ['Green Apple','Soda Burst',['Green Apple Syrup'=>30,'Soda Water'=>200,'Ice'=>200]],
                ['Blueberry','Soda Burst',['Blueberry Syrup'=>30,'Soda Water'=>200,'Ice'=>200]],
                ['Mango','Soda Burst',['Mango Syrup'=>30,'Soda Water'=>200,'Ice'=>200]],
                ['Passion Fruit','Soda Burst',['Passion Fruit Syrup'=>30,'Soda Water'=>200,'Ice'=>200]],
                ['Strawberry','Soda Burst',['Strawberry Syrup'=>30,'Soda Water'=>200,'Ice'=>200]],
            ];

            $ratio = 22 / 16;

            foreach ($menu as [$name,$category,$grandeRecipe]) {
                $product = Product::updateOrCreate(
                    ['name'=>$name],
                    ['category'=>$category,'description'=>'Inventory-deduction recipe.','status'=>'active']
                );

                foreach ($sizes as $sizeName => $size) {
                    ProductRecipe::where('product_id',$product->id)->where('size_id',$size->id)->delete();
                    $lines = $sizeName === 'Grande Boss (16oz)'
                        ? $grandeRecipe
                        : array_map(fn($qty) => round($qty * $ratio, 3), $grandeRecipe);

                    $lines[$sizeName === 'Grande Boss (16oz)' ? '16 oz Cup' : '22 oz Cup'] = 1;
                    $lines[$sizeName === 'Grande Boss (16oz)' ? '16 oz Lid' : '22 oz Lid'] = 1;

                    foreach ($lines as $itemName=>$qty) {
                        ProductRecipe::create([
                            'product_id'=>$product->id,
                            'size_id'=>$size->id,
                            'item_id'=>InventoryItem::where('name',$itemName)->value('id'),
                            'qty_per_unit'=>$qty,
                        ]);
                    }
                }
            }

            $extraShot = AddOn::firstOrCreate(['name'=>'Extra Espresso Shot']);
            AddOnRecipe::updateOrCreate(
                ['addon_id'=>$extraShot->id,'item_id'=>InventoryItem::where('name','Coffee Beans')->value('id')],
                ['qty_per_unit'=>8]
            );
        });
    }
}
