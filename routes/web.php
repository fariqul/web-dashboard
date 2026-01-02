<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CCTransactionController;
use App\Http\Controllers\ServiceFeeController;
use App\Http\Controllers\BfkoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SppdTransactionController;
use App\Models\SheetAdditionalFee;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// BFKO Routes
Route::get('/bfko', [BfkoController::class, 'index'])->name('bfko.index');
Route::post('/bfko/import', [BfkoController::class, 'import'])->name('bfko.import');
Route::get('/bfko/employee/{nip}', [BfkoController::class, 'employeeDetail'])->name('bfko.employee.detail');
Route::post('/bfko/payment/store', [BfkoController::class, 'storePayment'])->name('bfko.payment.store');
Route::put('/bfko/payment/{id}', [BfkoController::class, 'updatePayment'])->name('bfko.payment.update');
Route::delete('/bfko/payment/{id}', [BfkoController::class, 'deletePayment'])->name('bfko.payment.delete');
Route::delete('/bfko/employee/{nip}', [BfkoController::class, 'deleteEmployee'])->name('bfko.employee.delete');
Route::delete('/bfko/delete-all', [BfkoController::class, 'deleteAll'])->name('bfko.delete.all');
Route::get('/bfko/export/pdf', [BfkoController::class, 'exportPdf'])->name('bfko.export.pdf');
Route::get('/bfko/export/excel', [BfkoController::class, 'exportExcel'])->name('bfko.export.excel');

Route::get('/service-fee', [ServiceFeeController::class, 'index'])->name('service-fee.index');
Route::get('/service-fee/sheets', [ServiceFeeController::class, 'getAvailableSheets']);
Route::post('/service-fee/store', [ServiceFeeController::class, 'store']);
Route::post('/service-fee/import-csv', [ServiceFeeController::class, 'importCsv']);
Route::delete('/service-fee/sheet/delete', [ServiceFeeController::class, 'destroySheet']);
Route::delete('/service-fee/delete-all', [ServiceFeeController::class, 'deleteAll'])->name('service-fee.delete.all');
Route::get('/service-fee/{id}', [ServiceFeeController::class, 'show']);
Route::put('/service-fee/{id}', [ServiceFeeController::class, 'update']);
Route::delete('/service-fee/{id}', [ServiceFeeController::class, 'destroy']);

Route::get('/cc-card/destination-detail', function () {
    $destination = request('destination');
    $selectedSheet = request('sheet', 'all');
    $transactionType = request('type', 'payment'); // payment, refund, atau all
    $selectedYear = request('year', 'all');
    $searchQuery = request('search', '');
    $sortField = request('sort', 'departure_date');
    $sortDirection = request('direction', 'desc');
    
    if (!$destination) {
        return redirect('/cc-card');
    }
    
    // Query transaksi berdasarkan destination dan sheet
    $query = \App\Models\CCTransaction::where('trip_destination_full', $destination);
    
    // Filter by transaction type
    if ($transactionType !== 'all') {
        $query->where('transaction_type', $transactionType);
    }
    
    if ($selectedSheet !== 'all') {
        $query->where('sheet', $selectedSheet);
    }
    if ($selectedYear !== 'all') {
        $query->whereRaw("strftime('%Y', departure_date) = ?", [$selectedYear]);
    }
    
    // Apply search filter
    if (!empty($searchQuery)) {
        $query->where(function($q) use ($searchQuery) {
            $q->where('employee_name', 'like', '%' . $searchQuery . '%')
              ->orWhere('booking_id', 'like', '%' . $searchQuery . '%')
              ->orWhere('personel_number', 'like', '%' . $searchQuery . '%')
              ->orWhere('trip_number', 'like', '%' . $searchQuery . '%');
        });
    }
    
    // Get all transactions for summary (before pagination)
    $allTransactions = $query->get();
    $totalAmount = $allTransactions->sum('payment_amount');
    
    // Count trips based on transaction type
    if ($transactionType === 'payment') {
        // For payment: Count unique trips by grouping personel_number + trip_number
        $uniqueTrips = $allTransactions
            ->filter(function($transaction) {
                return !empty($transaction->personel_number) && !empty($transaction->trip_number);
            })
            ->groupBy(function($transaction) {
                return $transaction->personel_number . '|' . $transaction->trip_number;
            })
            ->count();
        
        $transactionsWithoutTripInfo = $allTransactions
            ->filter(function($transaction) {
                return empty($transaction->personel_number) || empty($transaction->trip_number);
            })
            ->count();
        
        $totalTrips = $uniqueTrips + $transactionsWithoutTripInfo;
    } else {
        // For refund or all: Count all transactions normally
        $totalTrips = $allTransactions->count();
    }
    $averageAmount = $totalTrips > 0 ? $totalAmount / $totalTrips : 0;
    $uniqueEmployees = $allTransactions->unique('employee_name')->count();
    
    // Apply sorting
    $validSortFields = ['employee_name', 'booking_id', 'personel_number', 'trip_number', 'departure_date', 'return_date', 'duration_days', 'payment_amount'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'departure_date';
    }
    if (!in_array($sortDirection, ['asc', 'desc'])) {
        $sortDirection = 'desc';
    }
    
    // Apply pagination
    $transactions = \App\Models\CCTransaction::where('trip_destination_full', $destination)
        ->when($transactionType !== 'all', function($q) use ($transactionType) {
            $q->where('transaction_type', $transactionType);
        })
        ->when($selectedSheet !== 'all', function($q) use ($selectedSheet) {
            $q->where('sheet', $selectedSheet);
        })
        ->when($selectedYear !== 'all', function($q) use ($selectedYear) {
            $q->whereRaw("strftime('%Y', departure_date) = ?", [$selectedYear]);
        })
        ->when(!empty($searchQuery), function($q) use ($searchQuery) {
            $q->where(function($subQ) use ($searchQuery) {
                $subQ->where('employee_name', 'like', '%' . $searchQuery . '%')
                     ->orWhere('booking_id', 'like', '%' . $searchQuery . '%')
                     ->orWhere('personel_number', 'like', '%' . $searchQuery . '%')
                     ->orWhere('trip_number', 'like', '%' . $searchQuery . '%');
            });
        })
        ->orderBy($sortField, $sortDirection)
        ->paginate(10)
        ->withQueryString();
    
    return Inertia::render('DestinationDetail', [
        'destination' => $destination,
        'selectedSheet' => $selectedSheet,
        'selectedYear' => $selectedYear,
        'transactionType' => $transactionType,
        'transactions' => $transactions,
        'filters' => [
            'search' => $searchQuery,
            'sort' => $sortField,
            'direction' => $sortDirection,
        ],
        'summary' => [
            'totalAmount' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
            'totalTrips' => $totalTrips,
            'averageAmount' => 'Rp ' . number_format($averageAmount, 0, ',', '.'),
            'uniqueEmployees' => $uniqueEmployees,
        ]
    ]);
});

