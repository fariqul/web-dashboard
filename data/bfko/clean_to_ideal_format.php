<?php

/**
 * Script untuk membersihkan data BFKO dari CSV kotor
 * Menghasilkan FORMAT IDEAL yang user-friendly
 * 
 * Format Output: nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
 */

// Path files
$inputFile = __DIR__ . '/bfko.csv';
$outputDir = __DIR__ . '/preproc';
$outputFile = $outputDir . '/bfko_format_ideal.csv';

// Create output directory if not exists
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo "=== BFKO Data Cleaning - Format Ideal ===\n\n";
echo "Input: {$inputFile}\n";
echo "Output: {$outputFile}\n\n";

// Baca file CSV
$rows = [];
if (($handle = fopen($inputFile, 'r')) !== false) {
    while (($data = fgetcsv($handle, 10000, ',')) !== false) {
        $rows[] = $data;
    }
    fclose($handle);
}

echo "Total rows read: " . count($rows) . "\n\n";

// Fungsi helper untuk membersihkan angka
function cleanNumber($value) {
    if (empty($value) || $value === '0' || $value === '-') {
        return null;
    }
    // Hapus koma ribuan, titik desimal .00
    $cleaned = str_replace([',', '.00'], ['', ''], trim($value));
    // Hapus spasi
    $cleaned = str_replace(' ', '', $cleaned);
    return is_numeric($cleaned) ? (float)$cleaned : null;
}

// Fungsi helper untuk membersihkan tanggal
function cleanDate($value) {
    if (empty($value) || $value === '0' || trim($value) === '') {
        return '';
    }
    
    $value = trim($value);
    
    // Handle format: 3/2/2025, 27/02/2025, dll
    if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $value, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return "{$year}-{$month}-{$day}";
    }
    
    return '';
}

// Fungsi untuk extract status angsuran
function extractStatus($rawText) {
    if (empty($rawText)) return '';
    
    if (preg_match('/Angsuran Ke - (\d+)/', $rawText, $matches)) {
        return "Angsuran Ke-" . $matches[1];
    }
    
    if (stripos($rawText, 'SELESAI') !== false) {
        return 'SELESAI';
    }
    
    return '';
}

// Array untuk menyimpan data clean
$cleanData = [];

// Mapping bulan
$bulanMap = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Skip header rows (baris 1-3) - Data mulai dari row index 3 (baris ke-4)
for ($i = 3; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    // Skip baris total atau kosong
    if (isset($row[0]) && (strtolower($row[0]) === 'total' || empty($row[1]) || empty($row[2]))) {
        continue;
    }
    
    // Extract employee data
    $nip = trim($row[1]);
    $nama = trim($row[2]);
    $jabatan = trim($row[3] ?? '');
    $unit = trim($row[5] ?? ''); // Unit ada di kolom ke-6
    $statusRaw = trim($row[6] ?? '');
    
    $statusAngsuran = extractStatus($statusRaw);
    
    // Extract payment data untuk setiap bulan
    // Struktur: Nilai (col 7), tgl (col 8), Nilai (col 9), tgl (col 10), dst
    $colIndex = 7; // Mulai dari kolom pertama nilai angsuran
    
    for ($bulanIdx = 0; $bulanIdx < 12; $bulanIdx++) {
        $bulan = $bulanMap[$bulanIdx];
        $nilaiIdx = $colIndex + ($bulanIdx * 2);
        $tglIdx = $nilaiIdx + 1;
        
        $nilai = cleanNumber($row[$nilaiIdx] ?? '');
        $tanggal = cleanDate($row[$tglIdx] ?? '');
        
        // Hanya simpan jika ada nilai angsuran
        if ($nilai !== null && $nilai > 0) {
            // Detect year from tanggal if available, otherwise use 2025
            $tahun = 2025;
            if (!empty($tanggal)) {
                $tahun = (int)substr($tanggal, 0, 4);
            }
            
            $cleanData[] = [
                'nip' => $nip,
                'nama' => $nama,
                'jabatan' => $jabatan,
                'unit' => $unit,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'nilai_angsuran' => $nilai,
                'tanggal_bayar' => $tanggal,
                'status_angsuran' => $statusAngsuran
            ];
        }
    }
}

echo "Data processed: " . count($cleanData) . " records\n\n";

// Write to CSV dengan format ideal
$fp = fopen($outputFile, 'w');

// Header yang user-friendly
fputcsv($fp, [
    'nip',
    'nama',
    'jabatan',
    'unit',
    'bulan',
    'tahun',
    'nilai_angsuran',
    'tanggal_bayar',
    'status_angsuran'
]);

foreach ($cleanData as $record) {
    fputcsv($fp, [
        $record['nip'],
        $record['nama'],
        $record['jabatan'],
        $record['unit'],
        $record['bulan'],
        $record['tahun'],
        $record['nilai_angsuran'],
        $record['tanggal_bayar'],
        $record['status_angsuran']
    ]);
}

fclose($fp);

echo "✓ Format ideal CSV created: {$outputFile}\n\n";

// Show statistics
echo "=== Statistics ===\n";
echo "Total Records: " . count($cleanData) . "\n\n";

// Breakdown by month
$monthStats = [];
foreach ($cleanData as $record) {
    $bulan = $record['bulan'];
    if (!isset($monthStats[$bulan])) {
        $monthStats[$bulan] = ['count' => 0, 'total' => 0];
    }
    $monthStats[$bulan]['count']++;
    $monthStats[$bulan]['total'] += $record['nilai_angsuran'];
}

echo "Breakdown by Month:\n";
foreach ($bulanMap as $bulan) {
    if (isset($monthStats[$bulan])) {
        $count = $monthStats[$bulan]['count'];
        $total = number_format($monthStats[$bulan]['total'], 0, ',', '.');
        echo "- {$bulan}: {$count} records, Total: Rp {$total}\n";
    }
}

// Breakdown by employee (top 5)
$employeeStats = [];
foreach ($cleanData as $record) {
    $nip = $record['nip'];
    if (!isset($employeeStats[$nip])) {
        $employeeStats[$nip] = [
            'nama' => $record['nama'],
            'count' => 0,
            'total' => 0
        ];
    }
    $employeeStats[$nip]['count']++;
    $employeeStats[$nip]['total'] += $record['nilai_angsuran'];
}

// Sort by total descending
uasort($employeeStats, function($a, $b) {
    return $b['total'] - $a['total'];
});

echo "\nTop 5 Employees by Total Payment:\n";
$rank = 1;
foreach (array_slice($employeeStats, 0, 5, true) as $nip => $stats) {
    $total = number_format($stats['total'], 0, ',', '.');
    echo "{$rank}. {$stats['nama']} (NIP: {$nip}) - Rp {$total} ({$stats['count']} payments)\n";
    $rank++;
}

echo "\n=== Cleaning Complete ===\n";
echo "\nFormat CSV yang dihasilkan siap untuk:\n";
echo "1. Langsung diimport ke dashboard (tanpa preprocessing lagi)\n";
echo "2. User tinggal tambah baris baru untuk data bulan berikutnya\n";
echo "3. Support multi-tahun (2024, 2025, 2026, dst)\n";
echo "4. Format user-friendly dan mudah dipahami\n";
