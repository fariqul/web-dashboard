<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BfkoData;

echo "=== BFKO Nilai Angsuran Analysis ===\n\n";

// Get sample of highest values
echo "TOP 10 HIGHEST VALUES:\n";
echo str_repeat("-", 80) . "\n";
$sample = BfkoData::select('nip', 'nama', 'nilai_angsuran', 'bulan', 'tahun')
    ->orderBy('nilai_angsuran', 'desc')
    ->take(10)
    ->get();

foreach ($sample as $s) {
    echo sprintf("%-20s | %-30s | %s | %s/%s\n", 
        $s->nip,
        substr($s->nama, 0, 30),
        number_format($s->nilai_angsuran, 0, ',', '.'),
        $s->bulan,
        $s->tahun
    );
}

echo "\n\nSAMPLE OF TYPICAL VALUES (1-5 juta range):\n";
echo str_repeat("-", 80) . "\n";
$typical = BfkoData::select('nip', 'nama', 'nilai_angsuran', 'bulan', 'tahun')
    ->whereBetween('nilai_angsuran', [1000000, 5000000])
    ->take(10)
    ->get();

foreach ($typical as $s) {
    echo sprintf("%-20s | %-30s | %s | %s/%s\n", 
        $s->nip,
        substr($s->nama, 0, 30),
        number_format($s->nilai_angsuran, 0, ',', '.'),
        $s->bulan,
        $s->tahun
    );
}

echo "\n\nVALUE DISTRIBUTION:\n";
echo str_repeat("-", 80) . "\n";

// Count by value ranges
$ranges = [
    'Under 1 Juta' => BfkoData::where('nilai_angsuran', '<', 1000000)->count(),
    '1-10 Juta' => BfkoData::whereBetween('nilai_angsuran', [1000000, 10000000])->count(),
    '10-50 Juta' => BfkoData::whereBetween('nilai_angsuran', [10000000, 50000000])->count(),
    '50-100 Juta' => BfkoData::whereBetween('nilai_angsuran', [50000000, 100000000])->count(),
    '100-500 Juta' => BfkoData::whereBetween('nilai_angsuran', [100000000, 500000000])->count(),
    '500 Juta - 1 M' => BfkoData::whereBetween('nilai_angsuran', [500000000, 1000000000])->count(),
    'Over 1 Miliar' => BfkoData::where('nilai_angsuran', '>', 1000000000)->count(),
];

foreach ($ranges as $range => $count) {
    echo sprintf("%-20s : %d records\n", $range, $count);
}

echo "\n\nTOTAL STATS:\n";
echo str_repeat("-", 80) . "\n";
echo "Total records: " . BfkoData::count() . "\n";
echo "Total nilai: Rp " . number_format(BfkoData::sum('nilai_angsuran'), 0, ',', '.') . "\n";
echo "Average nilai: Rp " . number_format(BfkoData::avg('nilai_angsuran'), 0, ',', '.') . "\n";
echo "Min nilai: Rp " . number_format(BfkoData::min('nilai_angsuran'), 0, ',', '.') . "\n";
echo "Max nilai: Rp " . number_format(BfkoData::max('nilai_angsuran'), 0, ',', '.') . "\n";