// CC Card Refund Detail Route (by employee name)
Route::get('/cc-card/refund-detail', function () {
    $employeeName = request('employee');
    $selectedSheet = request('sheet', 'all');
    $selectedYear = request('year', 'all');
    $searchQuery = request('search', '');
    $sortField = request('sort', 'departure_date');
    $sortDirection = request('direction', 'desc');
    
    if (!$employeeName) {
        return redirect('/cc-card');
    }
    
    // Query refund transactions by employee name
    $query = \App\Models\CCTransaction::where('employee_name', $employeeName)
        ->where('transaction_type', 'refund');
    
    if ($selectedSheet !== 'all') {
        $query->where('sheet', $selectedSheet);
    }
    if ($selectedYear !== 'all') {
        $query->whereRaw("strftime('%Y', created_at) = ?", [$selectedYear]);
    }
    
    // Apply search filter
    if (!empty($searchQuery)) {
        $query->where(function($q) use ($searchQuery) {
            $q->where('booking_id', 'like', '%' . $searchQuery . '%')
              ->orWhere('sheet', 'like', '%' . $searchQuery . '%');
        });
    }
    
    // Get all transactions for summary (before pagination)
    $allTransactions = $query->get();
    $totalAmount = $allTransactions->sum('payment_amount');
    $totalRefunds = $allTransactions->count();
    $averageAmount = $totalRefunds > 0 ? $totalAmount / $totalRefunds : 0;
    
    // Apply sorting
    $validSortFields = ['booking_id', 'payment_amount', 'created_at', 'sheet', 'departure_date', 'trip_destination_full'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'created_at';
    }
    if (!in_array($sortDirection, ['asc', 'desc'])) {
        $sortDirection = 'desc';
    }
    
    // Apply pagination
    $transactions = \App\Models\CCTransaction::where('employee_name', $employeeName)
        ->where('transaction_type', 'refund')
        ->when($selectedSheet !== 'all', function($q) use ($selectedSheet) {
            $q->where('sheet', $selectedSheet);
        })
        ->when($selectedYear !== 'all', function($q) use ($selectedYear) {
            $q->whereRaw("strftime('%Y', created_at) = ?", [$selectedYear]);
        })
        ->when(!empty($searchQuery), function($q) use ($searchQuery) {
            $q->where(function($subQ) use ($searchQuery) {
                $subQ->where('booking_id', 'like', '%' . $searchQuery . '%')
                     ->orWhere('sheet', 'like', '%' . $searchQuery . '%');
            });
        })
        ->orderBy($sortField, $sortDirection)
        ->paginate(10)
        ->withQueryString();
    
    return Inertia::render('RefundDetail', [
        'employeeName' => $employeeName,
        'selectedSheet' => $selectedSheet,
        'selectedYear' => $selectedYear,
        'transactions' => $transactions,
        'filters' => [
            'search' => $searchQuery,
            'sort' => $sortField,
            'direction' => $sortDirection,
        ],
        'summary' => [
            'totalAmount' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
            'totalRefunds' => $totalRefunds,
            'averageAmount' => 'Rp ' . number_format($averageAmount, 0, ',', '.'),
        ]
    ]);
});

