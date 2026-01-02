<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BfkoData;

echo "=== Checking SETIYAWAN and YULI records ===\n\n";

$records = BfkoData::where('nama', 'LIKE', '%SETIYAWAN%')
    ->orWhere('nama', 'LIKE', '%YULI%')
    ->orderBy('tahun')
    ->orderBy('bulan')
    ->get();

foreach ($records as $r) {
    echo sprintf("%-12s | %-25s | %-10s | %d | %s\n",
        $r->nip,
        $r->nama,
        $r->bulan,
        $r->tahun,
        number_format($r->nilai_angsuran, 0, ',', '.')
    );
}

echo "\n\n=== Checking all records over 1 Billion ===\n\n";
$bigRecords = BfkoData::where('nilai_angsuran', '>', 1000000000)->get();
foreach ($bigRecords as $r) {
    echo sprintf("%-12s | %-25s | %-10s | %d | %s\n",
        $r->nip,
        $r->nama,
        $r->bulan,
        $r->tahun,
        number_format($r->nilai_angsuran, 0, ',', '.')
    );
}

echo "\n\nChecking raw CSV content for these NIP:\n";
$csvFile = 'd:/Bu Intan/data/bfko/converted_bfko.csv';
if (file_exists($csvFile)) {
    $handle = fopen($csvFile, 'r');
    $header = fgetcsv($handle);
    
    while (($row = fgetcsv($handle)) !== false) {
        if (in_array($row[0], ['8006231Z', '7904006F'])) {
            echo implode(' | ', $row) . "\n";
        }
    }
    fclose($handle);
}
