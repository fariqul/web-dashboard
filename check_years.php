<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Cek tahun yang tersedia dengan query baru (handle 2 formats)
$years = \App\Models\CCTransaction::selectRaw("
    DISTINCT CASE 
        WHEN departure_date LIKE '%/%' THEN SUBSTR(departure_date, -4)
        WHEN departure_date LIKE '%-%' THEN SUBSTR(departure_date, 1, 4)
        ELSE NULL
    END as year
")
    ->orderByRaw("
        CASE 
            WHEN departure_date LIKE '%/%' THEN SUBSTR(departure_date, -4)
            WHEN departure_date LIKE '%-%' THEN SUBSTR(departure_date, 1, 4)
            ELSE NULL
        END DESC
    ")
    ->get();

echo "Available Years (Both Formats):\n";
foreach ($years as $year) {
    if ($year->year && preg_match('/^20\d{2}$/', $year->year)) {
        echo "- " . $year->year . "\n";
    }
}

// Test filter 2025
echo "\nTest Filter 2025:\n";
$test2025 = \App\Models\CCTransaction::where(function($q) {
    $q->whereRaw("SUBSTR(departure_date, -4) = ?", ['2025'])
      ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", ['2025']);
})->count();
echo "Total records: $test2025\n";

// Test filter 2026
echo "\nTest Filter 2026:\n";
$test2026 = \App\Models\CCTransaction::where(function($q) {
    $q->whereRaw("SUBSTR(departure_date, -4) = ?", ['2026'])
      ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", ['2026']);
})->count();
echo "Total records: $test2026\n";
