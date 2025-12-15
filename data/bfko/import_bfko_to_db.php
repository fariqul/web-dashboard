<?php

/**
 * Script untuk import data BFKO yang sudah dibersihkan ke database
 * Run: php import_bfko_to_db.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use App\Models\BfkoEmployee;
use App\Models\BfkoPayment;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BFKO Data Import to Database ===\n\n";

// Path to clean CSV files
$employeesFile = __DIR__ . '/preproc/bfko_employees_clean.csv';
$paymentsFile = __DIR__ . '/preproc/bfko_payments_clean.csv';

try {
    DB::beginTransaction();
    
    // === IMPORT EMPLOYEES ===
    echo "Importing Employees...\n";
    
    $handle = fopen($employeesFile, 'r');
    fgetcsv($handle); // Skip header
    
    $employeeCount = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (empty($data[0])) continue;
        
        BfkoEmployee::updateOrCreate(
            ['nip' => $data[0]],
            [
                'nama_pegawai' => $data[1],
                'jabatan' => $data[2],
                'jenjang_jabatan' => !empty($data[3]) ? $data[3] : null,
                'unit' => !empty($data[4]) ? $data[4] : null,
                'status_angsuran' => !empty($data[5]) ? $data[5] : null,
                'sisa_angsuran' => !empty($data[6]) ? (float)$data[6] : null
            ]
        );
        
        $employeeCount++;
    }
    
    fclose($handle);
    echo "✓ Employees imported: {$employeeCount}\n\n";
    
    // === IMPORT PAYMENTS ===
    echo "Importing Payments...\n";
    
    $handle = fopen($paymentsFile, 'r');
    fgetcsv($handle); // Skip header
    
    $paymentCount = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (empty($data[0]) || empty($data[1]) || empty($data[2])) continue;
        
        BfkoPayment::create([
            'nip' => $data[0],
            'bulan' => $data[1],
            'tahun' => (int)$data[2],
            'nilai_angsuran' => (float)$data[3],
            'tanggal_pembayaran' => !empty($data[4]) ? $data[4] : null
        ]);
        
        $paymentCount++;
    }
    
    fclose($handle);
    echo "✓ Payments imported: {$paymentCount}\n\n";
    
    DB::commit();
    
    // === STATISTICS ===
    echo "=== Import Statistics ===\n";
    echo "Total Employees: " . BfkoEmployee::count() . "\n";
    echo "Total Payments: " . BfkoPayment::count() . "\n";
    echo "Total Payment Amount: Rp " . number_format(BfkoPayment::sum('nilai_angsuran'), 0, ',', '.') . "\n\n";
    
    // Payment breakdown by month
    echo "Payment Breakdown by Month:\n";
    $monthlyStats = BfkoPayment::select('bulan', DB::raw('COUNT(*) as count'), DB::raw('SUM(nilai_angsuran) as total'))
        ->groupBy('bulan')
        ->get();
    
    // Manual sort by month order
    $bulanOrder = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $monthlyStats = $monthlyStats->sortBy(function($item) use ($bulanOrder) {
        return array_search($item->bulan, $bulanOrder);
    });
    
    foreach ($monthlyStats as $stat) {
        echo "- {$stat->bulan}: {$stat->count} payments, Total: Rp " . number_format($stat->total, 0, ',', '.') . "\n";
    }
    
    echo "\n=== Import Complete ===\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
