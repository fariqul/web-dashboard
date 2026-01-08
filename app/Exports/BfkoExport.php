<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Row;

class BfkoExport
{
    protected $data;
    protected $tahun;

    public function __construct($data, $tahun)
    {
        $this->data = $data;
        $this->tahun = $tahun;
    }

    /**
     * Generate clean Excel file using OpenSpout
     */
    public function download()
    {
        $yearText = $this->tahun === 'all' ? 'Semua Tahun' : 'Tahun ' . $this->tahun;
        $filename = 'BFKO_Report_' . ($this->tahun === 'all' ? 'All_Years' : $this->tahun) . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/tmp_export_' . uniqid() . '.xlsx');
        
        try {
            $writer = new Writer();
            $writer->openToFile($tempPath);
            
            // Group data by employee AND year
            $employees = $this->data->groupBy(function($item) {
                return $item->nip . '_' . $item->tahun;
            })->map(function($payments) {
                $first = $payments->first();
                return [
                    'nip' => $first->nip,
                    'nama' => $first->nama,
                    'jabatan' => $first->jabatan,
                    'tahun' => $first->tahun,
                    'payments' => $payments,
                    'total' => $payments->sum('nilai_angsuran')
                ];
            })->sortBy([
                ['tahun', 'desc'],
                ['nama', 'asc']
            ])->values();
            
            $totalAll = $this->data->sum('nilai_angsuran');
            $totalEmployees = $employees->count();
            $totalTransactions = $this->data->count();

            // ===== SIMPLE STYLES =====
            
            // Title Style
            $titleStyle = (new Style())
                ->setFontBold()
                ->setFontSize(12)
                ->setFontColor(Color::rgb(30, 58, 138));
            
            // Header Style (Navy)
            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(30, 58, 138))
                ->setCellAlignment(CellAlignment::CENTER);
            
            // Employee Group Style (Light Blue)
            $employeeGroupStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::rgb(30, 58, 138))
                ->setBackgroundColor(Color::rgb(219, 234, 254));
            
            // Data Style
            $dataStyle = (new Style())
                ->setFontSize(9);
            
            // Alternate Row Style
            $altRowStyle = (new Style())
                ->setFontSize(9)
                ->setBackgroundColor(Color::rgb(248, 250, 252));
            
            // Subtotal Style (Gold)
            $subtotalStyle = (new Style())
                ->setFontBold()
                ->setFontSize(9)
                ->setBackgroundColor(Color::rgb(254, 243, 199));
            
            // Grand Total Style (Navy)
            $grandTotalStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(30, 58, 138));

            // ===== BUILD EXCEL CONTENT =====
            
            // Header info
            $writer->addRow(Row::fromValues(['LAPORAN REKAPITULASI PEMBAYARAN BFKO'], $titleStyle));
            $writer->addRow(Row::fromValues(['PLN UID SULAWESI SELATAN, SULAWESI TENGGARA, DAN SULAWESI BARAT'], $titleStyle));
            $writer->addRow(Row::fromValues(['Periode: ' . $yearText]));
            $writer->addRow(Row::fromValues(['']));
            
            // Summary info
            $writer->addRow(Row::fromValues(['Total Pegawai: ' . $totalEmployees . ' | Total Transaksi: ' . $totalTransactions . ' | Total Pembayaran: Rp ' . number_format($totalAll, 0, ',', '.')]));
            $writer->addRow(Row::fromValues(['']));
            
            // Table Headers
            $headers = ['NO', 'NIP', 'NAMA', 'JABATAN', 'BULAN', 'TAHUN', 'NILAI ANGSURAN', 'TGL BAYAR', 'STATUS'];
            $writer->addRow(Row::fromValues($headers, $headerStyle));
            
            // Data Rows
            $rowNumber = 1;
            
            foreach ($employees as $employee) {
                // Employee Group Header
                $groupText = $employee['nama'] . ' (' . $employee['nip'] . ') - ' . $employee['jabatan'] . ' | Tahun: ' . $employee['tahun'] . ' | Total: Rp ' . number_format($employee['total'], 0, ',', '.');
                $writer->addRow(Row::fromValues([$groupText, '', '', '', '', '', '', '', ''], $employeeGroupStyle));
                
                // Payments
                foreach ($employee['payments'] as $idx => $payment) {
                    $tanggalBayar = '-';
                    if (!empty($payment->tanggal_bayar) && $payment->tanggal_bayar !== '-') {
                        try {
                            $tanggalBayar = \Carbon\Carbon::parse($payment->tanggal_bayar)->format('d M Y');
                        } catch (\Exception $e) {
                            $tanggalBayar = $payment->tanggal_bayar;
                        }
                    }
                    
                    $status = $payment->status_angsuran ?: ($tanggalBayar !== '-' ? 'Lunas' : 'Belum Bayar');
                    
                    $rowData = [
                        $rowNumber++,
                        $payment->nip,
                        $payment->nama,
                        $payment->jabatan,
                        $payment->bulan,
                        $payment->tahun,
                        'Rp ' . number_format($payment->nilai_angsuran, 0, ',', '.'),
                        $tanggalBayar,
                        $status
                    ];
                    
                    $rowStyle = ($idx % 2 == 0) ? $dataStyle : $altRowStyle;
                    $writer->addRow(Row::fromValues($rowData, $rowStyle));
                }
                
                // Subtotal
                $writer->addRow(Row::fromValues([
                    '', '', '', '', '', '', 'Subtotal:', 'Rp ' . number_format($employee['total'], 0, ',', '.'), ''
                ], $subtotalStyle));
            }
            
            // Grand Total
            $writer->addRow(Row::fromValues([
                '', '', '', '', '', '', 'TOTAL:', 'Rp ' . number_format($totalAll, 0, ',', '.'), ''
            ], $grandTotalStyle));

            $writer->close();

            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
            
        } catch (\Throwable $e) {
            Log::error('BFKO Export Excel failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            if (file_exists($tempPath)) @unlink($tempPath);
            abort(500, 'Gagal membuat file Excel: ' . $e->getMessage());
        }
    }
}
