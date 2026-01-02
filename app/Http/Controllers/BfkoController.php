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
            $fileName = $file->getClientOriginalName();
            
            Log::info('BFKO import started', [
                'filename' => $fileName,
                'extension' => $extension,
                'is_excel' => $isExcel
            ]);
            
            if ($isExcel) {
                // Convert Excel to CSV format
                $csvData = $this->convertExcelToBfkoCsv($file);
                
                if (!$csvData) {
                    Log::error('BFKO Excel conversion failed');
                    return back()->with('error', 'Format Excel tidak valid. Pastikan ada kolom NIP, Nama Pegawai, dan data bulan.');
                }
                
                Log::info('BFKO Excel converted to CSV successfully');
                
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
            $header = fgetcsv($handle);
            Log::info('BFKO CSV header', ['header' => $header]);
            
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            DB::beginTransaction();
            
            $rowNum = 0;
            while (($data = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Validate minimum required fields
                if (empty($data[0]) || empty($data[1]) || empty($data[4]) || empty($data[5]) || empty($data[6])) {
                    $skipped++;
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
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                    Log::warning('BFKO import row error', ['row' => $rowNum, 'error' => $e->getMessage()]);
                }
            }
            
            fclose($handle);
            if (isset($tempFile) && $tempFile) {
                fclose($tempFile);
            }
            DB::commit();
            
            Log::info('BFKO import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => count($errors)
            ]);
            
            $message = "Import berhasil! {$imported} data baru ditambahkan";
            if ($updated > 0) {
                $message .= ", {$updated} data diupdate";
            }
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati (data tidak lengkap)";
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
     * Supports both simple format and original format with sections
     */
    private function convertExcelToBfkoCsv($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetNames = $spreadsheet->getSheetNames();
            
            Log::info('BFKO Excel loaded', [
                'total_sheets' => count($sheetNames),
                'sheet_names' => $sheetNames
            ]);
            
            // Check if this is the original format with "Angsuran Bulanan BFKO" section
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
            
            $hasAngsuranSection = false;
            for ($i = 0; $i < min(30, count($rows)); $i++) {
                $firstCol = trim((string)($rows[$i][0] ?? ''));
                if (stripos($firstCol, 'Angsuran Bulanan') !== false) {
                    $hasAngsuranSection = true;
                    break;
                }
            }
            
            if ($hasAngsuranSection) {
                Log::info('BFKO Original format detected - using multi-sheet converter');
                return $this->convertOriginalBfkoExcel($spreadsheet);
            }
            
            // Otherwise, use simple format converter
            Log::info('BFKO Simple format detected');
            return $this->convertSimpleBfkoExcel($spreadsheet);
            
        } catch (\Exception $e) {
            Log::error('BFKO Excel conversion error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Convert original BFKO Excel format with "Angsuran Bulanan BFKO" section
     * Sheet names contain year info (e.g., "34 UID SULSELRABAR_2024")
     */
    private function convertOriginalBfkoExcel($spreadsheet)
    {
        $csvLines = [];
        $csvLines[] = 'nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran';
        
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                       'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        $totalRecords = 0;
        $sheetNames = $spreadsheet->getSheetNames();
        
        foreach ($sheetNames as $sheetIndex => $sheetName) {
            Log::info("BFKO Processing sheet: $sheetName");
            
            // Extract year from sheet name (e.g., "34 UID SULSELRABAR_2024" -> 2024)
            preg_match('/(\d{4})$/', $sheetName, $yearMatch);
            $tahun = $yearMatch[1] ?? date('Y');
            
            // Extract unit from sheet name
            preg_match('/UID\s+(\w+)/i', $sheetName, $unitMatch);
            $defaultUnit = $unitMatch[0] ?? 'UID SULSELRABAR';
            
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $rows = $sheet->toArray(null, true, true, false);
            
            // Find the Angsuran section header row
            $angsuranStartRow = -1;
            for ($i = 0; $i < count($rows); $i++) {
                $firstCol = trim((string)($rows[$i][0] ?? ''));
                if (stripos($firstCol, 'Angsuran Bulanan') !== false) {
                    $angsuranStartRow = $i;
                    break;
                }
            }
            
            if ($angsuranStartRow === -1) {
                Log::warning("BFKO 'Angsuran Bulanan' section not found in sheet: $sheetName");
                continue;
            }
            
            Log::info("BFKO Found 'Angsuran Bulanan BFKO' at row $angsuranStartRow");
            
            // Find the month header row (row with Januari, Februari, etc.)
            $monthHeaderRow = $angsuranStartRow + 2;
            
            // Dynamically detect month columns
            $monthColumns = [];
            if (isset($rows[$monthHeaderRow])) {
                foreach ($rows[$monthHeaderRow] as $colIdx => $cell) {
                    $cell = trim((string)$cell);
                    foreach ($monthNames as $month) {
                        if (strcasecmp($cell, $month) === 0) {
                            $monthColumns[$month] = [
                                'nilai' => $colIdx,
                                'tanggal' => $colIdx + 1
                            ];
                            break;
                        }
                    }
                }
            }
            
            if (empty($monthColumns)) {
                Log::warning("BFKO Could not detect month columns in sheet: $sheetName");
                continue;
            }
            
            Log::info("BFKO Detected " . count($monthColumns) . " month columns");
            
            // Data starts 1 row after month header
            $dataStartRow = $monthHeaderRow + 1;
            
            // Find next section or end of data
            $dataEndRow = count($rows);
            for ($i = $dataStartRow; $i < count($rows); $i++) {
                $firstCol = trim((string)($rows[$i][0] ?? ''));
                if (!empty($firstCol) && !is_numeric($firstCol) && strlen($firstCol) > 15) {
                    $dataEndRow = $i;
                    break;
                }
            }
            
            // Process data rows
            for ($i = $dataStartRow; $i < $dataEndRow; $i++) {
                $row = $rows[$i];
                
                $nip = trim((string)($row[1] ?? ''));
                if (empty($nip) || !preg_match('/^\d/', $nip)) {
                    continue;
                }
                
                $nama = $this->cleanValue($row[2] ?? '');
                $jabatan = $this->cleanValue($row[3] ?? '');
                $unit = $this->cleanValue($row[5] ?? $defaultUnit);
                
                if (empty($nama)) continue;
                
                // Process each month column
                foreach ($monthColumns as $bulan => $cols) {
                    $nilaiAngsuran = $row[$cols['nilai']] ?? '';
                    $tanggalBayar = $row[$cols['tanggal']] ?? '';
                    
                    $nilaiAngsuran = $this->cleanAmountBfko($nilaiAngsuran);
                    
                    if (empty($nilaiAngsuran) || $nilaiAngsuran <= 0) {
                        continue;
                    }
                    
                    $tanggalBayar = $this->parseOriginalBfkoDate($tanggalBayar);
                    $statusAngsuran = empty($tanggalBayar) ? 'Belum Bayar' : 'Lunas';
                    
                    $csvLines[] = sprintf(
                        '"%s","%s","%s","%s","%s","%s","%s","%s","%s"',
                        $this->cleanValue($nip),
                        $nama,
                        $jabatan,
                        $unit,
                        $bulan,
                        $tahun,
                        $nilaiAngsuran,
                        $tanggalBayar,
                        $statusAngsuran
                    );
                    $totalRecords++;
                }
            }
        }
        
        Log::info('BFKO Original format conversion complete', [
            'total_records' => $totalRecords,
            'sheets_processed' => count($sheetNames)
        ]);
        
        if (count($csvLines) <= 1) {
            Log::warning('BFKO conversion resulted in empty data');
            return null;
        }
        
        return implode("\n", $csvLines);
    }
    
    /**
     * Parse date from original BFKO Excel format
     * Handles: Japanese-like format (1212122024年1月29日), dd/mm/yyyy, Excel serial, Indonesian date
     */
    private function parseOriginalBfkoDate($value)
    {
        if (empty($value)) return '';
        
        $value = trim((string)$value);
        
        // Handle Japanese-like format: 1212122024年1月29日 or 9992024年9月23日
        if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/', $value, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "$year-$month-$day";
        }
        
        // Handle dd/mm/yyyy format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "$year-$month-$day";
        }
        
        // Handle Excel serial date
        if (is_numeric($value) && $value > 40000 && $value < 50000) {
            try {
                $baseDate = new \DateTime('1899-12-30');
                $baseDate->modify('+' . (int)$value . ' days');
                return $baseDate->format('Y-m-d');
            } catch (\Exception $e) {
                return '';
            }
        }
        
        // Handle Indonesian date: "29 Januari 2024"
        $monthMap = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'
        ];
        
        $valueLower = strtolower($value);
        foreach ($monthMap as $monthName => $monthNum) {
            if (strpos($valueLower, $monthName) !== false) {
                if (preg_match('/(\d{1,2})\s*' . $monthName . '\s*(\d{4})/i', $value, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $year = $matches[2];
                    return "$year-$monthNum-$day";
                }
            }
        }
        
        // Handle yyyy-mm-dd format (already correct)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return $value;
        }
        
        return '';
    }
    
    /**
     * Convert simple BFKO Excel format (single header row with months)
     */
    private function convertSimpleBfkoExcel($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
            
        Log::info('BFKO Simple Excel loaded, total rows: ' . count($rows));
        
        // Find header row with NIP column
        $headerRowIndex = -1;
        $headerRow = null;
        
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $row = $rows[$i];
            foreach ($row as $cell) {
                $cellClean = strtoupper(trim((string)$cell));
                if ($cellClean === 'NIP') {
                    $headerRow = $row;
                    $headerRowIndex = $i;
                    Log::info('BFKO header found at row: ' . $i);
                    break 2;
                }
            }
        }
        
        if (!$headerRow || $headerRowIndex < 0) {
            Log::error('BFKO header row with NIP not found');
            return null;
        }
        
        // Find column indices from header row
        $nipCol = null;
        $namaCol = null;
        $jabatanCol = null;
        $unitCol = null;
        
        foreach ($headerRow as $colIndex => $colName) {
            $cellClean = strtoupper(trim((string)$colName));
            if ($cellClean === 'NIP') {
                $nipCol = $colIndex;
            } elseif (stripos($colName, 'Nama') !== false && $namaCol === null) {
                $namaCol = $colIndex;
            } elseif (stripos($colName, 'Jabatan') !== false && $jabatanCol === null) {
                $jabatanCol = $colIndex;
            } elseif (stripos($colName, 'Unit') !== false) {
                $unitCol = $colIndex;
            }
        }
        
        if ($nipCol === null) {
            Log::error('BFKO NIP column not found');
            return null;
        }
        
        // Find month columns
        $monthColumns = [];
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        // Check header row and next row for month names
        $rowsToCheck = [$headerRow];
        if ($headerRowIndex + 1 < count($rows)) {
            $rowsToCheck[] = $rows[$headerRowIndex + 1];
        }
        
        foreach ($rowsToCheck as $rowToCheck) {
            foreach ($rowToCheck as $colIndex => $colName) {
                $cellClean = trim((string)$colName);
                foreach ($monthNames as $month) {
                    if (strcasecmp($cellClean, $month) === 0 && !isset($monthColumns[$month])) {
                        $monthColumns[$month] = $colIndex;
                        break;
                    }
                }
            }
        }
        
        // Detect year from title row
        $year = date('Y');
        foreach ($rows as $idx => $row) {
            if ($idx > 5) break;
            foreach ($row as $cell) {
                if (preg_match('/(\d{4})/', (string)$cell, $matches)) {
                    $year = $matches[1];
                    break 2;
                }
            }
        }
        
        // Determine data start row
        $dataStartRow = $headerRowIndex + 1;
        if ($headerRowIndex + 1 < count($rows)) {
            $possibleMonthRow = $rows[$headerRowIndex + 1];
            $hasMonthName = false;
            foreach ($possibleMonthRow as $cell) {
                foreach ($monthNames as $month) {
                    if (strcasecmp(trim((string)$cell), $month) === 0) {
                        $hasMonthName = true;
                        break 2;
                    }
                }
            }
            if ($hasMonthName) {
                $dataStartRow = $headerRowIndex + 2;
            }
        }
        
        // Build CSV
        $csvLines = [];
        $csvLines[] = 'nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran';
        
        $processedCount = 0;
        
        // Process data rows
        for ($i = $dataStartRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            $nipValue = isset($row[$nipCol]) ? trim((string)$row[$nipCol]) : '';
            if (empty($nipValue) || !preg_match('/[0-9]/', $nipValue)) {
                continue;
            }
            
            $nip = $this->cleanValue($nipValue);
            $nama = $this->cleanValue($row[$namaCol] ?? '');
            $jabatan = $this->cleanValue($row[$jabatanCol] ?? '');
            $unit = $this->cleanValue($row[$unitCol] ?? '');
            
            if (empty($nama)) continue;
            
            // Process each month column
            foreach ($monthColumns as $month => $colIndex) {
                if (!isset($row[$colIndex])) continue;
                
                $nilai = $this->cleanAmountBfko($row[$colIndex]);
                
                if ($nilai > 0) {
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
                    $processedCount++;
                }
            }
        }
        
        Log::info('BFKO Simple Excel converted', [
            'total_csv_lines' => count($csvLines),
            'processed_payments' => $processedCount
        ]);
        
        if (count($csvLines) <= 1) {
            Log::warning('BFKO conversion resulted in empty data');
            return null;
        }
        
        return implode("\n", $csvLines);
    }
    
    private function cleanValue($value)
    {
        return str_replace('"', '""', trim((string)$value));
    }
    
    private function cleanAmountBfko($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        $value = trim((string)$value);
        
        // Remove spaces
        $value = str_replace(' ', '', $value);
        
        // Handle format with comma as thousand separator and dot as decimal (e.g., 300,000,000.00)
        // Remove .00 or .XX decimal first if present, then remove commas
        if (preg_match('/^[\d,]+\.\d{2}$/', $value)) {
            $value = preg_replace('/\.\d{2}$/', '', $value);
            $value = str_replace(',', '', $value);
            return (float)$value;
        }
        
        // Handle Indonesian format with dots as thousand separator (3.734.355)
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float)$value;
        }
        
        // Handle format with comma as thousand separator only (3,734,355)
        if (preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
            $value = str_replace(',', '', $value);
            return (float)$value;
        }
        
        // Remove commas (might be thousand separator) and other non-numeric chars except dot
        $cleaned = preg_replace('/[^\d.]/', '', $value);
        return (float)$cleaned;
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
