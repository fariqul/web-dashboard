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
        Schema::create('sppd_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_number')->nullable();
            $table->string('trip_number', 50)->unique();
            $table->string('customer_name', 255);
            $table->string('origin', 100)->nullable();
            $table->string('destination', 100)->nullable();
            $table->string('trip_destination_full', 255);
            $table->text('reason_for_trip')->nullable();
            $table->date('trip_begins_on');
            $table->date('trip_ends_on');
            $table->integer('duration_days')->nullable();
            $table->decimal('paid_amount', 15, 2);
            $table->string('beneficiary_bank_name', 100)->nullable();
            $table->string('status', 50)->default('Complete');
            $table->string('sheet', 100)->nullable();
            $table->timestamps();
            
            $table->index('trip_number');
            $table->index('customer_name');
            $table->index('trip_begins_on');
            $table->index('destination');
            $table->index('beneficiary_bank_name');
            $table->index('sheet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sppd_transactions');
    }
};
