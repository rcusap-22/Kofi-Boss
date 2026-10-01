<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'status',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role helpers used throughout controllers/views/middleware
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStoreManager(): bool
    {
        return $this->role === 'store_manager';
    }

    public function isInventoryStaff(): bool
    {
        return $this->role === 'inventory_staff';
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }
}
