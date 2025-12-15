<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

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
     * Generate styled Excel file using OpenSpout - Corporate Dashboard Style
     */
    public function download()
    {
        $yearText = $this->tahun === 'all' ? 'Semua Tahun' : 'Tahun ' . $this->tahun;
        $filename = 'BFKO_Report_' . ($this->tahun === 'all' ? 'All_Years' : $this->tahun) . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/tmp_export_' . uniqid() . '.xlsx');
        
        try {
            $writer = new Writer();
            $writer->openToFile($tempPath);
            
            // Group data by employee
            $employees = $this->data->groupBy('nip')->map(function($payments) {
                $first = $payments->first();
                return [
                    'nip' => $first->nip,
                    'nama' => $first->nama,
                    'jabatan' => $first->jabatan,
                    'unit' => $first->unit,
                    'payments' => $payments,
                    'total' => $payments->sum('nilai_angsuran')
                ];
            })->values();
            
            $totalAll = $this->data->sum('nilai_angsuran');
            $totalEmployees = $employees->count();
            $totalTransactions = $this->data->count();

            $totalAll = $this->data->sum('nilai_angsuran');
            $totalEmployees = $employees->count();
            $totalTransactions = $this->data->count();

            // ===== STYLES DEFINITION =====
            
            // Logo/Title Style (Navy Blue)
            $logoStyle = (new Style())
                ->setFontBold()
                ->setFontSize(14)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(30, 58, 138)) // Navy #1e3a8a
                ->setCellAlignment(CellAlignment::CENTER);
            
            // Title with Gold Border Style
            $titleStyle = (new Style())
                ->setFontBold()
                ->setFontSize(16)
                ->setFontColor(Color::rgb(30, 58, 138))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID), // Gold
                    new BorderPart(Border::TOP, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID)
                ));
            
            // Subtitle Style
            $subtitleStyle = (new Style())
                ->setFontSize(10)
                ->setFontColor(Color::rgb(100, 116, 139))
                ->setCellAlignment(CellAlignment::CENTER);
            
            // Summary Card Label Style
            $summaryLabelStyle = (new Style())
                ->setFontSize(8)
                ->setFontBold()
                ->setFontColor(Color::rgb(100, 116, 139))
                ->setBackgroundColor(Color::rgb(248, 250, 252))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Summary Card Value Style
            $summaryValueStyle = (new Style())
                ->setFontSize(12)
                ->setFontBold()
                ->setFontColor(Color::rgb(30, 58, 138))
                ->setBackgroundColor(Color::rgb(248, 250, 252))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Summary Highlight Style (Navy background)
            $summaryHighlightStyle = (new Style())
                ->setFontSize(12)
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(30, 58, 138))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Table Header Style (Navy)
            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(30, 58, 138))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Employee Group Row Style (Light Blue)
            $employeeGroupStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::rgb(30, 58, 138))
                ->setBackgroundColor(Color::rgb(219, 234, 254)) // Light blue
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(30, 58, 138), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(30, 58, 138), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(30, 58, 138), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Data Row Style (White)
            $dataStyle = (new Style())
                ->setFontSize(9)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Alternate Row Style (Light Gray)
            $altRowStyle = (new Style())
                ->setFontSize(9)
                ->setBackgroundColor(Color::rgb(248, 250, 252))
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Subtotal Row Style (Yellow/Gold tint)
            $subtotalStyle = (new Style())
                ->setFontBold()
                ->setFontSize(9)
                ->setBackgroundColor(Color::rgb(254, 243, 199)) // Light gold
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(245, 158, 11), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(245, 158, 11), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(226, 232, 240), Border::WIDTH_THIN, Border::STYLE_SOLID)
                ));
            
            // Grand Total Style (Navy)
            $grandTotalStyle = (new Style())
                ->setFontBold()
                ->setFontSize(11)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(30, 58, 138))
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID),
                    new BorderPart(Border::TOP, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID),
                    new BorderPart(Border::LEFT, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID),
                    new BorderPart(Border::RIGHT, Color::rgb(245, 158, 11), Border::WIDTH_THICK, Border::STYLE_SOLID)
                ));

            // ===== BUILD EXCEL CONTENT =====
            
            // 1. Logo/Company Row (merged cells simulation with PLN)
            $writer->addRow(Row::fromValues(['PLN', '', '', '', '', '', '', '', ''], $logoStyle));
            
            // 2. Main Title with Gold Border
            $writer->addRow(Row::fromValues(['LAPORAN REKAPITULASI PEMBAYARAN BFKO', '', '', '', '', '', '', '', ''], $titleStyle));
            
            // 3. Subtitle
            $writer->addRow(Row::fromValues(['Bantuan Fasilitas Kendaraan Operasional - ' . $yearText, '', '', '', '', '', '', '', ''], $subtitleStyle));
            
            // 4. Empty row
            $writer->addRow(Row::fromValues(['', '', '', '', '', '', '', '', '']));
            
            // 5. Summary Cards Row 1 - Labels
            $writer->addRow(Row::fromValues([
                'PERIODE LAPORAN', '',
                'TOTAL PEGAWAI', '',
                'TOTAL TRANSAKSI', '',
                'TOTAL PEMBAYARAN', '', ''
            ], $summaryLabelStyle));
            
            // 6. Summary Cards Row 2 - Values
            $writer->addRow(Row::fromValues([
                $yearText, '',
                $totalEmployees . ' Orang', '',
                $totalTransactions . ' Data', '',
                'Rp ' . number_format($totalAll, 0, ',', '.'), '', ''
            ], $summaryHighlightStyle));
            
            // 7. Empty row
            $writer->addRow(Row::fromValues(['', '', '', '', '', '', '', '', '']));
            
            // 8. Table Headers
            $headers = ['NO', 'NIP', 'NAMA', 'JABATAN', 'UNIT', 'BULAN', 'TAHUN', 'NILAI ANGSURAN', 'STATUS'];
            $writer->addRow(Row::fromValues($headers, $headerStyle));
            
            // 9. Data Rows - Grouped by Employee
            $rowNumber = 1;
            foreach ($employees as $employee) {
                // Employee Group Header
                $groupHeader = sprintf(
                    '%s (%s) - %s | %s | Total: Rp %s',
                    $employee['nama'],
                    $employee['nip'],
                    $employee['jabatan'],
                    $employee['unit'],
                    number_format($employee['total'], 0, ',', '.')
                );
                $writer->addRow(Row::fromValues([$groupHeader, '', '', '', '', '', '', '', ''], $employeeGroupStyle));
                
                // Employee Payments
                foreach ($employee['payments'] as $idx => $payment) {
                    $rowData = [
                        $rowNumber++,
                        $payment->nip,
                        $payment->nama,
                        $payment->jabatan,
                        $payment->unit,
                        $payment->bulan,
                        $payment->tahun,
                        'Rp ' . number_format($payment->nilai_angsuran, 0, ',', '.'),
                        $payment->status_angsuran
                    ];
                    
                    $rowStyle = ($idx % 2 == 0) ? $dataStyle : $altRowStyle;
                    $writer->addRow(Row::fromValues($rowData, $rowStyle));
                }
                
                // Subtotal Row
                $writer->addRow(Row::fromValues([
                    '', '', '', '', '', '', 'SUBTOTAL:',
                    'Rp ' . number_format($employee['total'], 0, ',', '.'),
                    ''
                ], $subtotalStyle));
            }
            
            // 10. Grand Total Row
            $writer->addRow(Row::fromValues([
                '', '', '', '', '', '', 'TOTAL KESELURUHAN:',
                'Rp ' . number_format($totalAll, 0, ',', '.'),
                ''
            ], $grandTotalStyle));

            $writer->close();

            // Return response download and delete after send
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
