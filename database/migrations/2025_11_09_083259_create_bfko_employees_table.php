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
        Schema::create('bfko_employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('nama_pegawai');
            $table->string('jabatan');
            $table->string('jenjang_jabatan')->nullable();
            $table->string('unit')->nullable();
            $table->string('status_angsuran')->nullable(); // "Angsuran Ke - X", "SELESAI", etc
            $table->decimal('sisa_angsuran', 15, 2)->nullable(); // Sisa per 1 Jan 2025
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bfko_employees');
    }
};