// SPPD Main Route
Route::get('/sppd', function () {
    // Helper functions
    $formatSummaryDisplay = function($value) {
        if ($value >= 1000000000) {
            return 'Rp' . number_format($value / 1000000000, 1) . 'M';
        } else if ($value >= 1000000) {
            return 'Rp' . number_format($value / 1000000, 1) . 'Jt';
        } else if ($value >= 1000) {
            return 'Rp' . number_format($value / 1000, 1) . 'Rb';
        }
        return 'Rp' . number_format($value, 0);
    };
    
    $formatRupiah = function($value) {
        return 'Rp' . number_format($value / 1000000, 1) . 'Jt';
    };
    
    // Helper to determine trip status based on dates
    $getTripStatus = function($tripBeginsOn, $tripEndsOn) {
        $today = now()->startOfDay();
        $beginDate = $tripBeginsOn ? \Carbon\Carbon::parse($tripBeginsOn)->startOfDay() : null;
        $endDate = $tripEndsOn ? \Carbon\Carbon::parse($tripEndsOn)->startOfDay() : null;
        
        if (!$beginDate) return 'unknown';
        
        if ($beginDate->gt($today)) {
            return 'upcoming';
        } elseif ($endDate && $endDate->lt($today)) {
            return 'completed';
        } else {
            return 'ongoing';
        }
    };
    
    $selectedFilter = request('sheet', 'all');
    $selectedReason = request('reason', 'all');
    $selectedBank = request('bank', 'all');
    $selectedStatus = request('status', 'all'); // New status filter
    $searchQuery = request('search', '');
    
    $query = \App\Models\SppdTransaction::query();
    
    $selectedSheet = 'all';
    $selectedYear = 'all';
    
    if ($selectedFilter !== 'all') {
        if (str_starts_with($selectedFilter, 'year:')) {
            $selectedYear = substr($selectedFilter, 5);
            $query->whereRaw("strftime('%Y', trip_begins_on) = ?", [$selectedYear]);
        } else {
            $selectedSheet = $selectedFilter;
            $query->where('sheet', $selectedSheet);
        }
    }
    
    if ($selectedReason !== 'all') {
        $query->where('reason_for_trip', $selectedReason);
    }
    
    if ($selectedBank !== 'all') {
        $query->where('beneficiary_bank_name', $selectedBank);
    }
    
    if (!empty($searchQuery)) {
        $query->search($searchQuery);
    }
    
    $allTransactionsForStatus = $query->clone()->orderBy('trip_begins_on', 'desc')->get();
    
    // Add status to each transaction
    $allTransactionsForStatus = $allTransactionsForStatus->map(function($t) use ($getTripStatus) {
        $t->trip_status = $getTripStatus($t->trip_begins_on, $t->trip_ends_on);
        return $t;
    });
    
    // Calculate status counts BEFORE filtering by status
    $upcomingCount = $allTransactionsForStatus->where('trip_status', 'upcoming')->count();
    $ongoingCount = $allTransactionsForStatus->where('trip_status', 'ongoing')->count();
    $completedCount = $allTransactionsForStatus->where('trip_status', 'completed')->count();
    
    // Calculate paid amounts per status
    $upcomingAmount = $allTransactionsForStatus->where('trip_status', 'upcoming')->sum('paid_amount');
    $ongoingAmount = $allTransactionsForStatus->where('trip_status', 'ongoing')->sum('paid_amount');
    $completedAmount = $allTransactionsForStatus->where('trip_status', 'completed')->sum('paid_amount');
    
    // Now filter by status if needed
    if ($selectedStatus !== 'all') {
        $transactions = $allTransactionsForStatus->filter(function($t) use ($selectedStatus) {
            return $t->trip_status === $selectedStatus;
        })->values();
    } else {
        $transactions = $allTransactionsForStatus;
    }
    
    // Statistics (based on filtered transactions)
    $totalPaidAmount = $transactions->sum('paid_amount');
    $totalTrips = $transactions->count();
    $averageAmount = $totalTrips > 0 ? $totalPaidAmount / $totalTrips : 0;
    $uniqueCustomers = $transactions->unique('customer_name')->count();
    
    // Available sheets with formatting
    $availableSheets = \App\Models\SppdTransaction::query()
        ->select('sheet')
        ->distinct()
        ->orderBy('sheet')
        ->pluck('sheet')
        ->filter()
        ->toArray();
    
    $availableYears = \App\Models\SppdTransaction::selectRaw("DISTINCT strftime('%Y', trip_begins_on) as year")
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->filter()
        ->toArray();
    
    // Build availableFilters array similar to CC Card
    $availableFilters = [];
    $availableFilters[] = ['value' => 'all', 'label' => 'All Periods', 'type' => 'all'];
    
    foreach ($availableYears as $year) {
        $availableFilters[] = ['value' => "year:$year", 'label' => "Year: $year", 'type' => 'year'];
    }
    
    foreach ($availableSheets as $sheet) {
        $availableFilters[] = ['value' => $sheet, 'label' => $sheet, 'type' => 'sheet'];
    }
    
    // Available reasons for filter
    $availableReasons = \App\Models\SppdTransaction::query()
        ->select('reason_for_trip')
        ->distinct()
        ->orderBy('reason_for_trip')
        ->pluck('reason_for_trip')
        ->filter()
        ->toArray();
    
    // Available banks for filter
    $availableBanks = \App\Models\SppdTransaction::query()
        ->select('beneficiary_bank_name')
        ->distinct()
        ->orderBy('beneficiary_bank_name')
        ->pluck('beneficiary_bank_name')
        ->filter()
        ->toArray();
    
    // Group by reason for list
    $reasons = $transactions->groupBy('reason_for_trip')->map(function($group) use ($formatSummaryDisplay) {
        $reason = $group->first()->reason_for_trip ?: 'Tidak Ada Alasan';
        $count = $group->count();
        $totalAmount = $group->sum('paid_amount');
        
        // Get unique destinations for this reason
        $destinations = $group->pluck('trip_destination_full')->unique()->filter()->implode(', ');
        
        // Format amount for display
        $formattedAmount = $formatSummaryDisplay($totalAmount);
        
        return [
            'reason' => $reason,
            'trips' => "$count trip" . ($count > 1 ? 's' : ''),
            'amount' => $formattedAmount,
            'rawAmount' => $totalAmount / 1000000,
            'destinations' => $destinations ?: 'N/A',
        ];
    })->sortByDesc('rawAmount')->values()->toArray();
    
    // Dual Monthly chart data: Payment Date vs Trip Date
    $paymentChartData = [];
    $tripChartData = [];
    
    if ($selectedSheet !== 'all' && $selectedYear === 'all') {
        // Single sheet selected - Chart 1: Payment Date
        $paymentData = $transactions->filter(function($item) {
            return !empty($item->planned_payment_date);
        })->groupBy(function($item) {
            return date('Y-m', strtotime($item->planned_payment_date));
        })->map(function($group, $month) use ($selectedSheet) {
            // Format: "November 2025" (month and year only, no specific day)
            $monthName = date('F Y', strtotime($month . '-01'));
            // Get ALL unique dates (show all specific dates, not range)
            $dates = $group->pluck('planned_payment_date')->map(function($date) {
                return date('j', strtotime($date)); // Day number only
            })->sort()->unique()->values()->implode(', ');
            return [
                'sheet' => substr($month, 0, 7),
                'fullName' => $monthName,
                'rawDate' => $month, // Pass Y-m format for month-level grouping
                'dates' => $dates, // All dates: "7, 11, 18, 19, 20, 28"
                'transactionCount' => $group->count(),
                'total' => $group->sum('paid_amount') / 1000000,
            ];
        })->sortBy('sheet')->values()->toArray();
        $paymentChartData = $paymentData;
        
        // Chart 2: Trip Date
        $tripData = $transactions->groupBy(function($item) {
            return date('Y-m', strtotime($item->trip_begins_on));
        })->map(function($group, $month) use ($selectedSheet) {
            // Format: "November 2025" (month and year only, no specific day)
            $monthName = date('F Y', strtotime($month . '-01'));
            // Get ALL unique dates (show all specific dates, not range)
            $dates = $group->pluck('trip_begins_on')->map(function($date) {
                return date('j', strtotime($date)); // Day number only
            })->sort()->unique()->values()->implode(', ');
            return [
                'sheet' => substr($month, 0, 7),
                'fullName' => $monthName,
                'rawDate' => $month, // Pass Y-m format for month-level grouping
                'dates' => $dates, // All dates: "7, 11, 18, 19, 20, 28"
                'transactionCount' => $group->count(),
                'total' => $group->sum('paid_amount') / 1000000,
            ];
        })->sortBy('sheet')->values()->toArray();
        $tripChartData = $tripData;
        
    } elseif ($selectedYear !== 'all') {
        // Year selected - Chart 1: Payment Date
        $paymentData = $transactions->filter(function($item) {
            return !empty($item->planned_payment_date);
        })->groupBy(function($item) {
            return date('Y-m', strtotime($item->planned_payment_date));
        })->map(function($group, $month) use ($selectedYear) {
            // Format: "November 2025" (month and year only)
            $monthName = date('F Y', strtotime($month . '-01'));
            // Get ALL unique dates (show all specific dates, not range)
            $dates = $group->pluck('planned_payment_date')->map(function($date) {
                return date('j', strtotime($date)); // Day number only
            })->sort()->unique()->values()->implode(', ');
            return [
                'sheet' => substr($month, 0, 7),
                'fullName' => $monthName,
                'rawDate' => $month, // Pass Y-m format for month-level grouping
                'dates' => $dates, // All dates: "7, 11, 18, 19, 20, 28"
                'transactionCount' => $group->count(),
                'total' => $group->sum('paid_amount') / 1000000,
            ];
        })->sortBy('sheet')->values()->toArray();
        $paymentChartData = $paymentData;
        
        // Chart 2: Trip Date
        $tripData = $transactions->groupBy(function($item) {
            return date('Y-m', strtotime($item->trip_begins_on));
        })->map(function($group, $month) use ($selectedYear) {
            // Format: "November 2025" (month and year only)
            $monthName = date('F Y', strtotime($month . '-01'));
            // Get ALL unique dates (show all specific dates, not range)
            $dates = $group->pluck('trip_begins_on')->map(function($date) {
                return date('j', strtotime($date)); // Day number only
            })->sort()->unique()->values()->implode(', ');
            return [
                'sheet' => substr($month, 0, 7),
                'fullName' => $monthName,
                'rawDate' => $month, // Pass Y-m format for month-level grouping
                'dates' => $dates, // All dates: "7, 11, 18, 19, 20, 28"
                'transactionCount' => $group->count(),
                'total' => $group->sum('paid_amount') / 1000000,
            ];
        })->sortBy('sheet')->values()->toArray();
        $tripChartData = $tripData;
        
    } else {
        // All Periods - Chart 1: Payment Date
        $allTransactions = \App\Models\SppdTransaction::query()->orderBy('trip_begins_on')->get();
        $paymentData = $allTransactions->filter(function($item) {
            return !empty($item->planned_payment_date);
        })->groupBy(function($item) {
            return date('Y-m', strtotime($item->planned_payment_date));
        })->map(function($group, $month) {
            // Format: "November 2025" (full month name and year)
            $monthName = date('F Y', strtotime($month . '-01'));
            // Get ALL unique dates (show all specific dates, not range)
            $dates = $group->pluck('planned_payment_date')->map(function($date) {
                return date('j', strtotime($date)); // Day number only
            })->sort()->unique()->values()->implode(', ');
            return [
                'sheet' => substr($month, 0, 7),
                'fullName' => $monthName,
                'rawDate' => $month, // Pass Y-m format for month-level grouping
                'dates' => $dates, // All dates: "7, 11, 18, 19, 20, 28"
                'transactionCount' => $group->count(),
                'total' => $group->sum('paid_amount') / 1000000,
            ];
        })->sortBy('sheet')->values()->toArray();
        $paymentChartData = $paymentData;
        
        // Chart 2: Trip Date
        $tripData = $allTransactions->groupBy(function($item) {
            return date('Y-m', strtotime($item->trip_begins_on));
        })->map(function($group, $month) {
            // Format: "November 2025" (full month name and year)
            $monthName = date('F Y', strtotime($month . '-01'));
            // Get ALL unique dates (show all specific dates, not range)
            $dates = $group->pluck('trip_begins_on')->map(function($date) {
                return date('j', strtotime($date)); // Day number only
            })->sort()->unique()->values()->implode(', ');
            return [
                'sheet' => substr($month, 0, 7),
                'fullName' => $monthName,
                'rawDate' => $month, // Pass Y-m format for month-level grouping
                'dates' => $dates, // All dates: "7, 11, 18, 19, 20, 28"
                'transactionCount' => $group->count(),
                'total' => $group->sum('paid_amount') / 1000000,
            ];
        })->sortBy('sheet')->values()->toArray();
        $tripChartData = $tripData;
    }
    
    // Top customers by trip count
    $topCustomersByCount = $transactions->groupBy('customer_name')->map(function($group) {
        return [
            'name' => $group->first()->customer_name,
            'count' => $group->count(),
        ];
    })->sortByDesc('count')->take(10)->values()->toArray();
    
    // Top customers by amount
    $topCustomersByAmount = $transactions->groupBy('customer_name')->map(function($group) use ($formatRupiah) {
        $totalAmount = $group->sum('paid_amount');
        return [
            'name' => $group->first()->customer_name,
            'count' => $group->count(),
            'total' => $formatRupiah($totalAmount),
            'rawAmount' => $totalAmount,
        ];
    })->sortByDesc('rawAmount')->take(10)->values()->toArray();
    
    // Popular destinations by amount
    $popularDestinations = $transactions->groupBy('trip_destination_full')->map(function($group) use ($formatRupiah) {
        $totalAmount = $group->sum('paid_amount');
        return [
            'destination' => $group->first()->trip_destination_full ?: 'Unknown',
            'count' => $group->count(),
            'total' => $formatRupiah($totalAmount),
            'rawAmount' => $totalAmount,
        ];
    })->filter(function($item) {
        return $item['destination'] !== 'Unknown' && !empty($item['destination']);
    })->sortByDesc('rawAmount')->take(10)->values()->toArray();
    
    // Monthly Overview: Trip Begins vs Trip Ends grouped by month
    $monthlyOverviewData = [];
    $allMonths = collect();
    
    // Collect all months from trip_begins_on
    $transactions->each(function($t) use (&$allMonths) {
        if ($t->trip_begins_on) {
            $month = date('Y-m', strtotime($t->trip_begins_on));
            $allMonths->push($month);
        }
        if ($t->trip_ends_on) {
            $month = date('Y-m', strtotime($t->trip_ends_on));
            $allMonths->push($month);
        }
    });
    
    $uniqueMonths = $allMonths->unique()->sort()->values();
    
    foreach ($uniqueMonths as $month) {
        // Get begins transactions for this month
        $beginsTransactions = $transactions->filter(function($t) use ($month) {
            return $t->trip_begins_on && date('Y-m', strtotime($t->trip_begins_on)) === $month;
        });
        $beginsCount = $beginsTransactions->count();
        
        // Get all unique begins dates (day numbers only)
        $beginsDates = $beginsTransactions->map(function($t) {
            return (int) date('j', strtotime($t->trip_begins_on)); // e.g., 5
        })->sort()->unique()->values()->implode(', ');
        
        // Get ends transactions for this month
        $endsTransactions = $transactions->filter(function($t) use ($month) {
            return $t->trip_ends_on && date('Y-m', strtotime($t->trip_ends_on)) === $month;
        });
        $endsCount = $endsTransactions->count();
        
        // Get all unique ends dates (day numbers only)
        $endsDates = $endsTransactions->map(function($t) {
            return (int) date('j', strtotime($t->trip_ends_on)); // e.g., 10
        })->sort()->unique()->values()->implode(', ');
        
        $monthName = date('M Y', strtotime($month . '-01'));
        
        $monthlyOverviewData[] = [
            'month' => $monthName,
            'rawMonth' => $month,
            'begins' => $beginsCount,
            'ends' => $endsCount,
            'beginsDates' => $beginsDates,
            'endsDates' => $endsDates,
        ];
    }
    
    // Trips by Reason with status - grouped by reason and status
    $tripsByReasonAll = $allTransactionsForStatus->groupBy('reason_for_trip')->map(function($group) use ($formatSummaryDisplay) {
        $reason = $group->first()->reason_for_trip ?: 'Tidak Ada Alasan';
        $count = $group->count();
        $totalAmount = $group->sum('paid_amount');
        
        return [
            'reason' => $reason,
            'count' => $count,
            'amount' => $formatSummaryDisplay($totalAmount),
            'rawAmount' => $totalAmount,
        ];
    })->sortByDesc('rawAmount')->values()->toArray();
    
    // Trips by Reason - Upcoming only
    $tripsByReasonUpcoming = $allTransactionsForStatus->filter(function($t) {
        return $t->trip_status === 'upcoming';
    })->groupBy('reason_for_trip')->map(function($group) use ($formatSummaryDisplay) {
        $reason = $group->first()->reason_for_trip ?: 'Tidak Ada Alasan';
        $count = $group->count();
        $totalAmount = $group->sum('paid_amount');
        
        return [
            'reason' => $reason,
            'count' => $count,
            'amount' => $formatSummaryDisplay($totalAmount),
            'rawAmount' => $totalAmount,
        ];
    })->sortByDesc('rawAmount')->values()->toArray();
    
    // Trips by Reason - Ongoing only
    $tripsByReasonOngoing = $allTransactionsForStatus->filter(function($t) {
        return $t->trip_status === 'ongoing';
    })->groupBy('reason_for_trip')->map(function($group) use ($formatSummaryDisplay) {
        $reason = $group->first()->reason_for_trip ?: 'Tidak Ada Alasan';
        $count = $group->count();
        $totalAmount = $group->sum('paid_amount');
        
        return [
            'reason' => $reason,
            'count' => $count,
            'amount' => $formatSummaryDisplay($totalAmount),
            'rawAmount' => $totalAmount,
        ];
    })->sortByDesc('rawAmount')->values()->toArray();
    
    // Trips by Reason - Completed only
    $tripsByReasonCompleted = $allTransactionsForStatus->filter(function($t) {
        return $t->trip_status === 'completed';
    })->groupBy('reason_for_trip')->map(function($group) use ($formatSummaryDisplay) {
        $reason = $group->first()->reason_for_trip ?: 'Tidak Ada Alasan';
        $count = $group->count();
        $totalAmount = $group->sum('paid_amount');
        
        return [
            'reason' => $reason,
            'count' => $count,
            'amount' => $formatSummaryDisplay($totalAmount),
            'rawAmount' => $totalAmount,
        ];
    })->sortByDesc('rawAmount')->values()->toArray();
    
    return Inertia::render('SppdMonitoring', [
        'totalPaidAmount' => $totalPaidAmount,
        'totalTrips' => $totalTrips,
        'averageAmount' => $averageAmount,
        'uniqueCustomers' => $uniqueCustomers,
        'reasons' => $reasons,
        'availableFilters' => $availableFilters,
        'availableReasons' => $availableReasons,
        'availableBanks' => $availableBanks,
        'selectedFilter' => $selectedFilter,
        'selectedReason' => $selectedReason,
        'selectedBank' => $selectedBank,
        'selectedStatus' => $selectedStatus,
        'paymentChartData' => $paymentChartData,
        'tripChartData' => $tripChartData,
        'topCustomersByCount' => $topCustomersByCount,
        'topCustomersByAmount' => $topCustomersByAmount,
        'popularDestinations' => $popularDestinations,
        'monthlyOverviewData' => $monthlyOverviewData,
        'tripsByReason' => [
            'all' => $tripsByReasonAll,
            'upcoming' => $tripsByReasonUpcoming,
            'ongoing' => $tripsByReasonOngoing,
            'completed' => $tripsByReasonCompleted,
        ],
        'statusCounts' => [
            'upcoming' => $upcomingCount,
            'ongoing' => $ongoingCount,
            'completed' => $completedCount,
        ],
        'statusAmounts' => [
            'upcoming' => $upcomingAmount,
            'ongoing' => $ongoingAmount,
            'completed' => $completedAmount,
        ],
    ]);
});

