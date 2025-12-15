<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ambil semua unique departure_date untuk lihat formatnya
$allDates = \App\Models\CCTransaction::select('departure_date', 'sheet')
    ->distinct()
    ->orderBy('departure_date')
    ->limit(50)
    ->get();

echo "All Unique Date Formats:\n";
echo "========================\n";
foreach ($allDates as $date) {
    echo "Date: '{$date->departure_date}' | Sheet: {$date->sheet}\n";
}

// Cek apakah ada format YYYY-MM-DD
echo "\n\nDates with '-' (ISO format):\n";
$isoDates = \App\Models\CCTransaction::where('departure_date', 'like', '%-%')
    ->select('departure_date', 'sheet')
    ->limit(10)
    ->get();

foreach ($isoDates as $date) {
    echo "Date: '{$date->departure_date}' | Sheet: {$date->sheet}\n";
}

// Cek apakah ada format M/D/YYYY
echo "\n\nDates with '/' (M/D/YYYY format):\n";
$slashDates = \App\Models\CCTransaction::where('departure_date', 'like', '%/%')
    ->select('departure_date', 'sheet')
    ->limit(10)
    ->get();

foreach ($slashDates as $date) {
    echo "Date: '{$date->departure_date}' | Sheet: {$date->sheet}\n";
}
