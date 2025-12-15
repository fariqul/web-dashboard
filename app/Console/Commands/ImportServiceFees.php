<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceFee;
use Carbon\Carbon;

class ImportServiceFees extends Command
{
    protected $signature = 'import:service-fees {file} {--type=} {--sheet=}';
    protected $description = 'Import service fees dari CSV file';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return 1;
        }

        // Detect service type dari nama file atau parameter
        $serviceType = $this->option('type');
        if (!$serviceType) {
            if (stripos($filePath, 'HL') !== false || stripos($filePath, 'hotel') !== false) {
                $serviceType = 'hotel';
            } elseif (stripos($filePath, 'FL') !== false || stripos($filePath, 'flight') !== false) {
                $serviceType = 'flight';
            } else {
                $this->error("Tidak bisa detect service type. Gunakan --type=hotel atau --type=flight");
                return 1;
            }
        }

        // Extract sheet name dari nama file atau parameter
        $sheet = $this->option('sheet');
        if (!$sheet) {
            // Extract dari nama file: "service fee HL Juli 2025.csv" -> "Juli 2025"
            if (preg_match('/(\w+\s+\d{4})/', $filePath, $matches)) {
                $sheet = $matches[1];
            } else {
                $sheet = 'Unknown Sheet';
            }
        }

        $this->info("Importing {$serviceType} data from: {$filePath}");
        $this->info("Sheet: {$sheet}");

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle); // Read header
        
        // Map header indices
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $headerMap[trim($header)] = $index;
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip empty rows atau summary rows
            $firstCol = trim($row[0] ?? '');
            if (empty($firstCol) || 
                stripos($firstCol, 'SUBTOTAL') !== false || 
                stripos($firstCol, 'VAT') !== false || 
                stripos($firstCol, 'TOTAL') !== false ||
                stripos($firstCol, 'Pembayaran') !== false ||
                stripos($firstCol, 'No.') !== false) {
                $skipped++;
                continue;
            }

            try {
                $data = $this->parseRow($row, $serviceType, $sheet, $headerMap, $rowNumber);
                
                if ($data) {
                    ServiceFee::create($data);
                    $imported++;
                    
                    if ($imported % 50 == 0) {
                        $this->info("Imported: {$imported} records...");
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->warn("Error at row {$rowNumber}: " . $e->getMessage());
            }
        }

        fclose($handle);

        $this->info("\n=== Import Summary ===");
        $this->info("Imported: {$imported}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: {$errors}");

        return 0;
    }

    private function parseRow($row, $serviceType, $sheet, $headerMap, $rowNumber)
    {
        // Get values by header name
        $getValue = function($headerName) use ($row, $headerMap) {
            return isset($headerMap[$headerName]) ? trim($row[$headerMap[$headerName]] ?? '') : '';
        };

        $transactionTime = $this->parseDateTime($getValue('Transaction Time'));
        $bookingId = $getValue('Booking ID');
        $status = $getValue('Status') ?: 'ISSUED';
        $description = $getValue('Description');
        $transactionAmount = floatval(str_replace(',', '', $getValue('Transaction Amount')));
        $baseAmount = floatval(str_replace(',', '', $getValue('Base Amount')));

        if (!$transactionTime || !$bookingId || $transactionAmount == 0) {
            return null;
        }

        $data = [
            'transaction_no' => $rowNumber,
            'transaction_time' => $transactionTime,
            'booking_id' => $bookingId,
            'service_type' => $serviceType,
            'settlement_method' => $getValue('Transaction Settlement Method'),
            'status' => $status,
            'currency' => $getValue('Currency') ?: 'IDR',
            'transaction_amount' => $transactionAmount,
            'base_amount' => $baseAmount,
            'sheet' => $sheet,
        ];

        if ($serviceType === 'hotel') {
            $parsed = $this->parseHotelDescription($description);
            $data = array_merge($data, $parsed);
        } else {
            $parsed = $this->parseFlightDescription($description);
            $data = array_merge($data, $parsed);
        }

        return $data;
    }

    private function parseHotelDescription($description)
    {
        // Format: "SERVICE FEE BID: 1265543332 | Amaris Hotel Hertasning Makassar Smart Queen 2 ANDI FADLI"
        
        $hotelName = null;
        $roomType = null;
        $employeeName = null;

        if (preg_match('/SERVICE FEE BID:\s*\d+\s*\|\s*(.+)/', $description, $matches)) {
            $fullText = trim($matches[1]);
            
            // Split dengan pola: Hotel Name Room Type [Nights] Employee Name
            // Contoh: "Amaris Hotel Hertasning Makassar Smart Queen 2 ANDI FADLI"
            
            // Cari pola nama hotel (biasanya dimulai dengan huruf kapital dan mengandung "Hotel")
            if (preg_match('/^(.+?(?:Hotel|Inn|Resort|Residence|Apartemen)[^\d]*)\s+(.+?)\s+(\d+)\s+([A-Z\s]+)$/i', $fullText, $parts)) {
                $hotelName = trim($parts[1]);
                $roomType = trim($parts[2]) . ' ' . $parts[3]; // Room type + nights
                $employeeName = trim($parts[4]);
            } else {
                // Fallback: ambil semua kata kapital di akhir sebagai employee name
                if (preg_match('/^(.+?)\s+([A-Z\s]{2,}[A-Z])$/', $fullText, $parts)) {
                    $hotelName = trim($parts[1]);
                    $employeeName = trim($parts[2]);
                    
                    // Extract room type dari hotel name jika ada angka
                    if (preg_match('/(.+?)\s+([^\d]+\d+)$/', $hotelName, $roomParts)) {
                        $hotelName = trim($roomParts[1]);
                        $roomType = trim($roomParts[2]);
                    }
                } else {
                    $hotelName = $fullText;
                }
            }
        }

        return [
            'hotel_name' => $hotelName,
            'room_type' => $roomType,
            'employee_name' => $employeeName,
        ];
    }

    private function parseFlightDescription($description)
    {
        // Format: "ONE_WAY | CGK_UPG | Pax : 1 | Airline ID : GA\nBooker: email@pln.co.id\nPassengers: NAMA PEGAWAI"
        
        $route = null;
        $tripType = null;
        $pax = null;
        $airlineId = null;
        $bookerEmail = null;
        $employeeName = null;

        // Extract trip type
        if (preg_match('/(ONE_WAY|TWO_WAY|ROUND_TRIP)/', $description, $matches)) {
            $tripType = $matches[1];
        }

        // Extract route
        if (preg_match('/([A-Z]{3}_[A-Z]{3})/', $description, $matches)) {
            $route = $matches[1];
        }

        // Extract pax
        if (preg_match('/Pax\s*:\s*(\d+)/', $description, $matches)) {
            $pax = intval($matches[1]);
        }

        // Extract airline ID
        if (preg_match('/Airline ID\s*:\s*([A-Z0-9]+)/', $description, $matches)) {
            $airlineId = $matches[1];
        }

        // Extract booker email
        if (preg_match('/Booker:\s*([^\s\n]+@[^\s\n]+)/i', $description, $matches)) {
            $bookerEmail = $matches[1];
        }

        // Extract passenger name (nama pegawai)
        if (preg_match('/Passengers?:\s*([^\n\|]+)/i', $description, $matches)) {
            $employeeName = trim($matches[1]);
            // Bersihkan karakter tambahan
            $employeeName = preg_replace('/[\'"]+/', '', $employeeName);
        }

        return [
            'route' => $route,
            'trip_type' => $tripType,
            'pax' => $pax,
            'airline_id' => $airlineId,
            'booker_email' => $bookerEmail,
            'employee_name' => $employeeName,
        ];
    }

    private function parseDateTime($dateStr)
    {
        // Format: "01 Jul 2025, 17:08:28"
        try {
            return Carbon::createFromFormat('d M Y, H:i:s', trim($dateStr));
        } catch (\Exception $e) {
            return null;
        }
    }
}
