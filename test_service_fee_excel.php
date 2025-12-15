<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "Testing Service Fee Excel Conversion\n";
echo "====================================\n\n";

$files = [
    'Hotel' => __DIR__ . '/data/sample_service_fee_hotel.xlsx',
    'Flight' => __DIR__ . '/data/sample_service_fee_flight.xlsx'
];

foreach ($files as $type => $file) {
    echo "Testing $type Excel:\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        echo "Total rows: " . count($rows) . "\n";
        
        // Find header
        $headerRow = $rows[0];
        echo "Header: " . implode(', ', array_slice($headerRow, 0, 5)) . "...\n";
        
        // Detect type
        $isHotel = in_array('Hotel Name', $headerRow);
        $isFlight = in_array('Route', $headerRow);
        echo "Detected as: " . ($isHotel ? 'HOTEL' : 'FLIGHT') . "\n";
        
        // Show first data row
        if (isset($rows[1])) {
            $dataRow = $rows[1];
            echo "\nFirst record:\n";
            echo "  Booking ID: " . $dataRow[2] . "\n";
            
            if ($isHotel) {
                echo "  Transaction Amount: " . number_format($dataRow[7], 0, ',', '.') . "\n";
                echo "  Service Fee: " . number_format($dataRow[8], 0, ',', '.') . "\n";
                echo "  Hotel: " . $dataRow[4] . "\n";
                echo "  Employee: " . $dataRow[6] . "\n";
            } else {
                echo "  Transaction Amount: " . number_format($dataRow[10], 0, ',', '.') . "\n";
                echo "  Service Fee: " . number_format($dataRow[11], 0, ',', '.') . "\n";
                echo "  Route: " . $dataRow[4] . "\n";
                echo "  Airline: " . $dataRow[7] . "\n";
                echo "  Passenger: " . $dataRow[9] . "\n";
            }
        }
        
        echo "\n✓ SUCCESS\n\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}
