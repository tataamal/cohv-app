@foreach ($tData1 as $item)
    @php
        $soItem = ltrim($item->KDAUF, '0') . ' - ' . ltrim($item->KDPOS, '0');
        $matnr = ctype_digit($item->MATNR) ? ltrim($item->MATNR, '0') : $item->MATNR;
        $sisaQty = $item->real_sisa_qty ?? $item->MGVRG2 - $item->LMNGA;
    @endphp

    <tr class="pro-item draggable-item" 
        data-id="{{ $item->id }}"
        data-aufnr="{{ $item->AUFNR }}"
        data-pwwrk="{{ $item->PWWRK ?? $item->WERKS ?? '' }}"
        data-vgw01="{{ number_format($item->VGW01, 2, '.', '') }}"
        data-vge01="{{ $item->VGE01 }}" 
        data-psmng="{{ $item->PSMNG }}"
        data-sisa-qty="{{ $sisaQty }}" 
        data-conf-opr="{{ $item->LMNGA }}"
        data-qty-opr="{{ $item->MGVRG2 }}" 
        data-assigned-qty="0"
        data-employee-nik="" 
        data-employee-name="" 
        data-child-wc=""
        data-assigned-child-wcs='[]' 
        data-arbpl="{{ $item->ARBPL }}"
        data-matnr="{{ $item->MATNR }}" 
        data-maktx="{{ $item->MAKTX }}"
        data-meins="{{ $item->MEINS }}" 
        data-vornr="{{ $item->VORNR }}"
        data-kdauf="{{ $item->KDAUF }}" 
        data-kdpos="{{ $item->KDPOS }}"
        data-dispo="{{ $item->DISPO }}" 
        data-steus="{{ $item->STEUS }}"
        data-sssld="{{ $item->SSSLD }}" 
        data-ssavd="{{ $item->SSAVD }}"
        data-kapaz="{{ $item->KAPAZ }}"
        data-name1="{{ $item->NAME1 }}"
        data-netpr="{{ $item->NETPR }}"
        data-waerk="{{ $item->WAERK }}"> 

        {{-- 1. Checkbox --}}
        <td class="text-center table-col ps-3">
            <input class="form-check-input row-checkbox pointer" type="checkbox">
        </td>

        {{-- 2. PRO (Drag Visual Wrapper) --}}
        <td class="table-col preview-container ps-3">
            <div class="original-content">
                <span class="fw-bold text-primary">{{ $item->AUFNR }}</span>
            </div>
            {{-- Custom Drag Preview (Hidden by default, shown by JS on drag) --}}
            <div class="drag-preview-icon">
                <div class="icon-box bg-primary text-white rounded shadow-sm">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">{{ $item->AUFNR }}</div>
                    <div class="text-muted text-xs">Assigning to Workcenter...</div>
                </div>
                <div class="ms-3 ps-3 border-start">
                    <span class="badge bg-light text-dark border">{{ $item->STEUS }}</span>
                </div>
            </div>
        </td>

        {{-- 3. Info Columns --}}
        <td class="table-col text-muted">{{ $soItem }}</td>
        <td class="table-col">
            <div class="d-flex flex-column">
                <span class="fw-bold text-dark small">{{ $matnr }}</span>
                <span class="text-secondary text-truncate small" style="max-width: 200px;">{{ $item->MAKTX }}</span>
            </div>
        </td>
        <td class="text-center table-col"><span class="badge bg-light text-dark border">{{ $item->ARBPL }}</span></td>
        <td class="text-center table-col"><span class="badge bg-light text-secondary border">{{ $item->STEUS }}</span></td>
        @php
            $showUnit = $item->MEINS;
            if ($showUnit == 'ST') { $showUnit = 'PC'; }
            $decimals = in_array($item->MEINS, ['ST', 'SET']) ? 0 : 1;
        @endphp
        <td class="text-center table-col fw-bold text-dark">{{ number_format($item->MGVRG2, $decimals, ',', '.') }} {{ $showUnit }}</td>
        <td class="text-center table-col text-muted">{{ number_format($item->LMNGA, $decimals, ',', '.') }} {{ $showUnit }}</td>
        
        {{-- QTY SISA (Qty Opt - Qty WI) --}}
        @php
            $qtySisaDisplay = $item->MGVRG2 - $item->qty_wi;
            if ($qtySisaDisplay < 0) $qtySisaDisplay = 0;
        @endphp
        <td class="text-center table-col text-primary fw-bold">{{ number_format($qtySisaDisplay, $decimals, ',', '.') }} {{ $showUnit }}</td>
        
        @php
            $taktTime = $item->VGW01 * $item->MGVRG2;
            if ($item->VGE01 == 'S') {
                $taktTime = $taktTime / 60;
            }
            // Format: Remove trailing zeros (e.g. 5.00 -> 5, 5.50 -> 5.5)
            $taktTimeDisplay = (float) number_format($taktTime, 2, '.', '');
            $taktTimeDisplay = str_replace('.', ',', (string) $taktTimeDisplay);
        @endphp
        <td class="text-center table-col">{{ $taktTimeDisplay }} Min</td>
        <td class="card-view-content" colspan="9">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold text-primary small">{{ $item->AUFNR }}</span>
                <button type="button" class="btn btn-sm btn-icon btn-ghost-danger rounded-circle p-1" 
                        onclick="handleReturnToTable(this.closest('.pro-item-card'), this.closest('.wc-drop-zone'))" title="Kembalikan ke Table">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
                     <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-1">
                 <span class="badge bg-light text-dark border p-1 assigned-qty-badge" style="font-size: 0.7rem;">Qty: -</span>
                 <span class="badge bg-success bg-opacity-10 text-success p-1 border border-success border-opacity-25 child-wc-display" style="font-size: 0.7rem;"></span>
            </div>
            <!-- Hidden Elements for Logic -->
             <span class="d-none employee-name-text">-</span>
        </td>
    </tr>
@endforeach
