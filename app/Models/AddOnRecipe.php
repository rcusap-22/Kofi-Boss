<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddOnRecipe extends Model
{
    use HasFactory;

    protected $fillable = ['addon_id', 'item_id', 'qty_per_unit'];

    protected function casts(): array
    {
        return ['qty_per_unit' => 'decimal:3'];
    }

    public function addon()
    {
        return $this->belongsTo(AddOn::class, 'addon_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
