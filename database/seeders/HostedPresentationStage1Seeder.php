<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Carbon\Carbon;

class HostedPresentationStage1Seeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('inventory_items')->exists() || DB::table('stock_transactions')->exists() || DB::table('daily_stock_counts')->exists() || DB::table('purchases')->exists() || DB::table('receiving')->exists()) {
            throw new RuntimeException('Stage 1 requires empty operational tables; nothing was changed.');
        }
        $user = DB::table('users')->where('role', 'inventory_staff')->whereNull('deleted_at')->orderBy('id')->first();
        if (!$user) throw new RuntimeException('No active inventory_staff account found; nothing was changed.');
        $supplier = DB::table('suppliers')->where('name', 'Main Supplier')->value('id') ?? DB::table('suppliers')->orderBy('id')->value('id');

        // name, category, unit, opening quantity, reorder threshold
        $items = [
            ['Plastic Cups 16oz','Packaging','pcs',850,200],
            ['Plastic Cups 22oz','Packaging','pcs',520,150],
            ['Flat Lids 16oz','Packaging','pcs',650,150],
            ['Flat Lids 22oz','Packaging','pcs',430,100],
            ['Dome Lids 16oz','Packaging','pcs',280,80],
            ['Dome Lids 22oz','Packaging','pcs',210,60],
            ['Drinking Straws','Packaging','pcs',1450,250],
            ['Coffee Beans','Ingredients','packs',22,5],
            ['Fresh Milk','Ingredients','cartons',65,12],
            ['Evaporated Milk','Ingredients','cans',42,10],
            ['Condensed Milk','Ingredients','cans',38,8],
            ['Creamer','Ingredients','packs',24,5],
            ['Caramel Syrup','Ingredients','bottles',16,4],
            ['Chocolate Syrup','Ingredients','bottles',14,4],
            ['Mocha Sauce','Ingredients','bottles',12,3],
            ['Sugar','Ingredients','packs',28,6],
            ['Matcha Powder','Ingredients','packs',14,3],
            ['Chocolate Powder','Ingredients','packs',17,4],
            ['Strawberry Syrup','Ingredients','bottles',12,3],
            ['Strawberry Puree','Ingredients','bottles',10,3],
            ['Frappe Base','Ingredients','packs',16,4],
            ['Whipped Cream','Ingredients','cans',18,4],
            ['Vanilla Ice Cream','Ingredients','packs',12,3],
            ['Chocolate Ice Cream','Ingredients','packs',9,2],
            ['Strawberry Ice Cream','Ingredients','packs',8,2],
            ['Soda Water','Beverages','bottles',75,15],
            ['Green Apple Syrup','Beverages','bottles',9,3],
            ['Blueberry Syrup','Beverages','bottles',9,3],
            ['Mango Syrup','Beverages','bottles',11,3],
            ['Passion Fruit Syrup','Beverages','bottles',8,2],
            ['Strawberry Soda Syrup','Beverages','bottles',9,3],
            ['Tissue','Others','packs',22,5],
            ['Cleaning Liquid','Cleaning Supplies','bottles',8,2],
            ['Disposable Gloves','Others','boxes',6,2],
        ];
        DB::transaction(function () use ($items, $user, $supplier) {
            foreach ($items as $index => [$name,$category,$unit,$qty,$min]) {
                // Stagger record creation across October 5, 09:14–10:32 local shop time.
                $minute = intdiv($index * 78, count($items) - 1);
                $time = Carbon::create(2026,10,5,9,14,0,'Asia/Manila')->addMinutes($minute)->addSeconds(($index * 17 + 11) % 60)->format('Y-m-d H:i:s');
                $id = DB::table('inventory_items')->insertGetId([
                    'name'=>$name, 'category'=>$category, 'unit'=>$unit,
                    'stock_qty'=>$qty, 'min_stock'=>$min, 'supplier_id'=>$supplier,
                    'purchase_unit'=>null, 'purchase_unit_size'=>1, 'expiry_date'=>null,
                    'status'=>'in_stock', 'created_at'=>$time, 'updated_at'=>$time,
                ]);
                DB::table('stock_transactions')->insert([
                    'item_id'=>$id, 'user_id'=>$user->id, 'type'=>'in',
                    'quantity'=>$qty, 'reference'=>'Opening Balance',
                    'notes'=>'Opening stock entered when inventory item was created.',
                    'date'=>'2026-10-05', 'created_at'=>$time, 'updated_at'=>$time,
                ]);
            }
        });
        $this->command?->info('Stage 1 complete: 34 inventory items and 34 opening balance transactions created. Accounts and suppliers unchanged.');
    }
}
