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
        if (!Schema::hasColumn('sppd_transactions', 'document_number')) {
            Schema::table('sppd_transactions', function (Blueprint $table) {
                // Add document_number column (Group ID from Excel)
                $table->string('document_number', 50)->nullable()->after('trip_number');
                $table->index('document_number');
            });
        }
        
        // Also remove the unique constraint from trip_number since multiple trips can have same trip_number with different document_number
        try {
            Schema::table('sppd_transactions', function (Blueprint $table) {
                $table->dropUnique(['trip_number']);
            });
        } catch (\Exception $e) {
            // Unique constraint may not exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppd_transactions', function (Blueprint $table) {
            $table->dropIndex(['document_number']);
            $table->dropColumn('document_number');
            $table->unique('trip_number');
        });
    }
};
