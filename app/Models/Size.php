<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Size extends Model {
    use HasFactory;
    protected $fillable = ['name'];
    public function recipes(){ return $this->hasMany(ProductRecipe::class); }
    public function orderDetails(){ return $this->hasMany(OrderDetail::class); }
}
