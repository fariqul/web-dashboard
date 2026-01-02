<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceFee;

echo "=== SERVICE FEE DATA SUMMARY ===\n\n";
echo "Total records: " . ServiceFee::count() . "\n";
echo "Hotels: " . ServiceFee::where('service_type', 'hotel')->count() . "\n";
echo "Flights: " . ServiceFee::where('service_type', 'flight')->count() . "\n";

echo "\nBy Sheet:\n";
echo str_repeat('-', 80) . "\n";
printf("%-20s | %-10s | %-8s | %s\n", "Sheet", "Type", "Records", "Total Fee");
echo str_repeat('-', 80) . "\n";

$results = ServiceFee::selectRaw('sheet, service_type, count(*) as count, sum(service_fee) as total_fee')
    ->groupBy('sheet', 'service_type')
    ->orderBy('sheet')
    ->get();

foreach ($results as $r) {
    printf("%-20s | %-10s | %8d | Rp %s\n", 
        $r->sheet, 
        $r->service_type, 
        $r->count, 
        number_format($r->total_fee, 0, ',', '.')
    );
}

echo str_repeat('-', 80) . "\n";

// Grand totals
$totalFee = ServiceFee::sum('service_fee');
$totalVat = ServiceFee::sum('vat');
$totalTagihan = $totalFee + $totalVat;

echo "\n=== GRAND TOTAL ===\n";
echo "Total Service Fee: Rp " . number_format($totalFee, 0, ',', '.') . "\n";
echo "Total VAT (11%): Rp " . number_format($totalVat, 0, ',', '.') . "\n";
echo "Total Tagihan: Rp " . number_format($totalTagihan, 0, ',', '.') . "\n";

// Sample data check
echo "\n=== SAMPLE DATA (5 records) ===\n";
$samples = ServiceFee::take(5)->get();
foreach ($samples as $s) {
    echo "- [{$s->service_type}] {$s->booking_id} | {$s->sheet} | ";
    if ($s->service_type === 'hotel') {
        echo $s->hotel_name . " | " . $s->employee_name;
    } else {
        echo $s->route . " | " . $s->airline_id . " | " . $s->employee_name;
    }
    echo "\n";
}
