<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

echo "=== Testing SPPD Import with Document Number ===\n\n";

// Delete existing data
\App\Models\SppdTransaction::truncate();
echo "Deleted all existing SPPD transactions\n\n";

// Import the file
$filePath = __DIR__ . '/data/sppd/Lampiran Ams - Tgl Bayar 17112025.xlsx';
$controller = new \App\Http\Controllers\SppdTransactionController();

// Create a fake UploadedFile
$uploadedFile = new UploadedFile($filePath, 'Lampiran Ams - Tgl Bayar 17112025.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

// Create request
$request = Request::create('/sppd/import', 'POST', [
    'update_existing' => false,
]);
$request->files->set('csv_file', $uploadedFile);

echo "Calling import method...\n";
$response = $controller->import($request);

echo "\nImport completed!\n\n";

// Check results
$count = \App\Models\SppdTransaction::count();
$total = \App\Models\SppdTransaction::sum('paid_amount');

echo "=== Results ===\n";
echo "Total records imported: $count\n";
echo "Total paid amount: Rp " . number_format($total) . "\n";

// Show sample data with document_number
echo "\n=== Sample Data (first 10 records) ===\n";
$samples = \App\Models\SppdTransaction::limit(10)->get(['document_number', 'trip_number', 'customer_name', 'paid_amount']);
foreach ($samples as $s) {
    echo "DocNum: {$s->document_number}, TripNum: {$s->trip_number}, Customer: {$s->customer_name}, Amount: " . number_format($s->paid_amount) . "\n";
}
