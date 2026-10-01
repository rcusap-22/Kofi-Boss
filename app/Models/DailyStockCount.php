<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DailyStockCount extends Model {
    protected $fillable = ['count_date','user_id','submitted_at','notes'];
    protected function casts(): array { return ['count_date'=>'date','submitted_at'=>'datetime']; }
    public function user(){ return $this->belongsTo(User::class); }
    public function items(){ return $this->hasMany(DailyStockCountItem::class); }
}
