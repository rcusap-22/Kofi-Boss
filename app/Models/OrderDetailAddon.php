<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class OrderDetailAddon extends Model {
    use HasFactory;
    protected $fillable=['order_detail_id','addon_id','quantity'];
    public function orderDetail(){ return $this->belongsTo(OrderDetail::class); }
    public function addon(){ return $this->belongsTo(AddOn::class,'addon_id'); }
}
