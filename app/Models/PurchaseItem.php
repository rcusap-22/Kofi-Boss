<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PurchaseItem extends Model {
    use HasFactory;
    protected $fillable=['purchase_id','item_id','quantity','package_unit','package_size'];
    public function purchase(){ return $this->belongsTo(Purchase::class); }
    public function item(){ return $this->belongsTo(InventoryItem::class,'item_id'); }
}
