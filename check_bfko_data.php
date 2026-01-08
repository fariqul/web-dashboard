<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check sample employee data
$nip = '7393326B';

$data = App\Models\BfkoData::where('nip', $nip)
    ->orderBy('tahun')
    ->orderBy('bulan')
    ->get(['nip', 'nama', 'jabatan', 'unit', 'bulan', 'tahun']);

echo "=== Data for NIP: $nip ===\n";
foreach ($data as $row) {
    echo "Tahun: {$row->tahun}, Bulan: {$row->bulan}, Jabatan: {$row->jabatan}, Unit: {$row->unit}\n";
}

echo "\n=== Distinct Jabatan per Tahun ===\n";
$distinctData = App\Models\BfkoData::where('nip', $nip)
    ->select('tahun', 'jabatan', 'unit')
    ->distinct()
    ->orderBy('tahun')
    ->get();

foreach ($distinctData as $row) {
    echo "Tahun: {$row->tahun} => Jabatan: {$row->jabatan}, Unit: {$row->unit}\n";
}
