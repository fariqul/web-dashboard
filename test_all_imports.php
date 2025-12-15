<?php
/**
 * Script untuk menguji import CSV/Excel di semua modul
 * Jalankan: php test_all_imports.php
 */

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "TESTING IMPORT FUNCTIONALITY\n";
echo "========================================\n\n";

// ==========================================
// 1. Test BFKO Import
// ==========================================
echo "1. BFKO MODULE\n";
echo "   - CSV format: NIP,Nama Pegawai,Jabatan,Unit,Bulan,Tahun,Nilai Angsuran,Tanggal Bayar,Status\n";

$bfkoSampleFile = 'data/bfko/sample_bfko_test.xlsx';
if (file_exists($bfkoSampleFile)) {
    echo "   ✓ Sample file exists: $bfkoSampleFile\n";
    
    // Test Excel converter
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($bfkoSampleFile);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        echo "   ✓ Excel file readable, rows: " . count($rows) . "\n";
        
        // Check header
        $foundHeader = false;
        foreach ($rows as $idx => $row) {
            if (in_array('NIP', $row) || in_array('No', $row)) {
                $foundHeader = true;
                echo "   ✓ Header found at row: $idx\n";
                break;
            }
        }
        if (!$foundHeader) {
            echo "   ⚠ Header not found (looking for NIP or No column)\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error reading Excel: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠ Sample file not found: $bfkoSampleFile\n";
}

// Check CSV template
$bfkoCsvTemplate = 'data/bfko/TEMPLATE_BFKO.csv';
if (file_exists($bfkoCsvTemplate)) {
    echo "   ✓ CSV Template exists: $bfkoCsvTemplate\n";
    $header = fgetcsv(fopen($bfkoCsvTemplate, 'r'));
    echo "   ✓ CSV header columns: " . implode(', ', $header) . "\n";
}

echo "\n";

// ==========================================
// 2. Test Service Fee Import
// ==========================================
echo "2. SERVICE FEE MODULE\n";
echo "   - Supports Hotel and Flight service types\n";

$sfHotelFile = 'data/sample_service_fee_hotel.xlsx';
$sfFlightFile = 'data/sample_service_fee_flight.xlsx';

foreach ([$sfHotelFile => 'Hotel', $sfFlightFile => 'Flight'] as $file => $type) {
    if (file_exists($file)) {
        echo "   ✓ $type sample exists: $file\n";
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            echo "     - Rows: " . count($rows) . "\n";
            
            // Find header
            $headerRow = null;
            for ($i = 0; $i < min(5, count($rows)); $i++) {
                if (in_array('Booking ID', $rows[$i]) || in_array('Transaction Time', $rows[$i])) {
                    $headerRow = $rows[$i];
                    echo "     - Header at row $i: " . implode(', ', array_filter($headerRow)) . "\n";
                    break;
                }
            }
        } catch (Exception $e) {
            echo "     ✗ Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠ $type sample not found: $file\n";
    }
}

echo "\n";

// ==========================================
// 3. Test CC Card Import
// ==========================================
echo "3. CC CARD MODULE\n";
echo "   - CSV header: No.,Booking ID,Name,Personel Number,Trip Number,Origin,Destination,Trip Destination,Departure Date,Return Date,Duration Days,Payment,Transaction Type,Sheet\n";

$ccCardFiles = glob('data/Rekapitulasi Pembayaran CC*.csv');
if (!empty($ccCardFiles)) {
    foreach ($ccCardFiles as $file) {
        echo "   ✓ CSV file: $file\n";
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        echo "     - Header columns: " . count($header) . "\n";
        fclose($handle);
    }
}

$ccCardExcelFile = 'data/sample_cccard_test.xlsx';
if (file_exists($ccCardExcelFile)) {
    echo "   ✓ Excel sample exists: $ccCardExcelFile\n";
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($ccCardExcelFile);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        echo "     - Rows: " . count($rows) . "\n";
        
        // Find header
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            if (in_array('Booking ID', $rows[$i]) || in_array('Name', $rows[$i])) {
                echo "     - Header at row $i\n";
                break;
            }
        }
    } catch (Exception $e) {
        echo "     ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ==========================================
// 4. Test SPPD Import
// ==========================================
echo "4. SPPD MODULE\n";
echo "   - CSV header: trip_number,customer_name,trip_destination,reason_for_trip,trip_begins_on,trip_ends_on,planned_payment_date,paid_amount,beneficiary_bank_name\n";

$sppdCsvFile = 'data/sppd/sppd_sample_test.csv';
if (file_exists($sppdCsvFile)) {
    echo "   ✓ CSV sample: $sppdCsvFile\n";
    $handle = fopen($sppdCsvFile, 'r');
    $header = fgetcsv($handle);
    echo "     - Header: " . implode(', ', $header) . "\n";
    
    // Validate header
    $expectedHeaders = ['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip', 
                       'trip_begins_on', 'trip_ends_on', 'planned_payment_date', 'paid_amount', 'beneficiary_bank_name'];
    if ($header === $expectedHeaders) {
        echo "     ✓ Header format is correct!\n";
    } else {
        echo "     ⚠ Header mismatch. Expected: " . implode(', ', $expectedHeaders) . "\n";
    }
    fclose($handle);
}

$sppdExcelFile = 'data/sppd/sample_sppd_test.xlsx';
if (file_exists($sppdExcelFile)) {
    echo "   ✓ Excel sample: $sppdExcelFile\n";
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($sppdExcelFile);
        
        // Check available sheets
        $sheetNames = $spreadsheet->getSheetNames();
        echo "     - Sheets: " . implode(', ', $sheetNames) . "\n";
        
        // Check for Sheet1
        if (in_array('Sheet1', $sheetNames)) {
            $sheet = $spreadsheet->getSheetByName('Sheet1');
            $rows = $sheet->toArray();
            echo "     - Sheet1 rows: " . count($rows) . "\n";
            
            // Find header
            for ($i = 0; $i < min(10, count($rows)); $i++) {
                if (in_array('Trip Number', $rows[$i])) {
                    echo "     - Header at row $i: " . implode(', ', array_filter($rows[$i])) . "\n";
                    break;
                }
            }
        }
    } catch (Exception $e) {
        echo "     ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ==========================================
// Summary
// ==========================================
echo "========================================\n";
echo "CURRENT DATA COUNTS\n";
echo "========================================\n";
echo "  BFKO:        " . \App\Models\BfkoData::count() . " records\n";
echo "  Service Fee: " . \App\Models\ServiceFee::count() . " records\n";
echo "  CC Card:     " . \App\Models\CCTransaction::count() . " records\n";
echo "  SPPD:        " . \App\Models\SppdTransaction::count() . " records\n";
echo "========================================\n";

echo "\nAll import routes are available. You can test import via:\n";
echo "  - BFKO:        POST /bfko/import\n";
echo "  - Service Fee: POST /service-fee/import-csv\n";
echo "  - CC Card:     POST /cc-card/transaction/import\n";
echo "  - SPPD:        POST /sppd/transaction/import\n";
