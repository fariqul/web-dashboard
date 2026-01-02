<?php
/**
 * CLI Test for Service Fee Import from Original Excel
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceFee;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

echo "=== SERVICE FEE IMPORT TEST ===\n\n";

$file = 'data/Service fee/Rekapitulasi Service Fee Juli - September 2025.xlsx';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

// Check current count
$currentCount = ServiceFee::count();
echo "Current Service Fee records: $currentCount\n\n";

// Load and convert Excel
echo "Loading Excel file...\n";
$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

echo "Sheets found: " . count($sheetNames) . "\n";
foreach ($sheetNames as $name) {
    echo "  - $name\n";
}

$allRecords = [];

foreach ($sheetNames as $sheetName) {
    // Parse sheet name: "Juli 2025 - FL" or "Agustus 2025 - HL"
    if (!preg_match('/^(.+)\s*-\s*(FL|HL)$/i', $sheetName, $matches)) {
        echo "WARNING: Cannot parse sheet name: $sheetName\n";
        continue;
    }
    
    $monthYear = trim($matches[1]); // "Juli 2025"
    $typeCode = strtoupper($matches[2]); // "FL" or "HL"
    $serviceType = $typeCode === 'HL' ? 'hotel' : 'flight';
    
    echo "\nProcessing: $sheetName ($serviceType)\n";
    
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $rows = $sheet->toArray();
    
    // Find header row
    $headerRowIndex = null;
    $headerRow = null;
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $rowStr = strtolower(implode(' ', array_map('strval', $rows[$i])));
        if (strpos($rowStr, 'transaction time') !== false && strpos($rowStr, 'booking id') !== false) {
            $headerRowIndex = $i;
            $headerRow = $rows[$i];
            break;
        }
    }
    
    if ($headerRowIndex === null) {
        echo "  WARNING: Header not found!\n";
        continue;
    }
    
    // Map column indices
    $colMap = [];
    foreach ($headerRow as $idx => $colName) {
        $cleanName = strtolower(trim((string)$colName));
        $colMap[$cleanName] = $idx;
    }
    
    $recordCount = 0;
    
    // Process data rows
    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        $bookingIdCol = $colMap['booking id'] ?? 3;
        $bookingId = trim((string)($row[$bookingIdCol] ?? ''));
        
        if (empty($bookingId) || !is_numeric($bookingId)) {
            continue;
        }
        
        // Get fields
        $transactionTime = trim((string)($row[$colMap['transaction time'] ?? 2] ?? ''));
        $status = trim((string)($row[$colMap['status'] ?? 9] ?? 'ISSUED'));
        $description = trim((string)($row[$colMap['description'] ?? 10] ?? ''));
        
        // Parse amounts
        $transactionAmountRaw = $row[$colMap['transaction amount'] ?? 13] ?? 0;
        $baseAmountRaw = $row[$colMap['base amount'] ?? 14] ?? 0;
        
        $transactionAmount = parseAmount($transactionAmountRaw);
        $serviceFee = parseAmount($baseAmountRaw);
        
        // Parse description
        $parsed = $serviceType === 'hotel' 
            ? parseHotelDescription($description)
            : parseFlightDescription($description);
        
        // Parse transaction time
        try {
            $transactionDate = parseServiceFeeDate($transactionTime);
        } catch (\Exception $e) {
            $transactionDate = now();
        }
        
        $vat = floor($serviceFee * 0.11);
        
        $record = [
            'booking_id' => $bookingId,
            'merchant' => $serviceType === 'hotel' ? 'Traveloka Hotel' : 'Traveloka Flight',
            'transaction_time' => $transactionDate,
            'status' => strtolower($status),
            'transaction_amount' => $transactionAmount,
            'base_amount' => $serviceFee,
            'service_fee' => $serviceFee,
            'vat' => $vat,
            'total_tagihan' => $serviceFee + $vat,
            'service_type' => $serviceType,
            'sheet' => $monthYear,
            'description' => $description,
            'hotel_name' => $parsed['hotel_name'] ?? null,
            'room_type' => $parsed['room_type'] ?? null,
            'route' => $parsed['route'] ?? null,
            'trip_type' => $parsed['trip_type'] ?? null,
            'pax' => $parsed['pax'] ?? null,
            'airline_id' => $parsed['airline_id'] ?? null,
            'booker_email' => $parsed['booker_email'] ?? null,
            'employee_name' => $parsed['employee_name'] ?? null,
        ];
        
        $allRecords[] = $record;
        $recordCount++;
    }
    
    echo "  Records found: $recordCount\n";
}

echo "\n=== IMPORT SUMMARY ===\n";
echo "Total records to import: " . count($allRecords) . "\n";

$hotelCount = count(array_filter($allRecords, fn($r) => $r['service_type'] === 'hotel'));
$flightCount = count(array_filter($allRecords, fn($r) => $r['service_type'] === 'flight'));

echo "  Hotels: $hotelCount\n";
echo "  Flights: $flightCount\n";

// Auto-confirm for testing
echo "\nImporting...\n";

$imported = 0;
$skipped = 0;
$errors = [];

foreach ($allRecords as $record) {
    try {
        // Check for duplicate
        $existing = ServiceFee::where('booking_id', $record['booking_id'])->first();
        if ($existing) {
            $skipped++;
            continue;
        }
        
        ServiceFee::create($record);
        $imported++;
    } catch (\Exception $e) {
        $errors[] = "Error importing {$record['booking_id']}: " . $e->getMessage();
        $skipped++;
    }
}

echo "\n=== IMPORT COMPLETED ===\n";
echo "Imported: $imported\n";
echo "Skipped (duplicates): $skipped\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nFirst 5 errors:\n";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "  - $error\n";
    }
}

$newCount = ServiceFee::count();
echo "\nTotal Service Fee records now: $newCount\n";

// Helper functions
function parseAmount($value) {
    if (is_numeric($value)) {
        return (int)$value;
    }
    $cleaned = preg_replace('/[^\d]/', '', (string)$value);
    return (int)$cleaned;
}

function parseServiceFeeDate($dateString) {
    // Handle formats like "01 Jul 2025, 17:08:28"
    $monthMap = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'Mei' => 'May', 'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 
        'Aug' => 'Aug', 'Agt' => 'Aug', 'Sep' => 'Sep', 'Oct' => 'Oct', 
        'Okt' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dec', 'Des' => 'Dec'
    ];
    
    foreach ($monthMap as $from => $to) {
        if (stripos($dateString, $from) !== false) {
            $dateString = str_ireplace($from, $to, $dateString);
            break;
        }
    }
    
    return \Carbon\Carbon::parse($dateString);
}

function parseHotelDescription($description) {
    $result = [
        'hotel_name' => null,
        'room_type' => null,
        'employee_name' => null,
    ];
    
    $description = preg_replace('/^SERVICE FEE BID:\s*\d+\s*\|\s*/i', '', $description);
    
    if (empty($description)) {
        return $result;
    }
    
    if (preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{2,}){0,4})$/u', $description, $matches)) {
        $result['employee_name'] = trim($matches[1]);
        $description = trim(str_replace($matches[1], '', $description));
    }
    
    $roomTypePatterns = [
        'Deluxe King Bed', 'Deluxe Queen Bed', 'Deluxe Twin Bed',
        'Superior King Bed', 'Superior Queen Bed', 'Superior Twin Bed', 'Superior King',
        'Smart Queen', 'Smart Twin', 'Smart King',
        'Superior Queen', 'Superior Twin', 'Superior Double', 'Superior Single',
        'Deluxe Queen', 'Deluxe King', 'Deluxe Twin',
        'Family Room', 'Family', 'Queen', 'King', 'Twin', 'Single', 'Double'
    ];
    
    $roomTypePattern = implode('|', array_map('preg_quote', $roomTypePatterns));
    
    if (preg_match('/\b(' . $roomTypePattern . ')(?:\s+\d+)?\s*$/iu', $description, $matches)) {
        $result['room_type'] = trim($matches[1]);
        $description = trim(preg_replace('/\s*' . preg_quote($matches[0], '/') . '\s*$/', '', $description));
    }
    
    if (!empty($description)) {
        $result['hotel_name'] = trim($description);
    }
    
    return $result;
}

