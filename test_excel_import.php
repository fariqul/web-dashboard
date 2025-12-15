<?php
/**
 * Script untuk menguji import Excel di semua modul secara lebih detail
 * Jalankan: php test_excel_import.php
 */

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "========================================\n";
echo "TESTING EXCEL IMPORT FUNCTIONALITY\n";
echo "========================================\n\n";

// Helper function to test Excel to CSV conversion
function testBfkoExcelConversion($file) {
    echo "Testing BFKO Excel conversion...\n";
    
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Find header row
        $headerRow = null;
        $headerRowIndex = -1;
        
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = $rows[$i];
            if (in_array('NIP', $row) || in_array('No', $row)) {
                $headerRow = $row;
                $headerRowIndex = $i;
                break;
            }
        }
        
        if (!$headerRow) {
            echo "  ✗ Header not found\n";
            return false;
        }
        
        echo "  ✓ Header found at row: $headerRowIndex\n";
        
        // Find column indices
        $nipCol = array_search('NIP', $headerRow);
        $namaCol = array_search('Nama Pegawai', $headerRow);
        
        if ($nipCol === false || $namaCol === false) {
            // Try alternative column names
            $nipCol = $nipCol === false ? array_search('NIP', $headerRow) : $nipCol;
            $namaCol = $namaCol === false ? array_search('Nama', $headerRow) : $namaCol;
        }
        
        echo "  ✓ NIP column: $nipCol, Nama column: $namaCol\n";
        
        // Count data rows
        $dataCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            if (!empty($rows[$i][$nipCol])) {
                $dataCount++;
            }
        }
        
        echo "  ✓ Data rows found: $dataCount\n";
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

function testServiceFeeExcelConversion($file, $type) {
    echo "Testing Service Fee ($type) Excel conversion...\n";
    
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Find header row
        $headerRow = null;
        $headerRowIndex = -1;
        
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = $rows[$i];
            if (in_array('Booking ID', $row) || in_array('Transaction Time', $row)) {
                $headerRow = $row;
                $headerRowIndex = $i;
                break;
            }
        }
        
        if (!$headerRow) {
            echo "  ✗ Header not found\n";
            return false;
        }
        
        echo "  ✓ Header found at row: $headerRowIndex\n";
        
        $isHotel = in_array('Hotel Name', $headerRow);
        $isFlight = in_array('Route', $headerRow) || in_array('Airline ID', $headerRow);
        
        echo "  ✓ Detected type: " . ($isHotel ? 'Hotel' : ($isFlight ? 'Flight' : 'Unknown')) . "\n";
        
        // Count data rows
        $bookingIdCol = array_search('Booking ID', $headerRow);
        $dataCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            if (!empty($rows[$i][$bookingIdCol])) {
                $dataCount++;
            }
        }
        
        echo "  ✓ Data rows found: $dataCount\n";
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

function testCCCardExcelConversion($file) {
    echo "Testing CC Card Excel conversion...\n";
    
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Find header row
        $headerRow = null;
        $headerRowIndex = -1;
        
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = $rows[$i];
            if (in_array('Booking ID', $row) || in_array('Name', $row) || in_array('Trip Number', $row)) {
                $headerRow = $row;
                $headerRowIndex = $i;
                break;
            }
        }
        
        if (!$headerRow) {
            echo "  ✗ Header not found\n";
            return false;
        }
        
        echo "  ✓ Header found at row: $headerRowIndex\n";
        echo "  ✓ Columns: " . implode(', ', array_filter($headerRow)) . "\n";
        
        // Required columns
        $bookingIdCol = array_search('Booking ID', $headerRow);
        $nameCol = array_search('Name', $headerRow);
        $tripNumCol = array_search('Trip Number', $headerRow);
        
        if ($bookingIdCol === false || $nameCol === false || $tripNumCol === false) {
            echo "  ⚠ Missing required columns (Booking ID, Name, Trip Number)\n";
        } else {
            echo "  ✓ Required columns found\n";
        }
        
        // Count data rows
        $dataCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            if (!empty($rows[$i][$bookingIdCol])) {
                $dataCount++;
            }
        }
        
        echo "  ✓ Data rows found: $dataCount\n";
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

