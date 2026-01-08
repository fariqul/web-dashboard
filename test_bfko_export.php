<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

ini_set('memory_limit', '512M');

echo "Testing BFKO Export for all years...\n";

try {
    $tahun = 'all';
    
    $query = App\Models\BfkoData::query();
    
    // No year filter for 'all'
    $data = $query->orderBy('nama')->get();
    
    echo "Total records: " . $data->count() . "\n";
    
    // Month order mapping
    $monthOrder = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
    ];
    
    // Group by employee AND year
    $employees = $data->groupBy(function($item) {
        return $item->nip . '_' . $item->tahun;
    })->map(function($payments) use ($monthOrder) {
        $first = $payments->first();
        $sortedPayments = $payments->sortBy(function($a) use ($monthOrder) {
            return $monthOrder[$a->bulan] ?? 99;
        })->values();
        
        return [
            'nip' => $first->nip,
            'nama' => $first->nama,
            'jabatan' => $first->jabatan,
            'unit' => $first->unit,
            'tahun' => $first->tahun,
            'payments' => $sortedPayments,
            'total' => $payments->sum('nilai_angsuran')
        ];
    })->sortBy([
        ['tahun', 'desc'],
        ['nama', 'asc']
    ])->values();
    
    echo "Total employee groups: " . $employees->count() . "\n";
    echo "Memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    
    // Check for any potential issues
    foreach ($employees as $emp) {
        if (empty($emp['nip']) || empty($emp['nama'])) {
            echo "WARNING: Empty NIP or NAMA found\n";
        }
    }
    
    echo "Data grouping successful!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
