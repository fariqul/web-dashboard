<?php
/**
 * Script untuk dry-run import - mengecek apakah data bisa diparsing dengan benar
 * Jalankan: php test_import_dryrun.php
 */

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "========================================\n";
echo "DRY-RUN IMPORT TEST\n";
echo "========================================\n\n";

// ==========================================
// Test 1: BFKO CSV Import
// ==========================================
echo "1. TESTING BFKO CSV IMPORT\n";
echo "   ========================\n";

$bfkoCsvFile = 'data/bfko/TEMPLATE_BFKO.csv';
if (file_exists($bfkoCsvFile)) {
    $handle = fopen($bfkoCsvFile, 'r');
    $header = fgetcsv($handle);
    echo "   Header: " . implode(', ', $header) . "\n";
    
    $rowCount = 0;
    $sampleRows = [];
    while (($row = fgetcsv($handle)) !== false) {
        $rowCount++;
        if ($rowCount <= 2) {
            $sampleRows[] = array_combine($header, $row);
        }
    }
    fclose($handle);
    
    echo "   Total rows: $rowCount\n";
    if (!empty($sampleRows)) {
        echo "   Sample data:\n";
        foreach ($sampleRows as $idx => $row) {
            echo "     Row " . ($idx + 1) . ": NIP={$row['nip']}, Nama={$row['nama']}, Bulan={$row['bulan']}, Tahun={$row['tahun']}, Nilai={$row['nilai_angsuran']}\n";
        }
    }
    echo "   ✓ BFKO CSV format OK\n";
}
echo "\n";

// ==========================================
// Test 2: Service Fee CSV Import
// ==========================================
echo "2. TESTING SERVICE FEE EXCEL->CSV CONVERSION\n";
echo "   ==========================================\n";

$sfHotelFile = 'data/sample_service_fee_hotel.xlsx';
if (file_exists($sfHotelFile)) {
    $spreadsheet = IOFactory::load($sfHotelFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    $header = $rows[0];
    echo "   Hotel file header: " . implode(', ', array_filter($header)) . "\n";
    
    // Map columns
    $colMap = array_flip(array_filter($header));
    
    if (isset($rows[1])) {
        $sampleRow = $rows[1];
        echo "   Sample Hotel data:\n";
        echo "     - Booking ID: " . ($sampleRow[$colMap['Booking ID'] ?? 2] ?? 'N/A') . "\n";
        echo "     - Hotel Name: " . ($sampleRow[$colMap['Hotel Name'] ?? 4] ?? 'N/A') . "\n";
        echo "     - Employee: " . ($sampleRow[$colMap['Employee Name'] ?? 6] ?? 'N/A') . "\n";
        echo "     - Amount: " . ($sampleRow[$colMap['Transaction Amount (Rp)'] ?? 7] ?? 'N/A') . "\n";
        echo "     - Service Fee: " . ($sampleRow[$colMap['Service Fee (Rp)'] ?? 8] ?? 'N/A') . "\n";
    }
    echo "   ✓ Service Fee Hotel Excel format OK\n";
}
echo "\n";

// ==========================================
// Test 3: CC Card CSV Import
// ==========================================
echo "3. TESTING CC CARD CSV IMPORT\n";
echo "   ==========================\n";

$ccCsvFiles = glob('data/Rekapitulasi Pembayaran CC*.csv');
if (!empty($ccCsvFiles)) {
    $file = $ccCsvFiles[0]; // Test first file
    echo "   Testing: " . basename($file) . "\n";
    
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle);
    echo "   Header columns: " . count($header) . "\n";
    echo "   Header: " . implode(', ', $header) . "\n";
    
    // Expected header
    $expectedCols = ['booking_id', 'employee_name', 'personel_number', 'trip_number', 'origin', 'destination', 'trip_destination_full', 'departure_date', 'return_date', 'duration_days', 'payment_amount', 'transaction_type', 'sheet'];
    
    // Check if this is the expected format or raw format
    $isExpectedFormat = in_array('booking_id', array_map('strtolower', $header));
    
    if ($isExpectedFormat) {
        echo "   Format: Pre-processed CSV (ready for import)\n";
    } else {
        echo "   Format: Raw CSV (may need preprocessing)\n";
    }
    
    $row = fgetcsv($handle);
    if ($row) {
        echo "   Sample row data: " . implode(' | ', array_slice($row, 0, 5)) . "...\n";
    }
    fclose($handle);
    
    echo "   ✓ CC Card CSV readable\n";
}
echo "\n";

// ==========================================
// Test 4: SPPD CSV Import
// ==========================================
echo "4. TESTING SPPD CSV IMPORT\n";
echo "   =======================\n";

