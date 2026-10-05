<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address'];

    public static function ensureMainSupplier(): self
    {
        return static::firstOrCreate(['name' => 'Main Supplier']);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
