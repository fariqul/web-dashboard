<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BfkoData;

echo "Testing distinct() count:\n";
echo "========================\n\n";

// Method 1: select('nip')->distinct()->count()
$method1 = BfkoData::select('nip')->distinct()->count();
echo "Method 1 - select('nip')->distinct()->count(): $method1\n";

// Method 2: select('nip')->distinct()->get()->count()
$method2 = BfkoData::select('nip')->distinct()->get()->count();
echo "Method 2 - select('nip')->distinct()->get()->count(): $method2\n";

// Method 3: groupBy('nip')->count()
$method3 = BfkoData::groupBy('nip')->count();
echo "Method 3 - groupBy('nip')->count(): $method3\n";

// Method 4: Raw SQL
$method4 = BfkoData::distinct('nip')->count('nip');
echo "Method 4 - distinct('nip')->count('nip'): $method4\n";

// Method 5: Using DB facade
$method5 = \DB::table('bfko_data')->distinct()->count('nip');
echo "Method 5 - DB::table()->distinct()->count('nip'): $method5\n";

echo "\n✅ Correct answer should be: 19\n";
