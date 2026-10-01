<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DailyStockCountItem extends Model {
    protected $fillable = ['daily_stock_count_id','item_id','expected_qty','actual_qty','variance_qty'];
    protected function casts(): array { return ['expected_qty'=>'integer','actual_qty'=>'integer','variance_qty'=>'integer']; }
    public function count(){ return $this->belongsTo(DailyStockCount::class,'daily_stock_count_id'); }
    public function item(){ return $this->belongsTo(InventoryItem::class,'item_id'); }
}
