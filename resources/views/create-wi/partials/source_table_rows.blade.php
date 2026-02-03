@foreach ($tData1 as $item)
    @php
        $isMakeStock = (strcasecmp($item->KDAUF, 'Make Stock') === 0) || (strcasecmp($item->MAT_KDAUF ?? '', 'Make Stock') === 0);
        $soItem = $isMakeStock ? $item->KDAUF : ($item->KDAUF . ' - ' . ltrim($item->KDPOS, '0'));
        $matnr = ctype_digit($item->MATNR) ? ltrim($item->MATNR, '0') : $item->MATNR;
        $sisaQty = $item->real_sisa_qty ?? $item->MGVRG2 - $item->LMNGA;
    @endphp

    <tr class="pro-item draggable-item" 
        data-id="{{ $item->id }}"
        data-aufnr="{{ $item->AUFNR }}"
        data-pwwrk="{{ $item->PWWRK ?? $item->WERKS ?? '' }}"
        data-vgw01="{{ $item->VGW01 }}"
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
        data-stats="{{ $item->STATS }}"
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
                <div class="ms-3 ps-3 border-start">
                    <span class="badge bg-light text-dark border">{{ $item->VORNR }}</span>
                </div>
            </div>
        </td>

        {{-- 3. Info Columns --}}
        <td class="table-col text-muted">{{ $soItem }}</td>
        <td class="table-col">
            <div class="d-flex flex-column">
                <span class="fw-bold text-dark small">{{ $matnr }}</span>
                <span class="text-secondary small">{{ $item->MAKTX }}</span>
            </div>
        </td>
        <td class="text-center table-col">
            <span class="badge bg-light text-dark border text-wrap" style="max-width: 200px;">
                <span class="text-danger fw-bold d-block">{{ $item->ARBPL }}</span>
                {{ isset($wcDescriptions[$item->ARBPL]) ? $wcDescriptions[$item->ARBPL] : '' }}
            </span>
        </td>

        @php
            $statsClass = 'bg-light text-secondary border';
            if ($item->STATS == 'REL' || $item->STATS == 'PCNF REL') {
                $statsClass = 'bg-warning text-dark';
            } elseif (str_contains($item->STATS, 'DSP')) {
                $statsClass = 'bg-success text-light';
            } elseif (str_contains($item->STATS, 'CRTD')) {
                $statsClass = 'bg-info text-white pointer';
                // CRTD is special: clickable to release
            } elseif (str_contains($item->STATS, 'TECO')) {
                $statsClass = 'bg-danger text-white';
            }
        @endphp
        
        <td class="text-center table-col">
            @if (str_contains($item->STATS, 'CRTD'))
                <button class="btn badge bg-info text-white border-0" 
                        onclick="handleReleaseAndRefresh('{{ $item->AUFNR }}', '{{ addslashes($item->WERKSX) }}')"
                        title="Release & Refresh PRO">
                    {{ $item->STATS }}
                </button>
            @else
                <span class="badge {{ $statsClass }}">{{ $item->STATS }}</span>
            @endif
        </td>
        <td class="text-center table-col"><span class="badge bg-light text-secondary border">{{ $item->STEUS }}</span></td>
        <td class="text-center table-col"><span class="badge bg-light text-secondary border">{{ $item->VORNR }}</span></td>
        @php
            $showUnit = $item->MEINS;
            if ($showUnit == 'ST') { $showUnit = 'PC'; }
            $decimals = in_array($item->MEINS, ['ST', 'SET']) ? 0 : 1;
        @endphp
        <td class="text-center table-col fw-bold text-dark">{{ number_format($item->MGVRG2, $decimals, ',', '.') }} {{ $showUnit }}</td>
        <td class="text-center table-col text-muted">{{ number_format($item->LMNGA, $decimals, ',', '.') }} {{ $showUnit }}</td>
        
        @php
            $qtySisaDisplay = ($item->MGVRG2 - $item->LMNGA) - $item->qty_wi;
            if ($qtySisaDisplay < 0) $qtySisaDisplay = 0;
        @endphp
        <td class="text-center table-col text-primary fw-bold">{{ number_format($qtySisaDisplay, $decimals, ',', '.') }} {{ $showUnit }}</td>
        
        @php
            // Numeric standard value (time/value per unit)
            $vgw01 = ($item->VGW01 ?? $item->vgw01 ?? 0);
            $unitVge = strtoupper(trim($item->VGE01 ?? $item->vge01 ?? ''));
            $timeReq = $vgw01 * (float) $qtySisaDisplay;

            if (in_array($unitVge, ['S', 'SET', 'ST'], true)) {
                $timeReq = $timeReq / 60;
            }

            $taktTimeDisplay = number_format((float)$timeReq, 4, ',', '');
            $taktTimeDisplay = rtrim($taktTimeDisplay, '0');
            $taktTimeDisplay = rtrim($taktTimeDisplay, ',');
            // Fixed Format
        @endphp
        <td class="text-center table-col">{{ $taktTimeDisplay }} Min</td>
        <td class="text-center table-col">
            <div class="d-flex flex-column align-items-center justify-content-center" style="line-height: 1.2;">
                <span class="small fw-bold text-nowrap">{{ ($item->SSAVD ? \Carbon\Carbon::parse($item->SSAVD)->format('d/m/Y') : '-') }}</span>
                <span class="text-muted text-xs mx-1">-</span>
                <span class="small fw-bold text-nowrap">{{ ($item->SSSLD ? \Carbon\Carbon::parse($item->SSSLD)->format('d/m/Y') : '-') }}</span>
            </div>
        </td>
        <td class="card-view-content" colspan="9">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold text-primary small">{{ $item->AUFNR }}</span>
                <!-- X button removed by request -->
            </div>

            <div class="d-flex align-items-center justify-content-between gap-1">
                 <span class="badge bg-light text-dark border p-1 assigned-qty-badge" style="font-size: 0.7rem;">Qty: -</span>
                 <span class="badge bg-success bg-opacity-10 text-success p-1 border border-success border-opacity-25 child-wc-display" style="font-size: 0.7rem;"></span>
            </div>
            <span class="d-none employee-name-text">-</span>
        </td>
    </tr>
@endforeach
