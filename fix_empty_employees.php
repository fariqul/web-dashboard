<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Manual fixes for remaining empty employee names
$fixes = [
    668 => ['hotel_name' => 'Aryaduta Makassar', 'employee_name' => 'Baharuddin'],
    698 => ['hotel_name' => 'Luta Resort Toraja', 'employee_name' => 'Nasrun'],
    915 => ['hotel_name' => 'Aston Makassar Hotel & Convention Center', 'room_type' => 'Deluxe Business 1', 'employee_name' => 'Arif Indra Wibowo'],
];

echo "=== Fixing Empty Employee Names ===\n\n";

foreach ($fixes as $id => $data) {
    $current = DB::table('service_fees')->where('id', $id)->first();
    if ($current) {
        DB::table('service_fees')->where('id', $id)->update($data);
        echo "Fixed ID {$id}:\n";
        echo "  Before: {$current->hotel_name}\n";
        echo "  Hotel: {$data['hotel_name']}\n";
        if (isset($data['room_type'])) {
            echo "  Room: {$data['room_type']}\n";
        }
        echo "  Employee: {$data['employee_name']}\n";
        echo "\n";
    }
}

// Check remaining
$remaining = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('employee_name')
          ->orWhere('employee_name', '');
    })
    ->count();

echo "Remaining empty employee names: {$remaining}\n";
