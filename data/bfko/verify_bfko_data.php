<?php

/**
 * Verify BFKO data in database
 */

require __DIR__ . '/../../vendor/autoload.php';

use App\Models\BfkoEmployee;
use App\Models\BfkoPayment;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BFKO Database Verification ===\n\n";

// Check employees
echo "📋 EMPLOYEES SAMPLE (First 5):\n";
echo str_repeat("-", 80) . "\n";

$employees = BfkoEmployee::limit(5)->get();
foreach ($employees as $emp) {
    echo "NIP: {$emp->nip}\n";
    echo "Nama: {$emp->nama_pegawai}\n";
    echo "Jabatan: {$emp->jabatan}\n";
    echo "Unit: {$emp->unit}\n";
    echo "Status: {$emp->status_angsuran}\n";
    echo "Sisa Angsuran: Rp " . number_format($emp->sisa_angsuran ?? 0, 0, ',', '.') . "\n";
    echo str_repeat("-", 80) . "\n";
}

echo "\n💰 PAYMENTS SAMPLE (First 10):\n";
echo str_repeat("-", 80) . "\n";
printf("%-12s %-25s %-12s %-12s %s\n", "NIP", "Nama", "Bulan", "Tahun", "Nilai");
echo str_repeat("-", 80) . "\n";

$payments = BfkoPayment::with('employee')->limit(10)->get();
foreach ($payments as $payment) {
    $nama = $payment->employee ? substr($payment->employee->nama_pegawai, 0, 25) : 'Unknown';
    $nilai = "Rp " . number_format($payment->nilai_angsuran, 0, ',', '.');
    printf("%-12s %-25s %-12s %-12s %s\n", 
        $payment->nip,
        $nama,
        $payment->bulan,
        $payment->tahun,
        $nilai
    );
}

echo "\n📊 STATISTICS:\n";
echo str_repeat("-", 80) . "\n";

// Top 5 employees by total payment
echo "\nTop 5 Employees by Total Payment:\n";
$topEmployees = BfkoPayment::select('nip', DB::raw('SUM(nilai_angsuran) as total'))
    ->groupBy('nip')
    ->orderByDesc('total')
    ->limit(5)
    ->get();

foreach ($topEmployees as $idx => $emp) {
    $employee = BfkoEmployee::where('nip', $emp->nip)->first();
    $nama = $employee ? $employee->nama_pegawai : 'Unknown';
    $total = number_format($emp->total, 0, ',', '.');
    echo ($idx + 1) . ". {$nama} (NIP: {$emp->nip}) - Rp {$total}\n";
}

// Payments with missing dates
echo "\n\nPayments without payment date:\n";
$missingDates = BfkoPayment::whereNull('tanggal_pembayaran')->count();
echo "Total: {$missingDates} payments\n";

if ($missingDates > 0) {
    $samples = BfkoPayment::whereNull('tanggal_pembayaran')->limit(5)->get();
    foreach ($samples as $payment) {
        echo "- NIP: {$payment->nip}, {$payment->bulan} {$payment->tahun}, Rp " . number_format($payment->nilai_angsuran, 0, ',', '.') . "\n";
    }
}

echo "\n✅ Verification Complete\n";