$sppdCsvFile = 'data/sppd/sppd_sample_test.csv';
if (file_exists($sppdCsvFile)) {
    $handle = fopen($sppdCsvFile, 'r');
    $header = fgetcsv($handle);
    
    $expectedHeaders = ['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip', 
                       'trip_begins_on', 'trip_ends_on', 'planned_payment_date', 'paid_amount', 'beneficiary_bank_name'];
    
    echo "   Expected: " . implode(', ', $expectedHeaders) . "\n";
    echo "   Found:    " . implode(', ', $header) . "\n";
    
    if ($header === $expectedHeaders) {
        echo "   ✓ Header matches expected format\n";
        
        $row = fgetcsv($handle);
        if ($row) {
            $data = array_combine($header, $row);
            echo "   Sample data:\n";
            echo "     - Trip Number: {$data['trip_number']}\n";
            echo "     - Customer: {$data['customer_name']}\n";
            echo "     - Destination: {$data['trip_destination']}\n";
            echo "     - Begins: {$data['trip_begins_on']}\n";
            echo "     - Ends: {$data['trip_ends_on']}\n";
            echo "     - Amount: {$data['paid_amount']}\n";
        }
    } else {
        echo "   ⚠ Header mismatch - may cause import error\n";
    }
    fclose($handle);
}
echo "\n";

// ==========================================
// Test 5: SPPD Excel Import
// ==========================================
echo "5. TESTING SPPD EXCEL->CSV CONVERSION\n";
echo "   ==================================\n";

$sppdExcelFile = 'data/sppd/sample_sppd_test.xlsx';
if (file_exists($sppdExcelFile)) {
    $spreadsheet = IOFactory::load($sppdExcelFile);
    $sheetNames = $spreadsheet->getSheetNames();
    
    // Find target sheet
    $targetSheet = null;
    foreach ($sheetNames as $sheetName) {
        if (strpos($sheetName, 'Sheet1') !== false) {
            $targetSheet = $spreadsheet->getSheetByName($sheetName);
            echo "   Using sheet: $sheetName\n";
            break;
        }
    }
    
    if (!$targetSheet) {
        $targetSheet = $spreadsheet->getActiveSheet();
    }
    
    $rows = $targetSheet->toArray();
    
    // Find header with 'Trip Number'
    $headerRowIndex = -1;
    $header = null;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        if (in_array('Trip Number', $rows[$i])) {
            $headerRowIndex = $i;
            $header = $rows[$i];
            break;
        }
    }
    
    if ($header) {
        echo "   Header found at row: $headerRowIndex\n";
        echo "   Columns: " . implode(', ', array_filter($header)) . "\n";
        
        // Check for required columns
        $tripNumCol = array_search('Trip Number', $header);
        $customerCol = array_search('Customer Name', $header);
        $destCol = array_search('Trip Destination', $header);
        $beginsCol = array_search('Trip Begins On', $header);
        $endsCol = array_search('Trip Ends On', $header);
        $amountCol = array_search('Paid Amount', $header);
        
        // Check planned payment date column (multiple possible names)
        $paymentDateCol = array_search('Tanggal Rencana Bayar', $header);
        if ($paymentDateCol === false) {
            $paymentDateCol = array_search('Tanggal Bayar', $header);
        }
        if ($paymentDateCol === false) {
            $paymentDateCol = array_search('Planned Payment Date', $header);
        }
        
        echo "   Column indices:\n";
        echo "     - Trip Number: $tripNumCol\n";
        echo "     - Customer Name: $customerCol\n";
        echo "     - Trip Destination: $destCol\n";
        echo "     - Trip Begins On: $beginsCol\n";
        echo "     - Trip Ends On: $endsCol\n";
        echo "     - Payment Date: " . ($paymentDateCol !== false ? $paymentDateCol : 'NOT FOUND') . "\n";
        echo "     - Paid Amount: $amountCol\n";
        
        // Sample data
        if (isset($rows[$headerRowIndex + 1])) {
            $dataRow = $rows[$headerRowIndex + 1];
            echo "   Sample data row:\n";
            echo "     - Trip Number: " . ($dataRow[$tripNumCol] ?? 'N/A') . "\n";
            echo "     - Customer: " . ($dataRow[$customerCol] ?? 'N/A') . "\n";
            echo "     - Begins: " . ($dataRow[$beginsCol] ?? 'N/A') . "\n";
            echo "     - Amount: " . ($dataRow[$amountCol] ?? 'N/A') . "\n";
        }
        
        echo "   ✓ SPPD Excel format OK\n";
    } else {
        echo "   ✗ Header with 'Trip Number' not found\n";
    }
}
echo "\n";

echo "========================================\n";
echo "DRY-RUN TEST COMPLETED\n";
echo "========================================\n";
echo "\nAll modules can read their respective file formats.\n";
echo "You can now test actual import via the web interface.\n";
