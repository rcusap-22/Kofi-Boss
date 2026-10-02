<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'owner'],
            ['name' => 'Admin Owner', 'email' => 'owner@kofiboss.test', 'password' => Hash::make('password'), 'role' => 'owner']
        );
        User::updateOrCreate(
            ['username' => 'manager'],
            ['name' => 'Store Manager', 'email' => 'manager@kofiboss.test', 'password' => Hash::make('password'), 'role' => 'store_manager']
        );
        User::updateOrCreate(
            ['username' => 'dizza'],
            ['name' => 'Dizza (Inventory Staff)', 'email' => 'dizza@kofiboss.test', 'password' => Hash::make('password'), 'role' => 'inventory_staff']
        );
    }
}
