<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'data/Service fee/Rekapitulasi Service Fee Juli - September 2025.xlsx';
$outputDir = 'data/Service fee/converted/';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$spreadsheet = IOFactory::load($inputFile);
$sheetNames = $spreadsheet->getSheetNames();

echo "=== SERVICE FEE EXCEL CONVERTER ===\n\n";
echo "Processing: " . $inputFile . "\n";
echo "Total Sheets: " . count($sheetNames) . "\n\n";

$allRecords = [];

foreach ($sheetNames as $sheetName) {
    echo "Processing sheet: $sheetName\n";
    
    // Parse sheet name to get month/year and type
    // Format: "Juli 2025 - FL" or "Juli 2025 - HL"
    preg_match('/^(.+)\s*-\s*(FL|HL)$/i', $sheetName, $matches);
    
    if (!$matches) {
        echo "  WARNING: Cannot parse sheet name format\n";
        continue;
    }
    
    $monthYear = trim($matches[1]); // "Juli 2025"
    $typeCode = strtoupper($matches[2]); // "FL" or "HL"
    $serviceType = $typeCode === 'HL' ? 'hotel' : 'flight';
    
    echo "  Month/Year: $monthYear, Type: $serviceType\n";
    
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $rows = $sheet->toArray();
    
    // Find header row (contains "Transaction Time", "Booking ID")
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
        echo "  WARNING: Header row not found\n";
        continue;
    }
    
    // Map column indices
    $colMap = [];
    foreach ($headerRow as $idx => $colName) {
        $cleanName = strtolower(trim((string)$colName));
        $colMap[$cleanName] = $idx;
    }
    
    // Required columns
    $transactionTimeCol = $colMap['transaction time'] ?? null;
    $bookingIdCol = $colMap['booking id'] ?? null;
    $statusCol = $colMap['status'] ?? null;
    $descriptionCol = $colMap['description'] ?? null;
    $transactionAmountCol = $colMap['transaction amount'] ?? null;
    $baseAmountCol = $colMap['base amount'] ?? null;
    
    echo "  Columns - TransTime: $transactionTimeCol, BookingID: $bookingIdCol, Description: $descriptionCol, Amount: $transactionAmountCol, BaseAmount: $baseAmountCol\n";
    
    // Process data rows
    $recordCount = 0;
    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        // Get booking ID
        $bookingId = trim((string)($row[$bookingIdCol] ?? ''));
        if (empty($bookingId) || !is_numeric($bookingId)) {
            continue;
        }
        
        // Get other fields
        $transactionTime = trim((string)($row[$transactionTimeCol] ?? ''));
        $status = trim((string)($row[$statusCol] ?? 'ISSUED'));
        $description = trim((string)($row[$descriptionCol] ?? ''));
        
        // Parse amounts - remove "Rp" and formatting
        $transactionAmountRaw = $row[$transactionAmountCol] ?? 0;
        $baseAmountRaw = $row[$baseAmountCol] ?? 0;
        
        $transactionAmount = parseAmount($transactionAmountRaw);
        $baseAmount = parseAmount($baseAmountRaw);
        
        // Parse description based on service type
        if ($serviceType === 'hotel') {
            $parsed = parseHotelDescription($description);
        } else {
            $parsed = parseFlightDescription($description);
        }
        
        $record = [
            'booking_id' => $bookingId,
            'transaction_time' => $transactionTime,
            'status' => $status,
            'service_type' => $serviceType,
            'sheet' => $monthYear,
            'transaction_amount' => $transactionAmount,
            'service_fee' => $baseAmount,
            'description' => $description,
        ];
        
        // Add parsed fields
        $record = array_merge($record, $parsed);
        
        $allRecords[] = $record;
        $recordCount++;
    }
    
    echo "  Records processed: $recordCount\n\n";
}

// Separate into Hotel and Flight records
$hotelRecords = array_filter($allRecords, fn($r) => $r['service_type'] === 'hotel');
$flightRecords = array_filter($allRecords, fn($r) => $r['service_type'] === 'flight');

echo "\n=== SUMMARY ===\n";
echo "Total Hotel Records: " . count($hotelRecords) . "\n";
echo "Total Flight Records: " . count($flightRecords) . "\n";
echo "Grand Total: " . count($allRecords) . "\n";

// Write Hotel CSV
$hotelCsvFile = $outputDir . 'service_fee_hotel.csv';
$hotelFp = fopen($hotelCsvFile, 'w');
fputcsv($hotelFp, ['Transaction Time', 'Booking ID', 'Status', 'Hotel Name', 'Room Type', 'Employee Name', 'Transaction Amount', 'Service Fee', 'Sheet']);
foreach ($hotelRecords as $record) {
    fputcsv($hotelFp, [
        $record['transaction_time'],
        $record['booking_id'],
        $record['status'],
        $record['hotel_name'] ?? '',
        $record['room_type'] ?? '',
        $record['employee_name'] ?? '',
        $record['transaction_amount'],
        $record['service_fee'],
        $record['sheet'],
    ]);
}
fclose($hotelFp);
echo "\nHotel CSV written: $hotelCsvFile\n";

