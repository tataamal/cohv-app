<!DOCTYPE html>
<html>
<head>
    <title>Laporan Goods Receipt (Set)</title>
    <style>
        /* === RESET & BASE === */
        @page { 
            size: landscape; 
            margin: 1cm; 
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9pt; 
            color: #000; 
            margin: 0; 
            padding: 0; 
        }
        
        /* ... (Existing styles if fine, but adjusted for landscape) ... */
        table { width: 100%; border-collapse: collapse; border-spacing: 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* === HEADER / KOP SURAT === */
        .header-container {
            position: relative;
            width: 100%;
            height: auto; 
            min-height: 80px;
            margin-bottom: 20px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
        }
        .company-info {
            text-align: center;
            padding-left: 90px; 
            padding-right: 90px;
        }
        .company-name { font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .report-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-top: 5px; margin-bottom: 2px; }
        .report-meta { font-size: 8pt; color: #555; margin-top: 2px; }
        
        .filter-info {
            margin-top: 5px;
            font-size: 9pt;
            border: 1px dashed #999;
            display: inline-block;
            padding: 3px 10px;
            background-color: #f9f9f9;
        }

        /* === SUMMARY BOX === */
        .summary-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 20px;
        }
        .summary-label { font-size: 8pt; text-transform: uppercase; font-weight: bold; color: #444; }
        .summary-value { font-size: 11pt; font-weight: bold; margin-top: 2px; }

        /* === DATA TABLE === */
        .data-table { border: 1px solid #000; width: 100%; }
        .data-table th {
            background-color: #e0e0e0; color: #000; padding: 8px 5px; font-size: 9pt;
            text-transform: uppercase; border: 1px solid #000; font-weight: bold;
        }
        .data-table td { padding: 5px; border: 1px solid #000; font-size: 9pt; vertical-align: middle; }

        /* === GROUP HEADER === */
        .group-header td {
            background-color: #f2f2f2; /* Light Grey as requested */
            color: #333;
            font-weight: bold;
            border-top: 2px solid #000;
            padding: 8px 10px;
            font-size: 10pt;
        }
        .qty-set-badge {
            float: right;
            border: 1px solid #333;
            background: #fff;
            color: #000;
            padding: 2px 8px;
            border-radius: 4px;
            box-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        /* === FOOTER === */
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            font-size: 8pt;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="logo">
            <img src="{{ public_path('images/KMI.png') }}" style="width: 100%; max-height: 70px; object-fit: contain;">
        </div>
        
        <div class="company-info">
            <h1 class="company-name">PT. Kayu Mebel Indonesia</h1>
            <div class="report-title">LAPORAN SET HARIAN</div>
            
            <div class="filter-info">
                @php
                    $f = $summary['filter_info'] ?? [];
                @endphp
                <span class="bold">Tanggal:</span> {{ \Carbon\Carbon::parse($f['date_start'])->format('d/m/Y') }} 
                @if(!empty($f['plant_code']))
                    &nbsp;&nbsp;|&nbsp;&nbsp; <span class="bold">Bagian/Plant:</span> {{ $f['nama_bagian'] }}
                @endif
            </div>

            <div class="report-meta">
                Dicetak: {{ $summary['print_date'] }} WIB &nbsp;|&nbsp; User: {{ $summary['user'] }}
            </div>
        </div>
    </div>

    {{-- TABEL DATA UTAMA --}}
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="15%" class="text-center">SO / Item</th>
                <th width="12%">Material Code</th>
                <th width="43%">Material Description</th>
                <th width="10%" class="text-center">Workcenter</th>
                <th width="10%" class="text-center">QTY SET</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $currentGroup = null; 
                $no = 1;
            @endphp
            
            @foreach($data as $item)
                {{-- GROUPING HEADER BERDASARKAN AUFNR2 --}}
                @if($currentGroup !== $item->AUFNR2)
                    @php 
                        $currentGroup = $item->AUFNR2; 
                        // Qty Set stored in item property from controller
                        $qtySet = $item->MIN_MENGE_SET;
                    @endphp
                    <tr class="group-header">
                        <td colspan="6">
                            <span class="bold">REF PRO (SET): {{ $currentGroup }} {{ !empty($item->MAKTX2) ? ' - ' . $item->MAKTX2 : '' }}</span>
                            
                            <div class="qty-set-badge">
                                TOTAL QTY SET: <span style="font-size:11pt; font-weight:bold;">{{ number_format($qtySet, 0, ',', '.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center">
                        @php
                            $kdaufSet = $item->MAT_KDAUF ?? ($item->KDAUF ?? '');
                            $isMakeStockSet = (strcasecmp($kdaufSet, 'Make Stock') === 0);
                            $soItemSet = $isMakeStockSet ? $kdaufSet : ($kdaufSet . ($item->MAT_KDPOS ? ' / ' . intval($item->MAT_KDPOS) : ''));
                        @endphp
                        {{ $soItemSet }}
                    </td>
                    <td class="text-center">{{ ltrim($item->MATNR, '0') }}</td>
                    <td>{{ $item->MAKTX }}</td>
                    <td class="text-center">{{ $item->ARBPL }}</td>
                    <td class="text-center bold">{{ number_format($item->MENGE, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td class="text-left" width="50%">
                    <i>PT. Kayu Mebel Indonesia - Sistem Pelaporan Produksi</i>
                </td>
                <td class="text-right" width="50%">
                    {{-- Placeholder halaman --}}
                </td>
            </tr>
        </table>
    </div>

    {{-- Script Page Numbering DomPDF --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pdf->get_width() - $width - 30;
            $y = $pdf->get_height() - 25;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
