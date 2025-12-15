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
        Schema::create('bfko_payments', function (Blueprint $table) {
            $table->id();
            $table->string('nip');
            $table->string('bulan'); // Januari, Februari, dst
            $table->integer('tahun'); // 2024, 2025, dst
            $table->decimal('nilai_angsuran', 15, 2);
            $table->date('tanggal_pembayaran')->nullable();
            $table->timestamps();
            
            // Index untuk optimasi query
            $table->index('nip');
            $table->index(['bulan', 'tahun']);
            $table->index('tanggal_pembayaran');
            
            // Foreign key ke bfko_employees
            $table->foreign('nip')
                  ->references('nip')
                  ->on('bfko_employees')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bfko_payments');
    }
};
