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
            /* height: 98%; Removed to allow natural flow */
            padding-bottom: 20px;
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
        {{-- 1. HEADER --}}
        <table style="width: 100%;">
            <tr>
                <td class="header-left" style="width: 100%; border-right: 2px solid #000 !important;">
                    <table class="branding-table">
                        <tr>
                            <td class="logo-img">
                                <img src="{{ public_path('images/KMI.png') }}" style="max-height: 50px; width: auto;">
                            </td>
                            <td>
                                <div class="company-name">PT KAYU MEBEL INDONESIA</div>
                                <!-- New Title Format -->
                                <div class="doc-title">DAILY REPORT WI - {{ $report['nama_bagian'] }} - {{ $report['filterInfo'] }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <!-- Removed Right Header (Doc No) as requested -->
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
                    <th width="8%">DOC NO</th>
                    <th width="6%">NIK</th>
                    <th width="10%">NAME</th>
                    <th width="8%">SO-ITEM</th>
                    <th width="8%">PRO</th>
                    <th width="9%">MATERIAL NO</th>
                    <th width="15%">DESCRIPTION</th>
                    <th width="4%">QTY</th>
                    <th width="4%">CONF</th>
                    <th width="6%">TIME</th>
                    <th width="9%">REMARK</th>
                    <th width="8%">PRICE OK</th>
                    <th width="8%">PRICE FAIL</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $no = 1; 
                    $currentWc = null;
                    $items = $report['items'];
                @endphp

                @foreach($items as $row)
                    {{-- GROUP HEADER ROW --}}
                    @if($currentWc !== $row['workcenter'])
                        @php 
                            $currentWc = $row['workcenter']; 
                            // Calculate Group Totals (Optional but nice, Image 2 shows "1 Transaksi | Total Qty: 20")
                            // We can calculate this using collection inside the view or pass it.
                            // For simplicty/performance in Blade, let's filter the collection (might be heavy if large list).
                            // But usually list is < 100 items.
                            $groupItems = collect($items)->where('workcenter', $currentWc);
                            $groupCount = $groupItems->count();
                            $groupQty = $groupItems->sum('confirmed');
                            $wcDesc = $row['wc_description'] ?? '';
                        @endphp
                        <tr>
                            <td colspan="14" style="background-color: #f0f0f0; padding: 5px; border: 1px solid #000;">
                                <strong>WORKCENTER: {{ $currentWc }} 
                                @if(!empty($wcDesc) && $wcDesc !== '-') - {{ $wcDesc }} @endif
                                </strong> 
                                <span style="font-size: 8pt; margin-left: 10px;">
                                    ({{ $groupCount }} Transaksi | Total Confirmed: {{ $groupQty }})
                                </span>
                            </td>
                        </tr>
                    @endif

                    <tr class="data-row">
                        <td class="text-center">{{ $no++ }}</td>
                        <td class="text-center fw-bold">{{ $row['doc_no'] }}</td>
                        <td class="text-center">{{ $row['nik'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-center">{{ $row['so_item'] }}</td>
                        <td class="text-center fw-bold">{{ $row['aufnr'] }}</td>
                        <td class="text-center">{{ $row['material'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="text-center fw-bold">{{ floatval($row['assigned']) }}</td>
                        <td class="text-center fw-bold text-success">{{ floatval($row['confirmed']) }}</td>
                        <td class="text-center fw-bold">{{ $row['takt_time'] ?? '-' }}</td>
                        
                        {{-- Remark --}}
                        <td class="text-center" style="font-size: 7pt;">
                            @if(floatval($row['remark_qty']) > 0)
                                M:{{ floatval($row['remark_qty']) }} {{ $row['remark_text'] }}
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
            </tbody>
        </table>


    </div>

    </div>

    {{-- Page Break Removed --}}

@endforeach

</body>
</html>
