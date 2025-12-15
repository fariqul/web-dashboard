<?php

namespace App\Http\Controllers;

use App\Models\BfkoData;
use App\Models\ServiceFee;
use App\Models\CCTransaction;
use App\Models\SppdTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get fund source filter (54 or 52 or all)
        $fundSource = $request->get('fund', 'all');
        
        // Get BFKO summary (Fund 52)
        $bfkoTotal = BfkoData::sum('nilai_angsuran');
        $bfkoCount = BfkoData::count();
        $bfkoEmployees = BfkoData::select('nip')->distinct()->count();
        
        // Get Service Fee summary (Fund 54)
        $serviceFeeTotal = ServiceFee::sum('transaction_amount');
        $serviceFeeCount = ServiceFee::count();
        $serviceFeeHotel = ServiceFee::where('service_type', 'hotel')->count();
        $serviceFeeFlight = ServiceFee::where('service_type', 'flight')->count();
        
        // Get CC Card summary (Fund 54)
        $ccTotal = CCTransaction::sum('payment_amount');
        $ccCount = CCTransaction::count();
        $ccEmployees = CCTransaction::select('personel_number')->distinct()->count();
        
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
        
        // Build monthly data combining all 3 categories for all 12 months
        $monthlyData = collect($monthNames)->map(function($bulan) use ($monthOrder) {
            // BFKO data for this month
            $bfkoTotal = BfkoData::where('bulan', $bulan)->sum('nilai_angsuran');
            
            // Get month number for matching with date fields
            $monthNum = $monthOrder[$bulan] ?? 0;
            
            // CC Card - format M/D/YYYY tidak bisa diparse SQLite, harus manual
            $ccTotal = CCTransaction::whereNotNull('departure_date')
                ->get()
                ->filter(function($item) use ($monthNum) {
                    try {
                        // Parse format M/D/YYYY atau n/j/Y
                        $date = \DateTime::createFromFormat('n/j/Y', $item->departure_date);
                        if ($date && (int)$date->format('n') === $monthNum) {
                            return true;
                        }
                    } catch (\Exception $e) {
                    }
                    return false;
                })
                ->sum('payment_amount');
            
            // Service Fee - extract from transaction_time (any year)
            $sfTotal = ServiceFee::whereRaw("CAST(strftime('%m', transaction_time) AS INTEGER) = ?", [$monthNum])
                ->sum('transaction_amount');
            
            // SPPD - extract from trip_begins_on (any year)
            $sppdTotal = SppdTransaction::whereRaw("CAST(strftime('%m', trip_begins_on) AS INTEGER) = ?", [$monthNum])
                ->sum('paid_amount');
            
            return [
                'month' => substr($bulan, 0, 3),
                'bfko' => (float)$bfkoTotal,
                'ccCard' => (float)$ccTotal,
                'serviceFee' => (float)$sfTotal,
                'sppd' => (float)$sppdTotal,
            ];
        })->values();
        
        // Get recent transactions per category per month (like monthly comparison)
        $recentTransactions = collect();
        
        try {
            // Get last 3 months with data for each category
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            // Generate last 12 months data for all categories
            for ($i = 0; $i < 12; $i++) {
            $month = $currentMonth - $i;
            $year = $currentYear;
            
            if ($month <= 0) {
                $month += 12;
                $year -= 1;
            }
            
            $monthName = $monthNames[$month - 1];
            
            // BFKO for this month
            $monthBfkoAmount = BfkoData::where('bulan', $monthName)->sum('nilai_angsuran');
            $monthBfkoCount = BfkoData::where('bulan', $monthName)->count();
            if ($monthBfkoAmount > 0) {
                $recentTransactions->push([
                    'category' => 'BFKO',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "Angsuran BFKO - $monthName $year",
                    'total' => 'Rp ' . number_format($monthBfkoAmount, 0, ',', '.'),
                    'count' => $monthBfkoCount,
                    'status' => $monthBfkoCount > 0 ? 'Complete' : 'Lunas',
                    'sort_date' => strtotime("$year-$month-01"),
                ]);
            }
            
            // Service Fee for this month
            $monthSfAmount = ServiceFee::whereRaw("CAST(strftime('%m', transaction_time) AS INTEGER) = ?", [$month])
                ->whereRaw("CAST(strftime('%Y', transaction_time) AS INTEGER) = ?", [$year])
                ->sum('transaction_amount');
            $monthSfCount = ServiceFee::whereRaw("CAST(strftime('%m', transaction_time) AS INTEGER) = ?", [$month])
                ->whereRaw("CAST(strftime('%Y', transaction_time) AS INTEGER) = ?", [$year])
                ->count();
            if ($monthSfAmount > 0) {
                $recentTransactions->push([
                    'category' => 'Service Fee',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "Service Fee - $monthName $year",
                    'total' => 'Rp ' . number_format($monthSfAmount, 0, ',', '.'),
                    'count' => $monthSfCount,
                    'status' => 'Issued',
                    'sort_date' => strtotime("$year-$month-01"),
                ]);
            }
            
            // CC Card for this month
            $monthCcAmount = CCTransaction::whereNotNull('departure_date')
                ->get()
                ->filter(function($item) use ($month, $year) {
                    try {
                        $date = \DateTime::createFromFormat('n/j/Y', $item->departure_date);
                        return $date && (int)$date->format('n') === $month && (int)$date->format('Y') === $year;
                    } catch (\Exception $e) {
                        return false;
                    }
                })
                ->sum('payment_amount');
            $monthCcCount = CCTransaction::whereNotNull('departure_date')
                ->get()
                ->filter(function($item) use ($month, $year) {
                    try {
                        $date = \DateTime::createFromFormat('n/j/Y', $item->departure_date);
                        return $date && (int)$date->format('n') === $month && (int)$date->format('Y') === $year;
                    } catch (\Exception $e) {
                        return false;
                    }
                })
                ->count();
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
            
            // SPPD for this month
            $monthSppdAmount = SppdTransaction::whereRaw("CAST(strftime('%m', trip_begins_on) AS INTEGER) = ?", [$month])
                ->whereRaw("CAST(strftime('%Y', trip_begins_on) AS INTEGER) = ?", [$year])
                ->sum('paid_amount');
            $monthSppdCount = SppdTransaction::whereRaw("CAST(strftime('%m', trip_begins_on) AS INTEGER) = ?", [$month])
                ->whereRaw("CAST(strftime('%Y', trip_begins_on) AS INTEGER) = ?", [$year])
                ->count();
            if ($monthSppdAmount > 0) {
                $recentTransactions->push([
                    'category' => 'SPPD',
                    'month' => $monthName,
                    'year' => $year,
                    'date' => $monthName . ' ' . $year,
                    'description' => "SPPD Transactions - $monthName $year",
                    'total' => 'Rp ' . number_format($monthSppdAmount, 0, ',', '.'),
                    'count' => $monthSppdCount,
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
            \Log::error('Recent transactions error: ' . $e->getMessage());
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
