<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receiving extends Model
{
    use HasFactory;

    protected $table = 'receiving';

    protected $fillable = ['purchase_id', 'received_by', 'received_date', 'notes'];

    protected function casts(): array
    {
        return ['received_date' => 'date'];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(ReceivingItem::class, 'receiving_id');
    }
}
