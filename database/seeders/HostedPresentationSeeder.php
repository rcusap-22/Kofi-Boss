<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostedPresentationSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') !== 'mysql' ||
            config('app.env') !== 'production' ||
            env('RUN_HOSTED_PRESENTATION_SEED') !== 'yes') {
            throw new RuntimeException('Hosted seeding is disabled: requires production MySQL and explicit RUN_HOSTED_PRESENTATION_SEED=yes.');
        }
        $host = strtolower((string) config('database.connections.mysql.host'));
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Hosted seeding refuses localhost MySQL.');
        }
        if (!DB::table('users')->where('role', 'inventory_staff')->whereNull('deleted_at')->exists() ||
            !DB::table('users')->where('role', 'store_manager')->whereNull('deleted_at')->exists() ||
            !DB::table('suppliers')->exists()) {
            throw new RuntimeException('Requires existing active staff, manager and supplier. No changes made.');
        }
        $tables = ['inventory_items','stock_transactions','daily_stock_counts','daily_stock_count_items',
            'daily_count_requests','daily_count_request_items','purchases','purchase_items','receiving','receiving_items'];
        $counts = [];
        foreach ($tables as $table) $counts[$table] = DB::table($table)->count();
        if (array_sum($counts) > 0) {
            // Idempotent on a Render restart after a successful installation.
            if ($counts['inventory_items'] === 34 &&
                $counts['daily_stock_counts'] === 5 &&
                $counts['purchases'] === 3 &&
                $counts['receiving'] === 2 &&
                $counts['daily_count_requests'] === 0) {
                $this->command?->info('Hosted presentation inventory already populated; skipping.');
                return;
            }
            throw new RuntimeException('Hosted operational tables are not empty or do not match expected completed dataset. No changes made.');
        }
        DB::transaction(function () {
            $this->call(HostedPresentationStage1Seeder::class);
            $this->call(HostedPresentationStage2Seeder::class);
        });
        $this->command?->info('Hosted presentation inventory populated successfully.');
    }
}
