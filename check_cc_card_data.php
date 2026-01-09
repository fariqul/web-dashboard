<?php
// Script untuk cek data CC Card per bulan dan sheet
require 'vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as Capsule;

// Setup database connection (SQLite)
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Model
class CCTransaction extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'cc_transactions';
    public $timestamps = false;
}

$months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Cek data per bulan
echo "==== CC Card Data Per Bulan ====" . PHP_EOL;
foreach ($months as $idx => $bulan) {
    $monthNum = $idx + 1;
    $count = CCTransaction::whereNotNull('departure_date')
        ->get()
        ->filter(function($item) use ($monthNum) {
            $ok = false;
            // Format M/D/YYYY
            $date = DateTime::createFromFormat('n/j/Y', $item->departure_date);
            if ($date && (int)$date->format('n') === $monthNum) $ok = true;
            // Format YYYY-MM-DD
            $date2 = DateTime::createFromFormat('Y-m-d', $item->departure_date);
            if ($date2 && (int)$date2->format('n') === $monthNum) $ok = true;
            return $ok;
        })->count();
    $refundCount = CCTransaction::whereNull('departure_date')
        ->orWhere('departure_date', '')
        ->where('sheet', 'like', "%$bulan%")
        ->count();
    echo "$bulan: $count transaksi, $refundCount refund (tanpa tanggal)" . PHP_EOL;
}

// Cek data per sheet
echo PHP_EOL . "==== CC Card Data Per Sheet ====" . PHP_EOL;
$sheets = CCTransaction::select('sheet')->distinct()->pluck('sheet');
foreach ($sheets as $sheet) {
    $count = CCTransaction::where('sheet', $sheet)->count();
    echo "$sheet: $count transaksi" . PHP_EOL;
}
