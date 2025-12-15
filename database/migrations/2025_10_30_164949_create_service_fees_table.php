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
        Schema::create('service_fees', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_no')->nullable(); // No. dari CSV
            $table->dateTime('transaction_time'); // Transaction Time
            $table->string('booking_id', 50)->index(); // Booking ID
            $table->string('service_type', 20)->index(); // 'hotel' atau 'flight'
            $table->string('settlement_method', 50)->nullable(); // INVOICE
            $table->string('status', 50)->default('ISSUED'); // Status
            
            // Untuk Hotel
            $table->string('hotel_name')->nullable()->index(); // Extracted dari Description
            $table->string('room_type')->nullable(); // Extracted dari Description
            
            // Untuk Flight
            $table->string('route')->nullable()->index(); // e.g., "CGK_UPG"
            $table->string('trip_type')->nullable(); // ONE_WAY, TWO_WAY
            $table->integer('pax')->nullable(); // Jumlah penumpang
            $table->string('airline_id', 10)->nullable(); // GA, JT, QG, dll
            $table->string('booker_email')->nullable(); // Email booker
            
            // Common fields
            $table->string('employee_name')->nullable()->index(); // Extracted dari Description/Passengers
            $table->string('currency', 10)->default('IDR');
            $table->decimal('transaction_amount', 15, 2); // Transaction Amount (dalam rupiah)
            $table->decimal('base_amount', 15, 2); // Base Amount / Service Fee
            
            // Sheet management
            $table->string('sheet', 50)->index(); // e.g., "Juli 2025"
            
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index('transaction_time');
            $table->index(['sheet', 'service_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_fees');
    }
};
