<?php

/**
 * Script untuk import data BFKO format ideal ke database
 * Format: nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
 */

require __DIR__ . '/../../vendor/autoload.php';

use App\Models\BfkoData;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BFKO Data Import (Format Ideal) ===\n\n";

// Path to ideal format CSV
$inputFile = __DIR__ . '/preproc/bfko_format_ideal.csv';

if (!file_exists($inputFile)) {
    echo "❌ Error: File tidak ditemukan: {$inputFile}\n";
    echo "Jalankan dulu: php clean_to_ideal_format.php\n";
    exit(1);
}

try {
    DB::beginTransaction();
    
    echo "Importing data...\n";
    
    $handle = fopen($inputFile, 'r');
    fgetcsv($handle); // Skip header
    
    $imported = 0;
    $updated = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (empty($data[0]) || empty($data[1]) || empty($data[4]) || empty($data[5]) || empty($data[6])) {
            continue;
        }
        
        // Check if record exists
        $record = BfkoData::where('nip', $data[0])
            ->where('bulan', $data[4])
            ->where('tahun', (int)$data[5])
            ->first();
        
        $dataToSave = [
            'nip' => $data[0],
            'nama' => $data[1],
            'jabatan' => $data[2] ?? '',
            'unit' => $data[3] ?? null,
            'bulan' => $data[4],
            'tahun' => (int)$data[5],
            'nilai_angsuran' => (float)$data[6],
            'tanggal_bayar' => !empty($data[7]) ? $data[7] : null,
            'status_angsuran' => $data[8] ?? null
        ];
        
        if ($record) {
            $record->update($dataToSave);
            $updated++;
        } else {
            BfkoData::create($dataToSave);
            $imported++;
        }
    }
    
    fclose($handle);
    DB::commit();
    
    echo "✓ Import complete!\n\n";
    echo "=== Statistics ===\n";
    echo "New records: {$imported}\n";
    echo "Updated records: {$updated}\n";
    echo "Total in database: " . BfkoData::count() . "\n\n";
    
    // Show summary
    $totalAmount = BfkoData::sum('nilai_angsuran');
    $totalEmployees = BfkoData::select('nip')->distinct()->count();
    
    echo "Total Pembayaran: Rp " . number_format($totalAmount, 0, ',', '.') . "\n";
    echo "Total Pegawai: {$totalEmployees}\n\n";
    
    // Top 5 employees
    echo "Top 5 Employees:\n";
    $topEmployees = BfkoData::select('nip', 'nama', DB::raw('SUM(nilai_angsuran) as total'))
        ->groupBy('nip', 'nama')
        ->orderByDesc('total')
        ->limit(5)
        ->get();
    
    foreach ($topEmployees as $idx => $emp) {
        $total = number_format($emp->total, 0, ',', '.');
        echo ($idx + 1) . ". {$emp->nama} (NIP: {$emp->nip}) - Rp {$total}\n";
    }
    
    echo "\n✅ Database ready to use!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
