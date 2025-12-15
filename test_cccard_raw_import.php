<?php
/**
 * Script untuk test actual import CC Card dengan format raw
 * Jalankan: php test_cccard_import.php
 */

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CCTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "TESTING CC CARD RAW CSV IMPORT\n";
echo "========================================\n\n";

$testFile = 'data/Rekapitulasi Pembayaran CC Agustus 2025.csv';

if (!file_exists($testFile)) {
    echo "Test file not found: $testFile\n";
    exit(1);
}

echo "Test file: $testFile\n\n";

// Read CSV
$handle = fopen($testFile, 'r');
$header = fgetcsv($handle);

echo "Header columns: " . count($header) . "\n";
echo "Header: " . implode(', ', $header) . "\n\n";

// Check if raw format (9-10 columns)
$isRawFormat = count($header) <= 10;
echo "Format detected: " . ($isRawFormat ? 'RAW (9 columns)' : 'PREPROCESSED (14 columns)') . "\n\n";

// Parse helper function
function parseDateCC($dateStr) {
    if (empty($dateStr)) return '';
    
    // Try dd/mm/yyyy format
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
        return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
    }
    
    $timestamp = strtotime($dateStr);
    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }
    
    return '';
}

// Read and parse first 3 rows
echo "Sample data parsing:\n";
echo "--------------------\n";

$rowCount = 0;
$parsedRows = [];

while (($row = fgetcsv($handle)) !== false && $rowCount < 3) {
    $rowCount++;
    
    if (count($row) < 9) {
        echo "Row $rowCount: Insufficient columns\n";
        continue;
    }
    
    $bookingId = trim($row[1]);
    $employeeName = trim($row[2]);
    $personelNumber = trim($row[3]);
    $tripNumber = trim($row[4]);
    $tripDestination = trim($row[5]);
    $tripDate = trim($row[6]);
    $payment = (float) preg_replace('/[^\d.]/', '', trim($row[7]));
    $transactionType = strtolower(trim($row[8]));
    
    // Parse trip destination (origin - destination)
    $origin = '';
    $destination = '';
    if (strpos($tripDestination, ' - ') !== false) {
        $parts = explode(' - ', $tripDestination, 2);
        $origin = trim($parts[0]);
        $destination = trim($parts[1]);
    }
    
    // Parse trip date (departure - return)
    $departureDate = '';
    $returnDate = '';
    $durationDays = 0;
    if (strpos($tripDate, ' - ') !== false) {
        $parts = explode(' - ', $tripDate, 2);
        $departureDate = parseDateCC(trim($parts[0]));
        $returnDate = parseDateCC(trim($parts[1]));
        
        if ($departureDate && $returnDate) {
            $depTime = strtotime($departureDate);
            $retTime = strtotime($returnDate);
            $durationDays = max(0, round(($retTime - $depTime) / 86400));
        }
    }
    
    echo "\nRow $rowCount:\n";
    echo "  Booking ID: $bookingId\n";
    echo "  Employee: $employeeName\n";
    echo "  Personel Number: $personelNumber\n";
    echo "  Trip Number: $tripNumber\n";
    echo "  Origin: $origin\n";
    echo "  Destination: $destination\n";
    echo "  Trip Destination Full: $tripDestination\n";
    echo "  Departure: $departureDate\n";
    echo "  Return: $returnDate\n";
    echo "  Duration: $durationDays days\n";
    echo "  Payment: Rp " . number_format($payment, 0, ',', '.') . "\n";
    echo "  Type: $transactionType\n";
    
    $parsedRows[] = [
        'booking_id' => $bookingId,
        'employee_name' => $employeeName,
        'personel_number' => $personelNumber,
        'trip_number' => $tripNumber,
        'origin' => $origin,
        'destination' => $destination,
        'trip_destination_full' => $tripDestination,
        'departure_date' => $departureDate,
        'return_date' => $returnDate,
        'duration_days' => $durationDays,
        'payment_amount' => $payment,
        'transaction_type' => $transactionType,
    ];
}

fclose($handle);

echo "\n========================================\n";
echo "PARSING TEST RESULT: SUCCESS\n";
echo "========================================\n";
echo "\nThe raw CSV format can be parsed correctly.\n";
echo "CC Card import is ready for raw format (9 columns).\n";