// SPPD Reason/Destination Detail Route (supports both reason and destination filtering)
Route::get('/sppd/destination-detail', function () {
    $destination = request('destination');
    $reason = request('reason');
    $selectedSheet = request('sheet', 'all');
    $selectedYear = request('year', 'all');
    $selectedReason = request('filter_reason', request('reasonFilter', 'all')); // accept both parameter names
    $selectedBank = request('bank', 'all');
    $selectedStatus = request('status', 'all'); // Add status parameter
    $searchQuery = request('search', '');
    $sortField = request('sort', 'trip_begins_on');
    $sortDirection = request('direction', 'desc');
    
    // Require either reason or destination
    if (!$reason && !$destination) {
        return redirect('/sppd');
    }
    
    $query = \App\Models\SppdTransaction::query();
    
    // Primary filter: reason or destination
    if ($reason) {
        $query->where('reason_for_trip', $reason);
        $pageTitle = $reason;
        $filterType = 'reason';
    } else {
        $query->where('trip_destination_full', $destination);
        $pageTitle = $destination;
        $filterType = 'destination';
    }
    
    // Apply sheet filter
    if ($selectedSheet !== 'all') {
        $query->where('sheet', $selectedSheet);
    }
    
    // Apply year filter
    if ($selectedYear !== 'all') {
        $query->whereRaw("strftime('%Y', trip_begins_on) = ?", [$selectedYear]);
    }
    
    // Apply additional reason filter (when viewing destination)
    if ($selectedReason !== 'all') {
        $query->where('reason_for_trip', $selectedReason);
    }
    
    // Apply bank filter
    if ($selectedBank !== 'all') {
        $query->where('beneficiary_bank_name', $selectedBank);
    }
    
    // Apply search
    if (!empty($searchQuery)) {
        $query->search($searchQuery);
    }
    
    // Apply sorting
    $query->orderBy($sortField, $sortDirection);
    
    // Get paginated results
    $transactions = $query->paginate(20)->withQueryString();
    
    // Calculate summary
    $summaryQuery = \App\Models\SppdTransaction::query();
    
    if ($reason) {
        $summaryQuery->where('reason_for_trip', $reason);
    } else {
        $summaryQuery->where('trip_destination_full', $destination);
    }
    
    if ($selectedSheet !== 'all') {
        $summaryQuery->where('sheet', $selectedSheet);
    }
    
    if ($selectedYear !== 'all') {
        $summaryQuery->whereRaw("strftime('%Y', trip_begins_on) = ?", [$selectedYear]);
    }
    
    if ($selectedReason !== 'all') {
        $summaryQuery->where('reason_for_trip', $selectedReason);
    }
    
    if ($selectedBank !== 'all') {
        $summaryQuery->where('beneficiary_bank_name', $selectedBank);
    }
    
    $allTrips = $summaryQuery->get();
    
    $summary = [
        'totalTrips' => $allTrips->count(),
        'totalAmount' => $allTrips->sum('paid_amount'),
        'uniqueCustomers' => $allTrips->unique('customer_name')->count(),
    ];
    
    return Inertia::render('TripDestinationDetail', [
        'pageTitle' => $pageTitle,
        'filterType' => $filterType,
        'destination' => $destination,
        'reason' => $reason,
        'selectedSheet' => $selectedSheet,
        'selectedYear' => $selectedYear,
        'selectedReason' => $selectedReason,
        'selectedBank' => $selectedBank,
        'selectedStatus' => $selectedStatus,
        'transactions' => $transactions,
        'filters' => [
            'search' => $searchQuery,
            'sort' => $sortField,
            'direction' => $sortDirection,
        ],
        'summary' => $summary,
    ]);
});

