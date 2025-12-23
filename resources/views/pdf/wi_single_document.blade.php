<!DOCTYPE html>
<html>
<head>
    <title>Cetak WI</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm; 
            margin-bottom: 15mm; /* Space for footer */
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
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
        /* --- FRAMEWORK --- */
        .container-frame {
            width: 100%;
            border: 2px solid #000;
            min-height: 98%; /* Use min-height to allow expansion */
            /* height: 98%; REMOVED fixed height to prevent forcing breaks */
            display: block;
        }


        /* --- PAGE BREAK UTILITY --- */
        .page-break {
            page-break-after: always;
        }
        
        .avoid-break {
            page-break-inside: avoid;
        }

        /* --- TABLE STYLING --- */
        table { width: 100%; border-collapse: collapse; }
        
        td, th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
        }

        /* ... (Keep existing Header/Info styles if match/implied, but skipping to save tokens if unchanged. Wait, I must replacing contiguous block.) ... */
        /* Re-including Header/Info styles for safety since I'm replacing a large block? No, I can target specific blocks. */
        
        /* Let's target the logic block specifically for minRows and the signature wrapper. */
        /* BUT wait, I need to update CSS first. I will do this in chunks. Check lines. */


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
        
        .wi-code-display {
            font-size: 22pt; font-weight: 900;
            color: #008000; letter-spacing: 1px;
        }

        /* --- INFO BAR --- */
        .info-bar td {
            background-color: #f2f2f2;
            font-size: 8pt; font-weight: bold;
            text-transform: uppercase; padding: 6px;
            border-bottom: 2px solid #000;
        }
        .info-val { color: #000; margin-left: 5px; font-weight: normal; }
        .expired-alert { color: #d00; font-weight: 900; }

        /* --- DATA TABLE --- */
        .data-header th {
            background-color: #ccc; color: #000;
            text-transform: uppercase; font-size: 8pt;
            font-weight: 900; height: 25px;
            border-bottom: 2px solid #000;
        }
        .data-row td {
            font-size: 9pt; height: 25px;
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

{{-- LOOPING DOKUMEN (Agar setiap dokumen punya halaman sendiri) --}}
@foreach($documents as $doc)

    {{-- Ambil Items untuk dokumen ini --}}
    @php
        $items = is_array($doc->payload_data) ? $doc->payload_data : json_decode($doc->payload_data, true);
        if(!$items) $items = [];
    @endphp

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
                                <div class="doc-title">WORK INSTRUCTION SHEET</div>
                            </td>
                        </tr>
                    </table>
                </td>
                {{-- KODE WI DINAMIS SESUAI LOOP SAAT INI --}}
                <td class="header-right">
                    <div class="wi-code-display">{{ $doc->wi_document_code }}</div>
                </td>
            </tr>
        </table>

        {{-- 2. INFO BAR --}}
        <table>
            <tr class="info-bar">
                <td width="25%">DEPARTMENT: <span class="info-val fw-bold">{{ strtoupper($doc->department ?? $department) }}</span></td>
                @if(isset($isEmail) && $isEmail && isset($doc->total_price_formatted))
                    <td width="25%">TOTAL PRICE: <span class="info-val fw-bold">{{ $doc->total_price_formatted }}</span></td>
                @else
                    <td width="25%">STATUS: <span class="info-val fw-bold">ACTIVE</span></td>
                @endif
                <td width="25%">DATE: <span class="info-val">{{ $doc->document_date->format('d-M-Y') }}</span></td>
                <td width="25%" style="border-right: none;">EXPIRED: <span class="info-val expired-alert">{{ $doc->expired_at->format('d-M-Y H:i') }}</span></td>
            </tr>
        </table>

        {{-- 3. DATA TABLE --}}
        <table style="table-layout: fixed; page-break-after: avoid;">
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
                    <th width="6%">Time Req</th>
                    <th width="7%">NIK</th>
                    <th width="10%">NAME</th>
                    <th width="8%">REMARK</th>
                    <th width="9%">PRICE</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $minRows = 12;  // Reduced from 14 to prevent signature pushing
                    $itemCount = count($items);
                    $rowsToFill = max(0, $minRows - $itemCount);
                @endphp

                @foreach($items as $index => $item)
                @php
                    // --- LOGIC PER ITEM ---
                    $wc = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                    
                    $kdauf = $item['kdauf'] ?? '';
                    $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                    $soItem = $kdauf . '-' . $kdpos;

                    $matnr = $item['material_number'] ?? '';
                    if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }

                    $qtyWi = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;

                    $baseTime = isset($item['vgw01']) ? floatval($item['vgw01']) : 0;
                    $unit = isset($item['vge01']) ? strtoupper($item['vge01']) : '';
                    
                    $totalTime = $baseTime * $qtyWi;

                    if ($unit == 'S' || $unit == 'SEC') {
                        $finalTime = $totalTime / 60; 
                        $finalUnit = 'Menit';
                    } else {
                        $finalTime = $totalTime;
                        $finalUnit = $unit; 
                    }
                    
                    $taktDisplay = (fmod($finalTime, 1) !== 0.00) ? number_format($finalTime, 2) : number_format($finalTime, 0);

                    // NIK & Name
                    $nik = $item['nik'] ?? '-';
                    $empName = $item['employee_name'] ?? ($item['name'] ?? '-');
                    
                    // Price
                    $priceDisp = $item['price_sourced'] ?? '-';
                @endphp
                <tr class="data-row">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center fw-bold">{{ $wc }}</td>
                    <td class="text-center">{{ $soItem }}</td>
                    <td class="text-center">{{ $item['aufnr'] ?? '-' }}</td>
                    <td class="text-center">{{ $matnr }}</td>
                    <td>{{ $item['material_desc'] ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $qtyWi }}</td>
                    <td class="text-center fw-bold">0</td> {{-- Default 0 as requested --}}
                    <td class="text-center fw-bold">{{ $taktDisplay }} {{ $finalUnit }}</td>
                    <td class="text-center">{{ $nik }}</td>
                    <td>{{ $empName }}</td>
                    <td class="text-center">-</td> {{-- Remark Default - --}}
                    <td class="text-center">{{ $priceDisp }}</td>
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
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 4. FOOTER --}}
        <div class="avoid-break">
            <table>
                <tr>
                    <td class="footer-cell" width="33%">
                        <div class="sig-title">PREPARED BY</div>
                        <div class="sig-content"><br>( .............................................. )</div>
                    </td>
                    <td class="footer-cell" width="33%">
                        <div class="sig-title">CHECKED BY</div>
                        <div class="sig-content"><br>( .............................................. )</div>
                    </td>
                    <td class="footer-cell" width="34%" style="border-right: none;">
                        <div class="sig-title">APPROVED BY</div>
                        <div class="sig-content"><br>( .............................................. )</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- PAGE BREAK JIKA BUKAN HALAMAN TERAKHIR --}}
    @if(!$loop->last)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
