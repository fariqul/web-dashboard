<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceFee;

$countNull = ServiceFee::where('service_type', 'hotel')
    ->whereNull('room_type')
    ->count();

$countEmpty = ServiceFee::where('service_type', 'hotel')
    ->where('room_type', '')
    ->count();

echo "Hotels with NULL room_type: $countNull\n";
echo "Hotels with empty string room_type: $countEmpty\n";
echo "Total: " . ($countNull + $countEmpty) . "\n";
