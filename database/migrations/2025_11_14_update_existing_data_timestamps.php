<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update BFKO data with NULL timestamps
        DB::table('bfko_data')
            ->whereNull('created_at')
            ->orWhereNull('updated_at')
            ->update([
                'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                'updated_at' => DB::raw('COALESCE(updated_at, CURRENT_TIMESTAMP)')
            ]);

        // Update Service Fees with NULL timestamps
        DB::table('service_fees')
            ->whereNull('created_at')
            ->orWhereNull('updated_at')
            ->update([
                'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                'updated_at' => DB::raw('COALESCE(updated_at, CURRENT_TIMESTAMP)')
            ]);

        // Update CC Transactions with NULL timestamps
        DB::table('cc_transactions')
            ->whereNull('created_at')
            ->orWhereNull('updated_at')
            ->update([
                'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                'updated_at' => DB::raw('COALESCE(updated_at, CURRENT_TIMESTAMP)')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - we're just filling in missing data
    }
};
