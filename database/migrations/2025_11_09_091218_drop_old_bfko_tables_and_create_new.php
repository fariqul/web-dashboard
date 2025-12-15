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
        // Drop old tables
        Schema::dropIfExists('bfko_payments');
        Schema::dropIfExists('bfko_employees');
        
        // Create new unified table
        Schema::create('bfko_data', function (Blueprint $table) {
            $table->id();
            $table->string('nip');
            $table->string('nama');
            $table->string('jabatan');
            $table->string('unit')->nullable();
            $table->string('bulan'); // Januari, Februari, dst
            $table->integer('tahun'); // 2024, 2025, 2026, dst
            $table->decimal('nilai_angsuran', 15, 2);
            $table->date('tanggal_bayar')->nullable();
            $table->string('status_angsuran')->nullable(); // "Angsuran Ke-24", "Lunas", dll
            $table->text('keterangan')->nullable(); // Keterangan tambahan
            $table->timestamps();
            
            // Indexes untuk query cepat
            $table->index('nip');
            $table->index(['bulan', 'tahun']);
            $table->index('tanggal_bayar');
            
            // Unique constraint: satu pegawai hanya bisa bayar 1x per bulan-tahun
            $table->unique(['nip', 'bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bfko_data');
        
        // Recreate old tables if needed
        Schema::create('bfko_employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('nama_pegawai');
            $table->string('jabatan');
            $table->string('jenjang_jabatan')->nullable();
            $table->string('unit')->nullable();
            $table->string('status_angsuran')->nullable();
            $table->decimal('sisa_angsuran', 15, 2)->nullable();
            $table->timestamps();
        });
        
        Schema::create('bfko_payments', function (Blueprint $table) {
            $table->id();
            $table->string('nip');
            $table->string('bulan');
            $table->integer('tahun');
            $table->decimal('nilai_angsuran', 15, 2);
            $table->date('tanggal_pembayaran')->nullable();
            $table->timestamps();
            
            $table->index('nip');
            $table->index(['bulan', 'tahun']);
            $table->index('tanggal_pembayaran');
            
            $table->foreign('nip')->references('nip')->on('bfko_employees')->onDelete('cascade');
        });
    }
};
