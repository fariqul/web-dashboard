<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

ini_set('memory_limit', '512M');
set_time_limit(300);

echo "Testing BFKO PDF Export for all years...\n";

try {
    $tahun = 'all';
    
    $query = App\Models\BfkoData::query();
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
    
    $totalAll = $data->sum('nilai_angsuran');
    $yearText = 'Semua Tahun';
    
    echo "Generating PDF HTML...\n";
    
    // Generate HTML
    $html = view('exports.bfko-pdf', [
        'employees' => $employees,
        'totalAll' => $totalAll,
        'yearText' => $yearText,
        'exportDate' => now()->format('d-m-Y H:i')
    ])->render();
    
    echo "HTML generated, length: " . strlen($html) . " bytes\n";
    
    echo "Creating PDF...\n";
    
    // Use DomPDF
    $pdf = PDF::loadHTML($html);
    $pdf->setPaper('A4', 'landscape');
    
    $filename = storage_path('app/test_bfko_all.pdf');
    $pdf->save($filename);
    
    echo "PDF saved to: $filename\n";
    echo "SUCCESS!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
