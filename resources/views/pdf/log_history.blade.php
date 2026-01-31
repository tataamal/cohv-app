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
                <td class="header-left" style="width: 70%; border-right: none !important;">
                    <table class="branding-table">
                        <tr>
                            <td class="logo-img">
                                <img src="{{ public_path('images/KMI.png') }}" style="max-height: 50px; width: auto;">
                            </td>
                            <td>
                                <div class="company-name">PT KAYU MEBEL INDONESIA</div>
                                <!-- New Title Format -->
                                @if($isEmail ?? false)
                                    <div class="doc-title">{{ $report['report_title'] ?? 'DAILY REPORT WI' }} - {{ $report['nama_bagian'] }}</div>
                                @else
                                    @php
                                        $docStatus = strtoupper($report['doc_metadata']['status'] ?? '');
                                        $headerTitle = 'TASK INSTRUCTION';
                                        if (str_contains($docStatus, 'COMPLETED') && !str_contains($docStatus, 'NOT')) {
                                            $headerTitle = 'COMPLETED TASK INSTRUCTION';
                                        } elseif (str_contains($docStatus, 'EXPIRED') || str_contains($docStatus, 'NOT COMPLETED')) {
                                            $headerTitle = 'EXPIRED TASK INSTRUCTION';
                                        }
                                    @endphp
                                    <div class="doc-title" style="font-size: 14pt; color: #000;">{{ $headerTitle }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                
                {{-- RIGHT HEADER --}}
                 <td class="header-right" style="width: 30%; border-left: none !important; background-color: #fff; vertical-align: middle; padding: 5px 10px !important; text-align: right;">
                    @if(!empty($report['filterInfo']) && stripos($report['filterInfo'], 'all history') === false)
                        <div style="font-size: 11pt; font-weight: bold; color: rgba(0, 0, 0, 0.7);">
                            {{ $report['filterInfo'] }}
                        </div>
                    @endif
                </td>
        </table>

        {{-- 2. INFO ROW (For Manual Print) --}}
        @if(!($isEmail ?? false))
            <table class="info-bar" style="width: 100%; border: 2px solid #000; border-top: none; margin-bottom: 0px;">
                <tr>
                    <td style="width: 35%; border-right: 2px solid #000;">
                        BAGIAN: <span class="info-val">{{ $report['nama_bagian'] }}</span>
                    </td>
                    <td style="width: 25%; border-right: 2px solid #000;">
                        @php
                            $rawStatus = $report['doc_metadata']['status'] ?? '-';
                            $expStr = $report['doc_metadata']['expired'] ?? '';
                            
                            $isExpired = false;
                            try {
                                // Date format from controller usually 'd-M-Y H:i' or similar
                                // If validation fails, fallback to raw status
                                $expDate = \Carbon\Carbon::createFromFormat('d-M-Y H:i', $expStr);
                                if ($expDate && $expDate->isPast()) {
                                    $isExpired = true;
                                }
                            } catch (\Exception $e) {
                                // If parsing fails, ignore expiration check (likely '-' or empty)
                            }

                            $finalStatus = ($isExpired || strtoupper($rawStatus) === 'INACTIVE') ? 'INACTIVE' : $rawStatus;
                        @endphp
                        STATUS: <span class="info-val">{{ $finalStatus }}</span>
                    </td>
                    <td style="width: 20%; border-right: 2px solid #000;">
                        DATE: <span class="info-val">{{ $report['doc_metadata']['date'] ?? '-' }}</span>
                    </td>
                    <td style="width: 20%;">
                        EXPIRED: <span class="info-val" style="color: #d00;">{{ $report['doc_metadata']['expired'] ?? '-' }}</span>
                    </td>
                </tr>
            </table>
        @endif

         {{-- 3. SUMMARY TABLE (Inserted Here) --}}
         {{-- 3. SUMMARY TABLE --}}
         <table>
            <tr class="summary-header">
                @if($isEmail ?? false)
                    <td width="16%">Quantity Task</td>
                    <td width="16%">Terkonfirmasi</td>
                    <td width="16%">Tidak Terkonfirmasi</td>
                    <td width="20%">Rata-rata Keberhasilan</td>
                    <td width="16%">Total Price OK</td>
                    <td width="16%">Total Price Fail</td>
                @else
                    <td width="25%">QTY TASK</td>
                    <td width="25%">Terkonfirmasi</td>
                    <td width="25%">Tidak Terkonfirmasi</td>
                    <td width="25%">Rata-rata Keberhasilan</td>
                @endif
            </tr>
            <tr class="summary-values">
                <td>{{ number_format($report['summary']['total_assigned'], 0) }}</td>
                <td class="text-success">{{ number_format($report['summary']['total_confirmed'], 0) }}</td>
                <td class="text-danger">{{ number_format($report['summary']['total_failed'], 0) }}</td>
                <td>{{ $report['summary']['achievement_rate'] }}</td>
                @if($isEmail ?? false)
                    <td class="text-success">{{ $report['summary']['total_price_ok'] }}</td>
                    <td class="text-danger">{{ $report['summary']['total_price_fail'] }}</td>
                @endif
            </tr>
        </table>

        {{-- 4. DATA TABLE --}}
        <table style="table-layout: fixed;">
            <thead>
                <tr class="data-header">
                    <th width="3%">NO</th>
                    <th width="8%">DOC</th>
                    <th width="6%">TIME</th>
                    <th width="10%">WORKCENTER</th>
                    <th width="8%">SO-ITEM</th>
                    <th width="8%">PRO</th>
                    <th width="10%">MATERIAL</th>
                    <th width="4%">QTY</th>
                    <th width="4%">CONF</th>
                    <th width="20%">REMARK</th>
                    @if($isEmail ?? false)
                        <th width="8%">PRICE OK</th>
                        <th width="8%">PRICE FAIL</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php 
                    $no = 1; 
                    $currentNik = null;
                    $items = $report['items'];
                    $isActiveStatus = in_array(strtoupper($report['doc_metadata']['status'] ?? ''), ['ACTIVE', 'INACTIVE']);
                @endphp

                @foreach($items as $row)
                    {{-- GROUP HEADER ROW (NIK) --}}
                    @if($currentNik !== $row['nik'])
                        @php 
                            $currentNik = $row['nik']; 
                            $nikName = $row['name'] ?? '-';
                            
                            // Calculate Group Stats
                            $groupItems = collect($items)->where('nik', $currentNik);
                            $groupCount = $groupItems->count();
                            
                            $gAssigned = $groupItems->sum('assigned');
                            $gConfirmed = $groupItems->sum('confirmed');
                            $gRemark = $groupItems->sum(function($item) { return $item['remark_qty'] ?? 0; });
                            $gUnconfirmed = $gAssigned - $gConfirmed; // "Tidak Terkonfirmasi"
                            
                            $gPriceOk = $groupItems->sum('confirmed_price');
                            $gPriceFail = $groupItems->sum('failed_price');
                            $pctOk = ($gAssigned > 0) ? ($gConfirmed / $gAssigned) * 100 : 0;
                            $pctFail = ($gAssigned > 0) ? ($gUnconfirmed / $gAssigned) * 100 : 0;
                            
                            $gTotalTimeMin = $groupItems->sum('raw_total_time');
                            
                            $totalSeconds = $gTotalTimeMin * 60;
                            $hrs = floor($totalSeconds / 3600);
                            $mins = floor(($totalSeconds % 3600) / 60);
                            $secs = round($totalSeconds % 60);

                            $timeParts = [];
                            if ($hrs > 0) $timeParts[] = $hrs . ' Jam';
                            if ($mins > 0) $timeParts[] = $mins . ' Menit';
                            if ($secs > 0 || empty($timeParts)) $timeParts[] = $secs . ' Detik';
                            
                            $gTotalTimeHoursFmt = implode(', ', $timeParts);


                            $gCurr = $row['currency'] ?? 'IDR';
                            $pfx = (strtoupper($gCurr) === 'USD') ? '$ ' : 'Rp ';
                            $dec = (strtoupper($gCurr) === 'USD') ? 2 : 0;

                            $fmtOk = $pfx . number_format($gPriceOk, $dec, ',', '.');
                            $fmtFail = $pfx . number_format($gPriceFail, $dec, ',', '.');
                        @endphp
                        <tr>
                            <td colspan="13" style="background-color: #f0f0f0; padding: 5px; border: 1px solid #000;">
                                <strong>NIK {{ $currentNik }} {{ $nikName }}</strong>
                                <span style="font-size: 8pt; margin-left: 10px;">
                                    @if($isEmail ?? false)
                                        (Total Qty: {{ number_format($gAssigned, 0) }} | Working Hours: {{ $gTotalTimeHoursFmt }} | Konfirmasi: {{ number_format($gConfirmed, 0) }} ({{ number_format($pctOk, 1) }}%), Tidak Terkonfirmasi: {{ number_format($gUnconfirmed, 0) }} ({{ number_format($pctFail, 1) }}%) | OK : {{ $fmtOk }}, Fail : {{ $fmtFail }})
                                    @else
                                        (Total Qty: {{ number_format($gAssigned, 0) }} | Working Hours: {{ $gTotalTimeHoursFmt }})
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endif

                    <tr class="data-row">
                        <td class="text-center">{{ $no++ }}</td>
                        <td class="text-center fw-bold">
                            {{ $row['doc_no'] }}
                            @if(str_contains($report['report_title'] ?? '', 'WEEKLY'))
                                <br>
                                <span style="font-size: 7pt; font-weight: normal;">{{ $row['doc_date'] ?? '' }}</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">
                            @if(isset($row['takt_time']) && $row['takt_time'] !== '-')
                                {{ $row['takt_time'] }}
                            @else
                                -
                            @endif
                        </td>
                        {{-- Merged Column --}}
                        <td class="text-center">
                            <!-- <strong>{{ $row['workcenter'] }}</strong><br> -->
                            {{ $row['description'] ?? '-' }}
                        </td>
                        
                        <td class="text-center">{{ $row['so_item'] }}</td>
                        <td class="text-center fw-bold">
                            {{ $row['aufnr'] }}
                            @if(!empty($row['vornr']))
                                <br>
                                <span style="font-weight: normal; font-style: italic; font-size: 7pt;">({{ ltrim($row['vornr'], '0') }})</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $row['material'] }}</strong><br>
                            {{ $row['description'] }}
                        </td>
                        <td class="text-center fw-bold">{{ floatval($row['assigned']) }}</td>
                        <td class="text-center fw-bold text-success">{{ floatval($row['confirmed']) }}</td>
                        
                        {{-- Remark --}}
                        {{-- Remark --}}
                        <td class="text-left" style="font-size: 7pt;">
                            @if(!empty($row['remark_details']) && count($row['remark_details']) > 0)
                                <ul style="padding-left: 15px; margin: 0; text-align: left;">
                                @foreach($row['remark_details'] as $rem)
                                    <li>Qty : {{ floatval($rem['qty']) }}, {{ $rem['text'] }}</li>
                                @endforeach
                                </ul>
                            @elseif(floatval($row['remark_qty'] ?? 0) > 0)
                                <div style="text-align: center;">
                                    <strong>Qty: {{ floatval($row['remark_qty']) }}</strong><br>
                                    {{ $row['remark_text'] ?? '-' }}
                                </div>
                            @elseif(!empty($row['remark_text']) && $row['remark_text'] !== '-')
                                <div style="text-align: center;">{{ $row['remark_text'] }}</div>
                            @else
                                <div style="text-align: center;">-</div>
                            @endif
                        </td>

                        @if($isEmail ?? false)
                            <td class="text-center text-success" style="font-size: 7pt;">{{ $row['price_ok_fmt'] ?? '-' }}</td>
                            <td class="text-center text-danger" style="font-size: 7pt;">{{ $row['price_fail_fmt'] ?? '-' }}</td>
                        @endif
                    </tr>
                @endforeach
                
                {{-- FILL EMPTY ROWS (For Active Documents Manual Print) --}}
                @if(!($isEmail ?? false) && $isActiveStatus)
                    @php
                        // Estimate rows per page or fixed target. 
                        // Assuming landscape A4, roughly 15-20 rows fit well.
                        $minRows = 9;
                        $rowCount = count($items); // Roughly count items
                        $emptyRowsNeeded = max(0, $minRows - $rowCount);
                    @endphp

                    @for($i = 0; $i < $emptyRowsNeeded; $i++)
                        <tr class="data-row">
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            <td style="border: 1px solid #000;">&nbsp;</td>
                            {{-- Removed one td to match new column count (11) --}}
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>

        {{--
        @if(!($isEmail ?? false) && $isActiveStatus)
            <table style="width: 100%; border: 1px solid #000; border-top: none; page-break-inside: avoid;">
                <tr style="background-color: #ccc;">
                    <th style="border-right: 1px solid #000; width: 33%; height: 20px; font-size: 8pt; text-align: center; border-bottom: 1px solid #000;">PREPARED BY</th>
                    <th style="border-right: 1px solid #000; width: 33%; height: 20px; font-size: 8pt; text-align: center; border-bottom: 1px solid #000;">CHECKED BY</th>
                    <th style="width: 33%; height: 20px; font-size: 8pt; text-align: center; border-bottom: 1px solid #000;">APPROVED BY</th>
                </tr>
                <tr>
                    <td style="border-right: 1px solid #000; height: 80px;">&nbsp;</td>
                    <td style="border-right: 1px solid #000; height: 80px;">&nbsp;</td>
                    <td style="height: 80px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="border-right: 1px solid #000; text-align: center; padding-bottom: 5px; font-size: 8pt;">(...........................................)</td>
                    <td style="border-right: 1px solid #000; text-align: center; padding-bottom: 5px; font-size: 8pt;">(...........................................)</td>
                    <td style="text-align: center; padding-bottom: 5px; font-size: 8pt;">(...........................................)</td>
                </tr>
            </table>
        @endif
        --}}
    </div>

    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