// Write Flight CSV  
$flightCsvFile = $outputDir . 'service_fee_flight.csv';
$flightFp = fopen($flightCsvFile, 'w');
fputcsv($flightFp, ['Transaction Time', 'Booking ID', 'Status', 'Route', 'Trip Type', 'Pax', 'Airline ID', 'Booker Email', 'Passenger Name (Employee)', 'Transaction Amount', 'Service Fee', 'Sheet']);
foreach ($flightRecords as $record) {
    fputcsv($flightFp, [
        $record['transaction_time'],
        $record['booking_id'],
        $record['status'],
        $record['route'] ?? '',
        $record['trip_type'] ?? '',
        $record['pax'] ?? 1,
        $record['airline_id'] ?? '',
        $record['booker_email'] ?? '',
        $record['employee_name'] ?? '',
        $record['transaction_amount'],
        $record['service_fee'],
        $record['sheet'],
    ]);
}
fclose($flightFp);
echo "Flight CSV written: $flightCsvFile\n";

// Functions
function parseAmount($value) {
    if (is_numeric($value)) {
        return (int)$value;
    }
    // Remove "Rp", spaces, commas, and dots (except decimal)
    $cleaned = preg_replace('/[^\d]/', '', (string)$value);
    return (int)$cleaned;
}

function parseHotelDescription($description) {
    $result = [
        'hotel_name' => null,
        'room_type' => null,
        'employee_name' => null,
    ];
    
    // Remove "SERVICE FEE BID: xxxxx | " prefix
    $description = preg_replace('/^SERVICE FEE BID:\s*\d+\s*\|\s*/i', '', $description);
    
    if (empty($description)) {
        return $result;
    }
    
    // Try to extract employee name (usually 2-4 words in CAPS at the end)
    if (preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{2,}){0,4})$/u', $description, $matches)) {
        $result['employee_name'] = trim($matches[1]);
        $description = trim(str_replace($matches[1], '', $description));
    }
    
    // Room type patterns
    $roomTypePatterns = [
        'Deluxe King Bed', 'Deluxe Queen Bed', 'Deluxe Twin Bed',
        'Superior King Bed', 'Superior Queen Bed', 'Superior Twin Bed', 'Superior King',
        'Standard King Bed', 'Standard Queen Bed', 'Standard Twin Bed',
        'Smart Queen', 'Smart Twin', 'Smart King',
        'Superior Queen', 'Superior Twin', 'Superior Double', 'Superior Single',
        'Deluxe Queen', 'Deluxe King', 'Deluxe Twin',
        'Standard Queen', 'Standard King', 'Standard Twin',
        'Executive Queen', 'Executive King', 'Executive Suite',
        'Suite King', 'Suite Queen', 'Suite',
        'Family Room', 'Family', 'Queen', 'King', 'Twin', 'Single', 'Double', 'Triple'
    ];
    
    $roomTypePattern = implode('|', array_map('preg_quote', $roomTypePatterns));
    
    // Match room type (with optional number after)
    if (preg_match('/\b(' . $roomTypePattern . ')(?:\s+\d+)?\s*$/iu', $description, $matches)) {
        $result['room_type'] = trim($matches[1]);
        $description = trim(preg_replace('/\s*' . preg_quote($matches[0], '/') . '\s*$/', '', $description));
    }
    
    // Whatever remains is the hotel name
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
    
    // Split by newlines or |
    $parts = preg_split('/[\n|]+/', $description);
    
    foreach ($parts as $part) {
        $part = trim($part);
        
        // Trip type (ONE_WAY or TWO_WAY)
        if (preg_match('/^(ONE_WAY|TWO_WAY|ROUND_TRIP)/i', $part, $matches)) {
            $tripType = strtoupper($matches[1]);
            if ($tripType === 'ONE_WAY') {
                $result['trip_type'] = 'One Way';
            } else {
                $result['trip_type'] = 'Round Trip';
            }
        }
        
        // Route (e.g., CGK_UPG or UPG_CGK)
        if (preg_match('/([A-Z]{3})_([A-Z]{3})/', $part, $matches)) {
            $result['route'] = $matches[1] . '-' . $matches[2];
        }
        
        // Pax
        if (preg_match('/Pax\s*:\s*(\d+)/i', $part, $matches)) {
            $result['pax'] = (int)$matches[1];
        }
        
        // Airline ID
        if (preg_match('/Airline\s+ID\s*:\s*([A-Z0-9]{2})/i', $part, $matches)) {
            $result['airline_id'] = $matches[1];
        }
        
        // Booker email
        if (preg_match('/Booker:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $part, $matches)) {
            $result['booker_email'] = $matches[1];
        }
        
        // Passengers (employee name)
        if (preg_match('/Passengers?:\s*(.+)/i', $part, $matches)) {
            $result['employee_name'] = trim($matches[1]);
        }
    }
    
    return $result;
}
