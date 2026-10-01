<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class OrderDetail extends Model {
    use HasFactory;
    protected $fillable=['order_id','product_id','size_id','quantity'];
    public function order(){ return $this->belongsTo(Order::class); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function size(){ return $this->belongsTo(Size::class); }
    public function addons(){ return $this->hasMany(OrderDetailAddon::class); }
}
