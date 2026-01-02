<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$fixes = [
    663 => ['hotel_name' => 'Mercure Jakarta Simatupang', 'room_type' => 'Standard 1 King - Size Bed 2'],
    686 => ['hotel_name' => 'Pare Beach Hotel', 'room_type' => 'Deluxe Single 2', 'employee_name' => 'Ibrahim'],
    928 => ['hotel_name' => 'ibis budget Jakarta Airport', 'room_type' => 'Standard 1 Double And 1 Bunk Bed'],
    1104 => ['hotel_name' => 'ibis Styles Semarang Simpang Lima', 'room_type' => 'Superior Double Bed 1', 'employee_name' => 'YUSDI'],
    1154 => ['hotel_name' => 'Best Deal And Homey 2Br At Bale Hinggil Apartment', 'room_type' => 'Apartment'],
];

foreach ($fixes as $id => $data) {
    DB::table('service_fees')->where('id', $id)->update($data);
    echo "Fixed ID $id\n";
}
echo "Done!\n";
