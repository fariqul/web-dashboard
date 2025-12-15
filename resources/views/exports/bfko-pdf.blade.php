<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan BFKO - {{ $yearText }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.5;
            color: #1e293b;
        }
        
        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #1e3a8a;
        }
        
        .header .logo-placeholder {
            width: 60px;
            height: 60px;
            background: #1e3a8a;
            border-radius: 8px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24pt;
        }
        
        .header h1 {
            margin: 0;
            font-size: 20pt;
            color: #1e3a8a;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .header .gold-underline {
            width: 100px;
            height: 3px;
            background: #f59e0b;
            margin: 8px auto;
        }
        
        .header .subtitle {
            margin: 8px 0 0 0;
            font-size: 10pt;
            color: #64748b;
            font-weight: normal;
        }
        
        /* Summary Box */
        .summary-box {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-cell {
            display: table-cell;
            padding: 12px 15px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .summary-cell:last-child {
            border-right: none;
        }
        
        .summary-cell .label {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }
        
        .summary-cell .value {
            font-size: 12pt;
            color: #1e3a8a;
            font-weight: bold;
            display: block;
        }
        
        .summary-cell.highlight {
            background: #1e3a8a;
            color: white;
        }
        
        .summary-cell.highlight .label {
            color: #f59e0b;
        }
        
        .summary-cell.highlight .value {
            color: white;
        }
        
        /* Data Table */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 8pt;
        }
        
        table.main-table thead {
            background-color: #1e3a8a;
            color: #ffffff;
        }
        
        table.main-table th {
            padding: 10px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1e3a8a;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        table.main-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        
        table.main-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        table.main-table tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        table.main-table td {
            padding: 8px 6px;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }
        
        table.main-table td:first-child {
            border-left: 1px solid #cbd5e1;
        }
        
        table.main-table td:last-child {
            border-right: 1px solid #cbd5e1;
        }
        
        /* Employee Group Row */
        .employee-row {
            background-color: #dbeafe !important;
            font-weight: bold;
            border-top: 2px solid #1e3a8a !important;
            border-bottom: 2px solid #1e3a8a !important;
        }
        
        .employee-row td {
            padding: 10px 6px !important;
            color: #1e3a8a;
            background-color: #dbeafe;
        }
        
        /* Grand Total */
        .grand-total-row {
            background-color: #1e3a8a !important;
            font-weight: bold;
            border-top: 3px solid #f59e0b !important;
        }
        
        .grand-total-row td {
            padding: 12px 6px !important;
            color: white !important;
            font-size: 10pt;
            border: none !important;
        }
        
        .gold-accent {
            color: #f59e0b;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-lunas {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-cicilan {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Footer */
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #64748b;
        }
        
        .footer .disclaimer {
            margin-bottom: 5px;
            font-style: italic;
        }
        
        .footer .timestamp {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-placeholder">PLN</div>
        <h1>LAPORAN REKAPITULASI PEMBAYARAN BFKO</h1>
        <div class="gold-underline"></div>
        <p class="subtitle">Bantuan Fasilitas Kendaraan Operasional - {{ $yearText }}</p>
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <div class="summary-row">
            <div class="summary-cell">
                <span class="label">Periode Laporan</span>
                <span class="value">{{ $yearText }}</span>
            </div>
            <div class="summary-cell">
                <span class="label">Total Pegawai</span>
                <span class="value">{{ $employees->count() }} Orang</span>
            </div>
            <div class="summary-cell">
                <span class="label">Total Transaksi</span>
                <span class="value">{{ collect($employees)->sum(function($e) { return count($e['payments']); }) }} Data</span>
            </div>
            <div class="summary-cell highlight">
                <span class="label">Total Pembayaran</span>
                <span class="value">Rp {{ number_format($totalAll, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <table class="main-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="10%">NIP</th>
                <th width="18%">Nama Pegawai</th>
                <th width="16%">Jabatan</th>
                <th width="12%">Unit</th>
                <th width="9%">Bulan</th>
                <th width="6%">Tahun</th>
                <th width="13%" class="text-right">Nilai Angsuran</th>
                <th width="12%">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNumber = 1; @endphp
            @foreach($employees as $employee)
                <!-- Employee Group Header -->
                <tr class="employee-row">
                    <td colspan="9">
                        <strong>{{ $employee['nama'] }}</strong> ({{ $employee['nip'] }}) - 
                        {{ $employee['jabatan'] }} | {{ $employee['unit'] }} | 
                        <span class="gold-accent">Total: Rp {{ number_format($employee['total'], 0, ',', '.') }}</span>
                    </td>
                </tr>
                
                <!-- Employee Payments -->
                @foreach($employee['payments'] as $payment)
                <tr>
                    <td class="text-center">{{ $rowNumber++ }}</td>
                    <td>{{ $employee['nip'] }}</td>
                    <td>{{ $employee['nama'] }}</td>
                    <td>{{ $employee['jabatan'] }}</td>
                    <td>{{ $employee['unit'] }}</td>
                    <td>{{ $payment->bulan }}</td>
                    <td class="text-center">{{ $payment->tahun }}</td>
                    <td class="text-right">Rp {{ number_format($payment->nilai_angsuran, 0, ',', '.') }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $payment->status_angsuran)) }}">
                            {{ $payment->status_angsuran }}
                        </span>
                    </td>
                </tr>
                @endforeach
            @endforeach
            
            <!-- Grand Total Row -->
            <tr class="grand-total-row">
                <td colspan="7" class="text-right">
                    <span class="gold-accent">● </span>TOTAL KESELURUHAN PEMBAYARAN BFKO
                </td>
                <td class="text-right">Rp {{ number_format($totalAll, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p class="disclaimer">
            Dokumen ini adalah laporan resmi yang dibuat secara otomatis oleh Sistem Monitoring BFKO PT PLN (Persero)
        </p>
        <p class="timestamp">
            Dicetak pada: {{ $exportDate }} | Periode: {{ $yearText }} | Confidential Document
        </p>
    </div>
</body>
</html>
