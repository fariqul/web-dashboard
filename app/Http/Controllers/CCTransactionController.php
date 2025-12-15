<?php

namespace App\Http\Controllers;

use App\Models\CCTransaction;
use App\Models\SheetAdditionalFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CCTransactionController extends Controller
{
    /**
     * Get autocomplete suggestions for employee names
     */
    public function autocomplete(Request $request)
    {
        $search = $request->get('q', '');
        
        $employees = CCTransaction::select('employee_name', 'personel_number')
            ->where('employee_name', 'like', '%' . $search . '%')
            ->distinct()
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'label' => $item->employee_name . ' (' . $item->personel_number . ')',
                    'value' => $item->employee_name,
                    'personel_number' => $item->personel_number
                ];
            });
        
        return response()->json($employees);
    }
    
    /**
     * Store manual transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_name' => 'required|string|max:255',
            'personel_number' => 'required|string|max:50',
            'trip_number' => 'required|string|max:50',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'payment_amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:payment,refund',
            'custom_month' => 'required|string',
            'custom_year' => 'required|string',
            'cc_number' => 'required|string|in:5657,9386',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Build sheet name from month, year, and cc_number (cc_number is required now)
        $sheetName = $data['custom_month'] . ' ' . $data['custom_year'] . ' - CC ' . $data['cc_number'];
        $data['sheet'] = $sheetName;
        
        // Auto-generate booking ID
        $bookingId = time() . rand(1000, 9999);
        if ($data['transaction_type'] === 'refund') {
            $bookingId .= '-REFUND';
        }
        
        // Calculate duration
        $departureDate = new \DateTime($data['departure_date']);
        $returnDate = new \DateTime($data['return_date']);
        $duration = $returnDate->diff($departureDate)->days;
        
        // Full destination
        $tripDestination = $data['origin'] . ' - ' . $data['destination'];
        
        // Get next transaction number (handle NULL case)
        $maxTransactionNumber = CCTransaction::max('transaction_number');
        $nextTransactionNumber = $maxTransactionNumber ? $maxTransactionNumber + 1 : 1;
        
        $transaction = CCTransaction::create([
            'transaction_number' => $nextTransactionNumber,
            'booking_id' => $bookingId,
            'employee_name' => $data['employee_name'],
            'personel_number' => $data['personel_number'],
            'trip_number' => $data['trip_number'],
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'trip_destination_full' => $tripDestination,
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'duration_days' => $duration,
            'payment_amount' => $data['payment_amount'],
            'transaction_type' => $data['transaction_type'],
            'sheet' => $data['sheet'],
            'status' => 'Complete',
        ]);
        
        // Auto-create sheet in sheet_additional_fees if not exists
        $this->ensureSheetExists($data['sheet']);
        
        return response()->json([
            'message' => 'Transaction created successfully!',
            'transaction' => $transaction,
            'sheet' => $data['sheet']
        ]);
    }
    
    /**
     * Import transactions from CSV
     */
    public function import(Request $request)
    {
        Log::info('CC Card import method called', [
            'has_file' => $request->hasFile('csv_file'),
            'file_name' => $request->hasFile('csv_file') ? $request->file('csv_file')->getClientOriginalName() : 'N/A'
        ]);
        
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB max
            'update_existing' => 'boolean',
            'override_sheet_name' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        
        $file = $request->file('csv_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $isExcel = in_array($extension, ['xlsx', 'xls']);
        
        if ($isExcel) {
            // Convert Excel to CSV format
            $csvData = $this->convertExcelToCCCardCsv($file);
            
            if (!$csvData) {
                Log::error('CC Card Excel conversion failed - invalid format');
                return back()->with('error', 'Format Excel tidak valid. Pastikan ada kolom Booking ID, Name, Trip Number, dll.');
            }
            
            $lineCount = substr_count($csvData, "\n");
            Log::info('CC Card Excel converted successfully, lines: ' . $lineCount);
            
            // Log first 500 chars of CSV for debugging
            Log::debug('CC Card CSV preview: ' . substr($csvData, 0, 500));
            
            // Create temp file from CSV string
            $tempFile = tmpfile();
            fwrite($tempFile, $csvData);
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];
            $handle = fopen($tempFilePath, 'r');
            
            if (!$handle) {
                Log::error('CC Card failed to open temp file');
                return back()->with('error', 'Gagal membaca file CSV yang dikonversi');
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            $tempFile = null;
            
            if (!$handle) {
                Log::error('CC Card failed to open CSV file');
                return back()->with('error', 'Gagal membaca file CSV');
            }
        }
        
        $updateExisting = $request->boolean('update_existing', false);
        $overrideSheetName = $request->input('override_sheet_name');
        
        $header = fgetcsv($handle); // Skip header
        
        if (!$header) {
            Log::error('CC Card failed to read CSV header');
            fclose($handle);
            if ($tempFile) fclose($tempFile);
            return back()->with('error', 'Gagal membaca header CSV');
        }
        
        Log::info('CC Card CSV header read successfully, columns: ' . count($header));
        
        // Detect CSV format: raw (9 cols) vs preprocessed (14 cols)
        $isRawFormat = count($header) <= 10;
        Log::info('CC Card CSV format detected: ' . ($isRawFormat ? 'raw' : 'preprocessed'));
        
        Log::info('CC Card import started', [
            'has_override_sheet' => !empty($overrideSheetName),
            'override_sheet_name' => $overrideSheetName,
            'update_existing' => $updateExisting,
            'format' => $isRawFormat ? 'raw' : 'preprocessed'
        ]);
        
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        
        DB::beginTransaction();
        
        try {
            $rowNumber = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // Handle both raw format (9 cols) and preprocessed format (14 cols)
                if ($isRawFormat) {
                    // Raw format: No.,Booking ID,Name,Personel Number,Trip Number,Trip Destination,Trip Date,Payment,Transaction Type
                    if (count($row) < 9) {
                        Log::warning("CC Card row $rowNumber skipped - insufficient columns: " . count($row));
                        $skipped++;
                        continue;
                    }
                    
                    $bookingId = trim($row[1]);
                    $employeeName = trim($row[2]);
                    $personelNumber = trim($row[3]);
                    $tripNumber = trim($row[4]);
                    $tripDestination = trim($row[5]);
                    $tripDate = trim($row[6]);
                    $payment = (float) preg_replace('/[^\d.]/', '', trim($row[7]));
                    $transactionType = strtolower(trim($row[8]));
                    
                    // Parse trip destination (origin - destination)
                    $origin = '';
                    $destination = '';
                    if (strpos($tripDestination, ' - ') !== false) {
                        $parts = explode(' - ', $tripDestination, 2);
                        $origin = trim($parts[0]);
                        $destination = trim($parts[1]);
                    }
                    
                    // Parse trip date (departure - return)
                    $departureDate = '';
                    $returnDate = '';
                    $durationDays = 0;
                    if (strpos($tripDate, ' - ') !== false) {
                        $parts = explode(' - ', $tripDate, 2);
                        $departureDate = $this->parseDateCC(trim($parts[0]));
                        $returnDate = $this->parseDateCC(trim($parts[1]));
                        
                        if ($departureDate && $returnDate) {
                            $depTime = strtotime($departureDate);
                            $retTime = strtotime($returnDate);
                            $durationDays = max(0, round(($retTime - $depTime) / 86400));
                        }
                    }
                    
                    // Generate sheet name from override or filename
                    $sheetName = !empty($overrideSheetName) ? $overrideSheetName : 'Import ' . date('F Y');
                    
                    // Get transaction number
                    $transactionNumber = (int) trim($row[0]);
                    
                } else {
                    // Preprocessed format (14 columns)
                    if (count($row) < 14) {
                        Log::warning("CC Card row $rowNumber skipped - insufficient columns: " . count($row));
                        $skipped++;
                        continue;
                    }
                    
                    $transactionNumber = (int) trim($row[0]);
                    $bookingId = trim($row[1]);
                    $employeeName = trim($row[2]);
                    $personelNumber = trim($row[3]);
                    $tripNumber = trim($row[4]);
                    $origin = trim($row[5]);
                    $destination = trim($row[6]);
                    $tripDestination = trim($row[7]);
                    $departureDate = trim($row[8]);
                    $returnDate = trim($row[9]);
                    $durationDays = (int) trim($row[10]);
                    $payment = (float) trim($row[11]);
                    $transactionType = strtolower(trim($row[12]));
                    $sheetName = !empty($overrideSheetName) ? $overrideSheetName : trim($row[13]);
                }
                
                // For refunds, add suffix with payment amount to make it unique
                if ($transactionType === 'refund' && !str_contains($bookingId, '-REFUND')) {
                    // Check if this booking already has refund(s)
                    $existingRefundCount = CCTransaction::where('booking_id', 'LIKE', $bookingId . '-REFUND%')->count();
                    
                    if ($existingRefundCount > 0) {
                        // Multiple refunds for same booking - add sequence number
                        $bookingId .= '-REFUND-' . ($existingRefundCount + 1);
                    } else {
                        $bookingId .= '-REFUND';
                    }
                }
                
                $existing = CCTransaction::where('booking_id', $bookingId)->first();
                
                if ($existing && !$updateExisting) {
                    $skipped++;
                    continue;
                }
                
                $data = [
                    'transaction_number' => $transactionNumber,
                    'booking_id' => $bookingId,
                    'employee_name' => $employeeName,
                    'personel_number' => $personelNumber,
                    'trip_number' => $tripNumber,
                    'origin' => $origin,
                    'destination' => $destination,
                    'trip_destination_full' => $tripDestination,
                    'departure_date' => $departureDate,
                    'return_date' => $returnDate,
                    'duration_days' => $durationDays,
                    'payment_amount' => $payment,
                    'transaction_type' => $transactionType,
                    'sheet' => $sheetName,
                    'status' => 'active',
                ];
                
                if ($existing && $updateExisting) {
                    $existing->update($data);
                    $updated++;
                } else {
                    CCTransaction::create($data);
                    $imported++;
                }
            }
            
            DB::commit();
            fclose($handle);
            if ($tempFile) {
                fclose($tempFile);
            }
            
            Log::info('CC Card import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped
            ]);
            
            // Auto-create sheets in sheet_additional_fees
            $uniqueSheets = CCTransaction::select('sheet')->distinct()->pluck('sheet');
            foreach ($uniqueSheets as $sheetName) {
                $this->ensureSheetExists($sheetName);
            }
            
            $message = "Import berhasil! Ditambahkan: $imported, Diupdate: $updated, Dilewati: $skipped";
            
            return redirect('/cc-card')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            if (isset($tempFile) && $tempFile) {
                fclose($tempFile);
            }
            
            Log::error('CC Card import error: ' . $e->getMessage());
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all sheet additional fees
     */
    public function getFees()
    {
        $fees = SheetAdditionalFee::all();
        return response()->json($fees);
    }
    
    /**
     * Update sheet additional fees
     */
    public function updateFees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fees' => 'required|array',
            'fees.*.sheet_name' => 'required|string',
            'fees.*.biaya_adm_bunga' => 'nullable|numeric|min:0',
            'fees.*.biaya_transfer' => 'nullable|numeric|min:0',
            'fees.*.iuran_tahunan' => 'nullable|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($request->fees as $feeData) {
                SheetAdditionalFee::updateOrCreate(
                    ['sheet_name' => $feeData['sheet_name']],
                    [
                        'biaya_adm_bunga' => $feeData['biaya_adm_bunga'] ?? 0,
                        'biaya_transfer' => $feeData['biaya_transfer'] ?? 0,
                        'iuran_tahunan' => $feeData['iuran_tahunan'] ?? 0,
                    ]
                );
            }
            
            DB::commit();
            
            return response()->json(['message' => 'Fees updated successfully!']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete sheet additional fees (reset to 0)
     */
    public function deleteFees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sheet_name' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $fee = SheetAdditionalFee::where('sheet_name', $request->sheet_name)->first();
            
            if ($fee) {
                $fee->delete();
                return response()->json(['message' => 'Additional fees deleted successfully!']);
            }
            
            return response()->json(['message' => 'No fees found for this sheet.'], 404);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get single transaction detail
     */
    public function show($id)
    {
        $transaction = CCTransaction::findOrFail($id);
        return response()->json($transaction);
    }
    
    /**
     * Update transaction
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'employee_name' => 'required|string|max:255',
            'personel_number' => 'required|string|max:50',
            'trip_number' => 'required|string|max:50',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'payment_amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:payment,refund',
            'custom_month' => 'required',
            'custom_year' => 'required',
            'cc_number' => 'nullable|string|max:50',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $transaction = CCTransaction::findOrFail($id);
        $data = $validator->validated();
        
        // Build sheet name
        $sheetName = $data['custom_month'] . ' ' . $data['custom_year'];
        if (!empty($data['cc_number'])) {
            $sheetName .= ' - CC ' . $data['cc_number'];
        }
        
        // Calculate duration
        $departureDate = new \DateTime($data['departure_date']);
        $returnDate = new \DateTime($data['return_date']);
        $duration = $returnDate->diff($departureDate)->days;
        
        // Full destination
        $tripDestination = $data['origin'] . ' - ' . $data['destination'];
        
        // Update booking ID if transaction type changed
        $bookingId = $transaction->booking_id;
        if ($data['transaction_type'] === 'refund' && !str_contains($bookingId, '-REFUND')) {
            $bookingId .= '-REFUND';
        } else if ($data['transaction_type'] === 'payment' && str_contains($bookingId, '-REFUND')) {
            $bookingId = str_replace('-REFUND', '', $bookingId);
        }
        
        $transaction->update([
            'booking_id' => $bookingId,
            'employee_name' => $data['employee_name'],
            'personel_number' => $data['personel_number'],
            'trip_number' => $data['trip_number'],
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'trip_destination_full' => $tripDestination,
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'duration_days' => $duration,
            'payment_amount' => $data['payment_amount'],
            'transaction_type' => $data['transaction_type'],
            'sheet' => $sheetName,
        ]);
        
        return response()->json([
            'message' => 'Transaction updated successfully!',
            'transaction' => $transaction
        ]);
    }
    
    /**
     * Delete single transaction
     */
    public function destroy($id)
    {
        $transaction = CCTransaction::findOrFail($id);
        $transaction->delete();
        
        return redirect()->back()->with('success', 'Transaction deleted successfully!');
    }
    
    /**
     * Delete entire sheet (all transactions in a month/sheet)
     */
    public function destroySheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sheet_name' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $sheetName = $request->input('sheet_name');
        
        DB::beginTransaction();
        
        try {
            // Delete all transactions for this sheet
            $deletedCount = CCTransaction::where('sheet', $sheetName)->delete();
            
            // Optionally delete the sheet's additional fees
            SheetAdditionalFee::where('sheet_name', $sheetName)->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => "Sheet '{$sheetName}' deleted successfully!",
                'deleted_transactions' => $deletedCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Ensure sheet exists in sheet_additional_fees table
     * Create with default values (0) if not exists
     */
    private function ensureSheetExists($sheetName)
    {
        SheetAdditionalFee::firstOrCreate(
            ['sheet_name' => $sheetName],
            [
                'biaya_adm_bunga' => 0,
                'biaya_transfer' => 0,
                'iuran_tahunan' => 0,
            ]
        );
    }
    
    /**
     * Convert Excel file to CC Card CSV format
     */
    private function convertExcelToCCCardCsv($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            Log::info('CC Card Excel loaded, total rows: ' . count($rows));
            
            // Find header row
            $headerRow = null;
            $headerRowIndex = -1;
            
            for ($i = 0; $i < min(5, count($rows)); $i++) {
                $row = $rows[$i];
                // Look for key columns
                if (in_array('Booking ID', $row) || in_array('Name', $row) || in_array('Trip Number', $row)) {
                    $headerRow = $row;
                    $headerRowIndex = $i;
                    Log::info('CC Card header found at row: ' . $i);
                    break;
                }
            }
            
            if (!$headerRow) {
                Log::error('CC Card header row not found');
                return null;
            }
            
            // Find column indices
            $bookingIdCol = array_search('Booking ID', $headerRow);
            $nameCol = array_search('Name', $headerRow);
            $personelCol = array_search('Personel Number', $headerRow);
            $tripNumCol = array_search('Trip Number', $headerRow);
            $destCol = array_search('Trip Destination', $headerRow);
            $tripDateCol = array_search('Trip Date', $headerRow);
            $paymentCol = array_search('Payment', $headerRow);
            $typeCol = array_search('Transaction Type', $headerRow);
            
            if ($bookingIdCol === false || $nameCol === false || $tripNumCol === false) {
                return null;
            }
            
            // Detect sheet name from filename or use default
            $fileName = $file->getClientOriginalName();
            $sheetName = pathinfo($fileName, PATHINFO_FILENAME);
            
            // Try to extract CC number and month/year from filename
            $ccNumber = '5657'; // default
            if (preg_match('/(\d{4})/', $sheetName, $matches)) {
                $ccNumber = $matches[1];
            }
            
            // Try to extract month and year from filename
            $monthYear = '';
            if (preg_match('/(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s*(\d{4})/i', $sheetName, $matches)) {
                $monthYear = $matches[1] . ' ' . $matches[2];
            } else {
                // Default to current month/year
                $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $monthYear = $monthNames[date('n')] . ' ' . date('Y');
            }
            
            $defaultSheetName = $monthYear . ' - CC ' . $ccNumber;
            
            // Build CSV
            $csvLines = [];
            $csvLines[] = 'No.,Booking ID,Name,Personel Number,Trip Number,Origin,Destination,Trip Destination,Departure Date,Return Date,Duration Days,Payment,Transaction Type,Sheet';
            
            $transactionNumber = 1;
            
            // Process data rows
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Skip empty rows
                if (empty($row[$bookingIdCol])) {
                    continue;
                }
                
                $bookingId = $this->cleanValueCC($row[$bookingIdCol]);
                $name = $this->cleanValueCC($row[$nameCol] ?? '');
                $personelNumber = $this->cleanValueCC($row[$personelCol] ?? '');
                $tripNumber = $this->cleanValueCC($row[$tripNumCol] ?? '');
                $tripDestination = $this->cleanValueCC($row[$destCol] ?? '');
                $tripDate = $this->cleanValueCC($row[$tripDateCol] ?? '');
                $payment = $this->cleanAmountCC($row[$paymentCol] ?? 0);
                $transactionType = strtolower($this->cleanValueCC($row[$typeCol] ?? 'payment'));
                
                // Parse trip destination (origin - destination)
                $origin = '';
                $destination = '';
                if (strpos($tripDestination, ' - ') !== false) {
                    list($origin, $destination) = explode(' - ', $tripDestination, 2);
                    $origin = trim($origin);
                    $destination = trim($destination);
                }
                
                // Parse trip date (departure - return)
                $departureDate = '';
                $returnDate = '';
                $durationDays = 0;
                if (strpos($tripDate, ' - ') !== false) {
                    list($dep, $ret) = explode(' - ', $tripDate, 2);
                    $departureDate = $this->parseDateCC(trim($dep));
                    $returnDate = $this->parseDateCC(trim($ret));
                    
                    if ($departureDate && $returnDate) {
                        $depTime = strtotime($departureDate);
                        $retTime = strtotime($returnDate);
                        $durationDays = round(($retTime - $depTime) / 86400);
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
            
            $csvData = implode("\n", $csvLines);
            Log::info('CC Card CSV generated', [
                'total_lines' => count($csvLines),
                'default_sheet_name' => $defaultSheetName
            ]);
            
            return $csvData;
            
        } catch (\Exception $e) {
            Log::error('CC Card Excel conversion error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    private function cleanValueCC($value)
    {
        return str_replace('"', '""', trim((string)$value));
    }
    
    private function cleanAmountCC($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        $cleaned = preg_replace('/[^\d]/', '', (string)$value);
        return (int)$cleaned;
    }
    
    private function parseDateCC($dateStr)
    {
        // Handle various date formats: dd/mm/yyyy, Excel serial, etc.
        if (empty($dateStr)) {
            return '';
        }
        
        try {
            // If it's numeric (Excel serial)
            if (is_numeric($dateStr)) {
                $baseDate = new \DateTime('1899-12-30');
                $baseDate->modify('+' . (int)$dateStr . ' days');
                return $baseDate->format('Y-m-d');
            }
            
            // Try to parse dd/mm/yyyy
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
                return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }
            
            // Try standard strtotime
            $timestamp = strtotime($dateStr);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
