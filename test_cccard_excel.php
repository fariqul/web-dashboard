<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/data/sample_cccard_test.xlsx';

echo "Testing CC Card Excel Conversion\n";
echo "================================\n\n";

try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    echo "Total rows: " . count($rows) . "\n\n";
    
    // Find header
    $headerRow = null;
    $headerRowIndex = -1;
    
    for ($i = 0; $i < min(5, count($rows)); $i++) {
        $row = $rows[$i];
        if (in_array('Booking ID', $row) || in_array('Name', $row)) {
            $headerRow = $row;
            $headerRowIndex = $i;
            break;
        }
    }
    
    if (!$headerRow) {
        echo "❌ Header not found!\n";
        exit(1);
    }
    
    echo "✓ Header found at row: $headerRowIndex\n";
    echo "Columns: " . implode(', ', array_filter($headerRow)) . "\n\n";
    
    // Find column indices
    $bookingIdCol = array_search('Booking ID', $headerRow);
    $nameCol = array_search('Name', $headerRow);
    $personelCol = array_search('Personel Number', $headerRow);
    $tripNumCol = array_search('Trip Number', $headerRow);
    $destCol = array_search('Trip Destination', $headerRow);
    $tripDateCol = array_search('Trip Date', $headerRow);
    $paymentCol = array_search('Payment', $headerRow);
    $typeCol = array_search('Transaction Type', $headerRow);
    
    echo "Column indices:\n";
    echo "  Booking ID: $bookingIdCol\n";
    echo "  Name: $nameCol\n";
    echo "  Personel Number: $personelCol\n";
    echo "  Trip Number: $tripNumCol\n";
    echo "  Trip Destination: $destCol\n";
    echo "  Trip Date: $tripDateCol\n";
    echo "  Payment: $paymentCol\n";
    echo "  Transaction Type: $typeCol\n\n";
    
    // Build CSV
    $csvLines = [];
    $csvLines[] = 'No.,Booking ID,Name,Personel Number,Trip Number,Origin,Destination,Trip Destination,Departure Date,Return Date,Duration Days,Payment,Transaction Type,Sheet';
    
    $transactionNumber = 1;
    $defaultSheetName = 'Desember 2025 - CC 5657';
    
    // Process data rows
    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        if (empty($row[$bookingIdCol])) {
            continue;
        }
        
        $bookingId = trim($row[$bookingIdCol]);
        $name = trim($row[$nameCol] ?? '');
        $personelNumber = trim($row[$personelCol] ?? '');
        $tripNumber = trim($row[$tripNumCol] ?? '');
        $tripDestination = trim($row[$destCol] ?? '');
        $tripDate = trim($row[$tripDateCol] ?? '');
        $payment = (int)preg_replace('/[^\d]/', '', $row[$paymentCol] ?? 0);
        $transactionType = strtolower(trim($row[$typeCol] ?? 'payment'));
        
        // Parse destination
        $origin = '';
        $destination = '';
        if (strpos($tripDestination, ' - ') !== false) {
            list($origin, $destination) = explode(' - ', $tripDestination, 2);
            $origin = trim($origin);
            $destination = trim($destination);
        }
        
        // Parse date
        $departureDate = '';
        $returnDate = '';
        $durationDays = 0;
        if (strpos($tripDate, ' - ') !== false) {
            list($dep, $ret) = explode(' - ', $tripDate, 2);
            
            // Parse dd/mm/yyyy
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($dep), $m)) {
                $departureDate = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($ret), $m)) {
                $returnDate = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            
            if ($departureDate && $returnDate) {
                $durationDays = round((strtotime($returnDate) - strtotime($departureDate)) / 86400);
            }
        }
        
        $csvLines[] = sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"',
            $transactionNumber++,
            $bookingId,
            $name,
            $personelNumber,
            $tripNumber,
            $origin,
            $destination,
            $tripDestination,
            $departureDate,
            $returnDate,
            $durationDays,
            $payment,
            $transactionType,
            $defaultSheetName
        );
    }
    
    echo "✓ Generated " . (count($csvLines) - 1) . " data rows\n\n";
    echo "CSV Output:\n";
    echo "==========\n";
    echo implode("\n", $csvLines) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
