<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * One-time presentation reset for the confirmed existing hosted test dataset.
 * NEVER run as part of normal application startup after the initial deployment.
 */
class HostedPresentationResetSeeder extends Seeder
{
    public function run(): void
    {
        $host = strtolower((string) config('database.connections.mysql.host'));
        if (config('database.default') !== 'mysql'
            || config('app.env') !== 'production'
            || env('RUN_HOSTED_PRESENTATION_RESET') !== 'yes'
            || env('RUN_HOSTED_PRESENTATION_SEED') !== 'yes'
            || !str_ends_with($host, '.services.clever-cloud.com')) {
            throw new RuntimeException('Safety stop: requires production Clever Cloud MySQL and both explicit presentation flags.');
        }

        $items = (int) DB::table('inventory_items')->count();
        $purchases = (int) DB::table('purchases')->count();
        $counts = (int) DB::table('daily_stock_counts')->count();
        $receipts = (int) DB::table('receiving')->count();

        // A restarted instance must never erase the successfully populated data.
        if ($items === 34 && $counts === 5 && $purchases === 3 && $receipts === 2) {
            $this->command?->info('Presentation records already present; skipping reset and seeding.');
            return;
        }

        // Only the specifically confirmed old test dataset is eligible for deletion.
        if ($items !== 1 || $purchases !== 1) {
            throw new RuntimeException("Safety stop: expected exactly 1 old inventory item and 1 old purchase; found {$items} items and {$purchases} purchases. No changes made.");
        }

        if (!DB::table('users')->where('role', 'inventory_staff')->whereNull('deleted_at')->exists()
            || !DB::table('users')->where('role', 'store_manager')->whereNull('deleted_at')->exists()
            || !DB::table('suppliers')->exists()) {
            throw new RuntimeException('Safety stop: required staff, manager, or supplier missing. No changes made.');
        }

        DB::transaction(function () {
            // Child tables first to respect foreign keys. Preserve users and suppliers.
            $tables = [
                'daily_count_request_reads',
                'daily_count_request_items',
                'daily_count_requests',
                'daily_stock_count_items',
                'daily_stock_counts',
                'receiving_items',
                'receiving',
                'purchase_items',
                'purchases',
                'stock_transactions',
                'add_on_recipes',
                'product_recipes',
                'inventory_items',
            ];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $removed = DB::table($table)->delete();
                    $this->command?->line("Cleared {$table}: {$removed} rows");
                }
            }
            $this->call(HostedPresentationSeeder::class);
        });

        $this->command?->info('Hosted inventory reset and presentation population completed. Accounts and suppliers preserved.');
    }
}
