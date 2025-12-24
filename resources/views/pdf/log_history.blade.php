<!DOCTYPE html>
<html>
<head>
    <title>Production History Log</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm; 
            margin-bottom: 15mm; /* Space for footer */
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 0;
        }

        /* --- FOOTER / WATERMARK --- */
        .page-footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            height: 10mm;
            font-size: 8pt;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 2px;
        }
        .footer-content {
            width: 100%;
            display: table;
        }
        .footer-left {
            display: table-cell;
            text-align: left;
            width: 50%;
            font-style: italic;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
            width: 50%;
        }
        .page-number:after { content: counter(page); }

        /* --- FRAMEWORK --- */
        .container-frame {
            width: 100%;
            border: 2px solid #000;
            display: block;
            height: 98%;
        }

        /* --- PAGE BREAK UTILITY --- */
        .page-break {
            page-break-after: always;
        }

        /* --- TABLE STYLING --- */
        table { width: 100%; border-collapse: collapse; }
        
        td, th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
        }

        /* --- HEADER SECTION --- */
        .header-left {
            width: 70%; 
            padding: 5px 10px !important;
            border-bottom: 2px solid #000;
            border-right: none !important;
        }

        .header-right {
            width: 30%;
            padding: 0 !important;
            vertical-align: middle;
            text-align: center;
            border-bottom: 2px solid #000;
            background-color: #f9f9f9; 
            border-left: none !important;
        }

        .branding-table td { border: none !important; padding: 0; }
        .logo-img { width: 60px; vertical-align: middle; padding-right: 15px !important; }
        
        .company-name { 
            font-size: 18pt; font-weight: 900; margin: 0; 
            text-transform: uppercase; line-height: 1;
        }
        .doc-title { 
            font-size: 11pt; margin-top: 5px; font-weight: bold;
            letter-spacing: 3px; text-transform: uppercase; 
        }
        
        /* Report Title Display */
        .report-type-display {
            font-size: 18pt; font-weight: 900;
            color: #000080; letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* --- INFO BAR --- */
        .info-bar td {
            background-color: #f2f2f2;
            font-size: 8pt; font-weight: bold;
            text-transform: uppercase; padding: 6px;
            border-bottom: 2px solid #000;
        }
        .info-val { color: #000; margin-left: 5px; font-weight: normal; }
        
        /* --- SUMMARY SECTION (New Style) --- */
        .summary-header td {
            background-color: #e9ecef; text-align: center; font-size: 7.5pt; font-weight: bold; text-transform: uppercase;
        }
        .summary-values td {
            text-align: center; font-size: 9pt; font-weight: bold; padding: 6px; border-bottom: 2px solid #000;
        }
        .text-success { color: #008000; }
        .text-danger { color: #d00; }
        .text-warning { color: #E4A11B; } 

        /* --- DATA TABLE --- */
        .data-header th {
            background-color: #ccc; color: #000;
            text-transform: uppercase; font-size: 8pt;
            font-weight: 900; height: 25px;
            border-bottom: 2px solid #000;
        }
        .data-row td {
            font-size: 8pt; height: 25px;
            vertical-align: top; word-wrap: break-word;
        }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        /* --- FOOTER --- */
        .footer-cell {
            height: 80px; vertical-align: top !important;
            padding: 0 !important; border-top: 2px solid #000;
        }
        .sig-title {
            font-size: 7pt; font-weight: bold;
            background-color: #dfdfdf; border-bottom: 1px solid #000;
            padding: 3px; text-align: center;
        }
        .sig-content {
            padding-top: 40px; text-align: center; font-size: 9pt;
        }
    </style>
</head>
<body>
    
    {{-- FOOTER GLOBAL --}}
    <div class="page-footer">
        <div class="footer-content">
            <div class="footer-left">PT KAYU MEBEL INDONESIA - INTERNAL USE ONLY</div>
            <div class="footer-right">Page <span class="page-number"></span></div>
        </div>
    </div>

{{-- Main Loop Over Reports (One per Document) --}}
@foreach($reports as $report)
    <div class="container-frame">
        {{-- 1. HEADER --}}
        <table style="width: 100%;">
            <tr>
                <td class="header-left">
                    <table class="branding-table">
                        <tr>
                            <td class="logo-img">
                                <img src="{{ public_path('images/KMI.png') }}" style="max-height: 50px; width: auto;">
                            </td>
                            <td>
                                <div class="company-name">PT KAYU MEBEL INDONESIA</div>
                                <div class="doc-title">WORK INSTRUCTION RESULT REPORT</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="header-right">
                    <div class="report-type-display">{{ $report['items'][0]['doc_no'] ?? 'REPORT WI' }}</div>
                </td>
            </tr>
        </table>

        {{-- 2. INFO BAR --}}
        <table>
            <tr class="info-bar">
                <td width="25%">BAGIAN: <span class="info-val fw-bold">{{ strtoupper($report['nama_bagian']) }}</span></td>
                <td width="25%">PRINT DATE: <span class="info-val">{{ $report['printDate'] }}</span></td>
                <td width="50%" style="border-right: none;">RANGE DATA: <span class="info-val">{{ $report['filterInfo'] ?? '-' }}</span></td>
            </tr>
        </table>

         {{-- 3. SUMMARY TABLE (Inserted Here) --}}
         <table>
            <tr class="summary-header">
                <td width="16%">Quantity WI</td>
                <td width="16%">Quantity Terkonfirmasi</td>
                <td width="16%">Quantity Tidak Terkonfirmasi</td>
                <td width="16%">Total Price OK</td>
                <td width="16%">Total Price Fail</td>
                <td width="20%">Rata-rata Keberhasilan</td>
            </tr>
            <tr class="summary-values">
                <td>{{ number_format($report['summary']['total_assigned'], 0) }}</td>
                <td class="text-success">{{ number_format($report['summary']['total_confirmed'], 0) }}</td>
                <td class="text-danger">{{ number_format($report['summary']['total_failed'], 0) }}</td>
                <td class="text-success">{{ $report['summary']['total_price_ok'] }}</td>
                <td class="text-danger">{{ $report['summary']['total_price_fail'] }}</td>
                <td>{{ $report['summary']['achievement_rate'] }}</td>
            </tr>
        </table>

        {{-- 4. DATA TABLE --}}
        <table style="table-layout: fixed;">
            <thead>
                <tr class="data-header">
                    <th width="3%">NO</th>
                    <th width="7%">WORK CENTER</th>
                    <th width="8%">SO-ITEM</th>
                    <th width="8%">PRO</th>
                    <th width="9%">MATERIAL NO</th>
                    <th width="15%">DESCRIPTION</th>
                    <th width="5%">QTY WI</th>
                    <th width="5%">CONF</th>
                    <th width="6%">TIME REQ</th>
                    <th width="7%">NIK</th>
                    <th width="10%">NAME</th>
                    <th width="9%">REMARK</th>
                    <th width="8%">PRICE OK</th>
                    <th width="8%">PRICE FAIL</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $no = 1; 
                    $minRows = 10; // Slightly less due to summary occupying space
                    $items = $report['items'];
                    $itemCount = count($items);
                    $rowsToFill = max(0, $minRows - $itemCount);
                @endphp

                @foreach($items as $row)
                <tr class="data-row">
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center fw-bold">{{ $row['workcenter'] }}</td>
                    <td class="text-center">{{ $row['so_item'] }}</td>
                    <td class="text-center fw-bold">{{ $row['aufnr'] }}</td>
                    <td class="text-center">{{ $row['material'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-center fw-bold">{{ floatval($row['assigned']) }}</td>
                    <td class="text-center fw-bold text-success">{{ floatval($row['confirmed']) }}</td>
                    <td class="text-center fw-bold">{{ $row['takt_time'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['nik'] ?? '-' }}</td>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    
                    {{-- Remark Logic --}}
                    <td class="text-center" style="font-size: 7pt;">
                        @if(floatval($row['remark_qty']) > 0)
                            {{ $row['remark_text'] }}
                        @elseif(!empty($row['remark_text']) && $row['remark_text'] !== '-')
                            {{ $row['remark_text'] }}
                        @else
                            -
                        @endif
                    </td>

                    <td class="text-center text-success" style="font-size: 7pt;">{{ $row['price_ok_fmt'] ?? '-' }}</td>
                    <td class="text-center text-danger" style="font-size: 7pt;">{{ $row['price_fail_fmt'] ?? '-' }}</td>
                </tr>
                @endforeach

                  {{-- AUTO FILL ROWS --}}
                  @foreach(range(1, $rowsToFill) as $i)
                  <tr class="data-row">
                      <td class="text-center">&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                  </tr>
                  @endforeach
            </tbody>
        </table>


    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
