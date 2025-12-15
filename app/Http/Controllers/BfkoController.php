<?php

namespace App\Http\Controllers;

use App\Models\BfkoData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\BfkoExport;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BfkoController extends Controller
{
    /**
     * Display BFKO monitoring dashboard
     */
    public function index(Request $request)
    {
        $selectedBulan = $request->input('bulan', 'all');
        
        // Get all available years first
        $years = BfkoData::select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();
        
        // Default to latest year if no year selected and 'all' not explicitly chosen
        $latestYear = !empty($years) ? $years[0] : date('Y');
        $selectedTahun = $request->input('tahun', $latestYear);
        
        // Build base query
        $query = BfkoData::query();
        
        if ($selectedBulan !== 'all') {
            $query->where('bulan', $selectedBulan);
        }
        
        if ($selectedTahun !== 'all') {
            $query->where('tahun', $selectedTahun);
        }
        
        // Get summary statistics
        $totalPayments = $query->sum('nilai_angsuran');
        $totalRecords = $query->count();
        
        // Get total unique employees based on YEAR filter only (not month)
        // Total employees should be all unique employees in that year
        // Note: Must use ->get()->count() instead of ->count() for distinct to work correctly
        $totalEmployeesQuery = BfkoData::select('nip')->distinct();
        
        if ($selectedTahun !== 'all') {
            $totalEmployeesQuery->where('tahun', $selectedTahun);
        }
        
        $totalEmployees = $totalEmployeesQuery->get()->count();
        
        // Get monthly chart data
        $monthlyQuery = BfkoData::select('bulan', DB::raw('SUM(nilai_angsuran) as total'))
            ->when($selectedTahun !== 'all', function ($q) use ($selectedTahun) {
                return $q->where('tahun', $selectedTahun);
            })
            ->groupBy('bulan')
            ->get();
        
        // Sort by month order
        $bulanOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];
        
        $monthlyData = $monthlyQuery->sortBy(function($item) use ($bulanOrder) {
            return $bulanOrder[$item->bulan] ?? 99;
        })->map(function ($item) {
            return [
                'bulan' => $item->bulan,
                'total' => (float) $item->total
            ];
        })->values();
        
        // Month order for sorting
        $bulanOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];

        // Get top employees by payment
        $topEmployees = BfkoData::select('nip', 'nama', 'jabatan', 'unit', DB::raw('SUM(nilai_angsuran) as total'))
            ->when($selectedBulan !== 'all', function ($q) use ($selectedBulan) {
                return $q->where('bulan', $selectedBulan);
            })
            ->when($selectedTahun !== 'all', function ($q) use ($selectedTahun) {
                return $q->where('tahun', $selectedTahun);
            })
            ->groupBy('nip', 'nama', 'jabatan', 'unit')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($selectedBulan, $selectedTahun, $bulanOrder) {
                // Get payment details for this employee
                $payments = BfkoData::where('nip', $item->nip)
                    ->when($selectedBulan !== 'all', function ($q) use ($selectedBulan) {
                        return $q->where('bulan', $selectedBulan);
                    })
                    ->when($selectedTahun !== 'all', function ($q) use ($selectedTahun) {
                        return $q->where('tahun', $selectedTahun);
                    })
                    ->orderBy('tahun', 'desc')
                    ->get()
                    ->sortBy(function($payment) use ($bulanOrder) {
                        return $bulanOrder[$payment->bulan] ?? 99;
                    })
                    ->values();
                
                return [
                    'nip' => $item->nip,
                    'nama' => $item->nama,
                    'jabatan' => $item->jabatan,
                    'unit' => $item->unit,
                    'total' => (float) $item->total,
                    'payments' => $payments
                ];
            });

        // Get all employees by payment (not limited to top 10)
        $allEmployees = BfkoData::select('nip', 'nama', 'jabatan', 'unit', DB::raw('SUM(nilai_angsuran) as total'))
            ->when($selectedBulan !== 'all', function ($q) use ($selectedBulan) {
                return $q->where('bulan', $selectedBulan);
            })
            ->when($selectedTahun !== 'all', function ($q) use ($selectedTahun) {
                return $q->where('tahun', $selectedTahun);
            })
            ->groupBy('nip', 'nama', 'jabatan', 'unit')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($selectedBulan, $selectedTahun, $bulanOrder) {
                // Get payment details for this employee
                $payments = BfkoData::where('nip', $item->nip)
                    ->when($selectedBulan !== 'all', function ($q) use ($selectedBulan) {
                        return $q->where('bulan', $selectedBulan);
                    })
                    ->when($selectedTahun !== 'all', function ($q) use ($selectedTahun) {
                        return $q->where('tahun', $selectedTahun);
                    })
                    ->orderBy('tahun', 'desc')
                    ->get()
                    ->sortBy(function($payment) use ($bulanOrder) {
                        return $bulanOrder[$payment->bulan] ?? 99;
                    })
                    ->values();
                
                return [
                    'nip' => $item->nip,
                    'nama' => $item->nama,
                    'jabatan' => $item->jabatan,
                    'unit' => $item->unit,
                    'total' => (float) $item->total,
                    'payments' => $payments
                ];
            });
        
        return Inertia::render('BfkoMonitoring', [
            'filters' => [
                'bulan' => $selectedBulan,
                'tahun' => $selectedTahun
            ],
            'years' => $years,
            'summary' => [
                'totalPayments' => $totalPayments,
                'totalRecords' => $totalRecords,
                'totalEmployees' => $totalEmployees
            ],
            'monthlyData' => $monthlyData,
            'topEmployees' => $topEmployees,
            'allEmployees' => $allEmployees
        ]);
    }
    
    /**
     * Import data from ideal format CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls'
        ]);
        
        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $isExcel = in_array($extension, ['xlsx', 'xls']);
            
            if ($isExcel) {
                // Convert Excel to CSV format
                $csvData = $this->convertExcelToBfkoCsv($file);
                
                if (!$csvData) {
                    return back()->with('error', 'Format Excel tidak valid. Pastikan ada kolom NIP, Nama Pegawai, dll.');
                }
                
                // Create temp file from CSV string
                $tempFile = tmpfile();
                fwrite($tempFile, $csvData);
                $tempFilePath = stream_get_meta_data($tempFile)['uri'];
                $handle = fopen($tempFilePath, 'r');
            } else {
                $handle = fopen($file->getRealPath(), 'r');
                $tempFile = null;
            }
            
            // Skip header
            fgetcsv($handle);
            
            $imported = 0;
            $updated = 0;
            $errors = [];
            
            DB::beginTransaction();
            
            while (($data = fgetcsv($handle)) !== false) {
                // Validate minimum required fields
                if (empty($data[0]) || empty($data[1]) || empty($data[4]) || empty($data[5]) || empty($data[6])) {
                    continue;
                }
                
                try {
                    // Check if record exists (unique: nip + bulan + tahun)
                    $record = BfkoData::where('nip', $data[0])
                        ->where('bulan', $data[4])
                        ->where('tahun', (int)$data[5])
                        ->first();
                    
                    $dataToSave = [
                        'nip' => $data[0],
                        'nama' => $data[1],
                        'jabatan' => $data[2] ?? '',
                        'unit' => $data[3] ?? null,
                        'bulan' => $data[4],
                        'tahun' => (int)$data[5],
                        'nilai_angsuran' => (float)$data[6],
                        'tanggal_bayar' => !empty($data[7]) ? $data[7] : null,
                        'status_angsuran' => $data[8] ?? null
                    ];
                    
                    if ($record) {
                        $record->update($dataToSave);
                        $updated++;
                    } else {
                        BfkoData::create($dataToSave);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error on line: " . $e->getMessage();
                }
            }
            
            fclose($handle);
            if (isset($tempFile) && $tempFile) {
                fclose($tempFile);
            }
            DB::commit();
            
            $message = "Import berhasil! {$imported} data baru";
            if ($updated > 0) {
                $message .= ", {$updated} data diupdate";
            }
            if (!empty($errors)) {
                $message .= " | " . count($errors) . " error";
            }
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle)) fclose($handle);
            if (isset($tempFile) && $tempFile) {
                fclose($tempFile);
            }
            
            Log::error('BFKO import error: ' . $e->getMessage());
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Get employee detail with payments
     */
    public function employeeDetail(Request $request, $nip)
    {
        $selectedTahun = $request->input('tahun', 'all');
        
        $bulanOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];
        
        $paymentsQuery = BfkoData::where('nip', $nip);
        
        // Apply year filter if not 'all'
        if ($selectedTahun !== 'all') {
            $paymentsQuery->where('tahun', $selectedTahun);
        }
        
        $payments = $paymentsQuery->orderBy('tahun', 'desc')
            ->get()
            ->sortBy(function($payment) use ($bulanOrder) {
                return $bulanOrder[$payment->bulan] ?? 99;
            })
            ->values();
        
        if ($payments->isEmpty()) {
            return redirect('/bfko')->with('error', 'Pegawai tidak ditemukan');
        }
        
        $employee = $payments->first();
        $totalPayment = $payments->sum('nilai_angsuran');
        
        // Get available years for this employee
        $availableYears = BfkoData::where('nip', $nip)
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();
        
        return Inertia::render('BfkoEmployeeDetail', [
            'employee' => [
                'nip' => $employee->nip,
                'nama' => $employee->nama,
                'jabatan' => $employee->jabatan,
                'unit' => $employee->unit,
                'total' => $totalPayment
            ],
            'payments' => $payments,
            'availableYears' => $availableYears,
            'selectedYear' => $selectedTahun
        ]);
    }
    
    /**
     * Delete all BFKO data
     */
    public function deleteAll()
    {
        try {
            DB::beginTransaction();
            
            BfkoData::truncate();
            
            DB::commit();
            
            return back()->with('success', 'Semua data BFKO berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Store new payment for an employee
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'nip' => 'required',
            'nama' => 'required',
            'jabatan' => 'required',
            'bulan' => 'required',
            'tahun' => 'required|integer',
            'nilai_angsuran' => 'required|numeric'
        ]);

        try {
            BfkoData::create([
                'nip' => $request->nip,
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
                'unit' => $request->unit,
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'nilai_angsuran' => $request->nilai_angsuran,
                'tanggal_bayar' => $request->tanggal_bayar,
                'status_angsuran' => $request->status_angsuran
            ]);

            return back()->with('success', 'Pembayaran berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambah pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Update existing payment
     */
    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required|integer',
            'nilai_angsuran' => 'required|numeric'
        ]);

        try {
            $payment = BfkoData::findOrFail($id);
            
            $payment->update([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'nilai_angsuran' => $request->nilai_angsuran,
                'tanggal_bayar' => $request->tanggal_bayar,
                'status_angsuran' => $request->status_angsuran
            ]);

            return back()->with('success', 'Pembayaran berhasil diupdate');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Delete a payment
     */
    public function deletePayment($id)
    {
        try {
            $payment = BfkoData::findOrFail($id);
            $payment->delete();

            return back()->with('success', 'Pembayaran berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Delete all payments for an employee by NIP (optionally filter by year)
     */
    public function deleteEmployee(Request $request, $nip)
    {
        try {
            $year = $request->query('year');
            
            $employeeData = BfkoData::where('nip', $nip)->first();
            if (!$employeeData) {
                return back()->with('error', 'Pegawai tidak ditemukan');
            }

            $employeeName = $employeeData->nama;
            
            // Build query
            $query = BfkoData::where('nip', $nip);
            
            // Apply year filter if provided
            if ($year && $year !== 'all') {
                $query->where('tahun', $year);
                $message = "Data pegawai {$employeeName} tahun {$year} berhasil dihapus";
            } else {
                $message = "Semua data pegawai {$employeeName} berhasil dihapus";
            }
            
            $deletedCount = $query->delete();

            return back()->with('success', "{$message} ({$deletedCount} record)");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus pegawai: ' . $e->getMessage());
        }
    }

    /**
     * Export BFKO data to PDF
     */
    public function exportPdf(Request $request)
    {
        $tahun = $request->query('tahun', 'all');
        
        $query = BfkoData::query();
        
        // Apply year filter
        if ($tahun !== 'all') {
            $query->where('tahun', $tahun);
        }
        
        $data = $query->orderBy('nama')
                     ->get();
        
        // Month order mapping
        $monthOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];
        
        // Group by employee and sort payments by year DESC then month order
        $employees = $data->groupBy('nip')->map(function($payments, $nip) use ($monthOrder) {
            $first = $payments->first();
            $sortedPayments = $payments->sortBy([
                ['tahun', 'desc'],
                function($a) use ($monthOrder) {
                    return $monthOrder[$a->bulan] ?? 99;
                }
            ])->values();
            
            return [
                'nip' => $nip,
                'nama' => $first->nama,
                'jabatan' => $first->jabatan,
                'unit' => $first->unit,
                'payments' => $sortedPayments,
                'total' => $payments->sum('nilai_angsuran')
            ];
        })->values();
        
        $totalAll = $data->sum('nilai_angsuran');
        $yearText = $tahun === 'all' ? 'Semua Tahun' : 'Tahun ' . $tahun;
        
        // Generate PDF using simple HTML view
        $html = view('exports.bfko-pdf', [
            'employees' => $employees,
            'totalAll' => $totalAll,
            'yearText' => $yearText,
            'exportDate' => now()->format('d-m-Y H:i')
        ])->render();
        
        // Use DomPDF
        $pdf = \PDF::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'BFKO_Report_' . ($tahun === 'all' ? 'All_Years' : $tahun) . '_' . now()->format('Ymd_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export BFKO data to Excel
     */
    public function exportExcel(Request $request)
    {
        $tahun = $request->query('tahun', 'all');
        
        $query = BfkoData::query();
        
        // Apply year filter
        if ($tahun !== 'all') {
            $query->where('tahun', $tahun);
        }
        
        $data = $query->get();
        
        // Month order mapping
        $monthOrder = [
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
            'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
        ];
        
        // Sort by month order, then tahun DESC, then nama ASC (chained for correct multi-level sort)
        $data = $data->sortBy(function($item) use ($monthOrder) {
            return $monthOrder[$item->bulan] ?? 99;
        })->sortByDesc('tahun')->sortBy('nama')->values();
        
        $export = new BfkoExport($data, $tahun);
        return $export->download();
    }
    
    /**
     * Convert Excel file to BFKO CSV format
     */
    private function convertExcelToBfkoCsv($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Find header row (usually row 2 or 3 in BFKO Excel)
            $headerRow = null;
            $headerRowIndex = -1;
            
            for ($i = 0; $i < min(5, count($rows)); $i++) {
                $row = $rows[$i];
                // Look for NIP column
                if (in_array('NIP', $row) || in_array('No', $row)) {
                    $headerRow = $row;
                    $headerRowIndex = $i;
                    break;
                }
            }
            
            if (!$headerRow) {
                return null;
            }
            
            // Find column indices
            $nipCol = array_search('NIP', $headerRow);
            $namaCol = array_search('Nama Pegawai', $headerRow);
            $jabatanCol = array_search('Jabatan', $headerRow);
            $unitCol = array_search('Unit', $headerRow);
            
            if ($nipCol === false || $namaCol === false) {
                return null;
            }
            
            // Find month columns (Januari, Februari, etc.)
            $monthColumns = [];
            $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            foreach ($headerRow as $colIndex => $colName) {
                foreach ($monthNames as $month) {
                    if (stripos($colName, $month) !== false) {
                        $monthColumns[$month] = $colIndex;
                        break;
                    }
                }
            }
            
            // Detect year from title or use current year
            $year = date('Y');
            if (isset($rows[0][0]) && preg_match('/(\d{4})/', $rows[0][0], $matches)) {
                $year = $matches[1];
            }
            
            // Build CSV
            $csvLines = [];
            $csvLines[] = 'nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran';
            
            // Process data rows
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Skip empty rows
                if (empty($row[$nipCol])) {
                    continue;
                }
                
                $nip = $this->cleanValue($row[$nipCol]);
                $nama = $this->cleanValue($row[$namaCol] ?? '');
                $jabatan = $this->cleanValue($row[$jabatanCol] ?? '');
                $unit = $this->cleanValue($row[$unitCol] ?? '');
                
                // Skip if NIP is not numeric
                if (!is_numeric($nip)) {
                    continue;
                }
                
                // Create one row per month that has a payment
                foreach ($monthColumns as $month => $colIndex) {
                    if (isset($row[$colIndex]) && !empty($row[$colIndex])) {
                        $nilai = $this->cleanAmountBfko($row[$colIndex]);
                        
                        if ($nilai > 0) {
                            // Check if there's a date column next to amount
                            $tanggalBayar = '';
                            if (isset($row[$colIndex + 1])) {
                                $tanggalBayar = $this->formatExcelDateBfko($row[$colIndex + 1]);
                            }
                            
                            $csvLines[] = sprintf(
                                '"%s","%s","%s","%s","%s","%s","%s","%s","%s"',
                                $nip,
                                $nama,
                                $jabatan,
                                $unit,
                                $month,
                                $year,
                                $nilai,
                                $tanggalBayar,
                                'Lunas'
                            );
                        }
                    }
                }
            }
            
            return implode("\n", $csvLines);
            
        } catch (\Exception $e) {
            Log::error('BFKO Excel conversion error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function cleanValue($value)
    {
        return str_replace('"', '""', trim((string)$value));
    }
    
    private function cleanAmountBfko($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        // Remove currency symbols, commas, dots (except last dot for decimal)
        $cleaned = preg_replace('/[^\d]/', '', (string)$value);
        return (int)$cleaned;
    }
    
    private function formatExcelDateBfko($value)
    {
        if (empty($value)) {
            return '';
        }
        
        try {
            // If it's already a string date
            if (is_string($value) && strtotime($value)) {
                return date('Y-m-d', strtotime($value));
            }
            
            // Handle Excel serial number
            if (is_numeric($value)) {
                $baseDate = new \DateTime('1899-12-30');
                $baseDate->modify('+' . (int)$value . ' days');
                return $baseDate->format('Y-m-d');
            }
            
            // Handle DateTime object
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
