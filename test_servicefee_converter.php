<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'data/Service fee/Rekapitulasi Service Fee Juli - September 2025.xlsx';

echo "=== TEST SERVICE FEE CONVERTER ===\n\n";

// Load spreadsheet
$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

echo "Sheets found: " . count($sheetNames) . "\n";
foreach ($sheetNames as $name) {
    echo "  - $name\n";
}

// Check if it's original format
$isOriginalFormat = false;
foreach ($sheetNames as $name) {
    if (preg_match('/\s*-\s*(FL|HL)$/i', $name)) {
        $isOriginalFormat = true;
        break;
    }
}

echo "\nFormat detected: " . ($isOriginalFormat ? "ORIGINAL (with FL/HL sheets)" : "PREPROCESSED") . "\n\n";

// Test parsing a few records from each type
$hotelCount = 0;
$flightCount = 0;

foreach ($sheetNames as $sheetName) {
    if (!preg_match('/^(.+)\s*-\s*(FL|HL)$/i', $sheetName, $matches)) {
        continue;
    }
    
    $monthYear = trim($matches[1]);
    $typeCode = strtoupper($matches[2]);
    $serviceType = $typeCode === 'HL' ? 'hotel' : 'flight';
    
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $rows = $sheet->toArray();
    
    // Find header
    $headerRowIndex = null;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $rowStr = strtolower(implode(' ', array_map('strval', $rows[$i])));
        if (strpos($rowStr, 'transaction time') !== false && strpos($rowStr, 'booking id') !== false) {
            $headerRowIndex = $i;
            break;
        }
    }
    
    if ($headerRowIndex === null) continue;
    
    // Count data rows
    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $bookingId = trim((string)($rows[$i][3] ?? ''));
        if (!empty($bookingId) && is_numeric($bookingId)) {
            if ($serviceType === 'hotel') {
                $hotelCount++;
            } else {
                $flightCount++;
            }
        }
    }
}

echo "Hotel records found: $hotelCount\n";
echo "Flight records found: $flightCount\n";
echo "Total: " . ($hotelCount + $flightCount) . "\n";

// Test description parsing
echo "\n=== SAMPLE PARSING TEST ===\n";

// Hotel description test
$hotelDescriptions = [
    "SERVICE FEE BID: 1265543332 | Amaris Hotel Hertasning Makassar Smart Queen 2 ANDI FADLI",
    "SERVICE FEE BID: 1265717243 | CLARO Kendari Superior King 1 MUHAMMAD SUSGANDINATA",
    "SERVICE FEE BID: 1265877750 | The Naripan Hotel Deluxe King Bed 3 DHANI JULIANTO PUTRA",
];

echo "\nHotel Description Parsing:\n";
foreach ($hotelDescriptions as $desc) {
    $parsed = parseServiceFeeHotelDescription($desc);
    echo "  Original: " . substr($desc, 0, 60) . "...\n";
    echo "    Hotel: " . ($parsed['hotel_name'] ?? 'N/A') . "\n";
    echo "    Room: " . ($parsed['room_type'] ?? 'N/A') . "\n";
    echo "    Employee: " . ($parsed['employee_name'] ?? 'N/A') . "\n\n";
}

// Flight description test
$flightDescriptions = [
    "ONE_WAY | CGK_UPG | Pax : 1 | Airline ID : GA\nBooker: syahlan036@pln.co.id\nPassengers: SYAHLAN SYAHLAN",
    "TWO_WAY | UPG_MJU | Pax : 1\nBooker: ahmad@pln.co.id\nPassengers: AHMAD AMIRUL",
];

echo "Flight Description Parsing:\n";
foreach ($flightDescriptions as $desc) {
    $parsed = parseServiceFeeFlightDescription($desc);
    echo "  Original: " . str_replace("\n", " | ", substr($desc, 0, 60)) . "...\n";
    echo "    Route: " . ($parsed['route'] ?? 'N/A') . "\n";
    echo "    Trip: " . ($parsed['trip_type'] ?? 'N/A') . "\n";
    echo "    Pax: " . ($parsed['pax'] ?? 'N/A') . "\n";
    echo "    Airline: " . ($parsed['airline_id'] ?? 'N/A') . "\n";
    echo "    Email: " . ($parsed['booker_email'] ?? 'N/A') . "\n";
    echo "    Employee: " . ($parsed['employee_name'] ?? 'N/A') . "\n\n";
}

echo "\n✅ Test completed!\n";

// Helper functions
function parseServiceFeeHotelDescription($description) {
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

function parseServiceFeeFlightDescription($description) {
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
