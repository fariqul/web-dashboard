<?php
/**
 * BFKO Excel to CSV Converter v2
 * Converts original BFKO Excel format to standard CSV format for import
 * 
 * Excel Structure:
 * - Sheet naming: "34 UID SULSELRABAR_2024" (contains unit and year info)
 * - Section 1 (Row 0-19): Down Payment BFKO
 * - Section 2 (Row 20+): Angsuran Bulanan BFKO with monthly payment columns
 * 
 * Monthly columns layout:
 * Col 7: Januari nilai, Col 8: Januari tanggal
 * Col 9: Februari nilai, Col 10: Februari tanggal
 * ... and so on
 */

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';
$outputFile = 'd:/Bu Intan/data/bfko/converted_bfko.csv';

echo "=== BFKO Excel to CSV Converter v2 ===\n\n";

$spreadsheet = IOFactory::load($inputFile);
$sheetNames = $spreadsheet->getSheetNames();

// CSV header
$csvRows = [];
$csvRows[] = ['nip', 'nama', 'jabatan', 'unit', 'bulan', 'tahun', 'nilai_angsuran', 'tanggal_bayar', 'status_angsuran'];

$totalRecords = 0;

foreach ($sheetNames as $sheetIndex => $sheetName) {
    echo "Processing sheet: $sheetName\n";
    
    // Extract year from sheet name (e.g., "34 UID SULSELRABAR_2024" -> 2024)
    preg_match('/(\d{4})$/', $sheetName, $yearMatch);
    $tahun = $yearMatch[1] ?? date('Y');
    
    // Extract unit from sheet name
    preg_match('/UID\s+(\w+)/i', $sheetName, $unitMatch);
    $defaultUnit = $unitMatch[0] ?? 'UID SULSELRABAR';
    
    $sheet = $spreadsheet->getSheet($sheetIndex);
    $rows = $sheet->toArray(null, true, true, false);
    
    // Find the Angsuran section header row
    $angsuranStartRow = -1;
    for ($i = 0; $i < count($rows); $i++) {
        $firstCol = trim((string)($rows[$i][0] ?? ''));
        if (stripos($firstCol, 'Angsuran Bulanan') !== false) {
            $angsuranStartRow = $i;
            break;
        }
    }
    
    if ($angsuranStartRow === -1) {
        echo "  - Warning: 'Angsuran Bulanan BFKO' section not found, skipping sheet\n";
        continue;
    }
    
    echo "  - Found 'Angsuran Bulanan BFKO' at row $angsuranStartRow\n";
    
    // Find the actual month header row (row with Januari, Februari, etc.)
    $monthHeaderRow = $angsuranStartRow + 2; // Usually 2 rows after section title
    
    // Dynamically detect month columns from the month header row
    $monthColumns = detectMonthColumns($rows[$monthHeaderRow]);
    
    if (empty($monthColumns)) {
        echo "  - Warning: Could not detect month columns, using default\n";
        $monthColumns = getDefaultMonthColumns();
    } else {
        echo "  - Detected month columns: " . count($monthColumns) . " months\n";
    }
    
    // Data starts 1 row after month header
    $dataStartRow = $monthHeaderRow + 1;
    
    // Find next section or end of data
    $dataEndRow = count($rows);
    for ($i = $dataStartRow; $i < count($rows); $i++) {
        $firstCol = trim((string)($rows[$i][0] ?? ''));
        // Check if we've reached next section (non-numeric text in first column)
        if (!empty($firstCol) && !is_numeric($firstCol) && strlen($firstCol) > 15) {
            $dataEndRow = $i;
            break;
        }
    }
    
    // Process each data row
    $sheetRecords = 0;
    for ($i = $dataStartRow; $i < $dataEndRow; $i++) {
        $row = $rows[$i];
        
        // Check if row has valid data (NIP in column 1)
        $nip = trim((string)($row[1] ?? ''));
        if (empty($nip) || !preg_match('/^\d/', $nip)) {
            continue;
        }
        
        $nama = trim((string)($row[2] ?? ''));
        $jabatan = trim((string)($row[3] ?? ''));
        $unit = trim((string)($row[5] ?? $defaultUnit));
        
        // Skip if no name
        if (empty($nama)) continue;
        
        // Process each month column
        foreach ($monthColumns as $bulan => $cols) {
            $nilaiAngsuran = $row[$cols['nilai']] ?? '';
            $tanggalBayar = $row[$cols['tanggal']] ?? '';
            
            // Clean nilai angsuran
            $nilaiAngsuran = cleanAmount($nilaiAngsuran);
            
            // Skip if no payment value
            if (empty($nilaiAngsuran) || $nilaiAngsuran <= 0) {
                continue;
            }
            
            // Parse tanggal bayar
            $tanggalBayar = parseTanggalBayar($tanggalBayar, $bulan, $tahun);
            
            // Determine status
            $statusAngsuran = empty($tanggalBayar) ? 'Belum Bayar' : 'Lunas';
            
            // Add row to CSV
            $csvRows[] = [
                $nip,
                $nama,
                $jabatan,
                $unit,
                $bulan,
                $tahun,
                $nilaiAngsuran,
                $tanggalBayar,
                $statusAngsuran
            ];
            
            $sheetRecords++;
        }
    }
    
    echo "  - Extracted $sheetRecords payment records\n";
    $totalRecords += $sheetRecords;
}

