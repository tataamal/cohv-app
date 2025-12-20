<!DOCTYPE html>
<html>
<head>
    <title>Production History Log</title>
    <style>
        /* --- PAGE SETUP --- */
        @page { size: A4 landscape; margin: 5mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8pt; margin: 0; padding: 0; }

        /* --- FRAMEWORK --- */
        .container-frame { width: 100%; border: 2px solid #000; }
        
        /* --- TABLE STYLING --- */
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 3px; vertical-align: middle; }

        /* --- HEADER SECTION --- */
        .header-left {
            width: 70%; padding: 5px 10px !important;
            border-bottom: 2px solid #050505ff; border-right: none !important;
        }
        .header-right {
            width: 30%; padding: 0 !important; vertical-align: middle; text-align: center;
            border-bottom: 2px solid #000; border-left: none !important; background-color: #fff;
        }
        .branding-table td { border: none !important; padding: 0; }
        .logo-img { width: 60px; vertical-align: middle; padding-right: 15px !important; }
        .company-name { font-size: 16pt; font-weight: 900; margin: 0; text-transform: uppercase; line-height: 1; }
        .doc-title { font-size: 10pt; margin-top: 5px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        
        /* Judul Laporan di Kanan */
        .report-type-display {
            font-size: 16pt; font-weight: 900; color: #000080; letter-spacing: 1px; text-transform: uppercase;
        }

        /* --- INFO BAR --- */
        .info-bar td {
            background-color: #f2f2f2; font-size: 7pt; font-weight: bold;
            text-transform: uppercase; padding: 4px; border-bottom: 2px solid #000;
        }
        .info-val { color: #000; margin-left: 5px; font-weight: normal; }

        /* --- SUMMARY SECTION --- */
        .summary-header td {
            background-color: #e9ecef; text-align: center; font-size: 7pt; font-weight: bold; text-transform: uppercase;
        }
        .summary-values td {
            text-align: center; font-size: 10pt; font-weight: bold; padding: 6px; border-bottom: 2px solid #000;
        }
        .text-success { color: #198754; }
        .text-danger { color: #d00; }
        .text-warning { color: #E4A11B; } /* Orange-ish */

        /* --- DATA TABLE --- */
        .data-header th {
            background-color: #ccc; color: #000; text-transform: uppercase;
            font-size: 7pt; font-weight: 900; height: 20px; border-bottom: 2px solid #000;
        }
        .data-row td { font-size: 7.5pt; height: 20px; vertical-align: top; word-wrap: break-word; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }

    </style>
</head>
<body>

@foreach($reports as $report)
<div class="page-container" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
    <div class="container-frame">
        <table style="width: 100%;">
            <tr>
                <td class="header-left">
                    <table class="branding-table">
                        <tr>
                            <td class="logo-img">
                                <img src="{{ public_path('images/KMI.png') }}" style="max-height: 45px; width: auto;">
                            </td>
                            <td>
                                <div class="company-name">PT KAYU MEBEL INDONESIA</div>
                                <div class="doc-title">WORK INSTRUCTIONS RESULT REPORT</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="header-right">
                    <div class="report-type-display"> REPORT WORK INSTRUCTIONS</div>
                </td>
            </tr>
        </table>

        <table>
            <tr class="info-bar">
                <td width="25%">BAGIAN: <span class="info-val fw-bold">{{ strtoupper($report['department']) }}</span></td>
                {{-- User Removed --}}
                <td width="25%">PRINT DATE: <span class="info-val">{{ $report['printDate'] }}</span></td>
                {{-- Filter Data Removed --}}
                <td width="25%" style="border-right: none;">TOTAL DOCS: <span class="info-val fw-bold">{{ collect($report['items'])->unique('doc_no')->count() }}</span></td>
            </tr>
        </table>

        <table>
            <tr class="summary-header">
                <td width="16%">Quantity WI</td>
                <td width="16%">Quantity Terkonfirmasi</td>
                {{-- Quantity Remark Removed --}}
                <td width="16%">Quantity Tidak Terkonfirmasi</td>
                <td width="16%">Total Price OK</td>
                <td width="16%">Total Price Fail</td>
                <td width="20%">Rata-rata Keberhasilan</td>
            </tr>
            <tr class="summary-values">
                <td>{{ number_format($report['summary']['total_assigned'], 0) }}</td>
                <td class="text-success">{{ number_format($report['summary']['total_confirmed'], 0) }}</td>
                {{-- Value Removed --}}
                <td class="text-danger">{{ number_format($report['summary']['total_failed'], 0) }}</td>
                <td class="text-success" style="font-size: 8pt;">{{ $report['summary']['total_price_ok'] }}</td>
                <td class="text-danger" style="font-size: 8pt;">{{ $report['summary']['total_price_fail'] }}</td>
                <td>{{ $report['summary']['achievement_rate'] }}</td>
            </tr>
        </table>

        <table style="table-layout: fixed;">
            <thead>
                <tr class="data-header">
                    <th width="3%">NO</th>
                    <th width="8%">PRO</th>
                    {{-- Operator Removed --}}
                    <th width="8%">BUYER</th>
                    <th width="6%">WC</th>
                    <th width="8%">MAT</th>
                    <th width="20%">DESKRIPSI</th>
                    <th width="4%">WI</th>
                    <th width="4%">CONF</th>
                    <th width="8%">PRICE (OK)</th>
                    <th width="8%">PRICE (FAIL)</th>
                    {{-- Remark Removed --}}
                    <th width="6%">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($report['items'] as $row)
                <tr class="data-row">
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="fw-bold">{{ $row['aufnr'] }}</td>
                    {{-- Operator Removed --}}
                    <td class="text-center">{{ $row['buyer'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['workcenter'] }}</td>
                    <td class="text-center">{{ $row['material'] }}</td>
                    <td class="text-center">{{ $row['description'] }}</td>
                    <td class="text-center fw-bold">{{ floatval($row['assigned']) }}</td>
                    <td class="text-center fw-bold text-success">{{ floatval($row['confirmed']) }}</td>
                    <td class="text-center text-success" style="font-size: 6pt;">{{ $row['price_ok_fmt'] ?? '-' }}</td>
                    <td class="text-center text-danger" style="font-size: 6pt;">{{ $row['price_fail_fmt'] ?? '-' }}</td>
                    {{-- Remark Removed --}}
                    <td class="text-center" style="font-size: 6.5pt;">
                        <span class="{{ $row['status'] == 'COMPLETED' ? 'text-success' : ($row['status'] == 'NOT COMPLETED WITH REMARK' ? 'text-warning' : ($row['status'] == 'ACTIVE' ? '' : 'text-danger')) }}">
                            {{ $row['status'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

</body>
</html>
