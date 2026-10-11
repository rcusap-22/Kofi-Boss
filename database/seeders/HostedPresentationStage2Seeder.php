<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostedPresentationStage2Seeder extends Seeder
{
    private function stamp(int $day, string $clock): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', sprintf('2026-10-%02d %s', $day, $clock), 'Asia/Manila')->format('Y-m-d H:i:s');
    }

    private function insert(string $table, array $row, string $at): int
    {
        return DB::table($table)->insertGetId(array_merge($row, ['created_at' => $at, 'updated_at' => $at]));
    }

    public function run(): void
    {
        if (DB::table('inventory_items')->count() !== 34 || DB::table('stock_transactions')->count() !== 34 ||
            DB::table('daily_stock_counts')->exists() || DB::table('purchases')->exists() || DB::table('receiving')->exists() || DB::table('daily_count_requests')->exists()) {
            throw new RuntimeException('Stage 2 requires untouched Stage 1: 34 items, 34 opening transactions, and no later operations. Nothing changed.');
        }
        $staff = DB::table('users')->where('role', 'inventory_staff')->whereNull('deleted_at')->value('id');
        $manager = DB::table('users')->where('role', 'store_manager')->whereNull('deleted_at')->value('id');
        $supplier = DB::table('suppliers')->orderBy('id')->value('id');
        if (!$staff || !$manager || !$supplier) throw new RuntimeException('Active inventory_staff, store_manager and existing supplier required. Nothing changed.');
        $items = DB::table('inventory_items')->get()->keyBy('name');
        $names = [
            'Plastic Cups 16oz','Plastic Cups 22oz','Flat Lids 16oz','Flat Lids 22oz','Dome Lids 16oz','Dome Lids 22oz','Drinking Straws',
            'Coffee Beans','Fresh Milk','Evaporated Milk','Condensed Milk','Creamer','Caramel Syrup','Chocolate Syrup','Mocha Sauce','Sugar',
            'Matcha Powder','Chocolate Powder','Strawberry Syrup','Strawberry Puree','Frappe Base','Whipped Cream','Vanilla Ice Cream',
            'Chocolate Ice Cream','Strawberry Ice Cream','Soda Water','Green Apple Syrup','Blueberry Syrup','Mango Syrup',
            'Passion Fruit Syrup','Strawberry Soda Syrup','Tissue','Cleaning Liquid','Disposable Gloves'
        ];
        foreach ($names as $name) if (!isset($items[$name])) throw new RuntimeException('Missing Stage 1 item: '.$name);
        $stock = []; foreach ($names as $name) $stock[$name] = (int)$items[$name]->stock_qty;
        $days = [5,6,7,8,9];
        $cup16 = [117,139,108,147,128];
        $cup22 = [76,88,78,94,90];
        $closeTimes = ['23:21:37','23:08:52','23:34:16','23:16:43','23:27:09'];
        $specific = [
            'Caramel Syrup'=>[3,3,2,3,3], 'Matcha Powder'=>[3,3,3,3,2], 'Soda Water'=>[14,17,13,18,13],
            'Strawberry Syrup'=>[2,3,2,2,2], 'Coffee Beans'=>[3,3,2,3,2], 'Fresh Milk'=>[9,11,8,12,10],
            'Flat Lids 16oz'=>[85,110,74,112,96], 'Flat Lids 22oz'=>[42,56,48,64,54],
            'Dome Lids 16oz'=>[32,35,27,41,33], 'Dome Lids 22oz'=>[21,27,19,30,24],
            'Drinking Straws'=>[176,205,168,216,194],
        ];
        DB::transaction(function () use ($staff,$manager,$supplier,$items,$names,&$stock,$days,$cup16,$cup22,$closeTimes,$specific) {
            $movement = function (string $name, int $delta, string $reference, string $notes, string $at, int $user) use ($items,&$stock) {
                if ($delta === 0) return;
                if ($stock[$name] + $delta < 0) throw new RuntimeException('Negative stock for '.$name);
                $stock[$name] += $delta;
                DB::table('stock_transactions')->insert([
                    'item_id'=>$items[$name]->id,'user_id'=>$user,'type'=>$delta>0?'in':'out','quantity'=>abs($delta),
                    'reference'=>$reference,'notes'=>$notes,'date'=>substr($at,0,10),'created_at'=>$at,'updated_at'=>$at,
                ]);
            };
            $purchase = function (int $day, string $time, array $lines, string $status, string $note, ?string $reviewTime = null, ?string $reason = null) use ($staff,$manager,$supplier,$items) {
                $at = $this->stamp($day,$time);
                $id = $this->insert('purchases',[
                    'supplier_id'=>$supplier,'requested_by'=>$staff,'approved_by'=>in_array($status,['approved','completed','rejected'],true)?$manager:null,
                    'order_date'=>substr($at,0,10),'status'=>$status,'notes'=>$note,
                    'rejection_reason'=>$reason,
                ],$at);
                foreach ($lines as $name=>$qty) $this->insert('purchase_items',[
                    'purchase_id'=>$id,'item_id'=>$items[$name]->id,'quantity'=>$qty,'package_unit'=>$items[$name]->unit,'package_size'=>1,
                ],$at);
                if ($reviewTime) DB::table('purchases')->where('id',$id)->update(['updated_at'=>$this->stamp($day,$reviewTime)]);
                return $id;
            };
            $receive = function (int $purchaseId, int $day, string $time, array $lines, string $notes) use ($staff,$items,$movement) {
                $at=$this->stamp($day,$time);
                $id=$this->insert('receiving',[
                    'purchase_id'=>$purchaseId,'received_by'=>$staff,'received_date'=>substr($at,0,10),
                    'notes'=>$notes,
                ],$at);
                foreach ($lines as $name=>$qty) {
                    $this->insert('receiving_items',['receiving_id'=>$id,'item_id'=>$items[$name]->id,'qty_received'=>$qty],$at);
                    $movement($name,$qty,'Purchase #'.$purchaseId.' · '.$qty.' '.$items[$name]->unit,'Delivery recorded in receiving #'.$id,$at,$staff);
                }
            };
            $count = function (int $day, string $time, int $idx, bool $intentionalError = false) use ($staff,$items,$names,&$stock,$movement,$cup16,$cup22,$specific) {
                $at=$this->stamp($day,$time);
                $countId=$this->insert('daily_stock_counts',[
                    'count_date'=>substr($at,0,10),'user_id'=>$staff,'submitted_at'=>$at,
                    'notes'=>'End-of-day physical inventory count.',
                    'review_status'=>'submitted','was_corrected'=>0,'canceled_at'=>null,
                ],$at);
                $lines=[];
                foreach ($names as $i=>$name) {
                    $expected=$stock[$name];
                    if ($name==='Plastic Cups 16oz') $used=$cup16[$idx];
                    elseif ($name==='Plastic Cups 22oz') $used=$cup22[$idx];
                    elseif (isset($specific[$name])) $used=$specific[$name][$idx];
                    elseif (str_contains($name,'Syrup') || str_contains($name,'Sauce')) $used=[1,2,1,2,1][$idx];
                    elseif (in_array($name,['Tissue','Cleaning Liquid','Disposable Gloves'],true)) $used=[1,0,1,1,0][$idx];
                    else $used=[2,3,2,3,2][$idx] + (($i+$idx)%3===0 ? 1 : 0);
                    $actual=max(0,$expected-$used);
                    if ($intentionalError && $name==='Plastic Cups 16oz') $actual=max(0,$actual-5);
                    $lineId=$this->insert('daily_stock_count_items',[
                        'daily_stock_count_id'=>$countId,'item_id'=>$items[$name]->id,
                        'expected_qty'=>$expected,'actual_qty'=>$actual,'variance_qty'=>$actual-$expected,
                    ],$at);
                    $lines[$name]=['id'=>$lineId,'expected'=>$expected,'actual'=>$actual];
                    $movement($name,$actual-$expected,'Daily Count #'.$countId,
                        'End-of-day reconciliation. Expected: '.$expected.'; Actual: '.$actual,$at,$staff);
                }
                return [$countId,$lines];
            };
            $purchaseA=null;$purchaseB=null;
            for ($idx=0;$idx<5;$idx++) {
                $day=$days[$idx];
                if ($day===6) {
                    $purchaseA=$purchase(6,'14:12:19',[
                        'Fresh Milk'=>24,'Coffee Beans'=>10,'Plastic Cups 16oz'=>300,'Flat Lids 16oz'=>300,'Drinking Straws'=>400,
                    ],'approved','Restock packaging and milk for upcoming demand.','15:04:38');
                    $purchase(6,'16:41:25',['Cleaning Liquid'=>12,'Disposable Gloves'=>8],
                        'rejected','Requested extra cleaning stock.','17:03:14','Current supplies are sufficient; defer order.');
                }
                if ($day===7) {
                    $receive($purchaseA,7,'10:26:18',['Fresh Milk'=>12,'Coffee Beans'=>5,'Plastic Cups 16oz'=>150,'Flat Lids 16oz'=>150,'Drinking Straws'=>200],
                        'First partial delivery; remainder due tomorrow.');
                    $purchaseB=$purchase(7,'14:23:07',['Soda Water'=>40,'Matcha Powder'=>10,'Plastic Cups 22oz'=>200],
                        'approved','Urgent replenishment requested; delivery not yet received.','15:19:44');
                }
                if ($day===8) {
                    $receive($purchaseA,8,'09:48:32',['Fresh Milk'=>12,'Coffee Beans'=>5,'Plastic Cups 16oz'=>150,'Flat Lids 16oz'=>150,'Drinking Straws'=>200],
                        'Remaining delivery received; purchase completed.');
                    DB::table('purchases')->where('id',$purchaseA)->update(['status'=>'completed','updated_at'=>$this->stamp(8,'09:48:32')]);
                    
                }
                if ($day===9) {
                    
                }
                // One verified closing count per date; no correction/cancellation demonstration history.
                [$countId,$lines]=$count($day,$closeTimes[$idx],$idx);

            }
            foreach ($names as $name) {
                $qty=$stock[$name];$min=(int)$items[$name]->min_stock;
                $status=$qty===0?'out_of_stock':($qty<=$min?'low_stock':'in_stock');
                DB::table('inventory_items')->where('id',$items[$name]->id)->update([
                    'stock_qty'=>$qty,'status'=>$status,'updated_at'=>$this->stamp(9,'23:47:18'),
                ]);
            }
            // Verify transaction arithmetic and final item quantities before commit.
            foreach ($names as $name) {
                $itemId=$items[$name]->id;
                $ins=(int)DB::table('stock_transactions')->where('item_id',$itemId)->where('type','in')->sum('quantity');
                $outs=(int)DB::table('stock_transactions')->where('item_id',$itemId)->where('type','out')->sum('quantity');
                if ($ins-$outs !== $stock[$name]) throw new RuntimeException('Stock reconciliation failed: '.$name);
            }
        });
        $this->command?->info('Hosted operational history complete: five closing counts, purchases, receiving, and reconciled stock.');
        $this->command?->info('Existing accounts and suppliers were not changed.');
    }
}
