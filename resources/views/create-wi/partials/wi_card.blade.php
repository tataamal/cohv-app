@php
    // --- 1. DEFINISI VARIABEL (WAJIB ADA) ---
    $documentTime = \Carbon\Carbon::parse($document->document_time)->format('H:i');
    $documentDate = \Carbon\Carbon::parse($document->document_date)->format('d M Y');
    
    // Ambil Summary dari Controller (Ini yang sebelumnya error 'Undefined')
    // Kita berikan default array kosong jika null
    $summary = $document->pro_summary ?? ['details' => []];
    
    // Ambil Data Kapasitas
    $cap = $document->capacity_info ?? ['used_mins' => 0, 'max_mins' => 0, 'percentage' => 0];
    $capPct = $cap['percentage'];
    
    // Warna Bar Kapasitas
    $capColor = $capPct < 70 ? 'bg-success' : ($capPct < 95 ? 'bg-warning' : 'bg-danger');
@endphp

<div class="card mb-3 rounded-3 shadow-sm border-0 {{ $statusClass }}">
    <div class="card-body p-4 bg-white rounded-3">
        
        {{-- HEADER KARTU --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex align-items-start gap-3 w-100">
                
                {{-- BADGE WORKCENTER --}}
                <div class="text-center p-2 rounded bg-light border flex-shrink-0" style="min-width: 80px;">
                    <small class="d-block text-muted fw-bold" style="font-size: 0.65rem;">WORKCENTER</small>
                    <strong class="fs-4 text-dark">{{ $document->workcenter_code }}</strong>
                </div>

                {{-- INFO DOKUMEN --}}
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">
                                <span class="text-primary font-monospace">{{ $document->wi_document_code }}</span>
                            </h5>
                            <div class="text-muted small mb-2">
                                <i class="fa-regular fa-calendar me-1"></i> {{ $documentDate }}
                                <span class="mx-2">|</span>
                                <i class="fa-regular fa-clock me-1"></i> {{ $documentTime }}
                            </div>
                        </div>
                    </div>

                    {{-- Bar Kapasitas --}}
                    <div class="mt-1 p-2 rounded bg-light border" style="max-width: 500px;">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-bold text-muted" style="font-size: 0.7rem;">
                                <i class="fa-solid fa-battery-half me-1"></i> Capacity Load
                            </span>
                            <span class="fw-bold text-dark" style="font-size: 0.75rem;">
                                {{ number_format($cap['used_mins'], 0) }} / {{ number_format($cap['max_mins'], 0) }} Min 
                                ({{ number_format($capPct, 1) }}%)
                            </span>
                        </div>
                        <div class="progress bg-white border" style="height: 10px;">
                            <div class="progress-bar {{ $capColor }} progress-bar-striped" role="progressbar" 
                                style="width: {{ min($capPct, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- TOMBOL TOGGLE DETAIL --}}
            @if($statusClass == 'wi-card-active')
                <button type="button" 
                        class="btn btn-dark btn-sm shadow-sm"
                        data-bs-toggle="modal" 
                        data-bs-target="#printSingleModal"
                        data-wi-code="{{ $document->wi_document_code }}">
                    <i class="fa-solid fa-print"></i>
                </button>
            @endif
        </div>

        <hr class="text-muted opacity-25">

        {{-- DETAIL ITEM --}}
        <div class="collapse show" id="collapse-{{ $document->wi_document_code }}">
            <div class="d-flex flex-column gap-2">
                
                {{-- LOOPING DETAIL ITEM --}}
                @foreach ($summary['details'] as $item)
                    @php
                        $pct = min($item['progress_pct'], 100);
                        $barColor = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-primary' : 'bg-secondary');
                        
                        $assigned = fmod($item['assigned_qty'], 1) !== 0.0 ? number_format($item['assigned_qty'], 3, ',', '.') : number_format($item['assigned_qty'], 0, ',', '.');
                        $confirmed = fmod($item['confirmed_qty'], 1) !== 0.0 ? number_format($item['confirmed_qty'], 3, ',', '.') : number_format($item['confirmed_qty'], 0, ',', '.');
                        
                        // Ambil Qty Order (untuk validasi tombol edit)
                        $qtyOrder = $item['qty_order'] ?? $item['assigned_qty'];
                    @endphp

                    <div class="border rounded p-2 bg-light item-row position-relative" style="break-inside: avoid;">
                        <div class="row align-items-center g-2">
                            
                            {{-- Material Info --}}
                            <div class="col-md-4">
                                <span class="badge bg-dark mb-1" style="font-size: 0.7rem;">{{ $item['aufnr'] }}</span>
                                <div class="small text-dark fw-bold text-truncate" title="{{ $item['description'] }}">
                                    {{ $item['description'] ?: 'No Description' }}
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between small mb-1" style="font-size: 0.7rem;">
                                    <span class="text-muted">Production Progress</span>
                                    <span class="{{ $pct >= 100 ? 'text-success' : 'text-primary' }} fw-bold">{{ round($item['progress_pct'], 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>

                            {{-- Qty & Action --}}
                            <div class="col-md-3 text-end">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                                        {{ $confirmed }} <span class="text-muted small">/ {{ $assigned }}</span>
                                    </div>
                                    @if($item['status'] !== 'Completed')
                                    <button class="btn btn-link btn-sm p-0 text-primary btn-edit-qty no-print" 
                                            title="Edit Quantity"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editQtyModal"
                                            data-wi-code="{{ $document->wi_document_code }}"
                                            data-aufnr="{{ $item['aufnr'] }}"
                                            data-desc="{{ $item['description'] }}"
                                            data-current-qty="{{ $item['assigned_qty'] }}"
                                            data-max-qty="{{ $qtyOrder }}" 
                                            data-uom="{{ $item['uom'] }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endif
                                </div>
                                <span class="badge bg-secondary" style="font-size: 0.6rem;">{{ $item['uom'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>