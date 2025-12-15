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
            $table->string('transaction_type', 20)->default('payment')->after('payment_amount');
            $table->dropColumn('month'); // Hapus kolom month yang hardcoded
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cc_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_type');
            $table->string('month', 20)->default('Juli 2025')->after('status');
        });
    }
};
