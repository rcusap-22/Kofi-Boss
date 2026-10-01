<?php

namespace Database\Seeders;

use App\Models\{AddOn, AddOnRecipe, InventoryItem, Product, ProductRecipe, Size, Supplier, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name'=>'Admin Owner','username'=>'owner','email'=>'owner@kofiboss.test','password'=>Hash::make('password'),'role'=>'owner']);
        User::create(['name'=>'Store Manager','username'=>'manager','email'=>'manager@kofiboss.test','password'=>Hash::make('password'),'role'=>'store_manager']);
        User::create(['name'=>'Dizza (Inventory Staff)','username'=>'dizza','email'=>'dizza@kofiboss.test','password'=>Hash::make('password'),'role'=>'inventory_staff']);

        $supplier = Supplier::create(['name'=>'Main Supplier','contact_person'=>'Juan Dela Cruz','phone'=>'0917-000-0001']);

        $inventory = [
            ['Coffee Beans','Ingredient','bag','1 kg bag',5,2], ['Fresh Milk','Ingredient','carton','1 L carton',20,4],
            ['Condensed Milk','Ingredient','can','390 g can',12,3], ['Caramel Syrup','Ingredient','bottle','750 ml bottle',5.333,2],
            ['Chocolate Syrup','Ingredient','bottle','750 ml bottle',5.333,2], ['Matcha Powder','Ingredient','pouch','500 g pouch',2,1],
            ['Strawberry Syrup','Ingredient','bottle','750 ml bottle',5.333,2], ['Green Apple Syrup','Ingredient','bottle','750 ml bottle',4,2],
            ['Blueberry Syrup','Ingredient','bottle','750 ml bottle',4,2], ['Mango Syrup','Ingredient','bottle','750 ml bottle',4,2],
            ['Passion Fruit Syrup','Ingredient','bottle','750 ml bottle',4,2], ['Simple Syrup','Ingredient','bottle','750 ml bottle',4,2],
            ['Frappe Powder / Base','Ingredient','bag','1 kg bag',2,1], ['Soda Water','Ingredient','bottle','1.5 L bottle',8,2],
            ['Ice','Ingredient','bag','5 kg bag',5,2], ['16 oz Cup','Supply','pcs','individual cup',300,75],
            ['22 oz Cup','Supply','pcs','individual cup',300,75], ['16 oz Lid','Supply','pcs','individual lid',300,75],
            ['22 oz Lid','Supply','pcs','individual lid',300,75],
        ];
        foreach ($inventory as [$name,$category,$unit,$package,$stock,$min]) {
            $item = InventoryItem::create(['name'=>$name,'category'=>$category,'unit'=>$unit,'purchase_unit'=>$package,'purchase_unit_size'=>1,'stock_qty'=>$stock,'min_stock'=>$min,'supplier_id'=>$supplier->id]);
            $item->refreshStatus();
        }

        $sizes = [
            'Grande Boss (16oz)' => Size::create(['name'=>'Grande Boss (16oz)']),
            'Big Boss (22oz)' => Size::create(['name'=>'Big Boss (22oz)']),
        ];

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

        foreach ($menu as [$name,$category,$grandeRecipe]) {
            $product = Product::create(['name'=>$name,'category'=>$category,'description'=>'Simplified working recipe for inventory deduction.','status'=>'active']);
            $recipesBySize = [
                'Grande Boss (16oz)' => $grandeRecipe,
                'Big Boss (22oz)' => array_map(fn($qty) => round($qty * (22 / 16), 3), $grandeRecipe),
            ];
            foreach ($sizes as $sizeName => $size) {
                $lines = $recipesBySize[$sizeName];
                $lines[$sizeName === 'Grande Boss (16oz)' ? '16 oz Cup' : '22 oz Cup'] = 1;
                $lines[$sizeName === 'Grande Boss (16oz)' ? '16 oz Lid' : '22 oz Lid'] = 1;
                foreach ($lines as $itemName=>$qty) {
                    ProductRecipe::create(['product_id'=>$product->id,'size_id'=>$size->id,'item_id'=>InventoryItem::where('name',$itemName)->value('id'),'qty_per_unit'=>$qty]);
                }
            }
        }

        $extraShot = AddOn::create(['name'=>'Extra Espresso Shot']);
        AddOnRecipe::create(['addon_id'=>$extraShot->id,'item_id'=>InventoryItem::where('name','Coffee Beans')->value('id'),'qty_per_unit'=>8]);
    }
}
