<?php
/**
 * Script to fix Unit data in 2025 to match Jabatan
 * The Unit should be extracted from Jabatan (e.g., "MANAGER UP3 BULUKUMBA" => Unit should be "UP3 BULUKUMBA")
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fixing Unit Data Based on Jabatan ===\n\n";

// Get all records where Unit might be wrong
$records = App\Models\BfkoData::all();

$fixed = 0;
$checked = 0;

foreach ($records as $record) {
    $checked++;
    $jabatan = $record->jabatan;
    $currentUnit = $record->unit;
    
    // Try to extract unit from jabatan
    // Pattern: "MANAGER UP3 BULUKUMBA" => "UP3 BULUKUMBA"
    // Pattern: "ASMAN HAR UP3 MAKASSAR SELATAN" => "UP3 MAKASSAR SELATAN"
    // etc.
    
    $newUnit = null;
    
    // Check for UP3 pattern (capture all words after UP3)
    if (preg_match('/(UP3\s+[\w\s]+)$/i', $jabatan, $matches)) {
        $newUnit = strtoupper(trim($matches[1]));
    }
    // Check for UIW pattern
    elseif (preg_match('/(UIW\s+[\w\s]+)$/i', $jabatan, $matches)) {
        $newUnit = strtoupper(trim($matches[1]));
    }
    // Check for UP2K pattern
    elseif (preg_match('/(UP2K\s+[\w\s]+)$/i', $jabatan, $matches)) {
        $newUnit = strtoupper(trim($matches[1]));
    }
    // Check for UID pattern
    elseif (preg_match('/(UID\s+[\w\s]+)$/i', $jabatan, $matches)) {
        $newUnit = strtoupper(trim($matches[1]));
    }
    
    if ($newUnit && $newUnit !== $currentUnit) {
        echo "NIP: {$record->nip}, Tahun: {$record->tahun}, Bulan: {$record->bulan}\n";
        echo "  Jabatan: {$jabatan}\n";
        echo "  Old Unit: {$currentUnit} => New Unit: {$newUnit}\n\n";
        
        $record->unit = $newUnit;
        $record->save();
        $fixed++;
    }
}

echo "=== Summary ===\n";
echo "Checked: {$checked} records\n";
echo "Fixed: {$fixed} records\n";