function parseFlightDescription($description) {
    $result = [
        'route' => null,
        'trip_type' => null,
        'pax' => 1,
        'airline_id' => null,
        'booker_email' => null,
        'employee_name' => null,
    ];
    
    $parts = preg_split('/[\n|]+/', $description);
    
    foreach ($parts as $part) {
        $part = trim($part);
        
        if (preg_match('/^(ONE_WAY|TWO_WAY|ROUND_TRIP)/i', $part, $matches)) {
            $tripType = strtoupper($matches[1]);
            $result['trip_type'] = $tripType === 'ONE_WAY' ? 'One Way' : 'Round Trip';
        }
        
        if (preg_match('/([A-Z]{3})_([A-Z]{3})/', $part, $matches)) {
            $result['route'] = $matches[1] . '-' . $matches[2];
        }
        
        if (preg_match('/Pax\s*:\s*(\d+)/i', $part, $matches)) {
            $result['pax'] = (int)$matches[1];
        }
        
        if (preg_match('/Airline\s+ID\s*:\s*([A-Z0-9]{2})/i', $part, $matches)) {
            $result['airline_id'] = $matches[1];
        }
        
        if (preg_match('/Booker:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $part, $matches)) {
            $result['booker_email'] = $matches[1];
        }
        
        if (preg_match('/Passengers?:\s*(.+)/i', $part, $matches)) {
            $result['employee_name'] = trim($matches[1]);
        }
    }
    
    return $result;
}
