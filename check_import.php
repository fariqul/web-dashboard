<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BfkoData;

$totalRecords = BfkoData::count();
$uniqueEmployees = BfkoData::select('nip')->distinct()->count();
$testNipCount = BfkoData::where('nip', '7194010G')->count();
$testNipMonths = BfkoData::where('nip', '7194010G')->pluck('bulan')->toArray();

echo "📊 Total records: $totalRecords\n";
echo "👥 Unique employees: $uniqueEmployees\n";
echo "🔍 Records for NIP 7194010G: $testNipCount\n";
echo "📅 Months: " . implode(', ', $testNipMonths) . "\n";
