<?php

namespace App\Http\Controllers;

use App\Models\BfkoData;
use App\Models\ServiceFee;
use App\Models\CCTransaction;
use App\Models\SppdTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get fund source filter (54 or 52) - default to 54
        $fundSource = $request->get('fund', '54');
        
        // Get BFKO summary (Fund 52)
        $bfkoTotal = BfkoData::sum('nilai_angsuran');
        $bfkoCount = BfkoData::count();
        // Count unique employees - must use get()->count() for accurate distinct count
        $bfkoEmployees = BfkoData::whereNotNull('nip')
            ->where('nip', '!=', '')
            ->select('nip')
            ->distinct()
            ->get()
            ->count();
        
        // Get Service Fee summary (Fund 54)
        $serviceFeeTotal = ServiceFee::sum('transaction_amount');
        $serviceFeeCount = ServiceFee::count();
        $serviceFeeHotel = ServiceFee::where('service_type', 'hotel')->count();
        $serviceFeeFlight = ServiceFee::where('service_type', 'flight')->count();
        
        // Get CC Card summary (Fund 54)
        $ccTotal = CCTransaction::sum('payment_amount');
        $ccCount = CCTransaction::count();
        // Count unique employees - must use get()->count() for accurate distinct count
        $ccEmployees = CCTransaction::whereNotNull('personel_number')
            ->where('personel_number', '!=', '')
            ->select('personel_number')
            ->distinct()
            ->get()
            ->count();
        
        // Get SPPD summary (Fund 54)
        $sppdTotal = SppdTransaction::sum('paid_amount');
        $sppdCount = SppdTransaction::count();
        // Count unique employees with normalized names (using collection for accurate counting)
        $sppdEmployees = SppdTransaction::select('customer_name')
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->get()
            ->map(function($item) {
                return strtolower(trim($item->customer_name));
            })
            ->unique()
            ->count();
        
        // Get monthly data for all categories (all 12 months)
        $monthOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];
        
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        // Build monthly data using batch queries instead of per-month queries
        // BFKO: group by bulan
        $bfkoByMonth = BfkoData::selectRaw("bulan, SUM(nilai_angsuran) as total")
            ->groupBy('bulan')
            ->pluck('total', 'bulan');
        
        // CC Card: group by sheet (sheet name contains month name)
        $ccBySheet = CCTransaction::selectRaw("sheet, SUM(payment_amount) as total")
            ->whereNotNull('sheet')
            ->groupBy('sheet')
            ->pluck('total', 'sheet');
        
        // Service Fee: group by month number
        $sfByMonth = ServiceFee::selectRaw("CAST(strftime('%m', transaction_time) AS INTEGER) as month_num, SUM(transaction_amount) as total")
            ->groupBy('month_num')
            ->pluck('total', 'month_num');
        
        // SPPD: group by month number
        $sppdByMonth = SppdTransaction::selectRaw("CAST(strftime('%m', trip_begins_on) AS INTEGER) as month_num, SUM(paid_amount) as total")
            ->groupBy('month_num')
            ->pluck('total', 'month_num');
        
        $monthlyData = collect($monthNames)->map(function($bulan) use ($monthOrder, $bfkoByMonth, $ccBySheet, $sfByMonth, $sppdByMonth) {
            $monthNum = $monthOrder[$bulan] ?? 0;
            
            // BFKO
            $bfkoTotal = $bfkoByMonth->get($bulan, 0);
            
            // CC Card - match sheet names containing the month name
            $ccTotal = $ccBySheet->filter(function($val, $sheet) use ($bulan) {
                return str_contains($sheet, $bulan);
            })->sum();
            
            // Service Fee
            $sfTotal = $sfByMonth->get($monthNum, 0);
            
            // SPPD
            $sppdTotal = $sppdByMonth->get($monthNum, 0);
            
            return [
                'month' => substr($bulan, 0, 3),
                'bfko' => (float)$bfkoTotal,
                'ccCard' => (float)$ccTotal,
                'serviceFee' => (float)$sfTotal,
                'sppd' => (float)$sppdTotal,
            ];
        })->filter(function($item) {
            return ($item['bfko'] > 0) || ($item['ccCard'] > 0) || ($item['serviceFee'] > 0) || ($item['sppd'] > 0);
        })->values();
        
        // Get recent transactions per category per month (like monthly comparison)
        $recentTransactions = collect();
        
        try {
            // Get last 12 months data - use batch queries
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            // Batch: BFKO by bulan (already has month names)
            $bfkoRecent = BfkoData::selectRaw("bulan, SUM(nilai_angsuran) as total, COUNT(*) as cnt")
                ->groupBy('bulan')
                ->get()
                ->keyBy('bulan');
            
            // Batch: Service Fee by year-month
            $sfRecent = ServiceFee::selectRaw("CAST(strftime('%Y', transaction_time) AS INTEGER) as yr, CAST(strftime('%m', transaction_time) AS INTEGER) as mn, SUM(transaction_amount) as total, COUNT(*) as cnt")
                ->groupBy('yr', 'mn')
                ->get()
                ->keyBy(function($item) { return $item->yr . '-' . $item->mn; });
            
            // Batch: CC Card by sheet name (contains month + year)
            $ccRecent = CCTransaction::selectRaw("sheet, SUM(payment_amount) as total, COUNT(*) as cnt")
                ->whereNotNull('sheet')
                ->groupBy('sheet')
                ->get()
                ->keyBy('sheet');
            
            // Batch: SPPD by year-month
            $sppdRecent = SppdTransaction::selectRaw("CAST(strftime('%Y', trip_begins_on) AS INTEGER) as yr, CAST(strftime('%m', trip_begins_on) AS INTEGER) as mn, SUM(paid_amount) as total, COUNT(*) as cnt")
                ->groupBy('yr', 'mn')
                ->get()
                ->keyBy(function($item) { return $item->yr . '-' . $item->mn; });
            
            for ($i = 0; $i < 12; $i++) {
            $month = $currentMonth - $i;
            $year = $currentYear;
            
            if ($month <= 0) {
                $month += 12;
                $year -= 1;
            }
            
            $monthName = $monthNames[$month - 1];
            
            // BFKO
            $bfkoRow = $bfkoRecent->get($monthName);
            if ($bfkoRow && $bfkoRow->total > 0) {
                $recentTransactions->push([
                    'category' => 'BFKO',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "Angsuran BFKO - $monthName $year",
                    'total' => 'Rp ' . number_format($bfkoRow->total, 0, ',', '.'),
                    'count' => $bfkoRow->cnt,
                    'status' => $bfkoRow->cnt > 0 ? 'Complete' : 'Lunas',
                    'sort_date' => strtotime("$year-$month-01"),
                ]);
            }
            
            // Service Fee
            $sfRow = $sfRecent->get("$year-$month");
            if ($sfRow && $sfRow->total > 0) {
                $recentTransactions->push([
                    'category' => 'Service Fee',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "Service Fee - $monthName $year",
                    'total' => 'Rp ' . number_format($sfRow->total, 0, ',', '.'),
                    'count' => $sfRow->cnt,
                    'status' => 'Issued',
                    'sort_date' => strtotime("$year-$month-01"),
                ]);
            }
            
            // CC Card - match sheet containing month name and year
            $ccSheetName = "$monthName $year";
            $monthCcAmount = 0;
            $monthCcCount = 0;
            foreach ($ccRecent as $sheet => $row) {
                if (str_contains($sheet, $monthName) && str_contains($sheet, (string)$year)) {
                    $monthCcAmount += $row->total;
                    $monthCcCount += $row->cnt;
                }
            }
            if ($monthCcAmount > 0) {
                $recentTransactions->push([
                    'category' => 'CC Card',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "CC Card Payment - $monthName $year",
                    'total' => 'Rp ' . number_format($monthCcAmount, 0, ',', '.'),
                    'count' => $monthCcCount,
                    'status' => 'Active',
                    'sort_date' => strtotime("$year-$month-01"),
                ]);
            }
            
            // SPPD
            $sppdRow = $sppdRecent->get("$year-$month");
            if ($sppdRow && $sppdRow->total > 0) {
                $recentTransactions->push([
                    'category' => 'SPPD',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "SPPD Transactions - $monthName $year",
                    'total' => 'Rp ' . number_format($sppdRow->total, 0, ',', '.'),
                    'count' => $sppdRow->cnt,
                    'status' => 'Complete',
                    'sort_date' => strtotime("$year-$month-01"),
                ]);
            }
        }
        
            // Sort by date descending and take latest 8
            $recentTransactions = $recentTransactions
                ->sortByDesc('sort_date')
                ->take(8)
                ->values();
        } catch (\Exception $e) {
            Log::error('Recent transactions error: ' . $e->getMessage());
            $recentTransactions = collect();
        }
        
        $summaryData = [
            'bfko' => [
                'total' => $bfkoTotal,
                'count' => $bfkoCount,
                'employees' => $bfkoEmployees
            ],
            'serviceFee' => [
                'total' => $serviceFeeTotal,
                'count' => $serviceFeeCount,
                'hotel' => $serviceFeeHotel,
                'flight' => $serviceFeeFlight
            ],
            'ccCard' => [
                'total' => $ccTotal,
                'count' => $ccCount,
                'employees' => $ccEmployees
            ],
            'sppd' => [
                'total' => $sppdTotal,
                'count' => $sppdCount,
                'employees' => $sppdEmployees
            ]
        ];
        
        return Inertia::render('Dashboard', [
            'summary' => $summaryData,
            'monthlyData' => $monthlyData,
            'recentTransactions' => $recentTransactions,
            'fundSource' => $fundSource
        ]);
    }
}
