<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Purchase extends Model {
    use HasFactory;
    protected $fillable=['supplier_id','requested_by','approved_by','order_date','status','notes','rejection_reason'];
    protected function casts(): array { return ['order_date'=>'date']; }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function requestedBy(){ return $this->belongsTo(User::class,'requested_by'); }
    public function approvedBy(){ return $this->belongsTo(User::class,'approved_by'); }
    public function items(){ return $this->hasMany(PurchaseItem::class); }
    public function receiving(){ return $this->hasMany(Receiving::class); }
}
