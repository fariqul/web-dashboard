<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sppd_transactions', function (Blueprint $table) {
            $table->date('planned_payment_date')->nullable()->after('trip_ends_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppd_transactions', function (Blueprint $table) {
            $table->dropColumn('planned_payment_date');
        });
    }
};
