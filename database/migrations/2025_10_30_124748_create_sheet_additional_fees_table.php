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
        Schema::create('sheet_additional_fees', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_name')->unique();
            $table->decimal('biaya_adm_bunga', 15, 2)->default(0);
            $table->decimal('biaya_transfer', 15, 2)->default(0);
            $table->decimal('iuran_tahunan', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_additional_fees');
    }
};
