<?php
/**
 * Fix BFKO nilai_angsuran that were imported incorrectly
 * These values have an extra "00" at the end (multiplied by 100)
 */
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BfkoData;
use Illuminate\Support\Facades\DB;

echo "=== Fixing BFKO Nilai Angsuran ===\n\n";

// Read CSV to get correct values
$csvFile = 'd:/Bu Intan/data/bfko/converted_bfko.csv';
$correctValues = [];

if (file_exists($csvFile)) {
    $handle = fopen($csvFile, 'r');
    $header = fgetcsv($handle);
    
    while (($row = fgetcsv($handle)) !== false) {
        $key = $row[0] . '_' . $row[4] . '_' . $row[5]; // nip_bulan_tahun
        $correctValues[$key] = (float)$row[6];
    }
    fclose($handle);
}

echo "Loaded " . count($correctValues) . " records from CSV\n\n";

// Check for discrepancies
$allRecords = BfkoData::all();
$fixedCount = 0;
$discrepancies = [];

DB::beginTransaction();

foreach ($allRecords as $record) {
    $key = $record->nip . '_' . $record->bulan . '_' . $record->tahun;
    
    if (isset($correctValues[$key])) {
        $csvValue = $correctValues[$key];
        $dbValue = (float)$record->nilai_angsuran;
        
        // Check if DB value is roughly 100x the CSV value (off by factor of 100)
        if ($dbValue > $csvValue * 50 && $dbValue < $csvValue * 200) {
            $discrepancies[] = [
                'nip' => $record->nip,
                'nama' => $record->nama,
                'bulan' => $record->bulan,
                'tahun' => $record->tahun,
                'db_value' => $dbValue,
                'csv_value' => $csvValue,
                'ratio' => $dbValue / $csvValue
            ];
            
            // Fix the value
            $record->nilai_angsuran = $csvValue;
            $record->save();
            $fixedCount++;
        }
    }
}

echo "Found " . count($discrepancies) . " discrepancies:\n\n";
foreach ($discrepancies as $d) {
    echo sprintf("%-12s | %-25s | %-10s/%d | DB: %s -> CSV: %s (ratio: %.1fx)\n",
        $d['nip'],
        $d['nama'],
        $d['bulan'],
        $d['tahun'],
        number_format($d['db_value'], 0, ',', '.'),
        number_format($d['csv_value'], 0, ',', '.'),
        $d['ratio']
    );
}

if ($fixedCount > 0) {
    DB::commit();
    echo "\n✅ Fixed $fixedCount records!\n";
} else {
    DB::rollBack();
    echo "\nNo records needed fixing.\n";
}

// Verify the fix
echo "\n\n=== Verification ===\n";
$checkRecords = BfkoData::whereIn('nip', ['8006231Z', '7904006F'])->get();
foreach ($checkRecords as $r) {
    echo sprintf("%-12s | %-25s | %-10s | %d | %s\n",
        $r->nip,
        $r->nama,
        $r->bulan,
        $r->tahun,
        number_format($r->nilai_angsuran, 0, ',', '.')
    );
}
