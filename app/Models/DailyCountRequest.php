<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DailyCountRequest extends Model {
 protected $fillable=['daily_stock_count_id','requested_by','reviewed_by','type','status','reason','review_note','reviewed_at'];
 protected function casts(): array {return ['reviewed_at'=>'datetime'];}
 public function count(){return $this->belongsTo(DailyStockCount::class,'daily_stock_count_id');}
 public function requester(){return $this->belongsTo(User::class,'requested_by');}
 public function reviewer(){return $this->belongsTo(User::class,'reviewed_by');}
 public function readers(){return $this->belongsToMany(User::class,'daily_count_request_reads','daily_count_request_id','user_id')->withTimestamps();}
 public function items(){return $this->hasMany(DailyCountRequestItem::class);}
}