function testSppdExcelConversion($file) {
    echo "Testing SPPD Excel conversion...\n";
    
    try {
        $spreadsheet = IOFactory::load($file);
        
        $sheetNames = $spreadsheet->getSheetNames();
        echo "  ✓ Available sheets: " . implode(', ', $sheetNames) . "\n";
        
        // Try to find Sheet1 or similar
        $targetSheet = null;
        if (in_array('Sheet1', $sheetNames)) {
            $targetSheet = $spreadsheet->getSheetByName('Sheet1');
            echo "  ✓ Using Sheet1\n";
        } else {
            foreach ($sheetNames as $sheetName) {
                if (strpos($sheetName, 'Sheet1') !== false) {
                    $targetSheet = $spreadsheet->getSheetByName($sheetName);
                    echo "  ✓ Using sheet: $sheetName\n";
                    break;
                }
            }
        }
        
        if (!$targetSheet) {
            // Use first sheet
            $targetSheet = $spreadsheet->getActiveSheet();
            echo "  ⚠ Using active sheet: " . $targetSheet->getTitle() . "\n";
        }
        
        $rows = $targetSheet->toArray();
        
        // Find header row
        $headerRow = null;
        $headerRowIndex = -1;
        
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            if (in_array('Trip Number', $rows[$i])) {
                $headerRow = $rows[$i];
                $headerRowIndex = $i;
                break;
            }
        }
        
        if (!$headerRow) {
            echo "  ✗ Header with 'Trip Number' not found\n";
            return false;
        }
        
        echo "  ✓ Header found at row: $headerRowIndex\n";
        
        // Check required columns
        $requiredCols = ['Trip Number', 'Customer Name', 'Trip Destination', 'Paid Amount'];
        $foundCols = [];
        $missingCols = [];
        
        foreach ($requiredCols as $col) {
            if (in_array($col, $headerRow)) {
                $foundCols[] = $col;
            } else {
                $missingCols[] = $col;
            }
        }
        
        if (empty($missingCols)) {
            echo "  ✓ All required columns found\n";
        } else {
            echo "  ⚠ Missing columns: " . implode(', ', $missingCols) . "\n";
        }
        
        // Count data rows
        $tripNumberCol = array_search('Trip Number', $headerRow);
        $dataCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            if (!empty($rows[$i][$tripNumberCol]) && strpos($rows[$i][$tripNumberCol], 'TERBILANG') === false) {
                $dataCount++;
            }
        }
        
        echo "  ✓ Data rows found: $dataCount\n";
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run tests
echo "1. BFKO MODULE\n";
echo "   ---------------\n";
$bfkoFile = 'data/bfko/sample_bfko_test.xlsx';
if (file_exists($bfkoFile)) {
    testBfkoExcelConversion($bfkoFile);
} else {
    echo "   Sample file not found: $bfkoFile\n";
}
echo "\n";

echo "2. SERVICE FEE MODULE\n";
echo "   -------------------\n";
$sfHotelFile = 'data/sample_service_fee_hotel.xlsx';
$sfFlightFile = 'data/sample_service_fee_flight.xlsx';

if (file_exists($sfHotelFile)) {
    testServiceFeeExcelConversion($sfHotelFile, 'Hotel');
}
if (file_exists($sfFlightFile)) {
    testServiceFeeExcelConversion($sfFlightFile, 'Flight');
}
echo "\n";

echo "3. CC CARD MODULE\n";
echo "   ---------------\n";
$ccFile = 'data/sample_cccard_test.xlsx';
if (file_exists($ccFile)) {
    testCCCardExcelConversion($ccFile);
} else {
    echo "   Sample file not found: $ccFile\n";
}
echo "\n";

echo "4. SPPD MODULE\n";
echo "   ------------\n";
$sppdFile = 'data/sppd/sample_sppd_test.xlsx';
if (file_exists($sppdFile)) {
    testSppdExcelConversion($sppdFile);
} else {
    echo "   Sample file not found: $sppdFile\n";
}
echo "\n";

echo "========================================\n";
echo "TEST COMPLETED\n";
echo "========================================\n";
