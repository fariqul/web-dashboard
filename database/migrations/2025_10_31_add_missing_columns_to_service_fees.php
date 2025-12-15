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
        Schema::table('service_fees', function (Blueprint $table) {
            $table->string('merchant')->nullable()->after('booking_id');
            $table->text('description')->nullable()->after('employee_name');
            $table->decimal('service_fee', 15, 2)->nullable()->after('base_amount');
            $table->decimal('vat', 15, 2)->nullable()->after('service_fee');
            $table->decimal('total_tagihan', 15, 2)->nullable()->after('vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_fees', function (Blueprint $table) {
            $table->dropColumn(['merchant', 'description', 'service_fee', 'vat', 'total_tagihan']);
        });
    }
};
