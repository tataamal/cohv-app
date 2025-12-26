<!DOCTYPE html>
<html>
<head>
    <title>Laporan Goods Receipt</title>
    <style>
        /* === RESET & BASE === */
        @page { margin: 1cm; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 10pt; 
            color: #000; 
            margin: 0; 
            padding: 0; 
        }
        
        /* === LAYOUT UTILS === */
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
            min-height: 90px;
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
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
            margin-bottom: 2px;
        }
        .report-meta {
            font-size: 8pt;
            color: #555;
            margin-top: 2px;
        }
        
        /* Style untuk Info Filter di Header */
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
        .summary-label {
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #444;
        }
        .summary-value {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2px;
        }

        /* === DATA TABLE === */
        .data-table {
            border: 1px solid #000;
            width: 100%;
        }
        .data-table th {
            background-color: #e0e0e0;
            color: #000;
            padding: 8px 5px;
            font-size: 9pt;
            text-transform: uppercase;
            border: 1px solid #000;
            font-weight: bold;
        }
        .data-table td {
            padding: 5px;
            border: 1px solid #000;
            font-size: 9pt;
            vertical-align: middle;
        }

        /* === GROUP HEADER === */
        .group-header td {
            background-color: #f2f2f2;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 8px;
            padding-bottom: 8px;
            font-size: 9pt;
        }

        /* [BARU] Style untuk Subtotal Value di Header Workcenter */
        .wc-subtotal {
            float: right;
            font-size: 8pt;
            font-weight: bold;
            color: #333;
        }
        .wc-subtotal span {
            margin-left: 10px;
            padding: 2px 6px;
            background-color: #fff;
            border: 1px solid #999;
            border-radius: 3px;
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
            <div class="report-title">LAPORAN GOODS RECEIPT</div>
            
            <div class="filter-info">
                @php
                    $f = $summary['filter_info'] ?? [];
                    $hasDate = !empty($f['date_start']) && !empty($f['date_end']);
                @endphp

                @if($hasDate)
                    <span class="bold">Periode:</span> 
                    {{ \Carbon\Carbon::parse($f['date_start'])->format('d/m/Y') }} 
                    s/d 
                    {{ \Carbon\Carbon::parse($f['date_end'])->format('d/m/Y') }}
                @else
                    <span class="bold">Periode:</span> Semua Data Terpilih
                @endif

                @if(!empty($f['mrp']))
                    &nbsp;&nbsp;|&nbsp;&nbsp; <span class="bold">MRP:</span> {{ $f['mrp'] }}
                @endif

                @if(!empty($f['wc']))
                    &nbsp;&nbsp;|&nbsp;&nbsp; <span class="bold">Workcenter:</span> {{ $f['wc'] }}
                @endif
            </div>

            <div class="report-meta">
                Dicetak: {{ $summary['print_date'] }} WIB &nbsp;|&nbsp; User: {{ $summary['user'] }}
            </div>
        </div>
    </div>

    {{-- SUMMARY STATS --}}
    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td width="25%">
                    <div class="summary-label">Total Transaksi</div>
                    <div class="summary-value">{{ number_format($summary['total_items'], 0, ',', '.') }} Items</div>
                </td>
                <td width="25%">
                    <div class="summary-label">Total Quantity</div>
                    <div class="summary-value">{{ number_format($summary['total_qty'], 0, ',', '.') }} Units</div>
                </td>
                <td width="50%" class="text-right">
                    <div class="summary-label">Total Estimasi Value</div>
                    @foreach($summary['total_values'] as $currency => $val)
                        <div class="summary-value">
                            {{ $currency }} {{ number_format($val, 2, ',', '.') }}
                        </div>
                    @endforeach
                </td>
            </tr>
        </table>
    </div>

    {{-- TABEL DATA UTAMA --}}
    <table class="data-table">
        <thead>
            <tr>
                <th width="12%" class="text-center">PRO</th>
                <th width="35%">Material Description</th>
                <th width="15%" class="text-center">SO / Item</th>
                <th width="8%" class="text-center">MRP</th>
                <th width="12%" class="text-center">Posting Date</th>
                <th width="8%" class="text-center">Qty PRO</th>
                <th width="10%" class="text-center">Qty GR</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $currentWc = null; 
            @endphp

            @foreach($data as $item)
                {{-- GROUPING HEADER BERDASARKAN WORKCENTER --}}
                @if($currentWc !== $item->ARBPL)
                    @php 
                        $currentWc = $item->ARBPL; 
                        // Hitung subtotal per group untuk display header
                        $wcItems = $data->where('ARBPL', $currentWc);
                        $wcTotalQty = $wcItems->sum('MENGE');

                        // [BARU] Ambil Subtotal Value per Workcenter (yang dikirim dari Controller)
                        $wcKey = $currentWc ?? 'UNASSIGNED';
                        $wcValues = $summary['wc_values'][$wcKey] ?? [];
                    @endphp
                    <tr class="group-header">
                        <td colspan="7">
                            {{-- Info Kiri: Nama WC dan Jumlah Item/Qty --}}
                            WORKCENTER: {{ $currentWc ?? 'UNASSIGNED' }} 
                            <span style="font-weight:bold; font-size: 8pt; margin-left: 10px; color: #000;">
                                ( {{ $wcItems->count() }} Transaksi | Total Qty: {{ number_format($wcTotalQty, 0, ',', '.') }} )
                            </span>

                            {{-- [BARU] Info Kanan: Total Value per Currency --}}
                            <div class="wc-subtotal">
                                @foreach($wcValues as $curr => $val)
                                    <span>Total: {{ $curr }} {{ number_format($val, 2, ',', '.') }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endif

                {{-- BARIS DATA --}}
                <tr>
                    <td class="text-center">{{ $item->AUFNR }}</td>
                    <td>{{ $item->MAKTX }}</td>
                    <td class="text-center">{{ $item->MAT_KDAUF ?? '-' }} / {{ intval($item->MAT_KDPOS) }}</td>
                    <td class="text-center">{{ $item->DISPO }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->BUDAT_MKPF)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ number_format($item->PSMNG, 0, ',', '.') }}</td>
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
                    {{-- Placeholder halaman akan diganti script PHP dibawah --}}
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