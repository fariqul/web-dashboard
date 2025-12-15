<?php

namespace App\Http\Controllers;

use App\Models\ServiceFee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ServiceFeeController extends Controller
{
    public function index(Request $request)
    {
        $selectedSheet = $request->get('sheet', 'all');
        $search = $request->get('search', '');
        $serviceType = $request->get('type', 'all'); // all, hotel, flight
        $selectedYear = $request->get('year', 'all'); // Year filter for charts
        
        // Pagination and sorting parameters
        $perPage = $request->get('per_page', 10);
        $sortBy = $request->get('sort_by', 'transaction_time');
        $sortOrder = $request->get('sort_order', 'desc');

        // Get available sheets and sort chronologically
        $availableSheets = ServiceFee::select('sheet')
            ->distinct()
            ->pluck('sheet')
            ->toArray();
        
        // Sort sheets chronologically
        usort($availableSheets, function($a, $b) {
            return $this->parseSheetDate($a) <=> $this->parseSheetDate($b);
        });

        // Get available years from transaction_time (SQLite compatible)
        $availableYears = ServiceFee::selectRaw("strftime('%Y', transaction_time) as year")
            ->distinct()
            ->orderByRaw("strftime('%Y', transaction_time) DESC")
            ->pluck('year')
            ->toArray();

        // Base query
        $query = ServiceFee::query();

        // Apply filters
        if ($selectedSheet !== 'all') {
            $query->where('sheet', $selectedSheet);
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        if ($search) {
            $query->search($search);
        }

        // Calculate statistics
        $totalTransactionAmount = $query->sum('transaction_amount');
        $totalBaseAmount = $query->sum('base_amount');
        $totalBookings = $query->count();
        
        // Calculate average fee percentage
        $avgFeePercentage = $totalTransactionAmount > 0 
            ? ($totalBaseAmount / $totalTransactionAmount) * 100 
            : 0;

        // Format amounts in millions
        $totalAmountInMillions = $totalTransactionAmount / 1000000;
        $totalFeeInMillions = $totalBaseAmount / 1000000;

        // Get top hotels/flights by total amount (with year filter, top 3 each)
        // Always show ALL service types in Top Destinations (both HL and FL)
        $topDestinations = $this->getTopDestinations($selectedSheet, 'all', $search, $selectedYear, 3);

        // Get monthly chart data (with year filter)
        $monthlyChartData = $this->getMonthlyChartData($selectedSheet, $serviceType, $selectedYear);
        
        // Get separated chart data (hotel and flight as separate bars, with year filter)
        $monthlySeparatedData = $this->getMonthlySeparatedChartData($selectedSheet, $selectedYear);

        // Get sheet comparison data
        $sheetComparison = $this->getSheetComparison($serviceType);

        // Analytics: Top employees, service type breakdown
        $topEmployeesByCount = $this->getTopEmployeesByCount($selectedSheet, $serviceType, 10);
        $topEmployeesByAmount = $this->getTopEmployeesByAmount($selectedSheet, $serviceType, 10);
        $serviceTypeBreakdown = $this->getServiceTypeBreakdown($selectedSheet, $selectedYear);

        // Get hotel and flight bookings for tables WITH PAGINATION AND SORTING
        $hotelBookings = $this->getHotelBookings($selectedSheet, $search, $sortBy, $sortOrder, $perPage);
        $flightBookings = $this->getFlightBookings($selectedSheet, $search, $sortBy, $sortOrder, $perPage);

        // Calculate summary with VAT per type (with year filter)
        $hotelSummary = $this->calculateSummary('hotel', $selectedSheet, $selectedYear);
        $flightSummary = $this->calculateSummary('flight', $selectedSheet, $selectedYear);

        // Debug logging
        \Log::info('Hotel Summary for ' . $selectedSheet, $hotelSummary);
        \Log::info('Flight Summary for ' . $selectedSheet, $flightSummary);

        return Inertia::render('ServiceFeeMonitoring', [
            'totalFee' => 'Rp ' . number_format($totalFeeInMillions, 1, ',', '.') . 'M',
            'totalBookings' => $totalBookings,
            'averageFeeRate' => number_format($avgFeePercentage, 2) . '%',
            'totalTransactionAmount' => 'Rp ' . number_format($totalAmountInMillions, 1, ',', '.') . 'M',
            'topDestinations' => $topDestinations,
            'monthlyChartData' => $monthlyChartData,
            'monthlySeparatedData' => $monthlySeparatedData,
            'sheetComparison' => $sheetComparison,
            'availableSheets' => $availableSheets,
            'availableYears' => $availableYears,
            'selectedSheet' => $selectedSheet,
            'selectedYear' => $selectedYear,
            'selectedType' => $serviceType,
            'topEmployeesByCount' => $topEmployeesByCount,
            'topEmployeesByAmount' => $topEmployeesByAmount,
            'serviceTypeBreakdown' => $serviceTypeBreakdown,
            'hotelBookings' => $hotelBookings,
            'flightBookings' => $flightBookings,
            'hotelSummary' => $hotelSummary,
            'flightSummary' => $flightSummary,
            'filters' => [
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ],
        ]);
    }

    private function getTopDestinations($sheet, $serviceType, $search, $year = 'all', $limit = 3)
    {
        $query = ServiceFee::query();

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        if ($search) {
            $query->search($search);
        }

        // Filter by year
        if ($year !== 'all') {
            $query->whereRaw("strftime('%Y', transaction_time) = ?", [$year]);
        }

        // Get top hotels (top $limit)
        $hotels = (clone $query)
            ->where('service_type', 'hotel')
            ->whereNotNull('hotel_name')
            ->where('hotel_name', '!=', '')
            ->select('hotel_name as name', 'service_type')
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('SUM(base_amount) as total_fee')
            ->selectRaw('SUM(transaction_amount) as total_amount')
            ->groupBy('hotel_name', 'service_type')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function($dest) {
                return [
                    'name' => $dest->name ?? 'Unknown',
                    'type' => $dest->service_type,
                    'bookings' => $dest->bookings . ' Booking' . ($dest->bookings > 1 ? 's' : ''),
                    'amount' => 'Rp ' . number_format($dest->total_amount, 0, ',', '.'),
                    'rawAmount' => $dest->total_amount,
                ];
            });

        // Get top flights (top $limit) - group by airline_id
        $flights = (clone $query)
            ->where('service_type', 'flight')
            ->whereNotNull('airline_id')
            ->where('airline_id', '!=', '')
            ->select('airline_id as name', 'service_type')
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('SUM(base_amount) as total_fee')
            ->selectRaw('SUM(transaction_amount) as total_amount')
            ->groupBy('airline_id', 'service_type')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function($dest) {
                return [
                    'name' => $dest->name ?? 'Unknown',
                    'type' => $dest->service_type,
                    'bookings' => $dest->bookings . ' Booking' . ($dest->bookings > 1 ? 's' : ''),
                    'amount' => 'Rp ' . number_format($dest->total_amount, 0, ',', '.'),
                    'rawAmount' => $dest->total_amount,
                ];
            });

        // Concat hotels and flights (each already limited to $limit)
        $destinations = $hotels->concat($flights)->values()->toArray();

        return $destinations;
    }

    private function getMonthlyChartData($sheet, $serviceType, $year = 'all')
    {
        $query = ServiceFee::query();

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        // Filter by year (SQLite compatible)
        if ($year !== 'all') {
            $query->whereRaw("strftime('%Y', transaction_time) = ?", [$year]);
        }

        $data = $query->select('sheet')
            ->selectRaw('SUM(base_amount) as subtotal_fee')
            ->selectRaw('SUM(transaction_amount) as total_amount')
            ->groupBy('sheet')
            ->get()
            ->map(function($item) {
                // Calculate Total Tagihan (Service Fee + VAT 11%)
                $subtotalFee = $item->subtotal_fee;
                $vat = floor($subtotalFee * 11 / 100);
                $totalTagihan = floor($subtotalFee + $vat);
                
                return [
                    'sheet' => $item->sheet,
                    'fee' => $totalTagihan, // Total Tagihan with VAT (full number)
                    'amount' => $item->total_amount,
                ];
            })
            ->toArray();

        // Sort by date chronologically
        usort($data, function($a, $b) {
            return $this->parseSheetDate($a['sheet']) <=> $this->parseSheetDate($b['sheet']);
        });

        return $data;
    }

    private function getMonthlySeparatedChartData($sheet, $year = 'all')
    {
        $query = ServiceFee::query();

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        // Filter by year (SQLite compatible)
        if ($year !== 'all') {
            $query->whereRaw("strftime('%Y', transaction_time) = ?", [$year]);
        }

        $data = $query->select('sheet')
            ->selectRaw('SUM(CASE WHEN service_type = "hotel" THEN base_amount ELSE 0 END) as hotel_fee')
            ->selectRaw('SUM(CASE WHEN service_type = "flight" THEN base_amount ELSE 0 END) as flight_fee')
            ->groupBy('sheet')
            ->get()
            ->map(function($item) {
                // Calculate Total Tagihan with VAT for each type
                $hotelSubtotal = $item->hotel_fee;
                $hotelVat = floor($hotelSubtotal * 11 / 100);
                $hotelTotal = floor($hotelSubtotal + $hotelVat);

                $flightSubtotal = $item->flight_fee;
                $flightVat = floor($flightSubtotal * 11 / 100);
                $flightTotal = floor($flightSubtotal + $flightVat);
                
                return [
                    'sheet' => $item->sheet,
                    'hotel' => $hotelTotal,
                    'flight' => $flightTotal,
                ];
            })
            ->toArray();

        // Sort by date chronologically
        usort($data, function($a, $b) {
            return $this->parseSheetDate($a['sheet']) <=> $this->parseSheetDate($b['sheet']);
        });

        return $data;
    }

    private function getSheetComparison($serviceType)
    {
        $query = ServiceFee::query();

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        $data = $query->select('sheet')
            ->selectRaw('SUM(CASE WHEN service_type = "hotel" THEN base_amount ELSE 0 END) as hotel_fee')
            ->selectRaw('SUM(CASE WHEN service_type = "flight" THEN base_amount ELSE 0 END) as flight_fee')
            ->groupBy('sheet')
            ->get()
            ->map(function($item) {
                return [
                    'sheet' => $item->sheet,
                    'hotel' => $item->hotel_fee,
                    'flight' => $item->flight_fee,
                ];
            })
            ->toArray();

        // Sort by date chronologically
        usort($data, function($a, $b) {
            return $this->parseSheetDate($a['sheet']) <=> $this->parseSheetDate($b['sheet']);
        });

        return $data;
    }

    private function getTopEmployeesByCount($sheet, $serviceType, $limit = 10)
    {
        $query = ServiceFee::query();

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        return $query->select('employee_name')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('employee_name')
            ->groupBy('employee_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function($emp) {
                return [
                    'name' => $emp->employee_name,
                    'count' => $emp->count,
                ];
            })
            ->toArray();
    }

    private function getTopEmployeesByAmount($sheet, $serviceType, $limit = 10)
    {
        $query = ServiceFee::query();

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        return $query->select('employee_name')
            ->selectRaw('SUM(transaction_amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('employee_name')
            ->groupBy('employee_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function($emp) {
                return [
                    'name' => $emp->employee_name,
                    'total' => $emp->total,
                    'count' => $emp->count,
                ];
            })
            ->toArray();
    }

    private function getServiceTypeBreakdown($sheet, $year = 'all')
    {
        $query = ServiceFee::query();

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        // Filter by year
        if ($year !== 'all') {
            $query->whereRaw("strftime('%Y', transaction_time) = ?", [$year]);
        }

        $hotel = $query->clone()->where('service_type', 'hotel')->sum('base_amount') / 1000000;
        $flight = $query->clone()->where('service_type', 'flight')->sum('base_amount') / 1000000;

        $total = $hotel + $flight;
        $hotelPercentage = $total > 0 ? ($hotel / $total) * 100 : 0;
        $flightPercentage = $total > 0 ? ($flight / $total) * 100 : 0;

        return [
            'hotel' => round($hotel, 2),
            'flight' => round($flight, 2),
            'hotelPercentage' => round($hotelPercentage, 1),
            'flightPercentage' => round($flightPercentage, 1),
        ];
    }

    private function getHotelBookings($sheet, $search, $sortBy = 'transaction_time', $sortOrder = 'desc', $perPage = 10)
    {
        $query = ServiceFee::where('service_type', 'hotel');

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        if ($search) {
            $query->search($search);
        }

        // Apply sorting
        $allowedSortFields = ['transaction_time', 'booking_id', 'hotel_name', 'employee_name', 'transaction_amount', 'service_fee', 'status'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('transaction_time', 'desc');
        }

        // Paginate
        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ];
    }

    private function getFlightBookings($sheet, $search, $sortBy = 'transaction_time', $sortOrder = 'desc', $perPage = 10)
    {
        $query = ServiceFee::where('service_type', 'flight');

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        if ($search) {
            $query->search($search);
        }

        // Apply sorting
        $allowedSortFields = ['transaction_time', 'booking_id', 'route', 'employee_name', 'transaction_amount', 'service_fee', 'status'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('transaction_time', 'desc');
        }

        // Paginate
        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ];
    }

    private function calculateSummary($serviceType, $sheet, $year = 'all')
    {
        $query = ServiceFee::where('service_type', $serviceType);

        if ($sheet !== 'all') {
            $query->where('sheet', $sheet);
        }

        // Filter by year
        if ($year !== 'all') {
            $query->whereRaw("strftime('%Y', transaction_time) = ?", [$year]);
        }

        $totalBookings = $query->count();
        $totalTransactionAmount = $query->sum('transaction_amount');
        
        // Use service_fee column (the actual service fee amount from CSV)
        $subtotalServiceFee = $query->sum('service_fee');
        
        // VAT calculation: ROUNDDOWN(11/100 * subtotal, 0)
        $vat = floor($subtotalServiceFee * 11 / 100);
        
        // Total tagihan: ROUNDDOWN(subtotal + vat, 0)
        $totalTagihan = floor($subtotalServiceFee + $vat);

        return [
            'totalBookings' => $totalBookings,
            'totalTransactionAmount' => $totalTransactionAmount,
            'subtotalServiceFee' => $subtotalServiceFee,
            'vat' => $vat,
            'totalTagihan' => $totalTagihan,
        ];
    }

    private function parseSheetDate($sheet)
    {
        // Parse "Juli 2025", "Agustus 2025", etc to timestamp for sorting
        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
        ];
        
        $parts = explode(' ', strtolower(trim($sheet)));
        if (count($parts) >= 2) {
            $monthName = $parts[0];
            $year = intval($parts[1]);
            
            if (isset($months[$monthName])) {
                $month = $months[$monthName];
                return strtotime("$year-$month-01");
            }
        }
        
        return 0;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|string|unique:service_fees,booking_id',
            'transaction_time' => 'required|date',
            'sheet' => 'nullable|string', // Made nullable for auto-generation
            'status' => 'required|string',
            'transaction_amount' => 'required|numeric|min:0',
            'service_type' => 'required|in:hotel,flight',
            // Hotel fields
            'hotel_name' => 'required_if:service_type,hotel|nullable|string',
            'room_type' => 'required_if:service_type,hotel|nullable|string',
            // Flight fields
            'route' => 'required_if:service_type,flight|nullable|string',
            'trip_type' => 'required_if:service_type,flight|nullable|string',
            'pax' => 'required_if:service_type,flight|nullable|integer|min:1',
            'airline_id' => 'required_if:service_type,flight|nullable|string',
            'booker_email' => 'nullable|email',
            // Common
            'employee_name' => 'required|string',
        ]);

        // Auto-generate sheet name from transaction_time if not provided or set to "auto"
        $transactionDate = \Carbon\Carbon::parse($validated['transaction_time']);
        if (empty($validated['sheet']) || strtolower($validated['sheet']) === 'auto') {
            $validated['sheet'] = $this->generateSheetName($transactionDate);
        }

        // Calculate service fee and VAT
        $serviceFeeAmount = floor($validated['transaction_amount'] * 0.01); // 1%
        $vat = floor($serviceFeeAmount * 0.11); // 11%
        
        // Create service fee record
        $serviceFeeRecord = ServiceFee::create([
            'booking_id' => $validated['booking_id'],
            'merchant' => $validated['service_type'] === 'hotel' ? 'Traveloka Hotel' : 'Traveloka Flight',
            'transaction_time' => $transactionDate,
            'status' => $validated['status'],
            'transaction_amount' => $validated['transaction_amount'],
            'base_amount' => $serviceFeeAmount,
            'service_fee' => $serviceFeeAmount,
            'vat' => $vat,
            'total_tagihan' => $serviceFeeAmount + $vat,
            'service_type' => $validated['service_type'],
            'sheet' => $validated['sheet'],
            'hotel_name' => $validated['hotel_name'] ?? null,
            'room_type' => $validated['room_type'] ?? null,
            'route' => $validated['route'] ?? null,
            'trip_type' => $validated['trip_type'] ?? null,
            'pax' => $validated['pax'] ?? null,
            'airline_id' => $validated['airline_id'] ?? null,
            'booker_email' => $validated['booker_email'] ?? null,
            'employee_name' => $validated['employee_name'],
            'description' => $this->buildDescription($validated),
        ]);

        // Redirect back to the same sheet
        return redirect()->route('service-fee.index', ['sheet' => $serviceFeeRecord->sheet])
            ->with('success', 'Service fee data created successfully!');
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required_without:csv_files|file|mimes:csv,txt,xlsx,xls',
            'csv_files' => 'array',
            'csv_files.*' => 'file|mimes:csv,txt,xlsx,xls',
            'auto_preprocess' => 'boolean',
            'skip_summary' => 'boolean',
            'force_update' => 'boolean',
        ]);

        $autoPreprocess = $request->input('auto_preprocess', true);
        $skipSummary = $request->input('skip_summary', true);
        $forceUpdate = $request->input('force_update', false);

        $totalImported = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $allErrors = [];

        // Support both single and multiple file upload
        $files = [];
        if ($request->hasFile('csv_files')) {
            $files = $request->file('csv_files');
        } elseif ($request->hasFile('csv_file')) {
            $files = [$request->file('csv_file')];
        }

        if (empty($files)) {
            return redirect()->back()->with('error', 'No CSV files uploaded.');
        }

        // Process each file
        foreach ($files as $index => $file) {
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            try {
                $extension = strtolower($file->getClientOriginalExtension());
                $isExcel = in_array($extension, ['xlsx', 'xls']);
                
                if ($isExcel) {
                    // Convert Excel to CSV format
                    Log::info('Service Fee Excel file detected: ' . $file->getClientOriginalName());
                    
                    $csvData = $this->convertExcelToServiceFeeCsv($file);
                    
                    if (!$csvData) {
                        $allErrors[] = "Gagal mengkonversi Excel: " . $file->getClientOriginalName();
                        continue;
                    }
                    
                    Log::info('Service Fee Excel converted, lines: ' . substr_count($csvData, "\n"));
                    
                    // Create temp file from CSV string
                    $tempFile = tmpfile();
                    fwrite($tempFile, $csvData);
                    $tempFilePath = stream_get_meta_data($tempFile)['uri'];
                    $handle = fopen($tempFilePath, 'r');
                } else {
                    $handle = fopen($file->getPathname(), 'r');
                    $tempFile = null;
                }
            
            // Read header
            $header = fgetcsv($handle);
            
            // Log header for debugging
            Log::info('CSV Import - Header:', ['header' => $header]);
            
            // Detect CSV format (raw Traveloka vs preprocessed)
            // Preprocessed has separated columns like "Hotel Name", "Route", "Trip Type", etc.
            $isPreprocessed = in_array('Hotel Name', $header) 
                || in_array('Route', $header) 
                || in_array('Trip Type', $header)
                || in_array('Passenger Name (Employee)', $header);
            
            \Log::info('CSV Import - Format detected:', ['isPreprocessed' => $isPreprocessed]);
            
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map CSV columns
                $data = array_combine($header, $row);
                
                // Skip summary rows
                if ($skipSummary && isset($data['Description'])) {
                    if (stripos($data['Description'], 'SUBTOTAL') !== false ||
                        stripos($data['Description'], 'VAT') !== false ||
                        stripos($data['Description'], 'TOTAL') !== false) {
                        $skipped++;
                        continue;
                    }
                }

                // Get booking ID (different column names in different formats)
                $bookingId = $data['Booking ID'] ?? $data['booking_id'] ?? null;
                
                if (!$bookingId) {
                    $errors[] = "Missing Booking ID in row";
                    $skipped++;
                    continue;
                }

                // Check for duplicate booking_id
                $existingRecord = ServiceFee::where('booking_id', $bookingId)->first();
                if ($existingRecord && !$forceUpdate) {
                    $errors[] = "Duplicate booking ID: {$bookingId}";
                    $skipped++;
                    continue;
                }

                // Handle different CSV formats
                if ($isPreprocessed) {
                    // Preprocessed format (has Hotel Name, Employee Name, etc already separated)
                    
                    // Parse transaction amount - handle quoted values
                    $rawAmount = $data['Transaction Amount (Rp)'] ?? $data['Transaction Amount'] ?? '0';
                    $transactionAmount = $this->parseAmount($rawAmount);
                    
                    // Get service fee from CSV column (NOT calculated!)
                    // Support multiple column name variations
                    $rawServiceFee = $data['Service Fee (Rp)'] ?? $data['Service Fee'] ?? $data['Base Amount'] ?? '0';
                    $serviceFee = $this->parseAmount($rawServiceFee);
                    
                    // If service fee is missing or zero, calculate as fallback
                    if ($serviceFee == 0) {
                        $serviceFee = floor($transactionAmount * 0.01);
                    }
                    
                    $vat = floor($serviceFee * 0.11);
                    
                    // Determine service type from data - check flight-specific columns first
                    if (!empty($data['Route']) || !empty($data['Airline ID']) || !empty($data['Trip Type']) || !empty($data['Pax'])) {
                        $serviceType = 'flight';
                    } elseif (!empty($data['Hotel Name']) || !empty($data['Room Type'])) {
                        $serviceType = 'hotel';
                    } else {
                        // Fallback: detect from booking ID (FL for flight, HL for hotel)
                        $serviceType = (stripos($bookingId, 'FL') !== false) ? 'flight' : 'hotel';
                    }
                    
                    // Parse transaction time - handle both formats
                    $transactionTimeRaw = $data['Transaction Time'] ?? '';
                    try {
                        // Try parsing as "01 Aug 2025 10:58:04"
                        if (preg_match('/^\d{2}\s+\w+\s+\d{4}/', $transactionTimeRaw)) {
                            $transactionDate = $this->parseIndonesianDate($transactionTimeRaw);
                        } else {
                            $transactionDate = \Carbon\Carbon::parse($transactionTimeRaw);
                        }
                    } catch (\Exception $e) {
                        $transactionDate = \Carbon\Carbon::now();
                    }
                    
                    // Auto-generate sheet name from transaction time if not provided
                    $sheetName = !empty($data['Sheet']) && strtolower($data['Sheet']) !== 'unknown' 
                        ? $data['Sheet'] 
                        : $this->generateSheetName($transactionDate);
                    
                    // Get employee name - try multiple column name variations
                    $employeeName = $data['Employee Name'] 
                        ?? $data['Passenger Name (Employee)'] 
                        ?? $data['Passenger Name'] 
                        ?? null;
                    
                    // Clean quoted values (remove extra quotes from CSV)
                    if ($employeeName && is_string($employeeName)) {
                        $employeeName = trim($employeeName, '"');
                    }
                    
                    $recordData = [
                        'booking_id' => $bookingId,
                        'merchant' => $serviceType === 'hotel' ? 'Traveloka Hotel' : 'Traveloka Flight',
                        'transaction_time' => $transactionDate,
                        'status' => strtolower($data['Status'] ?? 'settlement'),
                        'transaction_amount' => $transactionAmount,
                        'base_amount' => $serviceFee,
                        'service_fee' => $serviceFee,
                        'vat' => $vat,
                        'total_tagihan' => $serviceFee + $vat,
                        'service_type' => $serviceType,
                        'sheet' => $sheetName,
                        'description' => '', // No description in preprocessed format
                        'hotel_name' => $data['Hotel Name'] ?? null,
                        'room_type' => $data['Room Type'] ?? null,
                        'route' => $data['Route'] ?? null,
                        'trip_type' => $data['Trip Type'] ?? null,
                        'pax' => $data['Pax'] ?? null,
                        'airline_id' => $data['Airline ID'] ?? null,
                        'booker_email' => $data['Booker Email'] ?? null,
                        'employee_name' => $employeeName,
                    ];
                    
                    \Log::info("Processing row (preprocessed): {$bookingId}", [
                        'service_type' => $serviceType,
                        'route' => $data['Route'] ?? 'N/A',
                        'hotel_name' => $data['Hotel Name'] ?? 'N/A',
                    ]);
                    
                    try {
                        if ($existingRecord && $forceUpdate) {
                            $existingRecord->update($recordData);
                            $updated++;
                        } else {
                            ServiceFee::create($recordData);
                            $imported++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error importing {$bookingId}: {$e->getMessage()}";
                        $skipped++;
                        \Log::error("Import error for {$bookingId}: " . $e->getMessage(), [
                            'data' => $recordData
                        ]);
                    }
                } else {
                    // Raw Traveloka format (needs parsing)
                    $serviceType = stripos($data['Merchant'], 'hotel') !== false ? 'hotel' : 'flight';

                    // Parse description if auto_preprocess is enabled
                    $parsedData = [];
                    if ($autoPreprocess && isset($data['Description'])) {
                        $parsedData = $serviceType === 'hotel' 
                            ? $this->parseHotelDescription($data['Description'])
                            : $this->parseFlightDescription($data['Description']);
                    }

                    // Calculate service fee
                    $transactionAmount = $this->parseAmount($data['Transaction Amount'] ?? '0');
                    $serviceFee = floor($transactionAmount * 0.01);
                    $vat = floor($serviceFee * 0.11);

                    // Auto-generate sheet name from transaction time if not provided
                    $transactionDate = \Carbon\Carbon::parse($data['Transaction Time']);
                    $sheetName = !empty($data['Sheet']) && strtolower($data['Sheet']) !== 'unknown' 
                        ? $data['Sheet'] 
                        : $this->generateSheetName($transactionDate);

                    $recordData = [
                        'booking_id' => $bookingId,
                        'merchant' => $data['Merchant'],
                        'transaction_time' => $transactionDate,
                        'status' => $data['Status'] ?? 'settlement',
                        'transaction_amount' => $transactionAmount,
                        'base_amount' => $serviceFee,
                        'service_fee' => $serviceFee,
                        'vat' => $vat,
                        'total_tagihan' => $serviceFee + $vat,
                        'service_type' => $serviceType,
                        'sheet' => $sheetName,
                        'description' => $data['Description'] ?? '',
                        'hotel_name' => $parsedData['hotel_name'] ?? null,
                        'room_type' => $parsedData['room_type'] ?? null,
                        'route' => $parsedData['route'] ?? null,
                        'trip_type' => $parsedData['trip_type'] ?? null,
                        'pax' => $parsedData['pax'] ?? null,
                        'airline_id' => $parsedData['airline_id'] ?? null,
                        'booker_email' => $parsedData['booker_email'] ?? null,
                        'employee_name' => $parsedData['employee_name'] ?? null,
                    ];

                    // Create or update record
                    try {
                        if ($existingRecord && $forceUpdate) {
                            $existingRecord->update($recordData);
                            $updated++;
                        } else {
                            ServiceFee::create($recordData);
                            $imported++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error importing {$bookingId}: {$e->getMessage()}";
                        $skipped++;
                    }
                }
            }

                fclose($handle);

                \Log::info('CSV Import completed for file: ' . $file->getClientOriginalName(), [
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors_count' => count($errors)
                ]);

                // Accumulate totals
                $totalImported += $imported;
                $totalUpdated += $updated;
                $totalSkipped += $skipped;
                $allErrors = array_merge($allErrors, $errors);

            } catch (\Exception $e) {
                \Log::error('CSV Import failed for file: ' . $file->getClientOriginalName(), [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                $allErrors[] = "File '{$file->getClientOriginalName()}': {$e->getMessage()}";
            }
        }

        // Build final message
        $message = "Processed " . count($files) . " file(s). ";
        $message .= "Successfully imported {$totalImported} new records.";
        if ($totalUpdated > 0) {
            $message .= " Updated {$totalUpdated} existing records.";
        }
        if ($totalSkipped > 0) {
            $message .= " Skipped {$totalSkipped} records.";
        }

        return redirect()->route('service-fee.index')
            ->with('success', $message)
            ->with('import_errors', $allErrors);
    }

    private function parseHotelDescription($description)
    {
        $result = [
            'hotel_name' => null,
            'room_type' => null,
            'employee_name' => null,
        ];

        // Remove "SERVICE FEE BID: xxxxx | " prefix if exists
        $description = preg_replace('/^SERVICE FEE BID:\s*\d+\s*\|\s*/i', '', $description);
        
        // Pattern: Hotel name, Room type (with possible numbers for nights), Employee name (usually all caps at the end)
        // Examples: 
        // "Amaris Hotel Hertasning Makassar Smart Queen 2 ANDI FADLI"
        // "CLARO Kendari Superior King 1 MUHAMMAD SUSGANDINATA"
        // "The Naripan Hotel Deluxe King Bed 3 DHANI JULIANTO PUTRA"
        
        // Try to extract employee name first (usually 2-4 words in caps at the end)
        if (preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{2,}){1,3})$/u', $description, $matches)) {
            $result['employee_name'] = trim($matches[1]);
            // Remove employee name from description
            $description = trim(str_replace($matches[1], '', $description));
        }
        
        // Try to extract room type (common patterns with optional "Bed" and number at the end)
        // Order matters: more specific patterns first (e.g., "Deluxe King Bed" before "King Bed")
        $roomTypePatterns = [
            'Deluxe King Bed',
            'Deluxe Queen Bed',
            'Deluxe Twin Bed',
            'Deluxe Single Bed',
            'Deluxe Double Bed',
            'Superior King Bed',
            'Superior Queen Bed',
            'Superior Twin Bed',
            'Standard King Bed',
            'Standard Queen Bed',
            'Standard Twin Bed',
            'Executive King Bed',
            'Executive Queen Bed',
            'Smart Queen Bed',
            'Smart Twin Bed',
            'Smart King Bed',
            'Queen Bed',
            'King Bed', 
            'Twin Bed',
            'Single Bed',
            'Double Bed',
            'Smart Queen',
            'Smart Twin',
            'Smart King',
            'Superior Queen',
            'Superior King',
            'Superior Twin',
            'Superior Double',
            'Superior Single',
            'Deluxe Queen',
            'Deluxe King',
            'Deluxe Twin',
            'Deluxe Single',
            'Deluxe Double',
            'Standard Queen',
            'Standard King',
            'Standard Twin',
            'Standard Single',
            'Standard Double',
            'Executive Queen',
            'Executive King',
            'Executive Twin',
            'Executive Suite',
            'Family Room',
            'Family',
            'Suite',
            'Queen',
            'King',
            'Twin',
            'Single',
            'Double',
            'Triple'
        ];
        
        $roomTypePattern = implode('|', array_map('preg_quote', $roomTypePatterns));
        
        // Match room type with optional number after it
        if (preg_match('/\b(' . $roomTypePattern . ')(?:\s+\d+)?\s*$/iu', $description, $matches)) {
            $result['room_type'] = trim($matches[1]);
            // Remove room type and trailing number from description
            $description = trim(preg_replace('/\s+' . preg_quote($matches[0], '/') . '\s*$/', '', $description));
        }
        
        // Whatever remains is the hotel name
        if (!empty($description)) {
            $result['hotel_name'] = trim($description);
        }

        return $result;
    }

    private function parseFlightDescription($description)
    {
        $result = [
            'route' => null,
            'trip_type' => null,
            'pax' => null,
            'airline_id' => null,
            'booker_email' => null,
            'employee_name' => null,
        ];

        $lines = explode("\n", $description);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Route
            if (preg_match('/([A-Z]{3})_([A-Z]{3})/', $line, $matches)) {
                $result['route'] = $matches[0];
            }
            
            // Trip type
            if (stripos($line, 'One Way') !== false) {
                $result['trip_type'] = 'One Way';
            } elseif (stripos($line, 'Return') !== false) {
                $result['trip_type'] = 'Return';
            }
            
            // Pax
            if (preg_match('/(\d+)\s*pax/i', $line, $matches)) {
                $result['pax'] = (int)$matches[1];
            }
            
            // Airline ID
            if (preg_match('/Airline\s+ID:\s*([A-Z0-9]+)/i', $line, $matches)) {
                $result['airline_id'] = $matches[1];
            }
            
            // Booker email
            if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $line, $matches)) {
                $result['booker_email'] = $matches[1];
            }
            
            // Employee name (usually last line, all caps)
            if (preg_match('/^([A-Z\s]{3,})$/', $line, $matches)) {
                $result['employee_name'] = trim($matches[1]);
            }
        }

        return $result;
    }

    private function parseAmount($amountString)
    {
        // Normalize Indonesian-formatted currency strings to integer rupiah
        // Examples:
        //  - "1.234.567" -> 1234567
        //  - "1.234,00" -> 1234 (ignore decimals)
        //  - "Rp 2.500.000" -> 2500000
        $cleaned = preg_replace('/[^0-9]/', '', (string)$amountString);
        return (float)($cleaned === '' ? 0 : $cleaned);
    }

    private function buildDescription($data)
    {
        if ($data['service_type'] === 'hotel') {
            return "{$data['hotel_name']} {$data['room_type']} {$data['employee_name']}";
        } else {
            $desc = "{$data['route']}\n";
            $desc .= "{$data['trip_type']}\n";
            $desc .= "{$data['pax']} pax\n";
            $desc .= "Airline ID: {$data['airline_id']}\n";
            if ($data['booker_email']) {
                $desc .= "{$data['booker_email']}\n";
            }
            $desc .= $data['employee_name'];
            return $desc;
        }
    }

    /**
     * Parse Indonesian/English date format 
     * Examples: "01 Okt 2025, 05:17:16" or "01 Aug 2025 10:58:04"
     */
    private function parseIndonesianDate($dateString)
    {
        // Indonesian to English month mapping
        $monthMap = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 
            'Agt' => 'Aug', 'Agust' => 'Aug', 'Agustus' => 'Aug',
            'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec'
        ];
        
        // Convert Indonesian months to English for Carbon parsing
        foreach ($monthMap as $indo => $eng) {
            if (stripos($dateString, $indo) !== false) {
                $dateString = str_ireplace($indo, $eng, $dateString);
                break;
            }
        }
        
        // Parse with Carbon (handles both "01 Aug 2025 10:58:04" and "01 Oct 2025, 05:17:16")
        return \Carbon\Carbon::parse($dateString);
    }

    /**
     * Generate sheet name from transaction date
     * Format: "Januari 2025", "Februari 2026", etc.
     */
    private function generateSheetName($transactionDate)
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $month = $transactionDate->month;
        $year = $transactionDate->year;

        return $monthNames[$month] . ' ' . $year;
    }

    /**
     * Get available sheets with the option to create new ones
     */
    public function getAvailableSheets()
    {
        $sheets = ServiceFee::select('sheet')
            ->distinct()
            ->pluck('sheet')
            ->toArray();
        
        // Sort chronologically
        usort($sheets, function($a, $b) {
            return $this->parseSheetDate($a) <=> $this->parseSheetDate($b);
        });

        return response()->json([
            'sheets' => $sheets,
            'currentMonth' => $this->generateSheetName(\Carbon\Carbon::now())
        ]);
    }

    /**
     * Show single service fee record
     */
    public function show($id)
    {
        $serviceFee = ServiceFee::findOrFail($id);
        
        // Ensure numeric values are properly set
        $serviceFee->transaction_amount = floatval($serviceFee->transaction_amount ?? 0);
        $serviceFee->base_amount = floatval($serviceFee->base_amount ?? 0);
        $serviceFee->service_fee = floatval($serviceFee->service_fee ?? 0);
        $serviceFee->vat = floatval($serviceFee->vat ?? 0);
        $serviceFee->total_tagihan = floatval($serviceFee->total_tagihan ?? 0);
        
        // If vat or total_tagihan are 0 or null, recalculate
        if ($serviceFee->vat == 0 || $serviceFee->total_tagihan == 0) {
            $serviceFeeAmount = $serviceFee->service_fee ?: ($serviceFee->base_amount ?: floor($serviceFee->transaction_amount * 0.01));
            $serviceFee->vat = floor($serviceFeeAmount * 0.11);
            $serviceFee->total_tagihan = $serviceFeeAmount + $serviceFee->vat;
        }
        
        return response()->json($serviceFee);
    }

    /**
     * Update service fee record
     */
    public function update(Request $request, $id)
    {
        $serviceFee = ServiceFee::findOrFail($id);
        
        $validated = $request->validate([
            'booking_id' => 'required|string|unique:service_fees,booking_id,' . $id,
            'transaction_time' => 'required|date',
            'sheet' => 'nullable|string',
            'status' => 'required|string',
            'transaction_amount' => 'required|numeric|min:0',
            'service_type' => 'required|in:hotel,flight',
            // Hotel fields
            'hotel_name' => 'required_if:service_type,hotel|nullable|string',
            'room_type' => 'required_if:service_type,hotel|nullable|string',
            // Flight fields
            'route' => 'required_if:service_type,flight|nullable|string',
            'trip_type' => 'required_if:service_type,flight|nullable|string',
            'pax' => 'required_if:service_type,flight|nullable|integer|min:1',
            'airline_id' => 'required_if:service_type,flight|nullable|string',
            'booker_email' => 'nullable|email',
            // Common
            'employee_name' => 'required|string',
        ]);

        // Auto-generate sheet name if needed
        $transactionDate = \Carbon\Carbon::parse($validated['transaction_time']);
        if (empty($validated['sheet']) || strtolower($validated['sheet']) === 'auto') {
            $validated['sheet'] = $this->generateSheetName($transactionDate);
        }

        // Recalculate service fee and VAT
        $serviceFeeAmount = floor($validated['transaction_amount'] * 0.01);
        $vat = floor($serviceFeeAmount * 0.11);

        // Update record
        $serviceFee->update([
            'booking_id' => $validated['booking_id'],
            'merchant' => $validated['service_type'] === 'hotel' ? 'Traveloka Hotel' : 'Traveloka Flight',
            'transaction_time' => $transactionDate,
            'status' => $validated['status'],
            'transaction_amount' => $validated['transaction_amount'],
            'base_amount' => $serviceFeeAmount,
            'service_fee' => $serviceFeeAmount,
            'vat' => $vat,
            'total_tagihan' => $serviceFeeAmount + $vat,
            'service_type' => $validated['service_type'],
            'sheet' => $validated['sheet'],
            'hotel_name' => $validated['hotel_name'] ?? null,
            'room_type' => $validated['room_type'] ?? null,
            'route' => $validated['route'] ?? null,
            'trip_type' => $validated['trip_type'] ?? null,
            'pax' => $validated['pax'] ?? null,
            'airline_id' => $validated['airline_id'] ?? null,
            'booker_email' => $validated['booker_email'] ?? null,
            'employee_name' => $validated['employee_name'],
            'description' => $this->buildDescription($validated),
        ]);

        // Redirect back to the same sheet
        return redirect()->route('service-fee.index', ['sheet' => $serviceFee->sheet])
            ->with('success', 'Service fee data updated successfully!');
    }

    /**
     * Delete service fee record
     */
    public function destroy($id)
    {
        $serviceFee = ServiceFee::findOrFail($id);
        $bookingId = $serviceFee->booking_id;
        $sheet = $serviceFee->sheet; // Store sheet before deleting
        
        $serviceFee->delete();

        // Redirect back to the same sheet
        return redirect()->route('service-fee.index', ['sheet' => $sheet])
            ->with('success', "Service fee record {$bookingId} deleted successfully!");
    }

    /**
     * Delete entire sheet or specific service type in a sheet
     */
    public function destroySheet(Request $request)
    {
        $validated = $request->validate([
            'sheet' => 'required|string',
            'service_type' => 'nullable|in:hotel,flight,all',
        ]);

        $sheet = $validated['sheet'];
        $serviceType = $validated['service_type'] ?? 'all';

        $query = ServiceFee::where('sheet', $sheet);

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        $count = $query->count();
        
        if ($count === 0) {
            return redirect()->route('service-fee.index')
                ->with('error', 'No records found for deletion.');
        }

        $query->delete();

        $typeLabel = $serviceType === 'all' ? 'all records' : ($serviceType === 'hotel' ? 'hotel records' : 'flight records');
        $message = "Successfully deleted {$count} {$typeLabel} from sheet '{$sheet}'.";

        \Log::info("Sheet deletion: {$count} records deleted", [
            'sheet' => $sheet,
            'service_type' => $serviceType
        ]);

        return redirect()->route('service-fee.index')
            ->with('success', $message);
    }
    
    /**
     * Convert Excel file to Service Fee CSV format
     */
    private function convertExcelToServiceFeeCsv($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            Log::info('Service Fee Excel loaded, total rows: ' . count($rows));
            
            // Find header row
            $headerRow = null;
            $headerRowIndex = -1;
            
            for ($i = 0; $i < min(5, count($rows)); $i++) {
                $row = $rows[$i];
                // Look for key columns - support both hotel and flight formats
                if (in_array('Booking ID', $row) || in_array('Transaction Time', $row) || in_array('Hotel Name', $row) || in_array('Route', $row)) {
                    $headerRow = $row;
                    $headerRowIndex = $i;
                    Log::info('Service Fee header found at row: ' . $i);
                    break;
                }
            }
            
            if (!$headerRow) {
                Log::error('Service Fee header row not found');
                return null;
            }
            
            // Detect if this is hotel or flight based on columns
            $isHotel = in_array('Hotel Name', $headerRow);
            $isFlight = in_array('Route', $headerRow) || in_array('Airline ID', $headerRow);
            
            // Build CSV header - use preprocessed format
            if ($isHotel) {
                $csvHeader = 'No,Transaction Time,Booking ID,Status,Hotel Name,Room Type,Employee Name,Transaction Amount (Rp),Service Fee (Rp),Sheet,Settlement Method,Currency';
            } else {
                $csvHeader = 'No,Transaction Time,Booking ID,Status,Route,Trip Type,Pax,Airline ID,Booker Email,Passenger Name (Employee),Transaction Amount (Rp),Service Fee (Rp),Sheet,Settlement Method,Currency';
            }
            
            $csvLines = [$csvHeader];
            $rowNumber = 0;
            
            // Find column indices based on actual format
            $colMap = [];
            foreach ($headerRow as $idx => $colName) {
                $colMap[$colName] = $idx;
            }
            
            // Process data rows
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $bookingId = $row[$colMap['Booking ID'] ?? 2] ?? '';
                
                if (empty($bookingId)) {
                    continue;
                }
                
                $rowNumber++;
                
                // Common fields
                $transactionTime = $row[$colMap['Transaction Time'] ?? 1] ?? '';
                $status = $row[$colMap['Status'] ?? 3] ?? 'ISSUED';
                $transactionAmount = $row[$colMap['Transaction Amount (Rp)'] ?? $colMap['Transaction Amount'] ?? 0] ?? 0;
                $serviceFee = $row[$colMap['Service Fee (Rp)'] ?? $colMap['Service Fee'] ?? 0] ?? 0;
                $sheetName = $row[$colMap['Sheet'] ?? 0] ?? 'Unknown';
                $settlementMethod = $row[$colMap['Settlement Method'] ?? 0] ?? 'INVOICE';
                $currency = $row[$colMap['Currency'] ?? 0] ?? 'IDR';
                
                // Clean amounts
                if (is_numeric($transactionAmount)) {
                    $transactionAmount = (int)$transactionAmount;
                } else {
                    $transactionAmount = (int)preg_replace('/[^\d]/', '', $transactionAmount);
                }
                
                if (is_numeric($serviceFee)) {
                    $serviceFee = (int)$serviceFee;
                } else {
                    $serviceFee = (int)preg_replace('/[^\d]/', '', $serviceFee);
                }
                
                if ($isHotel) {
                    $hotelName = $row[$colMap['Hotel Name'] ?? 4] ?? '';
                    $roomType = $row[$colMap['Room Type'] ?? 5] ?? '';
                    $employeeName = $row[$colMap['Employee Name'] ?? 6] ?? '';
                    
                    $csvLines[] = sprintf(
                        '%d,"%s","%s","%s","%s","%s","%s",%d,%d,"%s","%s","%s"',
                        $rowNumber,
                        $transactionTime,
                        $bookingId,
                        $status,
                        $hotelName,
                        $roomType,
                        $employeeName,
                        $transactionAmount,
                        $serviceFee,
                        $sheetName,
                        $settlementMethod,
                        $currency
                    );
                } else {
                    $route = $row[$colMap['Route'] ?? 4] ?? '';
                    $tripType = $row[$colMap['Trip Type'] ?? 5] ?? '';
                    $pax = $row[$colMap['Pax'] ?? 6] ?? 1;
                    $airlineId = $row[$colMap['Airline ID'] ?? 7] ?? '';
                    $bookerEmail = $row[$colMap['Booker Email'] ?? 8] ?? '';
                    $passengerName = $row[$colMap['Passenger Name (Employee)'] ?? $colMap['Employee Name'] ?? 9] ?? '';
                    
                    $csvLines[] = sprintf(
                        '%d,"%s","%s","%s","%s","%s",%d,"%s","%s","%s",%d,%d,"%s","%s","%s"',
                        $rowNumber,
                        $transactionTime,
                        $bookingId,
                        $status,
                        $route,
                        $tripType,
                        $pax,
                        $airlineId,
                        $bookerEmail,
                        $passengerName,
                        $transactionAmount,
                        $serviceFee,
                        $sheetName,
                        $settlementMethod,
                        $currency
                    );
                }
            }
            
            Log::info('Service Fee Excel converted', [
                'type' => $isHotel ? 'hotel' : 'flight',
                'rows' => count($csvLines) - 1
            ]);
            
            return implode("\n", $csvLines);
            
        } catch (\Exception $e) {
            Log::error('Service Fee Excel conversion error: ' . $e->getMessage());
            return null;
        }
    }
}