Route::get('/cc-card', function () {
    // Support filter sheet via query parameter (format: "sheet" or "all" or "year:2025")
    $selectedFilter = request('sheet', 'all'); // Can be sheet name, 'all', or 'year:2025'
    $selectedCard = request('card', 'all'); // '5657', '9386', or 'all'
    $searchQuery = request('search', '');
    
    $query = \App\Models\CCTransaction::query();
    
    // Parse filter: check if it's a year filter
    $selectedSheet = 'all';
    $selectedYear = 'all';
    $yearForComparison = 'all'; // Year to use for comparison chart
    
    if ($selectedFilter !== 'all') {
        if (str_starts_with($selectedFilter, 'year:')) {
            // Year filter: "year:2025"
            $selectedYear = substr($selectedFilter, 5);
            $yearForComparison = $selectedYear;
            // Match year for both formats: M/D/YYYY and YYYY-MM-DD
            $query->where(function($q) use ($selectedYear) {
                $q->whereRaw("SUBSTR(departure_date, -4) = ?", [$selectedYear]) // M/D/YYYY format
                  ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", [$selectedYear]); // YYYY-MM-DD format
            });
        } else {
            // Sheet filter: specific sheet name (e.g., "Juli 2025")
            $selectedSheet = $selectedFilter;
            $query->where('sheet', $selectedSheet);
            
            // Extract year from sheet name for comparison chart
            if (preg_match('/\b(20\d{2})\b/', $selectedSheet, $matches)) {
                $yearForComparison = $matches[0];
            }
        }
    }
    
    // Apply CC card filter
    if ($selectedCard !== 'all') {
        $query->where('sheet', 'like', "%CC {$selectedCard}%");
    }
    
    // Apply search filter if provided
    if (!empty($searchQuery)) {
        $query->where(function($q) use ($searchQuery) {
            $q->where('employee_name', 'like', '%' . $searchQuery . '%')
              ->orWhere('booking_id', 'like', '%' . $searchQuery . '%')
              ->orWhere('personel_number', 'like', '%' . $searchQuery . '%')
              ->orWhere('trip_destination_full', 'like', '%' . $searchQuery . '%')
              ->orWhere('origin', 'like', '%' . $searchQuery . '%')
              ->orWhere('destination', 'like', '%' . $searchQuery . '%');
        });
    }
    
    $transactions = $query->orderBy('departure_date', 'desc')->get();
    
    // Pisahkan payment dan refund
    $paymentTransactions = $transactions->where('transaction_type', 'payment');
    $refundTransactions = $transactions->where('transaction_type', 'refund');
    
    // Statistik untuk sheet yang dipilih
    $grossPayment = $paymentTransactions->sum('payment_amount');
    $totalRefund = $refundTransactions->sum('payment_amount');
    $netPayment = $grossPayment - $totalRefund; // Net payment setelah dikurangi refund
    
    // Biaya tambahan per sheet (dari database) - hitung manual karena accessor tidak bisa di-pluck
    $additionalFeesMap = [];
    foreach (SheetAdditionalFee::all() as $fee) {
        $additionalFeesMap[$fee->sheet_name] = $fee->biaya_adm_bunga + $fee->biaya_transfer + $fee->iuran_tahunan;
    }
    
    // Tambahkan biaya tambahan HANYA untuk sheet tertentu (bukan year filter atau all)
    if ($selectedSheet !== 'all' && isset($additionalFeesMap[$selectedSheet])) {
        $totalPayment = $netPayment + $additionalFeesMap[$selectedSheet];
    } else if ($selectedSheet === 'all' && $selectedYear === 'all' && $selectedCard === 'all') {
        // Jika benar-benar "all" (tidak ada filter apapun), jumlahkan semua biaya tambahan
        $totalAdditionalFees = array_sum($additionalFeesMap);
        $totalPayment = $netPayment + $totalAdditionalFees;
    } else {
        // Year filter, CC card filter, atau kondisi lain: 
        // Hitung biaya tambahan hanya dari sheet yang ada di transaksi yang difilter
        $relevantSheets = $transactions->pluck('sheet')->unique()->toArray();
        $filteredAdditionalFees = 0;
        foreach ($relevantSheets as $sheetName) {
            if (isset($additionalFeesMap[$sheetName])) {
                $filteredAdditionalFees += $additionalFeesMap[$sheetName];
            }
        }
        $totalPayment = $netPayment + $filteredAdditionalFees;
    }
    
    // Hitung total biaya administrasi dan bunga (semua additional fees)
    // Ambil sheet names dari transaksi yang sudah difilter
    $relevantSheets = $transactions->pluck('sheet')->unique()->toArray();
    
    // Sum semua biaya tambahan dari sheets yang ada di transaksi yang difilter
    $totalAdminInterest = 0;
    if (!empty($relevantSheets)) {
        $fees = SheetAdditionalFee::whereIn('sheet_name', $relevantSheets)->get();
        foreach ($fees as $fee) {
            $totalAdminInterest += $fee->biaya_adm_bunga + $fee->biaya_transfer + $fee->iuran_tahunan;
        }
    }
    
    // Daftar sheet yang tersedia
    $availableSheets = \App\Models\CCTransaction::query()
        ->select('sheet')
        ->distinct()
        ->orderBy('sheet')
        ->pluck('sheet')
        ->toArray();

    // Daftar tahun yang tersedia (berdasarkan departure_date)
    // Handle 2 formats: M/D/YYYY (e.g., 6/15/2025) and YYYY-MM-DD (e.g., 2026-02-13)
    $availableYears = \App\Models\CCTransaction::selectRaw("
        DISTINCT CASE 
            WHEN departure_date LIKE '%/%' THEN SUBSTR(departure_date, -4)
            WHEN departure_date LIKE '%-%' THEN SUBSTR(departure_date, 1, 4)
            ELSE NULL
        END as year
    ")
        ->orderByRaw("
            CASE 
                WHEN departure_date LIKE '%/%' THEN SUBSTR(departure_date, -4)
                WHEN departure_date LIKE '%-%' THEN SUBSTR(departure_date, 1, 4)
                ELSE NULL
            END DESC
        ")
        ->pluck('year')
        ->filter(function($year) {
            // Filter only valid 4-digit years starting with 20
            return preg_match('/^20\d{2}$/', $year);
        })
        ->unique()
        ->values()
        ->toArray();
    
    // Data perbandingan per sheet (pertimbangkan filter tahun dan CC card bila dipilih)
    $allTxQuery = \App\Models\CCTransaction::query();
    if ($yearForComparison !== 'all') {
        $allTxQuery->where(function($q) use ($yearForComparison) {
            $q->whereRaw("SUBSTR(departure_date, -4) = ?", [$yearForComparison]) // M/D/YYYY format
              ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", [$yearForComparison]); // YYYY-MM-DD format
        });
    }
    // Apply CC card filter to sheet comparison
    if ($selectedCard !== 'all') {
        $allTxQuery->where('sheet', 'like', "%CC {$selectedCard}%");
    }
    $allTransactions = $allTxQuery->get();
    
    // Month ordering map (Indonesian month names)
    $monthOrder = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
    ];
    
    $sheetComparison = $allTransactions
        ->groupBy('sheet')
        ->map(function($group, $sheetName) use ($monthOrder) {
            $payments = $group->where('transaction_type', 'payment');
            $refunds = $group->where('transaction_type', 'refund');
            
            // Singkat label sheet untuk chart
            $sheetLabel = $sheetName;
            $monthName = '';
            
            if (strlen($sheetLabel) > 15) {
                // Juli 2025 -> Juli
                // Agustus 2025 -> Agus
                // September 2025 - CC 5657 -> Sep (5657)
                // September 2025 - CC 9386 -> Sep (9386)
                if (str_contains($sheetLabel, 'September')) {
                    $ccNum = str_contains($sheetLabel, '5657') ? '5657' : '9386';
                    $sheetLabel = "Sep ($ccNum)";
                    $monthName = 'September';
                } else {
                    $parts = explode(' ', $sheetLabel);
                    $monthName = $parts[0];
                    $monthMap = [
                        'Juli' => 'Juli', 'Agustus' => 'Agus', 'September' => 'Sep',
                        'Oktober' => 'Okt', 'November' => 'Nov', 'Desember' => 'Des',
                        'Januari' => 'Jan', 'Februari' => 'Feb', 'Maret' => 'Mar',
                        'April' => 'Apr', 'Mei' => 'Mei', 'Juni' => 'Jun'
                    ];
                    $sheetLabel = $monthMap[$parts[0]] ?? $parts[0];
                }
            } else {
                // Extract month from short sheet names
                $parts = explode(' ', $sheetName);
                $monthName = $parts[0];
            }
            
            // Extract year from sheet name
            preg_match('/\d{4}/', $sheetName, $yearMatch);
            $year = isset($yearMatch[0]) ? (int)$yearMatch[0] : 2025;
            
            $grossPaymentAmount = $payments->sum('payment_amount');
            $refundAmount = $refunds->sum('payment_amount');
            $netPaymentAmount = $grossPaymentAmount - $refundAmount;
            
            // Biaya tambahan per sheet (dari database)
            $sheetFee = SheetAdditionalFee::where('sheet_name', $sheetName)->first();
            $additionalFee = $sheetFee ? ($sheetFee->biaya_adm_bunga + $sheetFee->biaya_transfer + $sheetFee->iuran_tahunan) : 0;
            $totalPaymentWithFees = $netPaymentAmount + $additionalFee;
            
            return [
                'sheet' => $sheetLabel,
                'payment' => round($totalPaymentWithFees / 1000000, 1), // Net payment + biaya tambahan
                'refund' => round($refundAmount / 1000000, 1),
                'total' => round($totalPaymentWithFees / 1000000, 1), // Total = Net payment + biaya tambahan
                'monthOrder' => $monthOrder[$monthName] ?? 99,
                'year' => $year,
                'fullName' => $sheetName
            ];
        })
        ->sort(function($a, $b) {
            // Sort by year asc (lowest first), then month asc
            if ($a['year'] != $b['year']) {
                return $a['year'] - $b['year'];
            }
            return $a['monthOrder'] - $b['monthOrder'];
        })
        ->values();
    
    // Top destinations (dari bulan yang dipilih) - return ALL destinations
    $topDestinations = $paymentTransactions
        ->groupBy('trip_destination_full')
        ->map(function($group) {
            // Count unique trips by personel_number + trip_number
            $uniqueTrips = $group
                ->filter(function($transaction) {
                    return !empty($transaction->personel_number) && !empty($transaction->trip_number);
                })
                ->groupBy(function($transaction) {
                    return $transaction->personel_number . '|' . $transaction->trip_number;
                })
                ->count();
            
            // Add transactions without trip info
            $transactionsWithoutTripInfo = $group
                ->filter(function($transaction) {
                    return empty($transaction->personel_number) || empty($transaction->trip_number);
                })
                ->count();
            
            $totalTrips = $uniqueTrips + $transactionsWithoutTripInfo;
            
            return [
                'route' => $group->first()->trip_destination_full,
                'trips' => $totalTrips . ' trips',
                'amount' => 'Rp ' . number_format($group->sum('payment_amount'), 0, ',', '.'),
                'rawAmount' => $group->sum('payment_amount'),
                'type' => 'payment'
            ];
        })
        ->sortByDesc(function($item) {
            return $item['rawAmount'];
        })
        ->values(); // Return all destinations, frontend will handle limiting to 5
    
    // Refund transactions list - group by employee_name since refunds don't have destination
    $refundList = $refundTransactions
        ->groupBy('employee_name')
        ->map(function($group) {
            return [
                'route' => $group->first()->employee_name, // Use employee name as "route" for display
                'employee_name' => $group->first()->employee_name,
                'trips' => $group->count() . ' refund' . ($group->count() > 1 ? 's' : ''),
                'amount' => 'Rp ' . number_format($group->sum('payment_amount'), 0, ',', '.'),
                'rawAmount' => $group->sum('payment_amount'),
                'type' => 'refund'
            ];
        })
        ->sortByDesc(function($item) {
            return $item['rawAmount'];
        })
        ->values();
    
    // Data untuk monthly chart (sheet yang dipilih atau all sheets)
    $monthlyChartData = null;
    
    if ($selectedSheet !== 'all') {
        // Single sheet - return as array with one item
        $sheetFee = SheetAdditionalFee::where('sheet_name', $selectedSheet)->first();
        $sheetAdditionalFee = $sheetFee ? ($sheetFee->biaya_adm_bunga + $sheetFee->biaya_transfer + $sheetFee->iuran_tahunan) : 0;
        $grossPaymentForSheet = $paymentTransactions->sum('payment_amount');
        $refundForSheet = $refundTransactions->sum('payment_amount');
        $netPaymentForSheet = $grossPaymentForSheet - $refundForSheet + $sheetAdditionalFee;
        
        // Singkat label untuk chart
        $shortLabel = $selectedSheet;
        if (str_contains($selectedSheet, 'September')) {
            $ccNum = str_contains($selectedSheet, '5657') ? '5657' : '9386';
            $shortLabel = "Sep ($ccNum)";
        } else {
            $parts = explode(' ', $selectedSheet);
            $monthMap = ['Juli' => 'Juli', 'Agustus' => 'Agus', 'Oktober' => 'Okt', 'Januari' => 'Jan', 'Februari' => 'Feb', 'Maret' => 'Mar', 'April' => 'Apr', 'Mei' => 'Mei', 'Juni' => 'Jun', 'November' => 'Nov', 'Desember' => 'Des'];
            $shortLabel = $monthMap[$parts[0]] ?? $parts[0];
        }
        
        $monthlyChartData = [[
            'sheet' => $shortLabel,
            'payment' => round($netPaymentForSheet / 1000000, 1),
            'refund' => round($refundForSheet / 1000000, 1),
        ]];
    } else {
        // All sheets - return multiple bars (satu per sheet)
        $allTxForChart = \App\Models\CCTransaction::query();
        if ($selectedYear !== 'all') {
            $allTxForChart->where(function($q) use ($selectedYear) {
                $q->whereRaw("SUBSTR(departure_date, -4) = ?", [$selectedYear]) // M/D/YYYY format
                  ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", [$selectedYear]); // YYYY-MM-DD format
            });
        }
        // Apply CC card filter to chart
        if ($selectedCard !== 'all') {
            $allTxForChart->where('sheet', 'like', "%CC {$selectedCard}%");
        }
        $allTransactionsGrouped = $allTxForChart->get()->groupBy('sheet');
        
        // Month ordering map (Indonesian month names)
        $monthOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];
        
        $monthlyChartData = $allTransactionsGrouped->map(function($group, $sheetName) use ($monthOrder) {
            $payments = $group->where('transaction_type', 'payment');
            $refunds = $group->where('transaction_type', 'refund');
            
            $grossPayment = $payments->sum('payment_amount');
            $refundAmount = $refunds->sum('payment_amount');
            
            // Get additional fee from database
            $sheetFee = SheetAdditionalFee::where('sheet_name', $sheetName)->first();
            $additionalFee = $sheetFee ? ($sheetFee->biaya_adm_bunga + $sheetFee->biaya_transfer + $sheetFee->iuran_tahunan) : 0;
            $netPayment = $grossPayment - $refundAmount + $additionalFee;
            
            // Singkat label untuk chart
            $shortLabel = $sheetName;
            $monthName = '';
            if (str_contains($sheetName, 'September')) {
                $ccNum = str_contains($sheetName, '5657') ? '5657' : '9386';
                $shortLabel = "Sep ($ccNum)";
                $monthName = 'September';
            } else {
                $parts = explode(' ', $sheetName);
                $monthName = $parts[0];
                $monthMap = ['Juli' => 'Juli', 'Agustus' => 'Agus', 'Oktober' => 'Okt', 'Januari' => 'Jan', 'Februari' => 'Feb', 'Maret' => 'Mar', 'April' => 'Apr', 'Mei' => 'Mei', 'Juni' => 'Jun', 'November' => 'Nov', 'Desember' => 'Des'];
                $shortLabel = $monthMap[$parts[0]] ?? $parts[0];
            }
            
            // Extract year from sheet name (format: "Month Year")
            preg_match('/\d{4}/', $sheetName, $yearMatch);
            $year = isset($yearMatch[0]) ? (int)$yearMatch[0] : 2025;
            
            return [
                'sheet' => $shortLabel,
                'payment' => round($netPayment / 1000000, 1),
                'refund' => round($refundAmount / 1000000, 1),
                'monthOrder' => $monthOrder[$monthName] ?? 99,
                'year' => $year,
                'fullName' => $sheetName
            ];
        })->sort(function($a, $b) {
            // Sort by year asc (lowest first), then month asc
            if ($a['year'] != $b['year']) {
                return $a['year'] - $b['year'];
            }
            return $a['monthOrder'] - $b['monthOrder'];
        })->values()->toArray();
    }
    
    // Top 10 Employees by Transaction Count (unique trips)
    $topEmployeesByCount = $paymentTransactions
        ->groupBy('employee_name')
        ->map(function($group) {
            // Count unique trips by personel_number + trip_number
            $uniqueTrips = $group
                ->filter(function($transaction) {
                    return !empty($transaction->personel_number) && !empty($transaction->trip_number);
                })
                ->groupBy(function($transaction) {
                    return $transaction->personel_number . '|' . $transaction->trip_number;
                })
                ->count();
            
            // Add transactions without trip info
            $transactionsWithoutTripInfo = $group
                ->filter(function($transaction) {
                    return empty($transaction->personel_number) || empty($transaction->trip_number);
                })
                ->count();
            
            $totalTrips = $uniqueTrips + $transactionsWithoutTripInfo;
            $totalAmount = $group->sum('payment_amount');
            
            return [
                'name' => $group->first()->employee_name,
                'count' => $totalTrips,
                'total' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                'rawTotal' => $totalAmount
            ];
        })
        ->sortByDesc('count')
        ->take(10)
        ->values();
    
    // Top 10 Employees by Total Amount (unique trips)
    $topEmployeesByAmount = $paymentTransactions
        ->groupBy('employee_name')
        ->map(function($group) {
            // Count unique trips by personel_number + trip_number
            $uniqueTrips = $group
                ->filter(function($transaction) {
                    return !empty($transaction->personel_number) && !empty($transaction->trip_number);
                })
                ->groupBy(function($transaction) {
                    return $transaction->personel_number . '|' . $transaction->trip_number;
                })
                ->count();
            
            // Add transactions without trip info
            $transactionsWithoutTripInfo = $group
                ->filter(function($transaction) {
                    return empty($transaction->personel_number) || empty($transaction->trip_number);
                })
                ->count();
            
            $totalTrips = $uniqueTrips + $transactionsWithoutTripInfo;
            $totalAmount = $group->sum('payment_amount');
            
            return [
                'name' => $group->first()->employee_name,
                'count' => $totalTrips,
                'total' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                'rawTotal' => $totalAmount
            ];
        })
        ->sortByDesc('rawTotal')
        ->take(10)
        ->values();
    
    // Payment vs Refund Ratio (for pie chart)
    $paymentRefundRatio = [
        'payment' => round($grossPayment / 1000000, 1),
        'refund' => round($totalRefund / 1000000, 1),
        'paymentPercentage' => $grossPayment + $totalRefund > 0 
            ? round(($grossPayment / ($grossPayment + $totalRefund)) * 100, 1) 
            : 0,
        'refundPercentage' => $grossPayment + $totalRefund > 0 
            ? round(($totalRefund / ($grossPayment + $totalRefund)) * 100, 1) 
            : 0,
    ];
    
    // Build available filters with sections
    $availableFilters = [
        [
            'label' => 'All Sheets',
            'value' => 'all',
            'type' => 'all'
        ]
    ];
    
    // Add year filters FIRST (before sheet headers)
    foreach ($availableYears as $year) {
        $availableFilters[] = [
            'label' => "Year: $year",
            'value' => "year:$year",
            'type' => 'year'
        ];
    }
    
    // Group sheets by year and add with year headers
    $sheetsByYear = [];
    $monthOrder = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
    ];
    
    foreach ($availableSheets as $sheet) {
        // Extract year from sheet name
        preg_match('/\b(20\d{2})\b/', $sheet, $matches);
        $sheetYear = $matches[0] ?? 'Unknown';
        
        if (!isset($sheetsByYear[$sheetYear])) {
            $sheetsByYear[$sheetYear] = [];
        }
        $sheetsByYear[$sheetYear][] = $sheet;
    }
    
    // Sort years descending
    krsort($sheetsByYear);
    
    // Add sheets grouped by year with proper month sorting
    foreach ($sheetsByYear as $year => $sheets) {
        // Sort sheets by month
        usort($sheets, function($a, $b) use ($monthOrder) {
            $orderA = 99;
            $orderB = 99;
            
            foreach ($monthOrder as $month => $order) {
                if (str_starts_with($a, $month)) {
                    $orderA = $order;
                }
                if (str_starts_with($b, $month)) {
                    $orderB = $order;
                }
            }
            
            return $orderA - $orderB;
        });
        
        // Add year header
        $availableFilters[] = [
            'label' => "Year $year",
            'value' => "header:$year",
            'type' => 'header'
        ];
        
        // Add sheets for this year
        foreach ($sheets as $sheet) {
            $availableFilters[] = [
                'label' => $sheet,
                'value' => $sheet,
                'type' => 'sheet',
                'year' => $year
            ];
        }
    }
    
    // Get available years from sheet names for chart year filter
    $availableYears = \App\Models\CCTransaction::query()
        ->selectRaw("DISTINCT strftime('%Y', departure_date) as year")
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->toArray();
    
    return Inertia::render('CCCardMonitoring', [
        'totalPayment' => $totalPayment,
        'totalAdminInterest' => $totalAdminInterest,
        'totalRefund' => $totalRefund,
        'sheetComparison' => $sheetComparison,
        'monthlyChartData' => $monthlyChartData,
        'destinations' => $topDestinations,
        'refundList' => $refundList,
        'availableFilters' => $availableFilters,
        'selectedFilter' => $selectedFilter,
        'selectedCard' => $selectedCard,
        'availableYears' => $availableYears,
        'topEmployeesByCount' => $topEmployeesByCount,
        'topEmployeesByAmount' => $topEmployeesByAmount,
        'paymentRefundRatio' => $paymentRefundRatio,
    ]);
});

