<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check specific IDs
$ids = [663, 1077, 1137, 1165, 1027];
foreach ($ids as $id) {
    $h = App\Models\ServiceFee::find($id);
    echo "ID: {$h->id}\n";
    echo "Hotel: {$h->hotel_name}\n";
    echo "Room: {$h->room_type}\n";
    echo "Employee: {$h->employee_name}\n";
    echo "---\n";
}
