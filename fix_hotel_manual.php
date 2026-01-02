<?php
/**
 * Fix hotel data - manual corrections
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix wrong extractions
$fixes = [
    // Hotel names that were incorrectly parsed
    1077 => ['hotel_name' => 'AI HOTEL JAKARTA THAMRIN'],
    1137 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG'],
    1138 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG'],
    1139 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG'],
    1140 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG'],
    663 => ['hotel_name' => 'Mercure Jakarta Simatupang', 'employee_name' => 'A HENDRA HERIANTO R'],
    1161 => ['hotel_name' => 'Swiss-Belinn Panakkukang', 'employee_name' => 'A HENDRA HERIANTO R'],
];

foreach ($fixes as $id => $data) {
    DB::table('service_fees')->where('id', $id)->update($data);
    echo "Fixed ID {$id}: " . implode(', ', array_map(fn($k, $v) => "$k=$v", array_keys($data), $data)) . "\n";
}

echo "\nDone!\n";
