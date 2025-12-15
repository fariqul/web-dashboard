<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BfkoData;

$file = fopen('data/bfko/preproc/bfko_format_ideal.csv', 'r');
$header = fgetcsv($file); // Skip header

$count = 0;
while (($row = fgetcsv($file)) !== false) {
    if (count($row) >= 9) {
        BfkoData::create([
            'nip' => $row[0],
            'nama' => $row[1],
            'jabatan' => $row[2],
            'unit' => $row[3],
            'bulan' => $row[4],
            'tahun' => $row[5],
            'nilai_angsuran' => $row[6],
            'tanggal_bayar' => !empty($row[7]) ? $row[7] : null,
            'status_angsuran' => !empty($row[8]) ? $row[8] : null,
        ]);
        $count++;
    }
}

fclose($file);

$uniqueEmployees = BfkoData::select('nip')->distinct()->count();
$totalRecords = BfkoData::count();

echo "✅ Import successful!\n";
echo "📊 Total records imported: $count\n";
echo "👥 Total unique employees: $uniqueEmployees\n";
echo "📁 Total records in database: $totalRecords\n";
