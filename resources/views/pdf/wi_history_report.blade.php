<!DOCTYPE html>
<html>
<head>
    <title>WI History Log</title>
    <style>
        /* --- PAGE SETUP --- */
        @page { size: A4 landscape; margin: 5mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; margin: 0; padding: 0; }

        /* --- FRAMEWORK --- */
        .container-frame { width: 100%; border: 2px solid #000; }
        
        /* --- TABLE STYLING --- */
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 4px; vertical-align: middle; }

        /* --- HEADER SECTION --- */
        .header-left {
            width: 70%; padding: 5px 10px !important;
            border-bottom: 2px solid #000; border-right: none !important;
        }
        .header-right {
            width: 30%; padding: 0 !important; vertical-align: middle; text-align: center;
            border-bottom: 2px solid #000; border-left: none !important; background-color: #fff;
        }
        .branding-table td { border: none !important; padding: 0; }
        .logo-img { width: 60px; vertical-align: middle; padding-right: 15px !important; }
        .company-name { font-size: 18pt; font-weight: 900; margin: 0; text-transform: uppercase; line-height: 1; }
        .doc-title { font-size: 11pt; margin-top: 5px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase; }
        
        /* Judul Laporan di Kanan */
        .report-type-display {
            font-size: 20pt; font-weight: 900; color: #008000; letter-spacing: 1px; text-transform: uppercase;
        }

        /* --- INFO BAR --- */
        .info-bar td {
            background-color: #f2f2f2; font-size: 8pt; font-weight: bold;
            text-transform: uppercase; padding: 6px; border-bottom: 2px solid #000;
        }
        .info-val { color: #000; margin-left: 5px; font-weight: normal; }
        .expired-alert { color: #d00; font-weight: 900; }

        /* --- DATA TABLE --- */
        .data-header th {
            background-color: #ccc; color: #000; text-transform: uppercase;
            font-size: 8pt; font-weight: 900; height: 25px; border-bottom: 2px solid #000;
        }
        .data-row td { font-size: 9pt; height: 25px; vertical-align: top; word-wrap: break-word; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #d00; }
        .text-success { color: #198754; }

        /* --- FOOTER --- */
        .footer-cell { height: 80px; vertical-align: top !important; padding: 0 !important; border-top: 2px solid #000; }
        .sig-title {
            font-size: 7pt; font-weight: bold; background-color: #dfdfdf;
            border-bottom: 1px solid #000; padding: 3px; text-align: center;
        }
        .sig-content { padding-top: 40px; text-align: center; font-size: 9pt; }
    </style>
</head>
<body>

<div class="container-frame">
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
                            <div class="doc-title">WORK INSTRUCTION HISTORY LOG</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-right">
                <div class="report-type-display">HISTORY LOG</div>
            </td>
        </tr>
    </table>

    <table>
        <tr class="info-bar">
            <td width="25%">DEPARTMENT: <span class="info-val fw-bold">{{ strtoupper($department) }}</span></td>
            <td width="25%">USER: <span class="info-val fw-bold">{{ strtoupper($printedBy) }}</span></td>
            <td width="25%">FILTER DATE: <span class="info-val">{{ $filterDate }}</span></td>
            <td width="25%" style="border-right: none;">TOTAL: <span class="info-val fw-bold">{{ count($reportRows) }} Records</span></td>
        </tr>
    </table>

    <table style="table-layout: fixed;">
        <thead>
            <tr class="data-header">
                <th width="3%">NO</th>
                <th width="9%">DOC NO</th>
                <th width="6%">WC</th>
                <th width="9%">SO-ITEM</th>
                <th width="8%">PRO</th>
                <th width="8%">MATERIAL</th>
                <th width="16%">DESCRIPTION</th>
                <th width="5%">QTY WI</th>
                <th width="8%">NIK</th>
                <th width="16%">NAME</th>
                <th width="6%">Time Required</th>
                <th width="6%">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $minRows = 14; 
                $itemCount = count($reportRows);
                $rowsToFill = max(0, $minRows - $itemCount);
            @endphp

            @foreach($reportRows as $index => $row)
            <tr class="data-row">
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="fw-bold">{{ $row['doc_no'] }}</td>
                <td class="text-center">{{ $row['workcenter'] }}</td>
                <td class="text-center">{{ $row['so_item'] }}</td>
                <td class="text-center">{{ $row['aufnr'] }}</td>
                <td class="text-center">{{ $row['material'] }}</td>
                <td>{{ $row['description'] }}</td>
                <td class="text-center fw-bold">{{ $row['qty_wi'] }}</td>
                <td class="text-center">{{ $row['nik'] ?? '-' }}</td>
                <td>{{ $row['employee_name'] ?? ($row['name'] ?? '-') }}</td>
                <td class="text-center">{{ $row['takt_time'] }}</td>
                <td class="text-center">
                    <span class="{{ $row['status'] == 'Expired' ? 'text-danger' : 'text-success' }}">
                        {{ strtoupper($row['status']) }}
                    </span>
                </td>
            </tr>
            @endforeach

            {{-- AUTO FILL ROWS --}}
            @for($i = 0; $i < $rowsToFill; $i++)
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
            </tr>
            @endfor
        </tbody>
    </table>
</div>

</body>
</html>