<?php
// Test Import BFKO ke Database

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BfkoData;
use Illuminate\Support\Facades\DB;

$file = __DIR__ . '/data/bfko/preproc/bfko_format_ideal.csv';

echo "=== BFKO Import Test ===\n\n";

// Count before
$countBefore = BfkoData::count();
echo "Data sebelum import: $countBefore\n";

// Import
$handle = fopen($file, 'r');
fgetcsv($handle); // Skip header

$imported = 0;
$updated = 0;

DB::beginTransaction();

try {
    while (($data = fgetcsv($handle)) !== false) {
        if (empty($data[0]) || empty($data[1]) || empty($data[4]) || empty($data[5]) || empty($data[6])) {
            continue;
        }
        
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
    
    DB::commit();
    echo "Import berhasil! Ditambahkan: $imported, Diupdate: $updated\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

fclose($handle);

// Count after
$countAfter = BfkoData::count();
echo "Data setelah import: $countAfter\n";
