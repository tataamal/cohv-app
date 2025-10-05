<x-layouts.app title="PRO Transaction">
<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-primary">
                PRO Transaction Detail
            </h3>
            <p class="text-muted">Production Order: {{ $proData->AUFNR ?? 'N/A' }} (Bagian: {{ $bagian ?? 'N/A' }})</p>
        </div>
    </div>

    {{-- --- BAGIAN I: DATA HEADER DAN BADGE (T1, T2, T3 Shortcut) --- --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Production Order Overview</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                
                {{-- Data Buyer (T1) --}}
                <div class="col-md-4">
                    <p class="mb-1 text-muted small">Buyer Name</p>
                    <span class="badge bg-success fs-6 p-2 w-100 text-start">
                        <i class="fas fa-user me-2"></i> {{ $buyerData->NAME1 ?? 'N/A' }}
                    </span>
                </div>

                {{-- Sales Order (T2 KDAUF) --}}
                <div class="col-md-3">
                    <p class="mb-1 text-muted small">SO</p>
                    <span class="badge bg-primary fs-6 p-2 w-100 text-start">
                        <i class="fas fa-receipt me-2"></i> {{ $proData->KDAUF ?? 'N/A' }}
                    </span>
                </div>
                
                {{-- Sales Order Item (T2 KDPOS) --}}
                <div class="col-md-2">
                    <p class="mb-1 text-muted small">SO Item</p>
                    <span class="badge bg-info text-dark fs-6 p-2 w-100 text-start">
                        {{ $proData->KDPOS ?? 'N/A' }}
                    </span>
                </div>

                {{-- Status PRO (T3) --}}
                <div class="col-md-3">
                    <p class="mb-1 text-muted small">PRO Status</p>
                    @php
                        $statusClass = ($proData->STATS ?? '') === 'REL' ? 'bg-warning text-dark' : 'bg-secondary';
                    @endphp
                    <span class="badge {{ $statusClass }} fs-6 p-2 w-100 text-start">
                        {{ $proData->STATS ?? 'N/A' }}
                    </span>
                </div>
                
            </div>
            
            <hr>

            {{-- Informasi Detail Tambahan PRO --}}
            <div class="row small text-muted g-1">
                <div class="col-md-3 text-center">Material: <strong>{{ $proData->MAKTX ?? '-' }}</strong></div>
                {{-- PERBAIKAN FINAL: Menggunakan check ganda dan Carbon::parse() --}}
                <div class="col-md-3 text-center">Start Date: 
                    <strong>
                        @if ($proData->GSTRP && !empty($proData->GSTRP) && $proData->GSTRP != '00000000')
                            {{ \Carbon\Carbon::parse($proData->GSTRP)->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </strong>
                </div>
                <div class="col-md-3 text-center">End Date: 
                    <strong>
                        @if ($proData->GSTRP && !empty($proData->GSTRP) && $proData->GSTRP != '00000000')
                            {{ \Carbon\Carbon::parse($proData->GLTRP)->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </strong>
                </div>
                <div class="col-md-3 text-center">GR Quantity: <strong>{{ $proData->WEMNG ?? '0' }}</strong></div>
            </div>
            
        </div>
    </div>
    {{-- -------------------------------------------------------------------------- --}}

    {{-- --- BAGIAN II: ACTION BUTTONS (Transaksi) --- --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h6 class="mb-3 text-primary"><i class="fas fa-cogs me-2"></i> Transaction Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                
                {{-- Scheduling & Refresh --}}
                <button type="button" class="btn btn-sm btn-info" 
                    onclick="openSchedule(
                        '{{ $proData->AUFNR ?? '' }}', 
                        // PERBAIKAN FINAL: Melakukan parsing dan formatting tanggal di dalam JS parameter
                        '{{ $proData->SSAVD && !empty($proData->SSAVD) && $proData->SSAVD != '00000000' ? \Carbon\Carbon::parse($proData->SSAVD)->format('d/m/Y') : '' }}'
                    )"
                    @if(empty($proData->AUFNR)) disabled @endif>
                    <i class="fas fa-clock-rotate-left me-1"></i> Reschedule
                </button>

                <button type="button" class="btn btn-sm btn-primary" onclick="openRefresh('{{ $proData->AUFNR ?? '' }}', '{{ $proData->WERKSX ?? '' }}')"
                    @if(empty($proData->AUFNR)) disabled @endif>
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh PRO
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="openChangePvModal('{{ $proData->AUFNR ?? '' }}', '{{ $proData->VERID ?? '' }}', '{{ $proData->WERKSX ?? '' }}')"
                    @if(empty($proData->AUFNR)) disabled @endif>
                    <i class="fa-solid fa-code-compare me-1"></i> Change PV
                </button>
                
                {{-- Documents & Status --}}
                <button type="button" class="btn btn-sm btn-success text-white" onclick="openReadPP('{{ $proData->AUFNR ?? '' }}')"
                    @if(empty($proData->AUFNR)) disabled @endif>
                    <i class="fas fa-book-open me-1"></i> READ PP
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="openTeco('{{ $proData->AUFNR ?? '' }}')"
                    @if(empty($proData->AUFNR)) disabled @endif>
                    <i class="fas fa-circle-check me-1"></i> TECO
                </button>
                
            </div>
        </div>
    </div>
    {{-- -------------------------------------------------------------------------- --}}

    {{-- --- BAGIAN III: TABEL DETAIL (T3, T1 Routing, T4 Component) --- --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="proTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Order Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="routing-tab" data-bs-toggle="tab" data-bs-target="#routing" type="button" role="tab">Routing Detail</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="components-tab" data-bs-toggle="tab" data-bs-target="#components" type="button" role="tab">Components</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="proTabsContent">
                        
                        {{-- TAB 1: ORDER OVERVIEW (T3) --}}
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <h5 class="mb-3">All Production Orders for this Item</h5>
                            @include('Admin.partials.pro-overview-table', ['proOrders' => $allTData3])
                        </div>

                        {{-- TAB 2: ROUTING DETAIL (T1) --}}
                        <div class="tab-pane fade" id="routing" role="tabpanel">
                            <h5 class="mb-3">Routing (Operations)</h5>
                            @include('Admin.partials.routing-table', ['routingData' => $allTData1])
                        </div>

                        {{-- TAB 3: COMPONENTS (T4) --}}
                        <div class="tab-pane fade" id="components" role="tabpanel">
                            <h5 class="mb-3">Required Components / BOM</h5>
                            <div class="d-flex justify-content-end mb-3 gap-2">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addComponentModal">
                                    <i class="fas fa-plus me-1"></i> Add Component
                                </button>
                                <button type="button" class="btn btn-sm btn-success">
                                    <i class="fas fa-check-double me-1"></i> Select Components
                                </button>
                                <button type="button" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Edit Component
                                </button>
                            </div>
                            @include('Admin.partials.components-table', ['componentData' => $allTData4ByAufnr])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.app>