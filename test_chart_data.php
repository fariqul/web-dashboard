<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate filter year 2025
$yearForComparison = '2025';

$allTxQuery = \App\Models\CCTransaction::query();
$allTxQuery->where(function($q) use ($yearForComparison) {
    $q->whereRaw("SUBSTR(departure_date, -4) = ?", [$yearForComparison]) // M/D/YYYY format
      ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", [$yearForComparison]); // YYYY-MM-DD format
});

$allTransactions = $allTxQuery->get();

echo "Total Transactions for 2025: " . $allTransactions->count() . "\n";
echo "Unique Sheets: " . $allTransactions->pluck('sheet')->unique()->count() . "\n\n";

// Group by sheet
$grouped = $allTransactions->groupBy('sheet');

echo "Sheets Found:\n";
foreach ($grouped as $sheetName => $group) {
    echo "- $sheetName: {$group->count()} transactions\n";
}

// Month ordering map
$monthOrder = [
    'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
    'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
    'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
];

echo "\n\nProcessing for Chart Data:\n";
$sheetComparison = $allTransactions
    ->groupBy('sheet')
    ->map(function($group, $sheetName) use ($monthOrder) {
        $payments = $group->where('transaction_type', 'payment');
        $refunds = $group->where('transaction_type', 'refund');
        
        // Singkat label sheet untuk chart
        $sheetLabel = $sheetName;
        $monthName = '';
        
        if (strlen($sheetLabel) > 15) {
            if (str_contains($sheetLabel, 'September')) {
                $ccNum = str_contains($sheetLabel, '5657') ? '5657' : '9386';
                $sheetLabel = "Sep ($ccNum)";
                $monthName = 'September';
            } else {
                $parts = explode(' ', $sheetLabel);
                $monthName = $parts[0];
                $monthMap = [
                    'Juli' => 'Juli', 'Agustus' => 'Agus', 'September' => 'Sep',
                    'Oktober' => 'Okt', 'November' => 'Nov', 'Desember' => 'Des',
                    'Januari' => 'Jan', 'Februari' => 'Feb', 'Maret' => 'Mar',
                    'April' => 'Apr', 'Mei' => 'Mei', 'Juni' => 'Jun'
                ];
                $sheetLabel = $monthMap[$parts[0]] ?? $parts[0];
            }
        } else {
            $parts = explode(' ', $sheetName);
            $monthName = $parts[0];
        }
        
        // Extract year from sheet name
        preg_match('/\d{4}/', $sheetName, $yearMatch);
        $year = isset($yearMatch[0]) ? (int)$yearMatch[0] : 2025;
        
        $grossPaymentAmount = $payments->sum('payment_amount');
        $refundAmount = $refunds->sum('payment_amount');
        $netPaymentAmount = $grossPaymentAmount - $refundAmount;
        
        $sheetFee = \App\Models\SheetAdditionalFee::where('sheet_name', $sheetName)->first();
        $additionalFee = $sheetFee ? ($sheetFee->biaya_adm_bunga + $sheetFee->biaya_transfer + $sheetFee->iuran_tahunan) : 0;
        $totalPaymentWithFees = $netPaymentAmount + $additionalFee;
        
        return [
            'sheet' => $sheetLabel,
            'payment' => round($totalPaymentWithFees / 1000000, 1),
            'refund' => round($refundAmount / 1000000, 1),
            'total' => round($totalPaymentWithFees / 1000000, 1),
            'monthOrder' => $monthOrder[$monthName] ?? 99,
            'year' => $year,
            'fullName' => $sheetName
        ];
    })
    ->sort(function($a, $b) {
        if ($a['year'] != $b['year']) {
            return $a['year'] - $b['year'];
        }
        return $a['monthOrder'] - $b['monthOrder'];
    })
    ->values();

echo "\nChart Data (JSON):\n";
echo json_encode($sheetComparison, JSON_PRETTY_PRINT);
