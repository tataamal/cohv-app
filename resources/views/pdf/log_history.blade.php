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
        .footer-content { width: 100%; display: table; }
        .footer-left { display: table-cell; text-align: left; width: 50%; font-style: italic; }
        .footer-right { display: table-cell; text-align: right; width: 50%; }
        .page-number:after { content: counter(page); }

        /* --- PAGE BREAK UTILITY --- */
        .page-break {
            page-break-after: always;
            height: 0;
            margin: 0;
            padding: 0;
        }

        /* --- TABLE GLOBAL --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            border: 1px solid #000; /* helps dompdf keep outer border consistent */
        }
        td, th {
            border: 1px solid #000;
            padding: 2px;
            vertical-align: middle;
        }

        /* DOMPDF pagination stability */
        thead { display: table-header-group; }
        tbody { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }

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

        .branding-table { border: none !important; }
        .branding-table td { border: none !important; padding: 0; }
        .logo-img { width: 60px; vertical-align: middle; padding-right: 15px !important; }

        .company-name {
            font-size: 18pt;
            font-weight: 900;
            margin: 0;
            text-transform: uppercase;
            line-height: 1;
        }
        .doc-title {
            font-size: 11pt;
            margin-top: 5px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* --- INFO BAR --- */
        .info-bar td {
            background-color: #f2f2f2;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px;
            border-bottom: 2px solid #000;
        }
        .info-val { color: #000; margin-left: 5px; font-weight: normal; }

        /* --- SUMMARY SECTION --- */
        .summary-header td {
            background-color: #e9ecef;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary-values td {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            padding: 6px;
            border-bottom: 2px solid #000;
        }
        .text-success { color: #008000; }
        .text-danger { color: #d00; }
        .text-warning { color: #E4A11B; }

        /* --- DATA TABLE --- */
        .data-header th {
            background-color: #ccc;
            color: #000;
            text-transform: uppercase;
            font-size: 8pt;
            font-weight: 900;
            height: 25px;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        .data-row td {
            min-height: 0;
            line-height: 1.15;
            vertical-align: top;
            word-wrap: break-word;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }

        .time-meta {
            font-size: 7pt;
            font-weight: normal;
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
    @php
        $itemsCheck = $report['items'] ?? [];

        $hasPriceData = false;
        if ($isEmail ?? false) {
            $hasPriceData = collect($itemsCheck)->contains(function($i) {
                return ($i['confirmed_price'] ?? 0) > 0 || ($i['failed_price'] ?? 0) > 0;
            });
        }

        // [REQ] Hide Price Cols
        $showPriceCols = false;

        // Custom Price Column logic (NETPR * Confirmed Qty)
        $totalConfirmedPrice = collect($itemsCheck)->reject(function($i) {
            $curr = trim($i['currency'] ?? '');
            return $curr === '-' || $curr === '';
        })->sum('confirmed_price');
        $showCustomPriceColumn = $totalConfirmedPrice > 0;
    @endphp

    {{-- 1) HEADER --}}
    <table style="border: none !important;">
        <tr>
            <td class="header-left" style="border-left: 2px solid #000; border-top: 2px solid #000;">
                <table class="branding-table" style="border: none !important;">
                    <tr>
                        <td class="logo-img">
                            <img src="{{ public_path('images/KMI.png') }}" style="max-height: 50px; width: auto;">
                        </td>
                        <td>
                            <div class="company-name">PT KAYU MEBEL INDONESIA</div>
                            @if($isEmail ?? false)
                                <div class="doc-title">{{ $report['report_title'] ?? 'DAILY REPORT' }} - {{ $report['nama_bagian'] }}</div>
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

            <td class="header-right" style="border-right: 2px solid #000; border-top: 2px solid #000; background-color: #fff; padding: 5px 10px !important; text-align: right;">
                @if(!empty($report['filterInfo']) && stripos($report['filterInfo'], 'all history') === false)
                    <div style="font-size: 11pt; font-weight: bold; color: rgba(0, 0, 0, 0.7);">
                        {{ $report['filterInfo'] }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- 2) INFO ROW (For Manual Print) --}}
    @if(!($isEmail ?? false))
        <table class="info-bar" style="border-left: 2px solid #000; border-right: 2px solid #000; border-top: none; border-bottom: none;">
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
                            $expDate = \Carbon\Carbon::createFromFormat('d-M-Y H:i', $expStr);
                            if ($expDate && $expDate->isPast()) {
                                $isExpired = true;
                            }
                        } catch (\Exception $e) {}

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

    {{-- 3) SUMMARY TABLE --}}
    @php
        $sum = $report['summary'] ?? [];
        // If the backend zeros out the raw totals for '-' currencies, this will naturally be false when all currencies are '-'.
        $showCustomPriceColumn = isset($sum['total_price_ok_raw']) && (($sum['total_price_ok_raw'] > 0) || ($sum['total_price_fail_raw'] > 0) || (($sum['total_price_assigned_raw'] ?? 0) > 0));
        
        // Use wc_kendala if exists, otherwise achievement_rate
        $judulRata = isset($sum['wc_kendala']) ? 'WC KENDALA / REMARK' : 'RATA-RATA KEBERHASILAN';
        $valRata = $sum['wc_kendala'] ?? ($sum['achievement_rate'] ?? '-');
    @endphp
    <table style="border-left: 2px solid #000; border-right: 2px solid #000; border-bottom: 2px solid #000; border-top: none;">
        <tr class="summary-header">
            <td width="20%">QTY TASK</td>
            <td width="20%">TERKONFIRMASI</td>
            <td width="20%">TIDAK TERKONFIRMASI</td>
            <td width="40%">{{ $judulRata }}</td>
        </tr>
        <tr class="summary-values" style="vertical-align: middle;">
            <td style="font-size: 11pt;">
                {{ number_format($sum['total_assigned'] ?? 0, 0) }}
                @if($showCustomPriceColumn)
                    <br><span style="font-size: 8pt; font-weight: normal; color: #555;">({{ $sum['total_price_assigned'] ?? '-' }})</span>
                @endif
            </td>
            <td class="text-success" style="font-size: 11pt;">
                {{ number_format($sum['total_confirmed'] ?? 0, 0) }}
                @if($showCustomPriceColumn)
                    <br><span style="font-size: 8pt; font-weight: normal; color: #555;">({{ $sum['total_price_ok'] ?? '-' }})</span>
                @endif
            </td>
            <td class="text-danger" style="font-size: 11pt;">
                {{ number_format($sum['total_failed'] ?? 0, 0) }}
                @if($showCustomPriceColumn)
                    <br><span style="font-size: 8pt; font-weight: normal; color: #555;">({{ $sum['total_price_fail'] ?? '-' }})</span>
                @endif
            </td>
            <td style="font-size: 7.5pt; font-weight: normal; word-wrap: break-word; white-space: normal; line-height: 1.3;">
                {{ $valRata }}
                @if(isset($sum['failure_rate']) && ($sum['total_failed'] ?? 0) > 0)
                    <br><br><strong>Presentase Kegagalan : {{ $sum['failure_rate'] }}</strong>
                @endif
            </td>
        </tr>
    </table>

    @php
        // --- PREPARATION ---
        $itemsArr = $report['items'] ?? [];
        $itemsCol = collect($itemsArr);
        $isReportMachining = $itemsCol->contains('is_machining', true);

        // Formatters
        $fmtTime = function($mins) {
            $totalSeconds = $mins * 60;
            $hrs = floor($totalSeconds / 3600);
            $mns = floor(($totalSeconds % 3600) / 60);
            $secs = round($totalSeconds % 60);
            $parts = [];
            if ($hrs > 0) $parts[] = $hrs . ' Jam';
            if ($mns > 0) $parts[] = $mns . ' Menit';
            if ($secs > 0 || empty($parts)) $parts[] = $secs . ' Detik';
            return implode(', ', $parts);
        };

        $fmtNum = function($val, $decimals = 2) {
            if (fmod((float)$val, 1) == 0.0) {
                return number_format($val, 0, ',', '.');
            }
            return number_format($val, $decimals, ',', '.');
        };

        // Group by NIK
        $grouped = $itemsCol->groupBy('nik');

        // --- PAGE CONFIG ---
        $maxLinesPerPage = 30;
        $currentLines = 0;

        // Helper to render Data Table Header
        $renderHeader = function() use ($showPriceCols, $isReportMachining, $showCustomPriceColumn) {
            $cols = '';
            if ($showPriceCols) {
                $cols .= '<th width="8%">PRICE OK</th><th width="8%">PRICE FAIL</th>';
            }
            $prog = '';
            if ($isReportMachining) {
                $prog = '<th width="10%">PROGRESS</th>';
            }

            return '
            <table style="table-layout: fixed; width: 100%; border-collapse: collapse; margin:0; border:1px solid #000;">
                <thead>
                    <tr class="data-header">
                        <th width="3%">NO</th>
                        <th width="8%">DOC</th>
                        <th width="8%">TIME REQ</th>
                        <th width="10%">WORKCENTER</th>
                        <th width="10%">SO-ITEM</th>
                        <th width="8%">PRO</th>
                        <th width="15%">MATERIAL</th>
                        <th width="4%">QTY</th>
                        <th width="4%">CONF</th>
                        ' . ($showCustomPriceColumn ? '<th width="8%">PRICE OK</th><th width="8%">PRICE FALSE</th>' : '') . '
                        ' . $cols . '
                        <th width="10%">REMARK</th>
                        ' . $prog . '
                    </tr>
                </thead>
                <tbody>';
        };
    @endphp

    {{-- OPEN FIRST DATA TABLE --}}
    {!! $renderHeader() !!}

    @foreach($grouped as $nik => $groupItems)
        @php
            // --- GROUP STATS ---
            $row0 = $groupItems->first() ?? [];
            $nikName = $row0['name'] ?? '-';
            $groupCount = $groupItems->count();
            $wiCount = $groupItems->pluck('doc_no')->filter()->unique()->count();
            $timeMetaStr = "{$groupCount} Task / {$wiCount} WI";

            $gAssigned = $groupItems->sum('assigned');
            $gConfirmed = $groupItems->sum('confirmed');
            $gUnconfirmed = $gAssigned - $gConfirmed;

            // For email format (keep same value/output as previous)
            $pctOk = ($gAssigned > 0) ? ($gConfirmed / $gAssigned) * 100 : 0;
            $pctFail = ($gAssigned > 0) ? ($gUnconfirmed / $gAssigned) * 100 : 0;

            $gTotalTimeMin = $groupItems->sum('raw_total_time');
            $gConfirmTimeMin = $groupItems->sum('raw_confirmed_time');
            $gTotalTimeFmt = $fmtTime($gTotalTimeMin);
            $jamKerjaStr = $fmtTime($gConfirmTimeMin);

            // Price calc (kept, though columns hidden)
            $gPriceOk = $groupItems->sum('confirmed_price');
            $gPriceFail = $groupItems->sum('failed_price');
            $gCurr = $row0['currency'] ?? 'IDR';
            $pfx = (strtoupper($gCurr) === 'USD') ? '$ ' : 'Rp ';
            $dec = (strtoupper($gCurr) === 'USD') ? 2 : 0;
            $fmtOk = $pfx . number_format($gPriceOk, $dec, ',', '.');
            $fmtFail = $pfx . number_format($gPriceFail, $dec, ',', '.');

            $isMachiningGroup = $groupItems->contains('is_machining', true);

            $gRemarkQty = $groupItems->sum('remark_qty');
            $progressNumerator = $gConfirmed + $gRemarkQty;
            $progressPct = ($gAssigned > 0) ? ($progressNumerator / $gAssigned) * 100 : 0;

            $nikColspan = $showPriceCols
                ? ($isReportMachining ? 13 : 12)
                : ($isReportMachining ? 11 : 10);
            
            if ($showCustomPriceColumn) {
                $nikColspan += 2;
            }

            $headerLines = 2;

            // Flag: show TIME REQ again after forced page-break inside group
            $forceShowTimeReq = false;

            // --- BREAK BEFORE GROUP HEADER IF NOT ENOUGH SPACE ---
            if (($currentLines + $headerLines + 1) > $maxLinesPerPage) {
                echo '</tbody></table><div class="page-break"></div>';
                echo $renderHeader();
                $currentLines = 0;
            }
        @endphp

        {{-- NIK HEADER --}}
        <tr class="nik-header">
            <td colspan="{{ $nikColspan }}" style="background-color:#f0f0f0; padding:5px; border:1px solid #000;">
                <strong>NIK {{ $nik }} {{ $nikName }}</strong>
                <span style="font-size: 8pt; margin-left: 10px;">
                    @if($isMachiningGroup)
                        (Qty Order: {{ $fmtNum($gAssigned) }} | Jam Kerja: {{ $jamKerjaStr }} |
                        Konfirmasi: {{ $fmtNum($gConfirmed) }}/{{ $fmtNum($gAssigned) }},
                        Progress Pengerjaan PRO: {{ $fmtNum($progressPct) }}%)
                    @else
                        @if($isEmail ?? false)
                            (Qty Order: {{ $fmtNum($gAssigned) }} | Jam Kerja: {{ $jamKerjaStr }} |
                            Konfirmasi: {{ $fmtNum($gConfirmed) }} ({{ $fmtNum($pctOk, 1) }}%),
                            Tidak Terkonfirmasi: {{ $fmtNum($gUnconfirmed) }} ({{ $fmtNum($pctFail, 1) }}%)
                            @if($showPriceCols) | OK : {{ $fmtOk }}, Fail : {{ $fmtFail }} @endif)
                        @else
                            (Qty Order: {{ $fmtNum($gAssigned) }} | Jam Kerja: {{ $jamKerjaStr }})
                        @endif
                    @endif
                </span>
            </td>
        </tr>
        @php $currentLines += $headerLines; $no = 1; @endphp

        {{-- LOOP ITEMS --}}
        @foreach($groupItems as $index => $row)
            @php
                $isFirstItem = ($index === 0);
                $isLastItem = ($index === ($groupCount - 1));

                // Estimate Item Height (line-based)
                $linesRemark = 0;
                $linesDesc = 0;
                $linesWc = 0;

                if(!empty($row['remark_details']) && is_array($row['remark_details'])) {
                    $linesRemark = count($row['remark_details']);
                } elseif (!empty($row['remark_text'])) {
                    $linesRemark = ceil(strlen($row['remark_text']) / 12);
                }

                if (!empty($row['description'])) {
                    $linesDesc = ceil(strlen($row['description']) / 20);
                }

                if (!empty($row['wc_description'])) {
                    $linesWc = 2;
                }

                $estHeight = max(1, $linesRemark, $linesDesc, $linesWc);
                if ($estHeight > 6) $estHeight = 6;

                $itemLines = $estHeight;

                $forcedBreak = (($currentLines + $itemLines) > $maxLinesPerPage);
            @endphp

            {{-- FORCED PAGE BREAK INSIDE GROUP --}}
            @if($forcedBreak)
                </tbody></table>
                <div class="page-break"></div>

                {!! $renderHeader() !!}

                {{-- REPEAT NIK HEADER ON NEW PAGE --}}
                <tr class="nik-header">
                    <td colspan="{{ $nikColspan }}" style="background-color:#f0f0f0; padding:5px; border:1px solid #000;">
                        <strong>NIK {{ $nik }} {{ $nikName }}</strong>
                        <span style="font-size: 8pt; margin-left: 10px;">
                            @if($isMachiningGroup)
                                (Qty Order: {{ $fmtNum($gAssigned) }} | Jam Kerja: {{ $jamKerjaStr }} |
                                Konfirmasi: {{ $fmtNum($gConfirmed) }}/{{ $fmtNum($gAssigned) }},
                                Progress Pengerjaan PRO: {{ $fmtNum($progressPct) }}%)
                            @else
                                @if($isEmail ?? false)
                                    (Qty Order: {{ $fmtNum($gAssigned) }} | Jam Kerja: {{ $jamKerjaStr }} |
                                    Konfirmasi: {{ $fmtNum($gConfirmed) }} ({{ $fmtNum($pctOk, 1) }}%),
                                    Tidak Terkonfirmasi: {{ $fmtNum($gUnconfirmed) }} ({{ $fmtNum($pctFail, 1) }}%)
                                    @if($showPriceCols) | OK : {{ $fmtOk }}, Fail : {{ $fmtFail }} @endif)
                                @else
                                    (Qty Order: {{ $fmtNum($gAssigned) }} | Jam Kerja: {{ $jamKerjaStr }})
                                @endif
                            @endif
                        </span>
                    </td>
                </tr>

                @php
                    $currentLines = $headerLines;  // because NIK header already rendered on the new page
                    $forceShowTimeReq = true;      // show TIME REQ again on first row of new page
                @endphp
            @endif

            @php
                // TIME COLUMN VISIBILITY & BORDER LOGIC
                $showTimeText = ($isFirstItem || $forceShowTimeReq);
                $borderTop = ($isFirstItem || $forceShowTimeReq) ? '1px solid #000' : 'none';

                // Predict next row for bottom border closure (TIME REQ cell)
                $nextItemLines = 1;
                if (isset($groupItems[$index+1])) {
                    $nextRow = $groupItems[$index+1];

                    $nlRemark = 0;
                    $nlDesc = 0;
                    $nlWc = 0;

                    if(!empty($nextRow['remark_details']) && is_array($nextRow['remark_details'])) {
                        $nlRemark = count($nextRow['remark_details']);
                    } elseif (!empty($nextRow['remark_text'])) {
                        $nlRemark = ceil(strlen($nextRow['remark_text']) / 12);
                    }

                    if (!empty($nextRow['description'])) {
                        $nlDesc = ceil(strlen($nextRow['description']) / 20);
                    }

                    if (!empty($nextRow['wc_description'])) {
                        $nlWc = 2;
                    }

                    $nextItemLines = max(1, $nlRemark, $nlDesc, $nlWc);
                    if ($nextItemLines > 6) $nextItemLines = 6;
                }

                $willBreakNext = (($currentLines + $itemLines + $nextItemLines) > $maxLinesPerPage);
                $borderBottom = ($isLastItem || $willBreakNext) ? '1px solid #000' : 'none';
            @endphp

            <tr class="data-row">
                {{-- NO --}}
                <td class="text-center">{{ $no++ }}</td>

                {{-- DOC --}}
                <td class="text-center fw-bold">
                    {{ $row['doc_no'] ?? '-' }}
                    @if(str_contains($report['report_title'] ?? '', 'WEEKLY'))
                        <br><span style="font-size: 7pt; font-weight: normal;">{{ $row['doc_date'] ?? '' }}</span>
                    @endif
                </td>

                {{-- TIME REQ --}}
                <td class="text-center fw-bold"
                    style="background-color:#fff; width:8%;
                        border-left: 1px solid #000;
                        border-right: 1px solid #000;
                        border-top: {{ $borderTop }};
                        border-bottom: {{ $borderBottom }};">
                    @if($showTimeText)
                        {{ $gTotalTimeFmt }}
                        <br>
                        <span class="time-meta">({{ $timeMetaStr }})</span>
                    @else
                        &nbsp;
                    @endif
                </td>

                {{-- WORKCENTER --}}
                <td class="text-center">
                    <strong>{{ $row['workcenter'] ?? '-' }}</strong><br>
                    {{ $row['wc_description'] ?? '-' }}
                </td>

                {{-- SO-ITEM --}}
                <td class="text-center">{{ $row['so_item'] ?? '-' }}</td>

                {{-- PRO --}}
                <td class="text-center fw-bold">
                    {{ $row['aufnr'] ?? '-' }}
                    @if(!empty($row['vornr']))
                        <br><span style="font-weight: normal; font-style: italic; font-size: 7pt;">({{ ltrim($row['vornr'], '0') }})</span>
                    @endif
                </td>

                {{-- MATERIAL --}}
                <td class="text-center">
                    <strong>{{ $row['material'] ?? '-' }}</strong><br>
                    {{ $row['description'] ?? '-' }}
                </td>

                {{-- QTY --}}
                <td class="text-center fw-bold">{{ floatval($row['assigned'] ?? 0) }}</td>

                {{-- CONF --}}
                <td class="text-center fw-bold text-success">{{ floatval($row['confirmed'] ?? 0) }}</td>

                @if($showCustomPriceColumn)
                    <td class="text-center text-success" style="font-size: 7pt;">
                        @if(($row['confirmed_price'] ?? 0) > 0 && trim($row['currency'] ?? '') !== '-' && trim($row['currency'] ?? '') !== '')
                            {{ $row['price_ok_fmt'] ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center text-danger" style="font-size: 7pt;">
                        @if(($row['failed_price'] ?? 0) > 0 && trim($row['currency'] ?? '') !== '-' && trim($row['currency'] ?? '') !== '')
                            {{ $row['price_fail_fmt'] ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                @endif

                {{-- PRICE COLS (hidden by request) --}}
                @if($showPriceCols)
                    <td class="text-center text-success" style="font-size: 7pt;">{{ $row['price_ok_fmt'] ?? '-' }}</td>
                    <td class="text-center text-danger" style="font-size: 7pt;">{{ $row['price_fail_fmt'] ?? '-' }}</td>
                @endif

                {{-- REMARK --}}
                <td class="text-left" style="font-size: 7pt;">
                    @if(!empty($row['remark_details']) && is_array($row['remark_details']) && count($row['remark_details']) > 0)
                        <ul style="padding-left: 15px; margin: 0; text-align: left;">
                            @foreach($row['remark_details'] as $rem)
                                <li>Qty : {{ floatval($rem['qty'] ?? 0) }}, {{ $rem['remark'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @elseif(floatval($row['remark_qty'] ?? 0) > 0)
                        <div style="text-align: center;">
                            <strong>Qty: {{ floatval($row['remark_qty'] ?? 0) }}</strong><br>
                            {{ $row['remark_text'] ?? '-' }}
                        </div>
                    @elseif(!empty($row['remark_text']) && ($row['remark_text'] ?? '-') !== '-')
                        <div style="text-align: center;">{{ $row['remark_text'] }}</div>
                    @else
                        <div style="text-align: center;">-</div>
                    @endif
                </td>

                {{-- PROGRESS --}}
                @if($isReportMachining)
                    <td class="text-center">
                        @if($row['is_machining'] ?? false)
                            <span style="font-size: 7pt;">({{ $fmtNum($row['item_progress_numerator'] ?? 0) }}/{{ $fmtNum($row['assigned'] ?? 0) }})</span>
                            <strong>{{ $fmtNum($row['item_progress_pct'] ?? 0, 0) }}%</strong>
                            @if(($row['item_progress_pct'] ?? 0) >= 100 || (($row['status'] ?? 'ACTIVE') !== 'ACTIVE'))
                                <br><span style="font-size: 6pt; font-style: italic;">{{ $row['status'] ?? '' }}</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                @endif
            </tr>

            @php
                // reset the one-time flag (so only the first row after page-break prints TIME REQ again)
                if ($forceShowTimeReq) $forceShowTimeReq = false;

                // advance "virtual lines"
                $currentLines += $itemLines;
            @endphp
        @endforeach
    @endforeach

    </tbody>
    </table>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
