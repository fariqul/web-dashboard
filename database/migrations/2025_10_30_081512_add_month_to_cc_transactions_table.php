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
        Schema::table('cc_transactions', function (Blueprint $table) {
            $table->string('month', 50)->after('transaction_type')->nullable();
            $table->index('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cc_transactions', function (Blueprint $table) {
            $table->dropIndex(['month']);
            $table->dropColumn('month');
        });
    }
};