// CC Transaction Management
Route::get('/cc-card/transaction/autocomplete', [CCTransactionController::class, 'autocomplete']);
Route::post('/cc-card/transaction/store', [CCTransactionController::class, 'store']);
Route::post('/cc-card/transaction/import', [CCTransactionController::class, 'import']);
Route::delete('/cc-card/delete-all', [CCTransactionController::class, 'deleteAll']);
Route::get('/cc-card/transaction/{id}', [CCTransactionController::class, 'show']);
Route::put('/cc-card/transaction/{id}', [CCTransactionController::class, 'update']);
Route::delete('/cc-card/transaction/{id}', [CCTransactionController::class, 'destroy']);
Route::delete('/cc-card/sheet/delete', [CCTransactionController::class, 'destroySheet']);
Route::get('/cc-card/fees', [CCTransactionController::class, 'getFees']);
Route::post('/cc-card/fees/update', [CCTransactionController::class, 'updateFees']);
Route::delete('/cc-card/fees/delete', [CCTransactionController::class, 'deleteFees']);

// SPPD Transaction Management Routes
Route::get('/sppd/transaction/autocomplete', [SppdTransactionController::class, 'autocomplete']);
Route::post('/sppd/store', [SppdTransactionController::class, 'store']); // Alias for manual add
Route::post('/sppd/transaction/store', [SppdTransactionController::class, 'store']);
Route::post('/sppd/transaction/import', [SppdTransactionController::class, 'import']);
Route::get('/sppd/transaction/{id}', [SppdTransactionController::class, 'show']);
Route::put('/sppd/transaction/{id}', [SppdTransactionController::class, 'update']);
Route::delete('/sppd/transaction/{id}', [SppdTransactionController::class, 'destroy']);
Route::delete('/sppd/sheet/delete', [SppdTransactionController::class, 'destroySheet']);

Route::get('/transaction/{person}', function ($person) {
    return Inertia::render('TransactionDetail', [
        'person' => ucwords(str_replace('-', '. ', $person))
    ]);
});

Route::get('/documents', function () {
    return Inertia::render('Documents');
});
