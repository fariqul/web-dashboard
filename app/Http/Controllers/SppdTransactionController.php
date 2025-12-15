<?php

namespace App\Http\Controllers;

use App\Models\SppdTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SppdTransactionController extends Controller
{
    /**
     * Get autocomplete suggestions for customer names
     */
    public function autocomplete(Request $request)
    {
        $search = $request->get('q', '');
        
        $customers = SppdTransaction::select('customer_name', 'beneficiary_bank_name')
            ->where('customer_name', 'like', '%' . $search . '%')
            ->distinct()
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'label' => $item->customer_name . ($item->beneficiary_bank_name ? ' (' . $item->beneficiary_bank_name . ')' : ''),
                    'value' => $item->customer_name,
                    'bank' => $item->beneficiary_bank_name
                ];
            });
        
        return response()->json($customers);
    }
    
    /**
     * Store manual transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_number' => 'required|string|max:50|unique:sppd_transactions,trip_number',
            'customer_name' => 'required|string|max:255',
            'trip_destination' => 'required|string|max:255',
            'reason_for_trip' => 'nullable|string',
            'trip_begins_on' => 'required|date',
            'trip_ends_on' => 'required|date|after_or_equal:trip_begins_on',
            'planned_payment_date' => 'nullable|date',
            'paid_amount' => 'required|numeric|min:0',
            'beneficiary_bank_name' => 'nullable|string|max:100',
            'sheet_month' => 'required|integer|min:1|max:12',
            'sheet_year' => 'required|integer|min:2020|max:2100',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $data = $validator->validated();
        
        // Build sheet name from month and year
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $sheetName = $monthNames[$data['sheet_month']] . ' ' . $data['sheet_year'];
        
        // Calculate duration
        $beginsOn = new \DateTime($data['trip_begins_on']);
        $endsOn = new \DateTime($data['trip_ends_on']);
        $duration = $endsOn->diff($beginsOn)->days + 1; // Include both start and end day
        
        // Get next transaction number
        $maxTransactionNumber = SppdTransaction::max('transaction_number');
        $nextTransactionNumber = $maxTransactionNumber ? $maxTransactionNumber + 1 : 1;
        
        $transaction = SppdTransaction::create([
            'transaction_number' => $nextTransactionNumber,
            'trip_number' => $data['trip_number'],
            'customer_name' => $data['customer_name'],
            'trip_destination' => $data['trip_destination'],
            'reason_for_trip' => $data['reason_for_trip'] ?? null,
            'trip_begins_on' => $data['trip_begins_on'],
            'trip_ends_on' => $data['trip_ends_on'],
            'planned_payment_date' => $data['planned_payment_date'] ?? null,
            'duration_days' => $duration,
            'paid_amount' => $data['paid_amount'],
            'beneficiary_bank_name' => $data['beneficiary_bank_name'] ?? null,
            'status' => 'Complete',
            'sheet' => $sheetName,
        ]);
        
        return redirect()->back()->with('success', 'Data SPPD berhasil ditambahkan!');
    }
    
    /**
     * Import transactions from CSV
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'update_existing' => 'boolean',
            'sheet_month' => 'nullable|integer|min:1|max:12',
            'sheet_year' => 'nullable|integer|min:2020|max:2100',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        
        $file = $request->file('csv_file');
        $updateExisting = $request->boolean('update_existing', false);
        $sheetMonth = $request->input('sheet_month');
        $sheetYear = $request->input('sheet_year');
        
        // Check if file is Excel
        $extension = strtolower($file->getClientOriginalExtension());
        $isExcel = in_array($extension, ['xlsx', 'xls']);
        
        if ($isExcel) {
            Log::info('Converting Excel file: ' . $file->getClientOriginalName());
            // Convert Excel to CSV format
            $csvData = $this->convertExcelToSppdCsv($file);
            if (!$csvData) {
                Log::error('Excel conversion failed for: ' . $file->getClientOriginalName());
                return back()->withErrors(['error' => 'Gagal convert file Excel. Pastikan file Excel memiliki kolom: Trip Number, Customer Name, Trip Destination, Reason for Trip, Trip Begins On, Trip Ends On, Tanggal Bayar, Paid Amount, Beneficiary Bank Name. Atau convert dulu ke CSV menggunakan script Python.']);
            }
            
            Log::info('Excel converted successfully, CSV data length: ' . strlen($csvData));
            // Create temporary CSV file
            $tempFile = tmpfile();
            fwrite($tempFile, $csvData);
            fseek($tempFile, 0); // Reset pointer to beginning
            $metaData = stream_get_meta_data($tempFile);
            $filePath = $metaData['uri'];
            Log::info('Temp CSV created at: ' . $filePath);
            $handle = fopen($filePath, 'r');
        } else {
            Log::info('Processing CSV file: ' . $file->getClientOriginalName());
            $handle = fopen($file->getRealPath(), 'r');
            $tempFile = null;
        }
        
        $header = fgetcsv($handle); // Read header
        
        // Validate header format
        $expectedHeaders = ['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip', 
                          'trip_begins_on', 'trip_ends_on', 'planned_payment_date', 'paid_amount', 'beneficiary_bank_name'];
        
        if ($header !== $expectedHeaders) {
            fclose($handle);
            return back()->withErrors(['error' => 'Invalid CSV format. Expected headers: ' . implode(', ', $expectedHeaders)]);
        }
        
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            $lineNumber = 1; // Start from 1 (after header)
            
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                
                if (count($row) < 9) {
                    $errors[] = "Line $lineNumber: Insufficient columns";
                    $skipped++;
                    continue;
                }
                
                $tripNumber = trim($row[0]);
                
                if (empty($tripNumber)) {
                    $errors[] = "Line $lineNumber: Trip number is required";
                    $skipped++;
                    continue;
                }
                
                $existing = SppdTransaction::where('trip_number', $tripNumber)->first();
                
                if ($existing && !$updateExisting) {
                    $skipped++;
                    continue;
                }
                
                // Parse trip_destination to extract origin and destination
                $tripDestination = trim($row[2]);
                $parts = explode(' - ', $tripDestination, 2);
                $origin = $parts[0] ?? '';
                $destination = $parts[1] ?? $tripDestination;
                
                // Parse dates
                $tripBeginsOn = trim($row[4]);
                $tripEndsOn = trim($row[5]);
                $plannedPaymentDate = trim($row[6]);
                
                // Calculate duration
                try {
                    $beginsDate = new \DateTime($tripBeginsOn);
                    $endsDate = new \DateTime($tripEndsOn);
                    $duration = $endsDate->diff($beginsDate)->days;
                } catch (\Exception $e) {
                    $errors[] = "Line $lineNumber: Invalid date format";
                    $skipped++;
                    continue;
                }
                
                // Parse amount (remove any non-numeric characters except decimal point)
                $paidAmount = preg_replace('/[^0-9.]/', '', trim($row[7]));
                
                // Determine sheet name
                if (!empty($sheetMonth) && !empty($sheetYear)) {
                    // Use user-specified month/year
                    $monthMap = [
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                    ];
                    $sheetName = $monthMap[$sheetMonth] . ' ' . $sheetYear;
                } else {
                    // Extract from trip_begins_on date
                    try {
                        $tripDate = new \DateTime($tripBeginsOn);
                        $sheetName = $tripDate->format('F Y');
                    } catch (\Exception $e) {
                        // Fallback to current date if parsing fails
                        $sheetName = date('F Y');
                    }
                }
                
                $data = [
                    'trip_number' => $tripNumber,
                    'customer_name' => trim($row[1]),
                    'origin' => $origin,
                    'destination' => $destination,
                    'trip_destination_full' => $tripDestination,
                    'reason_for_trip' => trim($row[3]),
                    'trip_begins_on' => $tripBeginsOn,
                    'trip_ends_on' => $tripEndsOn,
                    'planned_payment_date' => !empty($plannedPaymentDate) ? $plannedPaymentDate : null,
                    'duration_days' => $duration,
                    'paid_amount' => (float) $paidAmount,
                    'beneficiary_bank_name' => trim($row[8]),
                    'status' => 'Complete',
                    'sheet' => $sheetName,
                ];
                
                if ($existing && $updateExisting) {
                    $existing->update($data);
                    $updated++;
                } else {
                    // Set transaction number for new records
                    $maxTransactionNumber = SppdTransaction::max('transaction_number');
                    $data['transaction_number'] = $maxTransactionNumber ? $maxTransactionNumber + 1 : $imported + 1;
                    
                    SppdTransaction::create($data);
                    $imported++;
                }
            }
            
            DB::commit();
            fclose($handle);
            if ($tempFile !== null && is_resource($tempFile)) {
                fclose($tempFile);
            }
            
            $message = "Import berhasil! Ditambahkan: $imported, Diupdate: $updated, Dilewati: $skipped";
            
            if (!empty($errors) && count($errors) <= 10) {
                $message .= " | Error: " . implode('; ', $errors);
            }
            
            return redirect('/sppd')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) fclose($handle);
            if ($tempFile !== null && is_resource($tempFile)) {
                fclose($tempFile);
            }
            
            Log::error('SPPD import error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single transaction detail
     */
    public function show($id)
    {
        $transaction = SppdTransaction::findOrFail($id);
        return response()->json($transaction);
    }
    
    /**
     * Update transaction
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'trip_number' => 'required|string|max:50|unique:sppd_transactions,trip_number,' . $id,
            'customer_name' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'reason_for_trip' => 'nullable|string',
            'trip_begins_on' => 'required|date',
            'trip_ends_on' => 'required|date|after_or_equal:trip_begins_on',
            'paid_amount' => 'required|numeric|min:0',
            'beneficiary_bank_name' => 'nullable|string|max:100',
            'custom_month' => 'required|string',
            'custom_year' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $transaction = SppdTransaction::findOrFail($id);
        $data = $validator->validated();
        
        // Build sheet name
        $sheetName = $data['custom_month'] . ' ' . $data['custom_year'];
        
        // Calculate duration
        $beginsOn = new \DateTime($data['trip_begins_on']);
        $endsOn = new \DateTime($data['trip_ends_on']);
        $duration = $endsOn->diff($beginsOn)->days;
        
        // Full destination
        $tripDestination = $data['origin'] . ' - ' . $data['destination'];
        
        $transaction->update([
            'trip_number' => $data['trip_number'],
            'customer_name' => $data['customer_name'],
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'trip_destination_full' => $tripDestination,
            'reason_for_trip' => $data['reason_for_trip'],
            'trip_begins_on' => $data['trip_begins_on'],
            'trip_ends_on' => $data['trip_ends_on'],
            'duration_days' => $duration,
            'paid_amount' => $data['paid_amount'],
            'beneficiary_bank_name' => $data['beneficiary_bank_name'],
            'sheet' => $sheetName,
        ]);
        
        return response()->json([
            'message' => 'SPPD transaction updated successfully!',
            'transaction' => $transaction
        ]);
    }
    
    /**
     * Delete single transaction
     */
    public function destroy($id)
    {
        $transaction = SppdTransaction::findOrFail($id);
        $transaction->delete();
        
        return redirect()->back()->with('success', 'SPPD transaction deleted successfully!');
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
        
        try {
            $deletedCount = SppdTransaction::where('sheet', $sheetName)->delete();
            
            return response()->json([
                'message' => "Sheet '{$sheetName}' deleted successfully!",
                'deleted_transactions' => $deletedCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Convert Excel file to SPPD CSV format
     */
    private function convertExcelToSppdCsv($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            
            // Prioritize exactly 'Sheet1', not 'Sheet1 (2)'
            $sheetNames = $spreadsheet->getSheetNames();
            $targetSheet = null;
            
            // First try to find exact 'Sheet1'
            if (in_array('Sheet1', $sheetNames)) {
                $targetSheet = $spreadsheet->getSheetByName('Sheet1');
                Log::info('Using Sheet1');
            } else {
                // Otherwise look for any sheet with 'Sheet1' in the name
                foreach ($sheetNames as $sheetName) {
                    if (strpos($sheetName, 'Sheet1') !== false) {
                        $targetSheet = $spreadsheet->getSheetByName($sheetName);
                        Log::info('Using sheet: ' . $sheetName);
                        break;
                    }
                }
            }
            
            if (!$targetSheet) {
                Log::error('No suitable sheet found. Available sheets: ' . implode(', ', $sheetNames));
                return null;
            }
            
            // Get all rows as array but preserve cell objects for date detection
            $highestRow = $targetSheet->getHighestRow();
            $highestColumn = $targetSheet->getHighestColumn();
            
            // Find header row (search first 10 rows)
            $header = null;
            $headerRowIndex = 0;
            
            for ($row = 1; $row <= min(10, $highestRow); $row++) {
                $rowData = $targetSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row)[0];
                if (in_array('Trip Number', $rowData)) {
                    $header = $rowData;
                    $headerRowIndex = $row;
                    Log::info('Found header at row ' . $row);
                    break;
                }
            }
            
            if (!$header) {
                Log::error('Header row not found in first 10 rows');
                return null;
            }
            
            // Find column indices
            $tripNumberCol = array_search('Trip Number', $header);
            $customerNameCol = array_search('Customer Name', $header);
            $tripDestinationCol = array_search('Trip Destination', $header);
            $reasonCol = array_search('Reason for Trip', $header);
            $beginsOnCol = array_search('Trip Begins On', $header);
            $endsOnCol = array_search('Trip Ends On', $header);
            $plannedPaymentCol = array_search('Tanggal Rencana Bayar', $header);
            if ($plannedPaymentCol === false) {
                $plannedPaymentCol = array_search('Tanggal Bayar', $header);
            }
            if ($plannedPaymentCol === false) {
                $plannedPaymentCol = array_search('Planned Payment Date', $header);
            }
            $paidAmountCol = array_search('Paid Amount', $header);
            $bankNameCol = array_search('Beneficiary Bank Name', $header);
            
            if ($tripNumberCol === false || $customerNameCol === false) {
                Log::error('Required columns not found');
                return null;
            }
            
            Log::info('Columns found - trip: ' . $tripNumberCol . ', planned_payment: ' . var_export($plannedPaymentCol, true));
            
            // Build CSV
            $csvLines = [];
            $csvLines[] = 'trip_number,customer_name,trip_destination,reason_for_trip,trip_begins_on,trip_ends_on,planned_payment_date,paid_amount,beneficiary_bank_name';
            
            $processedCount = 0;
            for ($row = $headerRowIndex + 1; $row <= $highestRow; $row++) {
                // Get row data
                $rowData = $targetSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row)[0];
                
                // Skip empty rows or summary rows
                if (empty($rowData[$tripNumberCol]) || strpos($rowData[$tripNumberCol], 'TERBILANG') !== false) {
                    continue;
                }
                
                $tripNumber = $this->cleanTripNumber($rowData[$tripNumberCol]);
                if (empty($tripNumber)) {
                    continue;
                }
                
                $customerName = $this->cleanValue($rowData[$customerNameCol] ?? '');
                $tripDestination = $this->cleanValue($tripDestinationCol !== false ? ($rowData[$tripDestinationCol] ?? '') : '');
                $reason = $this->cleanValue($reasonCol !== false ? ($rowData[$reasonCol] ?? '') : '');
                
                // For dates, access cell directly to check formatting
                $beginsOn = '';
                $endsOn = '';
                $plannedPayment = '';
                
                if ($beginsOnCol !== false) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($beginsOnCol + 1);
                    $cell = $targetSheet->getCell($colLetter . $row);
                    $beginsOn = $this->formatExcelDateFromCell($cell);
                }
                
                if ($endsOnCol !== false) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endsOnCol + 1);
                    $cell = $targetSheet->getCell($colLetter . $row);
                    $endsOn = $this->formatExcelDateFromCell($cell);
                }
                
                if ($plannedPaymentCol !== false) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($plannedPaymentCol + 1);
                    $cell = $targetSheet->getCell($colLetter . $row);
                    $plannedPayment = $this->formatExcelDateFromCell($cell);
                }
                
                $paidAmount = $this->cleanAmount($paidAmountCol !== false ? ($rowData[$paidAmountCol] ?? 0) : 0);
                $bankName = $this->cleanValue($bankNameCol !== false ? ($rowData[$bankNameCol] ?? '') : '');
                
                $csvLines[] = sprintf(
                    '"%s","%s","%s","%s","%s","%s","%s","%s","%s"',
                    $tripNumber,
                    $customerName,
                    $tripDestination,
                    $reason,
                    $beginsOn,
                    $endsOn,
                    $plannedPayment,
                    $paidAmount,
                    $bankName
                );
                
                $processedCount++;
                if ($processedCount <= 3) {
                    Log::info("Row $row: $tripNumber, begins=$beginsOn, planned=$plannedPayment");
                }
            }
            
            Log::info("Processed $processedCount rows from Excel");
            
            return implode("\n", $csvLines);
            
        } catch (\Exception $e) {
            Log::error('Excel conversion error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function cleanTripNumber($value)
    {
        if (is_numeric($value)) {
            $value = (int)$value;
        }
        $cleaned = preg_replace('/[^\d]/', '', (string)$value);
        // Ensure 10 digits max
        return substr($cleaned, 0, 10);
    }
    
    private function cleanAmount($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return (int)preg_replace('/[^\d]/', '', (string)$value);
    }
    
    private function cleanValue($value)
    {
        return str_replace('"', '""', trim((string)$value));
    }
    
    private function formatExcelDateFromCell($cell)
    {
        try {
            $value = $cell->getValue();
            
            if (empty($value)) {
                return '';
            }
            
            // Check if cell is formatted as date
            $style = $cell->getStyle();
            $format = $style->getNumberFormat()->getFormatCode();
            
            // If cell has date format or value is DateTime
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }
            
            // If numeric and has date-like format code
            if (is_numeric($value) && $value > 0) {
                // Check if format suggests it's a date
                if (preg_match('/[dmy]/i', $format) || \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                    return $dateObj->format('Y-m-d');
                }
            }
            
            // If it's already a string date
            if (is_string($value) && strtotime($value)) {
                return date('Y-m-d', strtotime($value));
            }
            
            return '';
        } catch (\Exception $e) {
            Log::error('Date conversion error from cell: ' . $e->getMessage() . ' for value: ' . var_export($cell->getValue(), true));
            return '';
        }
    }
    
    private function formatExcelDate($value)
    {
        if (empty($value)) {
            return '';
        }
        
        try {
            // Handle DateTime object first (from PhpSpreadsheet)
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }
            
            // If it's already a string date, return as is
            if (is_string($value) && strtotime($value)) {
                return date('Y-m-d', strtotime($value));
            }
            
            // Handle Excel serial number (numeric value)
            if (is_numeric($value) && $value > 0) {
                // Use PhpSpreadsheet's date converter which handles Excel dates correctly
                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $dateObj->format('Y-m-d');
            }
            
            return '';
        } catch (\Exception $e) {
            Log::error('Date conversion error: ' . $e->getMessage() . ' for value: ' . var_export($value, true));
            return '';
        }
    }
}
