<?php

namespace Database\Seeders;

use App\Models\CCTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CCTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan file per sheet yang sudah diedit manual (Juli, Agustus, September 2x)
        $preprocDir = database_path('../data/preproc');
        $csvFile = $preprocDir . '/cc_transactions_per_sheet.csv';
        
        if (!file_exists($csvFile)) {
            $this->command->error("File CSV tidak ditemukan: $csvFile");
            return;
        }

        $this->command->info("🚀 Importing CC Transactions - Per Sheet/File");
        $this->command->info("Membaca file: $csvFile");
        
        // Truncate table
        DB::table('cc_transactions')->truncate();
        
        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header
        
        $count = 0;
        $paymentCount = 0;
        $refundCount = 0;
        $skipped = 0;
        $bookingIds = [];
        $sheetStats = [];
        
        while (($row = fgetcsv($file)) !== false) {
            $bookingId = $row[1];
            $transactionType = $row[12]; // Transaction Type column
            $sheet = $row[13]; // Sheet column
            
            // Untuk refund, modifikasi booking_id supaya unique
            $uniqueBookingId = $bookingId;
            if ($transactionType === 'refund') {
                $uniqueBookingId = $bookingId . '-REFUND';
                
                // Jika masih duplikat refund, tambahkan counter
                $counter = 2;
                while (in_array($uniqueBookingId, $bookingIds)) {
                    $uniqueBookingId = $bookingId . '-REFUND-' . $counter;
                    $counter++;
                }
            }
            
            // Skip jika booking_id sudah ada (duplikasi) - hanya untuk payment
            if (in_array($uniqueBookingId, $bookingIds)) {
                $skipped++;
                $this->command->warn("⚠️  Duplikasi booking_id: $uniqueBookingId - Transaction #{$row[0]} dilewati");
                continue;
            }
            
            $bookingIds[] = $uniqueBookingId;
            
            CCTransaction::create([
                'transaction_number' => (int) $row[0],
                'booking_id' => $uniqueBookingId, // Gunakan unique booking id
                'employee_name' => $row[2],
                'personel_number' => $row[3],
                'trip_number' => $row[4],
                'origin' => $row[5],
                'destination' => $row[6],
                'trip_destination_full' => $row[7],
                'departure_date' => $row[8],
                'return_date' => $row[9],
                'duration_days' => (int) $row[10],
                'payment_amount' => (float) $row[11],
                'transaction_type' => $transactionType,
                'sheet' => $sheet,
                'status' => 'Complete',
            ]);
            
            // Track statistik per sheet
            if (!isset($sheetStats[$sheet])) {
                $sheetStats[$sheet] = ['payment' => 0, 'refund' => 0];
            }
            
            if ($transactionType === 'refund') {
                $refundCount++;
                $sheetStats[$sheet]['refund']++;
            } else {
                $paymentCount++;
                $sheetStats[$sheet]['payment']++;
            }
            
            $count++;
        }
        
        fclose($file);
        
        if ($skipped > 0) {
            $this->command->warn("⚠️  $skipped transaksi dilewati karena duplikasi booking_id");
        }
        
        $this->command->info("\n✅ IMPORT SELESAI!");
        $this->command->info("📊 Total: $count transaksi");
        $this->command->info("   📥 Payment: $paymentCount transaksi");
        $this->command->info("   💸 Refund: $refundCount transaksi\n");
        
        $this->command->info("📈 Breakdown per sheet:");
        foreach ($sheetStats as $sheetName => $stats) {
            $total = $stats['payment'] + $stats['refund'];
            $this->command->info("   �  $sheetName: $total transaksi (Payment: {$stats['payment']}, Refund: {$stats['refund']})");
        }
    }
}
