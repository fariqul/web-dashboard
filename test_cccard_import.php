<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CCTransaction;
use App\Models\SheetAdditionalFee;

echo "Testing CC Card CSV Import\n";
echo "=========================\n\n";

$csvData = 'No.,Booking ID,Name,Personel Number,Trip Number,Origin,Destination,Trip Destination,Departure Date,Return Date,Duration Days,Payment,Transaction Type,Sheet
"1","1270119439","MUHAMMAD SUSGANDINATA","86097601","4120176655","Kota Makassar","Kota Yogyakarta","Kota Makassar - Kota Yogyakarta","2025-07-16","2025-07-19","3","413690","payment","Desember 2025 - CC 5657"
"2","1270146017","KIKI RESKI ANDRIANI","93153811","4120176640","Kota Makassar","Kota Semarang","Kota Makassar - Kota Semarang","2025-07-15","2025-07-19","4","865046","payment","Desember 2025 - CC 5657"
"3","1270116243","ERIK WICAKSONO","90087609","4120176608","Kota Kendari","Kota Makassar","Kota Kendari - Kota Makassar","2025-07-16","2025-07-18","2","925400","payment","Desember 2025 - CC 5657"';

// Write to temp file
$tempFile = tmpfile();
fwrite($tempFile, $csvData);
$tempFilePath = stream_get_meta_data($tempFile)['uri'];

echo "Temp file: $tempFilePath\n\n";

// Open and read
$handle = fopen($tempFilePath, 'r');

if (!$handle) {
    echo "❌ Failed to open temp file!\n";
    exit(1);
}

echo "✓ Temp file opened\n";

$header = fgetcsv($handle);
echo "Header columns: " . count($header) . "\n";
echo "Header: " . implode(', ', $header) . "\n\n";

if (count($header) != 14) {
    echo "❌ Expected 14 columns, got " . count($header) . "\n";
    fclose($handle);
    exit(1);
}

$imported = 0;
$updated = 0;
$rowNum = 1;

while (($data = fgetcsv($handle)) !== FALSE) {
    $rowNum++;
    
    if (count($data) != 14) {
        echo "⚠️ Row $rowNum: Expected 14 columns, got " . count($data) . " - SKIPPED\n";
        continue;
    }
    
    list($no, $bookingId, $name, $personelNumber, $tripNumber, $origin, $destination, 
         $tripDestination, $departureDate, $returnDate, $durationDays, $payment, $transactionType, $sheetName) = $data;
    
    echo "Row $rowNum: Booking ID=$bookingId, Name=$name, Payment=$payment, Sheet=$sheetName\n";
    
    // Find or create sheet
    $sheet = SheetAdditionalFee::firstOrCreate(
        ['sheet_name' => $sheetName],
        ['additional_fee' => 0]
    );
    
    // Check existing
    $existing = CCTransaction::where('booking_id', $bookingId)->first();
    
    if ($existing) {
        echo "  → Updating existing transaction\n";
        $existing->update([
            'employee_name' => $name,
            'personel_number' => $personelNumber,
            'trip_number' => $tripNumber,
            'origin' => $origin,
            'destination' => $destination,
            'trip_destination_full' => $tripDestination,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'duration_days' => (int)$durationDays,
            'payment_amount' => (int)$payment,
            'transaction_type' => $transactionType,
            'sheet' => $sheetName,
            'status' => 'active',
        ]);
        $updated++;
    } else {
        echo "  → Creating new transaction\n";
        CCTransaction::create([
            'transaction_number' => (int)$no,
            'booking_id' => $bookingId,
            'employee_name' => $name,
            'personel_number' => $personelNumber,
            'trip_number' => $tripNumber,
            'origin' => $origin,
            'destination' => $destination,
            'trip_destination_full' => $tripDestination,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'duration_days' => (int)$durationDays,
            'payment_amount' => (int)$payment,
            'transaction_type' => $transactionType,
            'sheet' => $sheetName,
            'status' => 'active',
        ]);
        $imported++;
    }
}

fclose($handle);
fclose($tempFile);

echo "\n✓ Import completed!\n";
echo "  New: $imported\n";
echo "  Updated: $updated\n";
echo "  Total rows processed: " . ($imported + $updated) . "\n";

// Check database
$total = CCTransaction::count();
echo "\nTotal CC Transactions in database: $total\n";
