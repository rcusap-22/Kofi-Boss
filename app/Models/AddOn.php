<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AddOn extends Model {
    use HasFactory;
    protected $table = 'add_ons';
    protected $fillable = ['name'];
    public function orderDetailAddons(){ return $this->hasMany(OrderDetailAddon::class, 'addon_id'); }
    public function recipes(){ return $this->hasMany(AddOnRecipe::class, 'addon_id'); }
}