// Write CSV
$fp = fopen($outputFile, 'w');
// Add BOM for UTF-8 Excel compatibility
fwrite($fp, "\xEF\xBB\xBF");
foreach ($csvRows as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

echo "\n=== Conversion Complete ===\n";
echo "Total records: $totalRecords\n";
echo "Output file: $outputFile\n";

// Helper functions
function detectMonthColumns($headerRow) {
    $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $monthColumns = [];
    
    foreach ($headerRow as $colIdx => $cell) {
        $cell = trim((string)$cell);
        foreach ($monthNames as $month) {
            if (strcasecmp($cell, $month) === 0) {
                // Value column is where the month name is, date column is next
                $monthColumns[$month] = [
                    'nilai' => $colIdx,
                    'tanggal' => $colIdx + 1
                ];
                break;
            }
        }
    }
    
    return $monthColumns;
}

function getDefaultMonthColumns() {
    return [
        'Januari' => ['nilai' => 7, 'tanggal' => 8],
        'Februari' => ['nilai' => 9, 'tanggal' => 10],
        'Maret' => ['nilai' => 11, 'tanggal' => 12],
        'April' => ['nilai' => 13, 'tanggal' => 14],
        'Mei' => ['nilai' => 15, 'tanggal' => 16],
        'Juni' => ['nilai' => 17, 'tanggal' => 18],
        'Juli' => ['nilai' => 19, 'tanggal' => 20],
        'Agustus' => ['nilai' => 21, 'tanggal' => 22],
        'September' => ['nilai' => 23, 'tanggal' => 24],
        'Oktober' => ['nilai' => 25, 'tanggal' => 26],
        'November' => ['nilai' => 27, 'tanggal' => 28],
        'Desember' => ['nilai' => 29, 'tanggal' => 30],
    ];
}

function cleanAmount($value) {
    if (empty($value)) return 0;
    
    $value = trim((string)$value);
    
    // Remove spaces
    $value = str_replace(' ', '', $value);
    
    // Handle format with comma as thousand separator and dot as decimal (e.g., 300,000,000.00)
    // Remove .00 or .XX decimal first if present
    if (preg_match('/^[\d,]+\.\d{2}$/', $value)) {
        // Format: 300,000,000.00 - remove decimal part and commas
        $value = preg_replace('/\.\d{2}$/', '', $value);
        $value = str_replace(',', '', $value);
        return (float)$value;
    }
    
    // Handle Indonesian format with dots as thousand separator (3.734.355)
    if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
        $value = str_replace('.', '', $value);
        return (float)$value;
    }
    
    // Handle format with comma as thousand separator (3,734,355)
    if (preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
        $value = str_replace(',', '', $value);
        return (float)$value;
    }
    
    // Remove commas (might be thousand separator)
    $value = str_replace(',', '', $value);
    
    // Remove any remaining non-numeric except dot
    $value = preg_replace('/[^\d.]/', '', $value);
    
    return (float)$value;
}

function parseTanggalBayar($value, $bulan, $tahun) {
    if (empty($value)) return '';
    
    $value = trim((string)$value);
    
    // Handle Japanese-like format: 1212122024年1月29日 or 9992024年9月23日
    // The prefix numbers (121212 or 999) seem to be some kind of code, ignore them
    if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/', $value, $matches)) {
        $year = $matches[1];
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        return "$year-$month-$day";
    }
    
    // Handle dd/mm/yyyy format
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return "$year-$month-$day";
    }
    
    // Handle numeric (Excel serial date)
    if (is_numeric($value) && $value > 40000 && $value < 50000) {
        $baseDate = new DateTime('1899-12-30');
        $baseDate->modify('+' . (int)$value . ' days');
        return $baseDate->format('Y-m-d');
    }
    
    // Handle Indonesian date: "29 Januari 2024" or "05 Januari 2024"
    $monthMap = [
        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'
    ];
    
    $valueLower = strtolower($value);
    foreach ($monthMap as $monthName => $monthNum) {
        if (strpos($valueLower, $monthName) !== false) {
            if (preg_match('/(\d{1,2})\s*' . $monthName . '\s*(\d{4})/i', $value, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $year = $matches[2];
                return "$year-$monthNum-$day";
            }
        }
    }
    
    // Handle yyyy-mm-dd format (already correct)
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
        return $value;
    }
    
    return '';
}
