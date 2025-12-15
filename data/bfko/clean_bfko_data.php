<?php

/**
 * Script untuk membersihkan data BFKO dari CSV kotor
 * Input: bfko.csv (format kompleks dengan header bertingkat)
 * Output: 
 * - preproc/bfko_employees_clean.csv
 * - preproc/bfko_payments_clean.csv
 */

// Path files
$inputFile = __DIR__ . '/bfko.csv';
$outputDir = __DIR__ . '/preproc';
$employeesOutput = $outputDir . '/bfko_employees_clean.csv';
$paymentsOutput = $outputDir . '/bfko_payments_clean.csv';

// Create output directory if not exists
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo "=== BFKO Data Cleaning Script ===\n\n";
echo "Input: {$inputFile}\n";
echo "Output Directory: {$outputDir}\n\n";

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
    // Hapus koma ribuan dan titik desimal
    $cleaned = str_replace([',', '.00'], ['', ''], $value);
    $cleaned = str_replace('.', '', $cleaned); // Hapus titik ribuan jika ada
    return is_numeric($cleaned) ? (float)$cleaned : null;
}

// Fungsi helper untuk membersihkan tanggal
function cleanDate($value) {
    if (empty($value) || $value === '0' || trim($value) === '') {
        return null;
    }
    
    // Remove extra spaces
    $value = trim($value);
    
    // Handle different date formats
    // Format: 3/2/2025, 27/02/2025, 26/03/2025, etc
    if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $value, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return "{$year}-{$month}-{$day}";
    }
    
    return null;
}

// Array untuk menyimpan data clean
$employees = [];
$payments = [];

// Mapping bulan
$bulanMap = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Skip header rows (baris 1-3)
// Data mulai dari row index 3 (baris ke-4)
for ($i = 3; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    // Skip baris total
    if (isset($row[0]) && strtolower($row[0]) === 'total') {
        continue;
    }
    
    // Skip baris kosong
    if (empty($row[1]) || empty($row[2])) {
        continue;
    }
    
    // Extract employee data
    $nip = trim($row[1]);
    $nama = trim($row[2]);
    $jabatan = trim($row[3] ?? '');
    $jenjang = trim($row[4] ?? '');
    $unit = trim($row[5] ?? '');
    $sisaAngsuranRaw = trim($row[6] ?? '');
    
    // Parse status angsuran dari kolom "Sisa Angsuran BFKO Per 1 Januari 2025"
    $statusAngsuran = '';
    $sisaAngsuran = null;
    
    if (preg_match('/Angsuran Ke - (\d+)/', $sisaAngsuranRaw, $matches)) {
        $statusAngsuran = "Angsuran Ke - " . $matches[1];
    } elseif (stripos($sisaAngsuranRaw, 'SELESAI') !== false) {
        $statusAngsuran = 'SELESAI';
    }
    
    // Simpan employee data
    $employees[$nip] = [
        'nip' => $nip,
        'nama_pegawai' => $nama,
        'jabatan' => $jabatan,
        'jenjang_jabatan' => $jenjang,
        'unit' => $unit,
        'status_angsuran' => $statusAngsuran,
        'sisa_angsuran' => $sisaAngsuran
    ];
    
    // Extract payment data untuk setiap bulan
    // Struktur: Nilai (col 7), tgl (col 8), Nilai (col 9), tgl (col 10), dst
    // Total 12 bulan x 2 kolom = 24 kolom mulai dari index 7
    
    $colIndex = 7; // Mulai dari kolom pertama nilai angsuran
    
    for ($bulanIdx = 0; $bulanIdx < 12; $bulanIdx++) {
        $bulan = $bulanMap[$bulanIdx];
        $nilaiIdx = $colIndex + ($bulanIdx * 2);
        $tglIdx = $nilaiIdx + 1;
        
        $nilai = cleanNumber($row[$nilaiIdx] ?? '');
        $tanggal = cleanDate($row[$tglIdx] ?? '');
        
        // Hanya simpan jika ada nilai angsuran
        if ($nilai !== null && $nilai > 0) {
            $payments[] = [
                'nip' => $nip,
                'bulan' => $bulan,
                'tahun' => 2025, // Hardcoded, bisa disesuaikan
                'nilai_angsuran' => $nilai,
                'tanggal_pembayaran' => $tanggal
            ];
        }
    }
}

echo "Employees processed: " . count($employees) . "\n";
echo "Payments processed: " . count($payments) . "\n\n";

// Write employees CSV
$fpEmployees = fopen($employeesOutput, 'w');
fputcsv($fpEmployees, ['nip', 'nama_pegawai', 'jabatan', 'jenjang_jabatan', 'unit', 'status_angsuran', 'sisa_angsuran']);

foreach ($employees as $emp) {
    fputcsv($fpEmployees, [
        $emp['nip'],
        $emp['nama_pegawai'],
        $emp['jabatan'],
        $emp['jenjang_jabatan'],
        $emp['unit'],
        $emp['status_angsuran'],
        $emp['sisa_angsuran']
    ]);
}
fclose($fpEmployees);

echo "✓ Employees CSV created: {$employeesOutput}\n";

// Write payments CSV
$fpPayments = fopen($paymentsOutput, 'w');
fputcsv($fpPayments, ['nip', 'bulan', 'tahun', 'nilai_angsuran', 'tanggal_pembayaran']);

foreach ($payments as $payment) {
    fputcsv($fpPayments, [
        $payment['nip'],
        $payment['bulan'],
        $payment['tahun'],
        $payment['nilai_angsuran'],
        $payment['tanggal_pembayaran']
    ]);
}
fclose($fpPayments);

echo "✓ Payments CSV created: {$paymentsOutput}\n\n";

// Show statistics
echo "=== Statistics ===\n";
echo "Total Employees: " . count($employees) . "\n";
echo "Total Payment Records: " . count($payments) . "\n";

// Payment breakdown by month
$monthStats = [];
foreach ($payments as $payment) {
    $bulan = $payment['bulan'];
    if (!isset($monthStats[$bulan])) {
        $monthStats[$bulan] = ['count' => 0, 'total' => 0];
    }
    $monthStats[$bulan]['count']++;
    $monthStats[$bulan]['total'] += $payment['nilai_angsuran'];
}

echo "\nPayment Breakdown by Month:\n";
foreach ($bulanMap as $bulan) {
    if (isset($monthStats[$bulan])) {
        $count = $monthStats[$bulan]['count'];
        $total = number_format($monthStats[$bulan]['total'], 0, ',', '.');
        echo "- {$bulan}: {$count} payments, Total: Rp {$total}\n";
    } else {
        echo "- {$bulan}: 0 payments\n";
    }
}

echo "\n=== Cleaning Complete ===\n";
