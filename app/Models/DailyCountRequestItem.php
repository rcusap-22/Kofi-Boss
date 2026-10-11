<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DailyCountRequestItem extends Model {
 protected $fillable=['daily_count_request_id','daily_stock_count_item_id','previous_qty','requested_qty'];
 public function countItem(){return $this->belongsTo(DailyStockCountItem::class,'daily_stock_count_item_id');}
}
