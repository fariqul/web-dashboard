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
        Schema::create('cc_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_number');
            $table->string('booking_id', 20)->unique();
            $table->string('employee_name', 100);
            $table->string('personel_number', 20);
            $table->string('trip_number', 20);
            $table->string('origin', 100);
            $table->string('destination', 100);
            $table->string('trip_destination_full', 200);
            $table->date('departure_date');
            $table->date('return_date');
            $table->integer('duration_days');
            $table->decimal('payment_amount', 12, 2);
            $table->string('status', 20)->default('Complete');
            $table->string('month', 20)->default('Juli 2025');
            $table->timestamps();
            
            $table->index('personel_number');
            $table->index('trip_number');
            $table->index('booking_id');
            $table->index('departure_date');
            $table->index('destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cc_transactions');
    }
};
