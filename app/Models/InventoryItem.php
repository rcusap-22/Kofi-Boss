<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class InventoryItem extends Model {
    use HasFactory;
    protected $fillable = ['name','category','unit','purchase_unit','purchase_unit_size','stock_qty','min_stock','expiry_date','supplier_id','status'];
    protected function casts(): array { return ['expiry_date'=>'date','stock_qty'=>'integer','min_stock'=>'integer']; }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function stockTransactions(){ return $this->hasMany(StockTransaction::class,'item_id'); }
    public function isLowStock(): bool { return $this->stock_qty <= $this->min_stock; }
    public function refreshStatus(): void {
        $this->status = $this->stock_qty <= 0 ? 'out_of_stock' : ($this->stock_qty <= $this->min_stock ? 'low_stock' : 'in_stock');
        $this->save();
    }
}
