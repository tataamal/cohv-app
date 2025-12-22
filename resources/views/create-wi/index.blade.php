<x-layouts.app title="Create Work Instruction">

    @push('styles')
        <style>
            :root {
                --bg-app: #eef2f6;
                --card-bg: #ffffff;
                --card-header-bg: #f8fafc;
                --border-color: #e2e8f0;
                --primary-dark: #1e293b;
                --primary-blue: #3b82f6;
                --text-secondary: #64748b;
                --success-color: #10b981;
                --warning-bg: #fff7ed;
                --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            }

            body { 
                background-color: var(--bg-app) !important; 
                color: var(--primary-dark); 
            }
            .icon-box {
                width: 32px; height: 32px;
                display: flex; align-items: center; justify-content: center;
                border-radius: 8px;
            }
            .fw-bolder { font-weight: 700 !important; }
            .text-xs { font-size: 0.75rem; }
            body.dragging-active, 
            body.dragging-active * {
                user-select: none !important;
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                cursor: grabbing !important;
            }
            .source-panel {
                background: var(--card-bg);
                border-radius: 12px;
                border: 1px solid var(--border-color);
                box-shadow: var(--shadow-soft);
                height: calc(100vh - 140px);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .source-header {
                background: var(--card-bg);
                padding: 15px 20px;
                border-bottom: 1px solid var(--border-color);
                z-index: 20;
            }
            .search-input-group {
                background: #f1f5f9;
                border-radius: 8px;
                border: 1px solid transparent;
                transition: all 0.2s;
            }
            .search-input-group:focus-within {
                background: #ffffff;
                border-color: var(--primary-blue);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            .search-input-group input { background: transparent; border: none; font-size: 0.9rem; }
            .search-input-group input:focus { box-shadow: none; }
            .table-scroll-area {
                flex-grow: 1;
                overflow-y: auto;
                background-color: #ffffff;
            }
            .table-custom thead th {
                background: #f8fafc;
                color: var(--text-secondary);
                font-weight: 600;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 12px 10px;
                border-bottom: 2px solid var(--border-color);
                position: sticky;
                top: 0;
                z-index: 10;
            }
            tr.pro-item {
                border-bottom: 1px solid #f1f5f9;
                transition: background 0.15s;
                cursor: grab;
                position: relative;
            }
            tr.pro-item * {
                user-select: text; 
            }
            tr.pro-item:active {
                cursor: grabbing;
            }
            .target-scroll-area {
                height: calc(100vh - 140px);
                overflow-y: auto;
                padding-right: 5px;
            }

            .wc-card-container {
                background: var(--card-bg);
                border-radius: 10px;
                border: 1px solid var(--border-color);
                box-shadow: var(--shadow-soft);
                margin-bottom: 1rem;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .wc-card-container:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-hover);
                border-color: #cbd5e1;
            }

            .wc-header {
                padding: 12px 15px;
                border-bottom: 1px solid #f1f5f9;
                background: var(--card-header-bg);
                border-radius: 10px 10px 0 0;
            }

            .wc-body { padding: 12px 15px; }
            .wc-drop-zone {
                min-height: 80px;
                max-height: 300px;
                overflow-y: auto;
                background-color: #f8fafc;
                border: 2px dashed #cbd5e1;
                border-radius: 8px;
                transition: all 0.2s;
                padding: 5px;
            }
            .wc-drop-zone.drag-over {
                background-color: #eff6ff;
                border-color: var(--primary-blue);
                box-shadow: inset 0 0 0 4px rgba(59, 130, 246, 0.1);
            }
            .empty-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 70px;
                color: #94a3b8;
                font-size: 0.8rem;
                font-weight: 500;
            }
            .wc-drop-zone .pro-item-card {
                background: white;
                border: 1px solid var(--border-color);
                border-left: 4px solid var(--primary-blue); /* Accent */
                border-radius: 6px;
                padding: 10px;
                margin-bottom: 8px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                cursor: grab;
                position: relative;
            }
            .wc-drop-zone .pro-item-card:hover {
                border-color: #cbd5e1;
                border-left-color: var(--primary-blue);
            }
            .sortable-ghost {
                opacity: 0.4;
                background-color: #e2e8f0 !important;
                border: 2px dashed var(--text-secondary) !important;
            }
            .sortable-drag { opacity: 1 !important; background: transparent; }
            .drag-preview-icon {
                background: white;
                padding: 10px 15px;
                border-radius: 8px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
                border: 1px solid var(--primary-blue);
                display: flex; align-items: center; gap: 10px;
                width: max-content;
                transform: rotate(2deg);
            }
            .wc-drop-zone .pro-item-card .table-col,
            .wc-drop-zone .pro-item-card .drag-handle,
            .wc-drop-zone .pro-item-card .preview-container .original-content,
            .wc-drop-zone .pro-item-card .row-checkbox { display: none !important; }

            .wc-drop-zone .pro-item-card .card-view-content { display: block !important; }
            .wc-drop-zone .pro-item-card .drag-preview-icon { display: none !important; }

            .source-table .pro-item .card-view-content { display: none; }
            .source-table .pro-item .drag-preview-icon { display: none; }
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
            #source-list {
            }
            #source-list:empty {
               display: block;
               min-height: 150px;
               width: 100%;
               background-image: linear-gradient(45deg, #f8fafc 25%, transparent 25%, transparent 75%, #f8fafc 75%, #f8fafc), linear-gradient(45deg, #f8fafc 25%, transparent 25%, transparent 75%, #f8fafc 75%, #f8fafc);
               background-size: 20px 20px;
               background-position: 0 0, 10px 10px;
            }
            #source-list:empty::after {
                content: "Drag items back here";
                display: flex;
                align-items: center;
                justify-content: center;
                height: 150px;
                color: #cbd5e1;
                font-weight: 600;
                font-size: 0.9rem;
            }
            
            .table-scroll-area {
                min-height: 150px;
            }
        </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4" style="max-width: 1600px;">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h4 fw-bolder text-dark mb-1">Work Instruction</h1>
                <p class="text-muted small mb-0">Drag PRO untuk membuat Work Instruction.</p>
            </div>
            
            <div class="d-flex gap-2 bg-white p-1 rounded-pill shadow-sm border">
                <a href="{{ route('wi.history', ['kode' => $kode]) }}" class="btn btn-white text-secondary fw-bold rounded-pill px-3 btn-sm">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>History
                </a>
                <button onclick="refreshData()" class="btn btn-white text-primary fw-bold rounded-pill px-3 btn-sm" title="Refresh data dari SAP">
                    <i class="fa-solid fa-rotate me-2"></i>Refresh Data
                </button>
                <div class="vr my-1"></div>
                <button onclick="resetAllAllocations()" class="btn btn-white text-danger fw-bold rounded-pill px-3 btn-sm">
                    <i class="fa-solid fa-arrow-rotate-left me-2"></i>Reset
                </button>
                <button onclick="saveAllocation(true)" class="btn btn-primary fw-bold text-white rounded-pill px-4 btn-sm shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Review & Save
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-9 col-md-8">
                <div class="source-panel">
                    <div class="source-header d-flex justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-list-ul"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">List PRO Siap WI</h6>
                            </div>
                        </div>

                        <div class="search-input-group px-3 py-1 d-flex align-items-center" style="width: 350px;">
                            <i class="fa-solid fa-magnifying-glass text-muted me-2"></i>
                            <input type="text" id="searchInput" class="form-control form-control-sm p-0" placeholder="Cari Material, PRO, or SO...">
                        </div>

                        {{-- FILTER DISABLED TEMPORARILY (DEFAULT ALL) --}}
                        {{-- 
                        <div class="ms-auto me-3">
                             <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('create-wi.index', ['kode' => $kode, 'filter' => 'today']) }}" class="btn btn-outline-secondary {{ $currentFilter == 'today' ? 'active' : '' }}" style="font-size: 0.7rem; padding: 2px 8px;">Today</a>
                                <a href="{{ route('create-wi.index', ['kode' => $kode, 'filter' => 'week']) }}" class="btn btn-outline-secondary {{ $currentFilter == 'week' ? 'active' : '' }}" style="font-size: 0.7rem; padding: 2px 8px;">Week</a>
                                <a href="{{ route('create-wi.index', ['kode' => $kode, 'filter' => 'all']) }}" class="btn btn-outline-secondary {{ $currentFilter == 'all' ? 'active' : '' }}" style="font-size: 0.7rem; padding: 2px 8px;">All</a>
                            </div>
                        </div> 
                        --}}
                    </div>
                    
                    {{-- ADVANCED SEARCH (MANUAL COLLAPSE) --}}
                    <div class="mb-2">
                        <button class="btn btn-sm btn-light w-100 border text-start text-secondary fw-bold d-flex align-items-center justify-content-between" 
                                type="button" 
                                onclick="toggleAdvancedSearch()"
                                style="font-size: 0.8rem;">
                            <span><i class="fa-solid fa-sliders me-2"></i> Advanced Search (Specific Fields)</span>
                            <i class="fa-solid fa-chevron-down text-xs" id="advSearchIcon"></i>
                        </button>
                        
                        <div class="d-none border-start border-end border-bottom rounded-bottom shadow-sm" id="advancedSearchCollapse">
                            <div class="bg-white p-3">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="advAufnr" class="form-control" placeholder="PRO">
                                            <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advAufnr', 'PRO List')" title="Input Multiple"><i class="fa-solid fa-list-ul"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="advMatnr" class="form-control" placeholder="Material">
                                            <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advMatnr', 'Material List')" title="Input Multiple"><i class="fa-solid fa-list-ul"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="advMaktx" class="form-control" placeholder="Description">
                                            <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advMaktx', 'Desc. List')" title="Input Multiple"><i class="fa-solid fa-list-ul"></i></button>
                                        </div>
                                    </div>
                                     <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="advArbpl" class="form-control" placeholder="Workcenter">
                                            <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advArbpl', 'WC List')" title="Input Multiple"><i class="fa-solid fa-list-ul"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="advKdauf" class="form-control" placeholder="SO (KDAUF)">
                                            <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advKdauf', 'SO List', false)" title="Input Multiple"><i class="fa-solid fa-list-ul"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="advKdpos" class="form-control" placeholder="Item">
                                            <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advKdpos', 'Item List', false)" title="Input Multiple"><i class="fa-solid fa-list-ul"></i></button>
                                        </div>
                                    </div>
                                     <div class="col-md-1">
                                        <button class="btn btn-sm btn-outline-danger w-100 fw-bold" onclick="clearAdvancedSearch()" title="Reset Filters"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>
                                <div class="text-xs text-muted mt-2 fst-italic">
                                    <i class="fa-solid fa-circle-info me-1"></i> Gunakan ini untuk pencarian spesifik per kolom. Pencarian utama diatas akan diabaikan jika kolom ini terisi.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-scroll-area custom-scrollbar" style="max-height: 700px; overflow-y: auto;">
                        <table class="table table-hover table-striped source-table mb-0 w-100" style="font-size: 0.8rem;">
                            <thead class="bg-light sticky-top" style="z-index: 5;">
                                <tr class="align-middle">
                                    <th class="text-center ps-3" width="40">
                                        <input class="form-check-input pointer" type="checkbox" id="selectAll">
                                    </th>
                                    <th class="ps-3">PRO</th>
                                    <th>SO-Item</th>
                                    <th>Material Description</th>
                                    <th class="text-center">WC</th>
                                    <th class="text-center">Op.Key</th>
                                    <th class="text-center">Qty Opt</th>
                                    <th class="text-center">Qty Conf</th>
                                    <th class="text-center">Qty Sisa</th> 
                                    <th class="text-center">Time Req</th>
                                </tr>
                            </thead>
                            <tbody id="source-list" class="sortable-list" data-group="shared-pro">
                                @include('create-wi.partials.source_table_rows', ['tData1' => $tData1])
                            </tbody>
                        </table>
                        <div id="loadingSpinner" class="text-center py-3 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2 text-muted small">Loading more data...</span>
                        </div>
                        <div id="endOfData" class="text-center py-2 text-muted small d-none">
                            End of results.
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUMN 2: TARGET WORKCENTERS --}}
            <div class="col-lg-3 col-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box bg-dark text-white shadow-sm" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-bullseye text-xs"></i>
                        </div>
                        <h6 class="mb-0 fw-bold">WC Target</h6>
                    </div>
                    <span class="badge bg-white border text-muted rounded-pill">{{ count($workcenters) }} WC</span>
                </div>
                
                {{-- Target Search Bar (Full Width) --}}
                <div class="mb-3"> {{-- Matches card margin --}}
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-white border-end-0 ps-3">
                             <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                        </span>
                        <input type="text" id="targetWcSearchInput" class="form-control border-start-0 ps-1" placeholder="Cari WC Target..." style="font-size: 0.85rem;">
                    </div>
                </div>

                <div class="target-scroll-area custom-scrollbar">
                    @foreach ($workcenters as $wc)
                        @php
                            // [UPDATED] Fixed Capacity Rule: 9.5 Hours (570 Mins) per Single/Child WC
                            // Parent Capacity = Sum of Children * 570 Mins
                            $fixedSingleMins = 570;
                            $fixedSingleHours = 9.5;
                            
                            if (array_key_exists($wc->kode_wc, $parentWorkcenters)) {
                                $childCount = count($parentWorkcenters[$wc->kode_wc]);
                                $kapazHours = $childCount * $fixedSingleHours;
                            } else {
                                $kapazHours = $fixedSingleHours;
                            }
                            $isUnknown = false; // Always known now due to fixed rule
                        @endphp

                        {{-- @if (!$isUnknown) --}}
                            <div class="wc-card-container" data-wc-id="{{ $wc->kode_wc }}" data-kapaz-wc="{{ $kapazHours }}">
                                {{-- Card Header --}}
                                <div class="wc-header">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold text-dark mb-0">{{ $wc->kode_wc }}</h6>
                                        <span class="badge bg-light text-dark border" id="label-cap-{{ $wc->kode_wc }}" style="font-size: 0.75rem;">0 / 0 Min</span>
                                    </div>
                                    <div class="text-muted text-xs text-truncate mb-2" title="{{ $wc->description }}">
                                        {{ $wc->description }}
                                    </div>
                                    
                                    {{-- Progress Bar --}}
                                    <div class="progress bg-white border" style="height: 8px; border-radius: 4px;">
                                        <div id="progress-{{ $wc->kode_wc }}" class="progress-bar rounded-pill bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                {{-- Card Body (Drop Zone) --}}
                                <div class="wc-body bg-white">
                                    <div id="zone-{{ $wc->kode_wc }}" class="wc-drop-zone sortable-list" data-group="shared-pro">
                                        <div class="empty-placeholder">
                                            <i class="fa-solid fa-arrow-down mb-1 opacity-50"></i>
                                            <span>Drop Items Here</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {{-- @endif --}}
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- MULTI INPUT MODAL --}}
    <div class="modal fade" id="multiInputModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-primary bg-opacity-10">
                    <h6 class="modal-title fw-bold text-primary" id="multiInputTitle">Input List Parameter</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="text-muted small mb-2">Paste list parameter disini (dipisahkan baris baru atau koma):</p>
                    <textarea id="multiInputTextarea" class="form-control" rows="10" placeholder="Contoh:&#10;1001&#10;1002&#10;1003"></textarea>
                </div>
                <div class="modal-footer border-0 p-2 bg-light">
                    <button type="button" class="btn btn-white text-muted fw-bold btn-sm border" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary fw-bold btn-sm shadow-sm" onclick="applyMultiInput()">Isi Parameter</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL MISMATCH (NEW) --}}
    <div class="modal fade" id="mismatchModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning bg-opacity-10 border-0">
                    <h6 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Workcenter Mismatch</h6>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4 small">PRO yang Anda drop memiliki Workcenter asal yang berbeda dengan target. Apakah Anda ingin mengubah Workcenter?</p>
                    
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-5">
                            <label class="small fw-bold text-muted mb-1">Worckenter saat ini</label>
                            <input type="text" class="form-control bg-light" id="mismatchCurrentWC" readonly>
                        </div>
                        <div class="col-2 text-center">
                            <i class="fa-solid fa-arrow-right text-muted"></i>
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-primary mb-1">Target Workcenter</label>
                            <input type="text" class="form-control border-primary bg-primary bg-opacity-10 text-primary fw-bold" id="mismatchTargetWC" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-2">
                    <button type="button" class="btn btn-white text-muted fw-bold btn-sm shadow-sm border" id="btnCancelMismatch">
                        Batalkan Pemindahan
                    </button>
                    <button type="button" class="btn btn-warning fw-bold btn-sm shadow-sm" id="btnChangeWC">
                        Ubah Workcenter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uniqueAssignmentModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Assign Operator</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <select id="employeeTemplateSelect" class="d-none">
                    <option value="" selected disabled>Pilih Operator...</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp['pernr'] }}" data-name="{{ $emp['stext'] }}" data-arbpl="{{ $emp['arbpl'] ?? '' }}">
                            {{ $emp['pernr'] }} - {{ $emp['stext'] }}
                        </option>
                    @endforeach
                </select>
                <div class="modal-body bg-light p-3">
                    <div class="bg-white p-3 rounded-3 shadow-sm border mb-3" style="position: sticky; top: 0; z-index: 105;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                             <div class="fw-bold" id="modalProDetails"></div>
                        </div>
                        <div id="bulkWarning" class="alert alert-info d-none text-xs p-2 mb-0 border-0 bg-info bg-opacity-10 text-info rounded fw-bold">
                            <i class="fa-solid fa-list-check me-1"></i> Mode Pemetaan Massal
                        </div>
                        <div id="capacityInjectionContainer" class="mt-2"></div>
                    </div>

                    <div id="assignmentCardContainer" class="bg-white rounded border shadow-sm p-3 custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                        {{-- Cards injected by JS --}}
                    </div>
                </div>
                <div class="modal-footer border-0 p-2 bg-white d-flex">
                    <button type="button" class="btn btn-outline-danger btn-sm flex-fill fw-bold" id="btnCancelDrop">Batal</button>
                    <button type="button" class="btn btn-success btn-sm flex-fill fw-bold shadow-sm" id="btnConfirmFinalAssignment">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
                minDate: "today",
                defaultDate: "today"
            });
            flatpickr(".flatpickr-time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                defaultDate: "07:00"
            });
        });
    </script>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            const PARENT_WORKCENTERS = @json($parentWorkcenters);
            const PLANT_CODE = '{{ $kode }}';

            let draggedItemsCache = [];
            let targetContainerCache = null;
            let sourceContainerCache = null;
            let assignmentModalInstance = null;
            let previewModalInstance = null;
            let mismatchModalInstance = null; // New Modal Instance
            let tempSplits = [];
            let currentSisaQty = 0;
            let pendingMismatchItem = null;
            let pendingMismatchEvent = null;


            let multiInputTargetId = null;
            let multiInputModalInstance = null;

            document.addEventListener('DOMContentLoaded', function() {
                assignmentModalInstance = new bootstrap.Modal(document.getElementById('uniqueAssignmentModal'));
                previewModalInstance = new bootstrap.Modal(document.getElementById('previewModal'));
                mismatchModalInstance = new bootstrap.Modal(document.getElementById('mismatchModal')); 
                multiInputModalInstance = new bootstrap.Modal(document.getElementById('multiInputModal')); // New Modal

                calculateAllRows();
                setupSearch();
                setupCheckboxes();
                setupDragAndDrop();
                setupModalLogic();
                setupMismatchLogic(); 
                setupTargetWcSearch(); 
                document.getElementById('uniqueAssignmentModal').addEventListener('hide.bs.modal', function() {
                    if(draggedItemsCache.length > 0 && tempSplits.length === 0) {
                        cancelDrop();
                    }
                });
            });

            // New: Multi Input Logic
            window.openMultiInput = function(targetId, title, isSo = false) {
                const input = document.getElementById(targetId);
                const textarea = document.getElementById('multiInputTextarea');
                
                multiInputTargetId = targetId;
                document.getElementById('multiInputTitle').innerText = 'Input List: ' + title;
                
                // Pre-fill textarea from current input (replace commas with newlines for editing)
                let currentVal = input.value;
                if(currentVal) {
                    // Split by comma+space or just comma
                    let vals = currentVal.split(/\s*,\s*/);
                    textarea.value = vals.join('\n');
                } else {
                    textarea.value = '';
                }
                
                if(isSo) {
                    textarea.placeholder = "Format: SO - Item\nContoh:\nMake Stock - 10\nOther Order - 20";
                } else {
                    textarea.placeholder = "Contoh:\nValue 1\nValue 2\nValue 3";
                }

                multiInputModalInstance.show();
            };

            window.applyMultiInput = function() {
                const textarea = document.getElementById('multiInputTextarea');
                const targetInput = document.getElementById(multiInputTargetId);
                
                if(!targetInput) return;
                
                // Split by newline or comma
                let rawVal = textarea.value;
                let lines = rawVal.split(/[\n,]+/);
                
                // Clean and Filter
                let cleanVals = lines.map(l => l.trim()).filter(l => l.length > 0);
                
                // Join back with comma space
                targetInput.value = cleanVals.join(', ');
                
                // Trigger Event for Search
                targetInput.dispatchEvent(new Event('keyup'));
                
                multiInputModalInstance.hide();
            };

            function setupDragAndDrop() {
                const sourceList = document.getElementById('source-list');
                const wcZones = document.querySelectorAll('.wc-drop-zone');

                new Sortable(sourceList, {
                    group: 'shared-pro',
                    animation: 150,
                    forceFallback: true,
                    fallbackClass: "sortable-drag",
                    ghostClass: "sortable-ghost",
                    sort: false,
                    onStart: function(evt) { 
                        document.body.classList.add('dragging-active');
                        const checked = document.querySelectorAll('#source-list .pro-item .row-checkbox:checked');
                        const draggedIsChecked = evt.item.querySelector('.row-checkbox')?.checked;
                        
                        if (checked.length > 0 && draggedIsChecked) {
                            draggedItemsCache = Array.from(checked).map(cb => cb.closest('.pro-item'));
                        } else {
                            draggedItemsCache = [evt.item];
                        }
                        
                        if (checked.length > 1 && draggedIsChecked) {
                            setTimeout(() => {
                                const mirror = document.querySelector('.sortable-drag');
                                if (mirror) {
                                    const badge = document.createElement('div');
                                    badge.innerText = checked.length + " Items";
                                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white shadow-sm';
                                    badge.style.zIndex = '9999';
                                    badge.style.fontSize = '0.7rem';
                                    
                                    mirror.style.position = 'fixed'; 
                                    mirror.style.overflow = 'visible'; 
                                    mirror.appendChild(badge);
                                }
                            }, 10);
                        }
                    },
                    onAdd: function(evt) {
                        document.body.classList.remove('dragging-active');
                        handleReturnToTable(evt.item, null);
                    },
                    onEnd: function(evt) { document.body.classList.remove('dragging-active'); }
                });

                wcZones.forEach(zone => {
                    new Sortable(zone, {
                        group: 'shared-pro',
                        animation: 150,
                        forceFallback: true,
                        onStart: function(evt) { document.body.classList.add('dragging-active'); },
                        onAdd: function(evt) {
                            document.body.classList.remove('dragging-active');
                            handleDropToWc(evt);
                        },
                        onEnd: function(evt) {
                            document.body.classList.remove('dragging-active');
                            updateCapacity(zone.closest('.wc-card-container'));
                        }
                    });
                    checkEmptyPlaceholder(zone);
                });
            }

            function getAssignedChildWCs(aufnr) {
                const assignedWCs = [];

                document.querySelectorAll(`.wc-drop-zone .pro-item-card[data-aufnr="${aufnr}"]`).forEach(item => {
                    if (item.dataset.childWc) {
                        if (!assignedWCs.includes(item.dataset.childWc)) {
                            assignedWCs.push(item.dataset.childWc);
                        }
                    }
                });

                const sourceItem = document.querySelector(`#source-list .pro-item[data-aufnr="${aufnr}"]`);
                if (sourceItem && sourceItem.dataset.assignedChildWcs && sourceItem.dataset.assignedChildWcs !== '[]') {
                    try {
                        const currentAssigned = JSON.parse(sourceItem.dataset.assignedChildWcs);
                        currentAssigned.forEach(wc => {
                            if (!assignedWCs.includes(wc)) {
                                assignedWCs.push(wc);
                            }
                        });
                    } catch (e) {
                        console.error("Error parsing assigned child WCs from source item:", e);
                    }
                }

                tempSplits.forEach(split => {
                    if (split.childWc && !assignedWCs.includes(split.childWc)) {
                        assignedWCs.push(split.childWc);
                    }
                });

                return assignedWCs;
            }

            function handleDropToWc(evt) {
                const item = evt.item;
                const toList = evt.to;
                const fromList = evt.from;
                const wcContainer = toList.closest('.wc-card-container');
                const targetWcId = wcContainer ? wcContainer.dataset.wcId : '';
                const originWc = item.dataset.arbpl;

                // --- CAPACITY PRE-CHECK START ---
                if (wcContainer) {
                    const kapazHours = parseFloat(wcContainer.dataset.kapazWc) || 0;
                    const maxMins = kapazHours * 60;
                    
                    // Calc Current Load (excluding the newly dropped item and any cached dragged items if they are momentarily in the list)
                    let currentLoad = 0;
                    wcContainer.querySelectorAll('.pro-item-card').forEach(card => {
                        // Check if this card is part of the currently dragged set
                        const isDragged = (card === item) || (draggedItemsCache && draggedItemsCache.includes(card));
                        if (!isDragged) {
                             // Force calculation to ensure accuracy (fix for potential stale dataset)
                             const mins = calculateItemMinutes(card);
                             currentLoad += mins;
                        }
                    });

                    // Calc Incoming Load
                    let incomingLoad = 0;
                    // If bulk drag
                    if (draggedItemsCache && draggedItemsCache.length > 0) {
                         draggedItemsCache.forEach(dItem => {
                             incomingLoad += parseFloat(dItem.dataset.calculatedMins) || 0;
                         });
                    } else {
                         // Single item fallback
                         incomingLoad += parseFloat(item.dataset.calculatedMins) || 0;
                    }

                    if ((currentLoad + incomingLoad) > (maxMins + 0.1)) { // Tolerance
                        // REVERT DROP (Bulk / Single)
                        
                        // 1. Determine items to revert
                        let itemsToRevert = [item];
                        if (draggedItemsCache && draggedItemsCache.length > 0) {
                            itemsToRevert = draggedItemsCache;
                        }

                        // 2. Move them back to source
                        itemsToRevert.forEach(revItem => {
                            if(revItem) fromList.appendChild(revItem);
                        });

                        // 3. Reset Mirror/Ghost effects if any (handled by Sortable usually, but good to be safe)
                        draggedItemsCache = []; // Clear cache to avoid side effects
                        document.body.classList.remove('dragging-active');

                        Swal.fire({
                            icon: 'error',
                            title: 'Kapasitas Penuh!',
                            text: `Workcenter ${targetWcId} tidak mencukupi untuk ${itemsToRevert.length} item ini.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        return;
                    }
                }
                // --- CAPACITY PRE-CHECK END ---

                if (originWc && targetWcId && originWc !== targetWcId) {
                    pendingMismatchItem = item;
                    targetContainerCache = toList;
                    sourceContainerCache = fromList;
                    
                    if (!draggedItemsCache || draggedItemsCache.length === 0) {
                        draggedItemsCache = [item]; 
                    }
                    
                    document.getElementById('mismatchCurrentWC').value = originWc;
                    document.getElementById('mismatchTargetWC').value = targetWcId;
                    
                    mismatchModalInstance.show();
                    return;
                }

                processDrop(evt, item, toList, fromList, targetWcId);
            }

            function processDrop(evt, item, toList, fromList, targetWcId) {
                const proAufnr = item.dataset.aufnr;
                
                targetContainerCache = toList;
                sourceContainerCache = fromList;
                draggedItemsCache = [];
                tempSplits = []; 
                currentSisaQty = parseFloat(item.dataset.sisaQty) || 0;
                
                transformToCardView(item);
                
                const checkbox = item.querySelector('.row-checkbox');
                if (checkbox && checkbox.checked) {
                    document.querySelectorAll('#source-list .pro-item .row-checkbox:checked').forEach(cb => {
                         const row = cb.closest('.pro-item');
                         if(row && !draggedItemsCache.includes(row)) draggedItemsCache.push(row);
                    });
                    if (!draggedItemsCache.includes(item)) draggedItemsCache.push(item);
                } else {
                    draggedItemsCache.push(item);
                }

                const proTitle = draggedItemsCache.length > 1 
                    ? `<span class="badge bg-primary">${draggedItemsCache.length} Items Selected</span>` 
                    : `<span class="badge bg-primary text-wrap">${proAufnr}</span>`;
                document.getElementById('modalProDetails').innerHTML = proTitle;

                const bulkWarning = document.getElementById('bulkWarning');
                if(draggedItemsCache.length > 1) {
                    if(bulkWarning) bulkWarning.classList.remove('d-none');
                } else {
                    if(bulkWarning) bulkWarning.classList.add('d-none');
                }

                const container = document.getElementById('assignmentCardContainer');
                container.innerHTML = '';

                const normalizedTargetWc = targetWcId.toUpperCase();
                const hasChildren = PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWc);
                
                let childOptionsHtml = '<option value="">- Induk/None -</option>';
                let capacityInfoHtml = '';

                if (hasChildren) {
                    const children = PARENT_WORKCENTERS[normalizedTargetWc];
                    
                    let totalChildMins = 0;
                    children.forEach(c => {
                         const k = parseFloat(String(c.kapaz).replace(',', '.')) || 0;
                         totalChildMins += (k * 60);
                    });

                    children.forEach(child => {
                        childOptionsHtml += `<option value="${child.code}">${child.code} - ${child.name}</option>`;
                    });
                    
                    capacityInfoHtml = `
                        <div class="card border-secondary bg-light border-opacity-25">
                            <div class="card-body p-2"> 
                                <h6 class="text-uppercase fw-bold small text-muted mb-2">
                                    <i class="fa-solid fa-network-wired me-1"></i> ${targetWcId} Capacity Distribution
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-dark text-white pt-1 pb-1">Total Limit: ${(children.length * 570).toLocaleString()} Min</span>
                                </div>
                                <div class="row g-2" id="topCapacityContainer">
                                    <!-- Child Status Bars Injected Here Dynamically via updateCardSummary -->
                                    ${children.map(child => {
                                        const limit = 570; // FIXED RULE
                                        return `
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between text-xs fw-bold mb-1">
                                                <span>${child.code} - ${child.name}</span>
                                                <span id="cap-text-${child.code}">0 / ${limit.toFixed(1)} Min</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div id="cap-bar-${child.code}" class="progress-bar bg-secondary" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    `;}).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Inject into the dedicated TOP sticky container
                const topInjContainer = document.getElementById('capacityInjectionContainer');
                if (topInjContainer) {
                    topInjContainer.innerHTML = capacityInfoHtml;
                    if(capacityInfoHtml === '') topInjContainer.style.display = 'none';
                    else topInjContainer.style.display = 'block';
                }

                const empOptions = document.getElementById('employeeTemplateSelect').innerHTML;
                
                draggedItemsCache.forEach((row, index) => {
                    const rAufnr = row.dataset.aufnr;
                    const rMaktx = row.dataset.maktx || ''; 
                    const rSisa = parseFloat(row.dataset.sisaQty) || 0;
                    const rVgw01 = parseFloat(row.dataset.vgw01) || 0; 
                    const rVge01 = row.dataset.vge01 || ''; 

                    const card = document.createElement('div');
                    card.className = 'card mb-3 border-0 shadow-sm pro-card';
                    card.dataset.refAufnr = rAufnr;
                    card.dataset.maxQty = rSisa; 
                    card.dataset.vgw01 = rVgw01; 
                    card.dataset.vge01 = rVge01; 
                    card.dataset.targetWc = targetWcId; 
                    card.dataset.hasChildren = hasChildren;

                    card.innerHTML = `
                        <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold small"><i class="fa-solid fa-box me-1"></i> ${rAufnr} - ${rMaktx}</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary border border-light">Max: ${rSisa}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 text-white p-0 ms-2" style="width: 20px; height: 20px; line-height: 1;" onclick="removeProFromModal(this)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-2 bg-light">
                            <!-- Rows Container -->
                            <div class="assignment-rows">
                                <!-- Initial Row -->
                                <div class="row g-2 align-items-end mb-2 assignment-row">
                                    <div class="col-md-4">
                                        <label class="small text-muted fw-bold mb-0 d-none d-md-block">Operator</label>
                                        <select class="form-select form-select-sm emp-select shadow-none border-secondary" required onchange="updateEmpOptions('${rAufnr}')">
                                            ${empOptions}
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted fw-bold mb-0 d-none d-md-block">Sub-WC</label>
                                        <select class="form-select form-select-sm child-select shadow-none border-secondary" onchange="updateEmpOptions('${rAufnr}'); updateCardSummary('${rAufnr}')" ${!hasChildren ? 'disabled' : ''}>
                                            ${childOptionsHtml}
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small text-muted fw-bold mb-0 d-none d-md-block">Qty</label>
                                        <input type="number" class="form-control form-control-sm qty-input shadow-none border-secondary fw-bold text-center" 
                                            value="${rSisa}" max="${rSisa}" min="1" step="1" oninput="updateCardSummary('${rAufnr}')" ${hasChildren ? 'disabled title="Pilih Sub-WC terlebih dahulu"' : ''}>
                                    </div>
                                    <!-- NEW: Time Field (Auto Calc) -->
                                    <div class="col-md-2">
                                        <label class="small text-muted fw-bold mb-0 d-none d-md-block">Time (Min)</label>
                                        <input type="text" class="form-control form-control-sm time-input shadow-none border-secondary text-center bg-white text-dark fw-bold" value="0" readonly>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <button class="btn btn-outline-danger btn-sm btn-remove-row d-none w-100 p-1 mb-1" type="button" onclick="removeAssignmentRow(this)" title="Remove">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add Button / Summary -->
                             <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                <button class="btn btn-sm btn-outline-primary border-dashed fw-bold px-3 btn-add-split" type="button" onclick="addAssignmentRow('${rAufnr}')" ${hasChildren ? 'disabled' : ''}>
                                    <i class="fa-solid fa-plus-circle me-1"></i> Add Split
                                </button>
                                <div class="small fw-bold text-muted">
                                    Remaining: <span class="qty-remaining text-success">${rSisa}</span>
                                </div>
                             </div>
                        </div>
                    `;
                    container.appendChild(card);
                    if(hasChildren) updateChildWCOptions(rAufnr);
                    updateEmpOptions(rAufnr);
                    updateCardSummary(rAufnr); 
                });

                assignmentModalInstance.show();
            }
            window.addAssignmentRow = function(aufnr) {
                const card = document.querySelector(`.pro-card[data-ref-aufnr="${aufnr}"]`);
                if (!card) return;

                const rowsContainer = card.querySelector('.assignment-rows');
                const lastRow = rowsContainer.lastElementChild;
                
                const newRow = lastRow.cloneNode(true);
                
                const empSelect = newRow.querySelector('.emp-select');
                empSelect.value = "";
                empSelect.onchange = function() { updateEmpOptions(aufnr); };
                
                const childSelect = newRow.querySelector('.child-select');
                childSelect.value = "";
                childSelect.disabled = false;
                childSelect.classList.remove('border-danger', 'text-danger');
                childSelect.onchange = function() { updateEmpOptions(aufnr); updateCardSummary(aufnr); }; 

                const qtyInput = newRow.querySelector('.qty-input');
                qtyInput.value = ""; 
                qtyInput.placeholder = "0";
                qtyInput.classList.remove('is-invalid', 'text-danger');
                qtyInput.oninput = function() { updateCardSummary(aufnr); };
                
                const hasChildren = card.dataset.hasChildren === 'true';
                if(hasChildren) {
                    qtyInput.disabled = true;
                    qtyInput.title = "Pilih Sub-WC terlebih dahulu";
                } else {
                    qtyInput.disabled = false;
                    qtyInput.title = "";
                }

                const timeInput = newRow.querySelector('.time-input');
                timeInput.value = "0 Min";

                const removeBtn = newRow.querySelector('.btn-remove-row');
                removeBtn.classList.remove('d-none');
                
                rowsContainer.appendChild(newRow);
                
                updateChildWCOptions(aufnr);
                updateCardSummary(aufnr);
                updateEmpOptions(aufnr); 
            };

            window.removeAssignmentRow = function(btn) {
                const row = btn.closest('.assignment-row');
                const card = row.closest('.pro-card');
                const aufnr = card.dataset.refAufnr;
                
                row.remove();
                
                updateEmpOptions(aufnr);
                updateCardSummary(aufnr);
            };

            window.updateCardSummary = function(TriggerAufnr) {
                const allCards = document.querySelectorAll('#assignmentCardContainer .pro-card');
                let globalChildUsage = {}; 
                let targetWcId = null;

                // 1. Determine Target WC (from first card in modal)
                if (allCards.length > 0) {
                    targetWcId = allCards[0].dataset.targetWc;
                }

                // 2. Pre-calculate usage from ALREADY assigned items in the box (Main Page)
                if (targetWcId) {
                    const mainContainer = document.querySelector(`.wc-card-container[data-wc-id="${targetWcId}"]`);
                    if (mainContainer) {
                        const existingItems = mainContainer.querySelectorAll('.pro-item-card');
                        const modalAufnrs = Array.from(allCards).map(c => c.dataset.refAufnr);

                        existingItems.forEach(item => {
                            const itemAufnr = item.dataset.aufnr;
                            // Skip items currently being edited in the modal
                            if (modalAufnrs.includes(itemAufnr)) return;

                            // Skip items that are just being dropped (if any mismatch) - logic check
                            // Just check data-child-wc
                            const assignedChild = item.dataset.childWc;
                            if (assignedChild) {
                                const mins = parseFloat(item.dataset.calculatedMins) || 0;
                                globalChildUsage[assignedChild] = (globalChildUsage[assignedChild] || 0) + mins;
                            }
                        });
                    }
                }
                
                allCards.forEach(card => {
                    if (!targetWcId) targetWcId = card.dataset.targetWc; 
                    const vgw01 = parseFloat(card.dataset.vgw01) || 0;
                    const vge01 = card.dataset.vge01 || '';
                    const hasChildren = card.dataset.hasChildren === 'true';
                    const maxQty = parseFloat(card.dataset.maxQty) || 0;

                    const rows = card.querySelectorAll('.assignment-row');
                    
                    let currentCardTotal = 0;
                    rows.forEach(r => currentCardTotal += parseFloat(r.querySelector('.qty-input').value) || 0);

                    rows.forEach(row => {
                        const qtyInp = row.querySelector('.qty-input');
                        const timeInp = row.querySelector('.time-input');
                        const childSel = row.querySelector('.child-select');
                        
                        const subWc = childSel.value;
                        if (hasChildren) {
                            if (!subWc) {
                                if (!qtyInp.disabled) {
                                    qtyInp.value = ""; 
                                    qtyInp.disabled = true;
                                    qtyInp.title = "Pilih Sub-WC terlebih dahulu";
                                    timeInp.value = "0 Min"; 
                                }
                                return; 
                            } else {
                                if (qtyInp.disabled) {
                                    qtyInp.disabled = false;
                                    qtyInp.title = "";
                                }
                            }
                        }

                        let qty = parseFloat(qtyInp.value) || 0;
                        const activeElement = document.activeElement;
                        if (activeElement === qtyInp) {
                            if (currentCardTotal > maxQty + 0.0001) {
                                const otherTotal = currentCardTotal - qty;
                                let allowed = maxQty - otherTotal;
                                if (allowed < 0) allowed = 0;
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Limit Exceeded',
                                    text: `Max Quantity is ${maxQty}. Remaining: ${allowed.toFixed(3)}`,
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                                
                                qtyInp.value = allowed; 
                                qty = allowed; 
                                currentCardTotal = otherTotal + allowed; 
                            }
                        }
                        
                        // CALC TIME
                        let timeMins = (qty * vgw01);
                        if (vge01 === 'S') {
                            timeMins = timeMins / 60;
                        }
                        timeMins = parseFloat(timeMins.toFixed(3));
                        
                        timeInp.value = timeMins + ' Min'; 
                        
                        if (subWc) {
                            globalChildUsage[subWc] = (globalChildUsage[subWc] || 0) + timeMins;
                        }
                    });
                });

                if (targetWcId) {
                    const normalizedTargetWc = targetWcId.toUpperCase();
                    if (PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWc)) {
                        const children = PARENT_WORKCENTERS[normalizedTargetWc]; 
                        
                        children.forEach(child => {
                            const childCode = child.code;
                            const thisChildLimit = 570; // FIXED RULE
                            
                            const usage = globalChildUsage[childCode] || 0;
                            const pct = thisChildLimit > 0 ? (usage / thisChildLimit) * 100 : 0;
                            
                            const bar = document.getElementById(`cap-bar-${childCode}`);
                            const text = document.getElementById(`cap-text-${childCode}`);
                            
                            if (bar && text) {
                                bar.style.width = Math.min(pct, 100) + '%';
                                text.innerText = `${usage.toFixed(1)} / ${thisChildLimit.toFixed(1)} Min`;
                                
                                if (usage > thisChildLimit) {
                                    bar.className = 'progress-bar bg-danger';
                                    text.classList.remove('text-warning');
                                    text.classList.add('text-danger');
                                } else {
                                    bar.className = 'progress-bar bg-success';
                                    text.classList.remove('text-danger');
                                }
                            }
                        });
                    }
                }

                const card = document.querySelector(`.pro-card[data-ref-aufnr="${TriggerAufnr}"]`);
                if (!card) return;

                const maxQty = parseFloat(card.dataset.maxQty) || 0;
                const remainingSpan = card.querySelector('.qty-remaining');
                const addBtn = card.querySelector('.btn-add-split');
                const inputs = card.querySelectorAll('.qty-input');
                
                let totalAssigned = 0;
                inputs.forEach(inp => totalAssigned += parseFloat(inp.value) || 0);
                const remaining = maxQty - totalAssigned;
                
                remainingSpan.innerText = remaining.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                
                if (remaining < -0.0001) {
                    remainingSpan.classList.remove('text-success');
                    remainingSpan.classList.add('text-danger');
                } else {
                    remainingSpan.classList.remove('text-danger');
                    remainingSpan.classList.add('text-success');
                }

                if (addBtn) {
                     if(remaining <= 0.0001) {
                        addBtn.disabled = true;
                        addBtn.classList.add('disabled');
                     } else {
                        addBtn.disabled = false;
                        addBtn.classList.remove('disabled');
                     }
                }

                // 4. GLOBAL BUTTON STATE
                validateGlobalAssignmentState();
            };

            window.validateGlobalAssignmentState = function() {
                const confirmBtn = document.getElementById('btnConfirmFinalAssignment');
                if (!confirmBtn) return;

                let hasError = false;
                
                 // Check 1: Max Qty on all cards
                const cards = document.querySelectorAll('#assignmentCardContainer .pro-card');
                cards.forEach(card => {
                     const maxQty = parseFloat(card.dataset.maxQty) || 0;
                     const inputs = card.querySelectorAll('.qty-input');
                     let totalAssigned = 0;
                     inputs.forEach(inp => totalAssigned += parseFloat(inp.value) || 0);
                     if (totalAssigned > maxQty + 0.0001) hasError = true;
                });
                
                const redBars = document.querySelectorAll('#topCapacityContainer .bg-danger');
                if (redBars.length > 0) hasError = true;

                confirmBtn.disabled = hasError;
            };

            window.updateChildWCOptions = function(aufnr) {
                const card = document.querySelector(`.pro-card[data-ref-aufnr="${aufnr}"]`);
                if (!card) return;

                const selects = card.querySelectorAll('.child-select');
                
                // 1. Gather all currently used NIKs in this CARD
                const allEmpSelects = card.querySelectorAll('.emp-select');
                const usedNiks = Array.from(allEmpSelects).map(s => s.value).filter(v => v !== "");

                const empTemplate = document.getElementById('employeeTemplateSelect');
                const wcPoolMap = {};
                
                Array.from(empTemplate.options).forEach(opt => {
                    if (opt.value === "") return;
                    const arbpl = opt.dataset.arbpl;
                    if (arbpl) {
                        if (!wcPoolMap[arbpl]) wcPoolMap[arbpl] = [];
                        wcPoolMap[arbpl].push(opt.value);
                    }
                });

                // 3. Update each Child WC Select
                selects.forEach(sel => {
                    const myCurrentWc = sel.value; // Keep selected even if full
                    const options = sel.querySelectorAll('option');
                    
                    options.forEach(opt => {
                        if (opt.value === "") return; 
                        
                        const wcCode = opt.value;
                        const potentialNiks = wcPoolMap[wcCode] || [];
                        
                        const usedCount = usedNiks.filter(nik => potentialNiks.includes(nik)).length;
                        const totalCap = potentialNiks.length;
                        const remaining = totalCap - usedCount;

                        const isFull = remaining <= 0;
                        const isMySelection = (wcCode === myCurrentWc);

                        if (isFull && !isMySelection) {
                            opt.disabled = true;
                            if (!opt.innerText.includes('(Full)')) {
                                opt.innerText = opt.innerText.replace(' (Full)', '') + ' (Full)';
                            }
                        } else {
                            opt.disabled = false;
                            opt.innerText = opt.innerText.replace(' (Full)', '');
                        }
                    });
                });
            };

            function setupMismatchLogic() {
                document.getElementById('btnCancelMismatch').addEventListener('click', function() {
                    mismatchModalInstance.hide();
                    cancelDrop();
                });
                document.getElementById('btnChangeWC').addEventListener('click', async function() {
                    const itemsToProcess = (draggedItemsCache && draggedItemsCache.length > 0) 
                        ? draggedItemsCache 
                        : (pendingMismatchItem ? [pendingMismatchItem] : []);

                    if (itemsToProcess.length === 0) return;

                    const targetWc = document.getElementById('mismatchTargetWC').value;
                    
                    const proData = itemsToProcess.map(item => ({
                        proCode: item.dataset.aufnr,
                        oper: item.dataset.vornr || '0010',
                        pwwrk: item.dataset.pwwrk || ''
                    }));

                    mismatchModalInstance.hide();
                    cancelDrop();

                    await executeBulkChangeStream(targetWc, proData);
                    itemsToProcess.forEach(item => {
                         const pendingRows = document.querySelectorAll(`[data-aufnr="${item.dataset.aufnr}"]`);
                         pendingRows.forEach(r => {
                            r.dataset.arbpl = targetWc;
                            const badge = r.querySelector('.badge.bg-light.text-dark');
                            if(badge && badge.innerText.trim() !== targetWc) badge.innerText = targetWc; 
                        });
                    });

                    processDrop(
                        null, // evt null
                        itemsToProcess[0],
                        targetContainerCache,
                        sourceContainerCache,
                        targetWc
                    );
                });
            }

            function updateChildWCDropdown(normalizedTargetWcId, proAufnr) {
                updateEmpOptions(proAufnr);

                const childWcSelect = document.getElementById('childWorkcenterSelect');
                if(!childWcSelect) return; // Guard clause

                childWcSelect.innerHTML = '<option value="" selected disabled>Pilih Workcenter Anak...</option>';
                childWcSelect.disabled = false;

                const usedChildWCs = getAssignedChildWCs(proAufnr);
                let optionsAdded = 0;
                
                if (PARENT_WORKCENTERS[normalizedTargetWcId]) {
                    PARENT_WORKCENTERS[normalizedTargetWcId].forEach(child => {
                         // HANYA TAMBAHKAN JIKA BELUM DIGUNAKAN
                        if (!usedChildWCs.includes(child.code)) {
                            const option = document.createElement('option');
                            option.value = child.code;
                            option.innerText = `${child.code} - ${child.name}`;
                            childWcSelect.appendChild(option);
                            optionsAdded++;
                        }
                    });
                }

                if (optionsAdded === 0) {
                    childWcSelect.innerHTML =
                        '<option value="" selected disabled>Semua Sub-WC sudah dialokasikan untuk PRO ini</option>';
                    childWcSelect.disabled = true;
                }
            }
            
            function updateEmpOptions(aufnr) {
                const card = document.querySelector(`.pro-card[data-ref-aufnr="${aufnr}"]`);
                if (!card) return;

                const hasChildren = card.dataset.hasChildren === 'true';
                const parentTargetWc = card.dataset.targetWc; // WC Induk / Target Utama
                
                // Get fresh template options
                const templateSelect = document.getElementById('employeeTemplateSelect');
                const templateOptions = Array.from(templateSelect.options);

                // Collect selected NIKs across rows to disable duplicates
                const allSelects = Array.from(card.querySelectorAll('.emp-select'));
                const selectedNiks = allSelects
                    .map(sel => sel.value)
                    .filter(val => val !== "");

                allSelects.forEach(select => {
                    const row = select.closest('.assignment-row');
                    const childSelect = row.querySelector('.child-select');
                    const currentVal = select.value;
                    
                    let requiredArbpl = null;

                    // 1. Determine Filtering Criteria
                    if (hasChildren) {
                        const subWc = childSelect.value;
                        if (!subWc) {
                            // Enforce Sub-WC selection first
                            select.innerHTML = '<option value="" selected disabled>Pilih Sub-WC terlebih dahulu...</option>';
                            select.disabled = true;
                            return; // Stop processing for this select
                        } else {
                            requiredArbpl = subWc;
                            select.disabled = false;
                        }
                    } else {
                         // Single WC (No Children) -> Filter by Target WC
                         requiredArbpl = parentTargetWc;
                         select.disabled = false;
                    }

                    select.innerHTML = '<option value="" selected disabled>Pilih Operator...</option>';

                    templateOptions.forEach(tmplOpt => {
                        if (tmplOpt.value === "") return;

                        const empArbpl = tmplOpt.getAttribute('data-arbpl'); // Get from data attribute
                        const newOpt = tmplOpt.cloneNode(true);
                        
                        if (selectedNiks.includes(newOpt.value) && newOpt.value !== currentVal) {
                            newOpt.disabled = true;
                            newOpt.innerText += ' (Selected)';
                        }
                        
                        select.appendChild(newOpt);
                    });
                    let valid = false;
                    Array.from(select.options).forEach(opt => {
                        if (opt.value === currentVal && currentVal !== "") valid = true;
                    });
                    
                    if (valid) {
                        select.value = currentVal;
                    } else if (currentVal !== "") {
                        select.value = "";
                    }
                });
            }

            function setupModalLogic() {
                const btnConfirm = document.getElementById('btnConfirmFinalAssignment');
                const btnCancel = document.getElementById('btnCancelDrop');
                if (btnConfirm) btnConfirm.onclick = confirmFinalAssignment;
                if (btnCancel) btnCancel.onclick = cancelDrop;
            }


            function confirmFinalAssignment() {
                const cards = document.querySelectorAll('#assignmentCardContainer .pro-card');
                let allValid = true;
                let assignments = [];
                let validationErrors = [];
                
                cards.forEach(card => {
                    const aufnr = card.dataset.refAufnr;
                    const maxQty = parseFloat(card.dataset.maxQty) || 0;
                    
                    const rows = card.querySelectorAll('.assignment-rows .assignment-row');
                    let currentSum = 0;
                    
                    rows.forEach(row => {
                        const empSelect = row.querySelector('.emp-select');
                        const childSelect = row.querySelector('.child-select');
                        const qtyInput = row.querySelector('.qty-input');
                        
                        const nik = empSelect.value;
                        const name = empSelect.options[empSelect.selectedIndex]?.dataset.name || '';
                        const rawText = empSelect.options[empSelect.selectedIndex]?.innerText || '';
                        
                        const childWc = childSelect.value;
                        const qty = parseFloat(qtyInput.value) || 0;
                        
                        if (!nik || qty < 1) {
                            allValid = false;
                            row.classList.add('border', 'border-danger');
                            if(qty < 1) {
                                validationErrors.push(`Quantity minimal 1 untuk item ${aufnr}`);
                            }
                        } else {
                            row.classList.remove('border', 'border-danger');
                        }
                        
                        // Collect Assignment
                        if (nik && qty >= 1) {
                            const item = draggedItemsCache.find(i => i.dataset.aufnr == aufnr); 
                            
                            if (item) {
                                assignments.push({ item, nik, name: name || rawText, childWc, qty });
                            }
                        }
                        
                        currentSum += qty;
                    });
                    
                    if (currentSum > maxQty) {
                        allValid = false;
                        validationErrors.push(`Total Qty for ${aufnr} exceeds limit (${currentSum} > ${maxQty})`);
                        card.classList.remove('border-0');
                        card.classList.add('border-danger');
                    } else {
                        card.classList.add('border-0');
                        card.classList.remove('border-danger');
                    }
                });

                if (!allValid) {
                     Swal.fire('Validation Error', validationErrors.length > 0 ? validationErrors.join('<br>') : 'Please complete all fields correctly.', 'warning');
                     return;
                }
                 const groupedAssignments = {};
                assignments.forEach(a => {
                    if (!groupedAssignments[a.item.dataset.aufnr]) groupedAssignments[a.item.dataset.aufnr] = [];
                    groupedAssignments[a.item.dataset.aufnr].push(a);
                });

                for (const [aufnr, group] of Object.entries(groupedAssignments)) {
                     const item = group[0].item;
                     const sisaOriginal = parseFloat(item.dataset.sisaQty) || 0;
                     const totalAssigned = group.reduce((sum, a) => sum + a.qty, 0);
                     
                     if (parseFloat(totalAssigned.toFixed(3)) < parseFloat(sisaOriginal.toFixed(3))) {
                         const remainingQty = sisaOriginal - totalAssigned;
                         const remainingRow = item.cloneNode(true);
                         remainingRow.dataset.id = Date.now() + Math.random();
                         remainingRow.dataset.sisaQty = remainingQty;
                         remainingRow.dataset.assignedQty = 0;
                         
                         transformToTableView(remainingRow);
                         const sisaCell = remainingRow.querySelector('.col-sisa-qty');
                         if(sisaCell) sisaCell.innerText = remainingQty.toLocaleString('id-ID');
                         
                         sourceContainerCache.appendChild(remainingRow);
                     }
                     
                     group.forEach((assign, idx) => {
                         let targetItem;
                         if (idx === 0) {
                             targetItem = item;
                         } else {
                             targetItem = item.cloneNode(true);
                             targetItem.dataset.id = Date.now() + Math.random();
                         }
                         
                         transformToCardView(targetItem);
                         targetContainerCache.appendChild(targetItem);
                         
                         targetItem.dataset.sisaQty = assign.qty;
                         targetItem.dataset.assignedQty = assign.qty;
                         targetItem.dataset.employeeNik = assign.nik;
                         targetItem.dataset.employeeName = assign.name;
                         targetItem.dataset.childWc = assign.childWc || '';
                         
                         updateRowUI(targetItem, assign.name, assign.qty, assign.childWc);
                     });
                }

                // Cleanup
                draggedItemsCache = [];
                document.getElementById('selectAll').checked = false;
                updateCapacity(targetContainerCache.closest('.wc-card-container'));
                checkEmptyPlaceholder(targetContainerCache);
                checkEmptyPlaceholder(sourceContainerCache);
                
                assignmentModalInstance.hide();
            }

            function setupTargetWcSearch() {
                const searchInput = document.getElementById('targetWcSearchInput');
                if (!searchInput) return;

                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase().trim();
                    const cards = document.querySelectorAll('.wc-card-container');

                    cards.forEach(card => {
                        const wcCode = (card.dataset.wcId || '').toLowerCase();
                        // Also search in description
                        const descriptionEl = card.querySelector('.text-muted.text-xs.text-truncate');
                        const description = descriptionEl ? descriptionEl.innerText.toLowerCase() : '';

                        if (wcCode.includes(term) || description.includes(term)) {
                            card.classList.remove('d-none');
                        } else {
                            card.classList.add('d-none');
                        }
                    });
                });
            }

            window.removeProFromModal = function(btn) {
                const card = btn.closest('.pro-card');
                if(!card) return;

                const aufnr = card.dataset.refAufnr;
                const container = document.getElementById('assignmentCardContainer');
                const proCards = Array.from(container.querySelectorAll('.pro-card'));
                const cardIndex = proCards.indexOf(card);
                
                if (cardIndex > -1 && draggedItemsCache[cardIndex]) {
                    const item = draggedItemsCache[cardIndex];
                    handleReturnToTable(item, sourceContainerCache);
                    draggedItemsCache.splice(cardIndex, 1);
                    card.remove();
                    if (draggedItemsCache.length > 0) {
                        const proTitle = draggedItemsCache.length > 1 
                            ? `<span class="badge bg-primary">${draggedItemsCache.length} Items Selected</span>` 
                            : `<span class="badge bg-primary text-wrap">${draggedItemsCache[0].dataset.aufnr}</span>`;
                        document.getElementById('modalProDetails').innerHTML = proTitle;
                        
                        const bulkWarning = document.getElementById('bulkWarning');
                        if (draggedItemsCache.length > 1) {
                            bulkWarning.classList.remove('d-none');
                        } else {
                            bulkWarning.classList.add('d-none');
                        }
                    } else {
                        assignmentModalInstance.hide();
                    }
                }
            };

            function updateAssignedChildWCs(aufnr, newChildWc) {
                if (!newChildWc) return;

                document.querySelectorAll(`[data-aufnr="${aufnr}"]`).forEach(row => {
                    let assignedWCs = [];
                    try {
                        assignedWCs = JSON.parse(row.dataset.assignedChildWcs || '[]');
                    } catch (e) {
                        assignedWCs = [];
                    }

                    if (!assignedWCs.includes(newChildWc)) {
                        assignedWCs.push(newChildWc);
                    }

                    row.dataset.assignedChildWcs = JSON.stringify(assignedWCs);
                });
            }


            window.cancelDrop = function() { 
                console.log("Assignment cancelled. Returning items to source and resetting splits.");
                const qtyToReturn = tempSplits.reduce((sum, split) => sum + split.qty, 0);
                currentSisaQty += qtyToReturn;
                tempSplits = [];
                const item = draggedItemsCache[0];
                if (sourceContainerCache) sourceContainerCache.appendChild(item);
                item.dataset.employeeNik = "";
                item.dataset.employeeName = "";
                item.dataset.childWc = "";
                item.dataset.assignedQty = 0;
                const originalQtyForModal = (parseFloat(item.dataset.sisaQty) || 0) + qtyToReturn;
                item.dataset.sisaQty = originalQtyForModal;
                const sisaCell = item.querySelector('.col-sisa-qty');
                if (sisaCell) {
                    sisaCell.innerText = originalQtyForModal.toLocaleString('id-ID');
                    sisaCell.classList.remove('text-success');
                    sisaCell.classList.add('text-danger');
                }

                transformToTableView(item);
                draggedItemsCache = [];
                currentSisaQty = 0;
                assignmentModalInstance.hide();
            }

            function resetAllAllocations() {
                if (!confirm('Apakah Anda yakin ingin mereset semua PRO yang ditugaskan ke table?')) return;
                const allWcContainers = Array.from(document.querySelectorAll('.wc-card-container'));

                allWcContainers.forEach(wcContainer => {
                    const zone = wcContainer.querySelector('.wc-drop-zone');
                    const items = Array.from(zone.querySelectorAll('.pro-item-card'));
                    const sourceList = document.getElementById('source-list');

                    items.forEach(item => {
                        const originalQtyOpr = parseFloat(item.dataset.qtyOpr) || 0;
                        const aufnr = item.dataset.aufnr;
                        let existingSourceItem = sourceList.querySelector(`tr.pro-item[data-aufnr="${aufnr}"]`);

                        if (existingSourceItem) {
                            let currentSisaQty = parseFloat(existingSourceItem.dataset.sisaQty) || 0;
                            const qtyAssignedDiHapus = parseFloat(item.dataset.assignedQty) || 0;
                            const newSisaQty = currentSisaQty + qtyAssignedDiHapus;

                            existingSourceItem.dataset.sisaQty = newSisaQty;
                            existingSourceItem.dataset.assignedChildWcs = '[]';

                            const sisaCell = existingSourceItem.querySelector('.col-sisa-qty');
                            if (sisaCell) {
                                sisaCell.innerText = newSisaQty.toLocaleString('id-ID');
                                sisaCell.classList.remove('text-success');
                                sisaCell.classList.add('text-danger');
                            }

                            item.remove();
                        } else {
                            sourceList.appendChild(item);

                            item.dataset.employeeNik = "";
                            item.dataset.employeeName = "";
                            item.dataset.childWc = "";
                            item.dataset.assignedQty = 0;
                            item.dataset.assignedChildWcs = '[]';
                            item.dataset.sisaQty = originalQtyOpr;

                            const sisaCell = item.querySelector('.col-sisa-qty');
                            if (sisaCell) {
                                sisaCell.innerText = originalQtyOpr.toLocaleString('id-ID');
                                sisaCell.classList.remove('text-success');
                                sisaCell.classList.add('text-danger');
                            }

                            transformToTableView(item);
                        }
                    });

                    updateCapacity(wcContainer);
                    checkEmptyPlaceholder(zone);
                });
            }

            function handleReturnToTable(item, fromContainer) {
                
                let wcId = 'Unknown';
                if (fromContainer) {
                    const container = fromContainer.closest('.wc-card-container');
                    if (container) {
                        wcId = container.dataset.wcId;
                    }
                }
                const returnedChildWc = item.dataset.childWc;
                const existingSourceItems = document.querySelectorAll(`#source-list .pro-item[data-aufnr="${item.dataset.aufnr}"]`);
                let sourceItem = null;
                existingSourceItems.forEach(el => {
                    if(el !== item) sourceItem = el;
                });

                const returnedQty = parseFloat(item.dataset.assignedQty) || 0;

                const targetItemToUpdate = sourceItem || item;
                let sisaQtyOriginal = parseFloat(targetItemToUpdate.dataset.sisaQty) || 0;
        
                if (returnedQty > 0) {
                     sisaQtyOriginal = parseFloat((sisaQtyOriginal + returnedQty).toFixed(3));
                } else if (targetItemToUpdate === item && returnedQty === 0) {

                }

                targetItemToUpdate.dataset.sisaQty = sisaQtyOriginal;

                const sisaCell = targetItemToUpdate.querySelector('.col-sisa-qty');
                if (sisaCell) {
                    sisaCell.innerText = sisaQtyOriginal.toLocaleString('id-ID');
                    sisaCell.classList.remove('text-success');
                    sisaCell.classList.add('text-danger');
                }

                if (returnedChildWc) {
                    removeAssignedChildWC(item.dataset.aufnr, returnedChildWc);
                }

                if (targetItemToUpdate !== item) {
                    item.remove();
                } else {
                    targetItemToUpdate.dataset.employeeNik = "";
                    targetItemToUpdate.dataset.employeeName = "";
                    targetItemToUpdate.dataset.childWc = "";
                    targetItemToUpdate.dataset.assignedQty = 0;
                    transformToTableView(targetItemToUpdate);
                }

                if (fromContainer) {
                    updateCapacity(fromContainer.closest('.wc-card-container'));
                    checkEmptyPlaceholder(fromContainer);
                }
            }

            function removeAssignedChildWC(aufnr, childWcToRemove) {
                document.querySelectorAll(`[data-aufnr="${aufnr}"]`).forEach(row => {
                    let assignedWCs = [];
                    try {
                        assignedWCs = JSON.parse(row.dataset.assignedChildWcs || '[]');
                    } catch (e) {
                        assignedWCs = [];
                    }

                    const index = assignedWCs.indexOf(childWcToRemove);
                    if (index > -1) {
                        assignedWCs.splice(index, 1);
                    }

                    row.dataset.assignedChildWcs = JSON.stringify(assignedWCs);
                });
            }

            function updateRowUI(row, name, qty, childWc) {
                row.querySelector('.employee-name-text').innerText = name;
                row.querySelector('.assigned-qty-badge').innerText = 'Qty: ' + qty;
                
                const childDisplay = row.querySelector('.child-wc-display');
                if(childWc) {
                    childDisplay.innerText = childWc;
                    childDisplay.classList.remove('d-none');
                } else {
                    childDisplay.classList.add('d-none');
                }
                
                const mins = calculateItemMinutes(row, qty);
                row.dataset.calculatedMins = mins;
            }

            function transformToCardView(row) {
                row.classList.remove('draggable-item');
                row.classList.add('pro-item-card');
                row.querySelectorAll('.table-col').forEach(el => el.style.display = 'none');
                row.querySelector('.card-view-content').style.display = 'block';
                const handle = row.querySelector('.drag-handle');
                if (handle) handle.style.display = 'none';
            }

            function transformToTableView(row) {
                row.classList.add('draggable-item');
                row.classList.remove('pro-item-card');
                row.querySelectorAll('.table-col').forEach(el => el.style.display = '');
                row.querySelector('.card-view-content').style.display = 'none';
                const handle = row.querySelector('.drag-handle');
                if (handle) handle.style.display = '';
            }

            function calculateItemMinutes(row, qtyOverride = null) {
                const parseNum = (str) => {
                    if (!str) return 0;
                    let stringVal = String(str).trim();
                    if (stringVal.includes(',')) {
                        stringVal = stringVal.replace(/\./g, '').replace(/,/g, '.');
                    }
                    return parseFloat(stringVal) || 0;
                };
                const vgw01 = parseNum(row.dataset.vgw01);
                const vge01 = (row.dataset.vge01 || '').toUpperCase();
                let rawQty = (qtyOverride !== null) ? qtyOverride : row.dataset.sisaQty;
                const qty = parseNum(rawQty);

                if (vgw01 > 0 && qty > 0) {
                    let totalRaw = vgw01 * qty;
                    if (vge01 === 'S' || vge01 === 'SEC') return totalRaw / 60;
                    else if (vge01 === 'MIN') return totalRaw;
                    else if (vge01 === 'H' || vge01 === 'HUR') return totalRaw * 60;
                    else return totalRaw * 60;
                }
                return 0;
            }

            function calculateAllRows() {
               document.querySelectorAll('#source-list tr.pro-item').forEach(row => {
                   const sisaQty = parseFloat(row.dataset.sisaQty) || 0;
                   const mins = calculateItemMinutes(row, sisaQty);
                   row.dataset.calculatedMins = mins;
                   row.dataset.currentQty = sisaQty; 
               });
               document.querySelectorAll('.wc-card-container').forEach(updateCapacity);
            }

            function updateCapacity(cardContainer) {
                if (!cardContainer) return;
                const wcId = cardContainer.dataset.wcId;
                const kapazHours = parseFloat(cardContainer.dataset.kapazWc) || 0;
                const maxMins = kapazHours * 60;

                let currentLoad = 0;
                cardContainer.querySelectorAll('.pro-item-card').forEach(item => {
                    currentLoad += parseFloat(item.dataset.calculatedMins) || 0;
                });

                const pct = maxMins > 0 ? (currentLoad / maxMins) * 100 : 0;
                const bar = document.getElementById(`progress-${wcId}`);
                const lbl = document.getElementById(`label-cap-${wcId}`);

                if (lbl) lbl.innerText = `${Math.ceil(currentLoad)} / ${Math.ceil(maxMins)} Min`;
                if (bar) {
                    bar.style.width = Math.min(pct, 100) + "%";
                    // Change color logic: Green (<70%), Yellow (<100%), Red (>=100%)
                    let colorClass = 'bg-success';
                    if (pct >= 100) {
                        colorClass = 'bg-danger';
                        if(lbl) lbl.classList.add('text-danger', 'fw-bold');
                    } else if (pct >= 70) {
                        colorClass = 'bg-warning';
                         if(lbl) lbl.classList.remove('text-danger', 'fw-bold');
                    } else {
                         if(lbl) lbl.classList.remove('text-danger', 'fw-bold');
                    }
                    bar.className = 'progress-bar rounded-pill ' + colorClass;
                }
                
                const placeholder = cardContainer.querySelector('.empty-placeholder');
                const hasItems = cardContainer.querySelectorAll('.pro-item-card').length > 0;
                if (placeholder) placeholder.style.display = hasItems ? 'none' : 'flex';
            }

            function checkEmptyPlaceholder(container) {
                const placeholder = container.querySelector('.empty-placeholder');
                const hasItems = container.querySelectorAll('.pro-item, .pro-item-card').length > 0;
                if (placeholder) placeholder.style.display = hasItems ? 'none' : 'block';
            }

            function setupSearch() {
                let timeout = null;
                const searchInput = document.getElementById('searchInput');
                const scrollArea = document.querySelector('.table-scroll-area');
                const spinner = document.getElementById('loadingSpinner');
                const endOfData = document.getElementById('endOfData');
                const tbody = document.getElementById('source-list');

                let nextPage = {{ $nextPage ?? 'null' }};
                let isLoading = false;
                let currentSearch = '';

                // Main Search Listener
                searchInput.addEventListener('keyup', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        currentSearch = this.value;
                        resetAndFetch();
                    }, 500); 
                });

                // Advanced Search Listeners
                const advIds = ['advAufnr', 'advMatnr', 'advMaktx', 'advArbpl', 'advKdauf', 'advKdpos'];
                advIds.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) {
                        el.addEventListener('keyup', function() {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => {
                                resetAndFetch();
                            }, 500);
                        });
                    }
                });

                // Clear Function (Global)
                window.clearAdvancedSearch = function() {
                    advIds.forEach(id => {
                        const el = document.getElementById(id);
                        if(el) el.value = '';
                    });
                    resetAndFetch();
                };

                // Toggle Function (Global)
                window.toggleAdvancedSearch = function() {
                    const box = document.getElementById('advancedSearchCollapse');
                    const icon = document.getElementById('advSearchIcon');
                    if(box) {
                        if(box.classList.contains('d-none')) {
                            box.classList.remove('d-none');
                            if(icon) icon.className = "fa-solid fa-chevron-up text-xs";
                        } else {
                            box.classList.add('d-none');
                            if(icon) icon.className = "fa-solid fa-chevron-down text-xs";
                        }
                    }
                };

                scrollArea.addEventListener('scroll', function() {
                    if (isLoading || !nextPage) return;
                    
                    const threshold = 100; 
                    if (this.scrollHeight - this.scrollTop - this.clientHeight < threshold) {
                         fetchMore();
                    }
                });

                function resetAndFetch() {
                    nextPage = 1; 
                    isLoading = true;
                    tbody.innerHTML = ''; // Clear current
                    spinner.classList.remove('d-none');
                    endOfData.classList.add('d-none');
                    
                    // Main search value might be updated by keyup listener above,
                    // but let's grab it fresh to be sure if called from elsewhere
                    currentSearch = searchInput.value; 
                    
                    fetchData(1, currentSearch, true);
                }

                function fetchMore() {
                    isLoading = true;
                    spinner.classList.remove('d-none');
                    fetchData(nextPage, currentSearch, false);
                }

                function fetchData(page, search, isReset) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    
                    // Main Search
                    if(search) url.searchParams.set('search', search);
                    else url.searchParams.delete('search');

                    // Advanced Search Params
                    advIds.forEach(id => {
                        const val = document.getElementById(id)?.value?.trim();
                        // ID: advAufnr -> Param: adv_aufnr
                        const key = id.replace(/([A-Z])/g, '_$1').toLowerCase(); 
                        
                        if(val) url.searchParams.set(key, val);
                        else url.searchParams.delete(key);
                    });

                    fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (isReset) {
                            tbody.innerHTML = data.html;
                        } else {
                            tbody.insertAdjacentHTML('beforeend', data.html); // Append
                        }
                        
                        nextPage = data.next_page;
                        
                        if (!nextPage) {
                            endOfData.classList.remove('d-none');
                        }

                        // [UPDATED] Auto-Check if Global Select All is Active
                        if(window.isSelectAllActive) {
                             const newRows = tbody.querySelectorAll('.row-checkbox:not(:checked)');
                             newRows.forEach(cb => {
                                 cb.checked = true;
                                 cb.closest('tr').classList.add('selected-row');
                             });
                        }

                        
                    })
                    .catch(e => console.error('Load Error:', e))
                    .finally(() => {
                        isLoading = false;
                        spinner.classList.add('d-none');
                    });
                }
            }

            window.isSelectAllActive = false; // Global Flag

            function setupCheckboxes() {
                const selectAll = document.getElementById('selectAll');
                const selectedCountMsg = document.getElementById('selectedCountMsg'); // If exists in UI

                selectAll.addEventListener('change', function() {
                    window.isSelectAllActive = this.checked;
                    
                    // 1. Visually check/uncheck loaded rows
                    const visibleRows = Array.from(document.querySelectorAll('#source-list tr.draggable-item')).filter(r => r.style.display !== 'none');
                    visibleRows.forEach(row => {
                        const cb = row.querySelector('.row-checkbox');
                        if (cb) {
                            cb.checked = this.checked;
                            this.checked ? row.classList.add('selected-row') : row.classList.remove('selected-row');
                        }
                    });
                    
                    // 2. Feedback (Optional)
                    if(window.isSelectAllActive) {
                        // We don't know total count easily here without API call, 
                        // but we can say "All items selected".
                        console.log("Global Select All Active");
                    }
                });

                document.getElementById('source-list').addEventListener('change', (e) => {
                    if (e.target.classList.contains('row-checkbox')) {
                        const row = e.target.closest('tr');
                        e.target.checked ? row.classList.add('selected-row') : row.classList.remove('selected-row');
                        
                        // If user unchecks one, Global Select All is broken
                        if(!e.target.checked && window.isSelectAllActive) {
                             window.isSelectAllActive = false;
                             selectAll.checked = false;
                        }
                        
                        // If all loaded are checked, should we check SelectAll? 
                        // No, because we don't know if unloading/next pages are matches. 
                        // Keep simple: Manual Select All = Global. Manual Uncheck = Not Global.
                    }
                });
            }

            function saveAllocation(isFinalSave = false) {
                let allocationData = [];
                let totalWcCount = 0;

                document.querySelectorAll('.wc-card-container').forEach(card => {
                    const wcId = card.dataset.wcId; // WC Induk
                    const items = [];

                    card.querySelectorAll('.pro-item-card').forEach(item => {
                        const assignedQty = parseFloat(item.dataset.assignedQty) || 0;
                        const qtyOrder = parseFloat(item.dataset.psmng) || 0;
                        const takTimeMins = parseFloat(item.dataset.calculatedMins) || 0;

                        if (item.dataset.employeeNik && assignedQty > 0) {
                            items.push({
                                aufnr: item.dataset.aufnr,
                                nik: item.dataset.employeeNik,
                                name: item.dataset.employeeName,
                                assigned_qty: assignedQty,
                                material_number: item.dataset.matnr || '-',
                                material_desc: item.dataset.maktx || '-',
                                qty_order: item.dataset.sisaQty,
                                confirmed_qty: 0,
                                uom: item.dataset.meins || '-',
                                vornr: item.dataset.vornr || '-',
                                kdauf: item.dataset.kdauf || '-',
                                kdpos: item.dataset.kdpos || '-',
                                dispo: item.dataset.dispo || '-',
                                steus: item.dataset.steus || '-',
                                sssld: item.dataset.sssld || '-',
                                ssavd: item.dataset.ssavd || '-',
                                kapaz: item.dataset.kapaz || '-',
                                vgw01: item.dataset.vgw01 || '-',
                                vge01: item.dataset.vge01 || '',
                                name1: item.dataset.name1 || '-',
                                netpr: item.dataset.netpr || '-',
                                waerk: item.dataset.waerk || '-',
                                calculated_tak_time: takTimeMins.toFixed(2),
                                status_pro_wi: 'Created',
                                workcenter_induk: item.dataset.arbpl || wcId,
                                child_workcenter: item.dataset.childWc || null
                            });
                        }
                    });

                    if (items.length > 0) {
                        totalWcCount++;

                        const labelElement = card.querySelector(`#label-cap-${wcId}`);
                        let loadMinText = '0';

                        if (labelElement) {
                            loadMinText = labelElement.innerText.split('/')[0].trim();
                        }
                        const loadMins = Math.ceil(parseFloat(loadMinText));

                        allocationData.push({
                            workcenter: wcId,
                            pro_items: items,
                            load_mins: loadMins
                        });
                    }
                });

                if (isFinalSave) {
                    showPreviewModal(allocationData, totalWcCount);
                }

                return allocationData;
            }

            function sendFinalAllocation(data) {
                console.log('sendFinalAllocation called with', data);
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const documentDateInput = document.getElementById('wiDocumentDate');
                const documentDate = documentDateInput ? documentDateInput.value : '';
                const documentTime = document.getElementById('wiDocumentTime').value;

                if (!documentDate || !documentTime) { 
                    Swal.fire('Perhatian!', 'Tanggal dan Jam Dokumen harus diisi.', 'warning');
                    return; 
                }
                if (data.length === 0) {
                    Swal.fire('Perhatian!', 'Tidak ada alokasi yang dibuat.', 'warning');
                    return;
                }
                showPreviewModal(data, data.length);
            } // Close sendFinalAllocation

            const confirmBtn = document.getElementById('confirmSaveBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    console.log('confirmSaveBtn clicked! Calling startWiCreationStream...');
                    startWiCreationStream();
                });
            } else {
                console.error('confirmSaveBtn NOT FOUND in DOM!');
            }

            // --- STREAMING FUNCTION ---
            window.startWiCreationStream = async function() {
                console.log('startWiCreationStream STARTING...');
                const previewContent = document.getElementById('previewContent');
                if (!previewContent) return;

                const payload = [];
                const plantCode = '{{ $kode }}';
                
                const dateInput = document.getElementById('wiDocumentDate').value; 
                const timeInput = document.getElementById('wiDocumentTime').value;

                if (!window.latestAllocations || window.latestAllocations.length === 0) {
                     Swal.fire('Perhatian!', 'Tidak ada alokasi yang dibuat.', 'warning'); 
                     return;
                }

                // Close Preview Modal
                const previewModalEl = document.getElementById('previewModal');
                const previewModal = bootstrap.Modal.getInstance(previewModalEl);
                if (previewModal) previewModal.hide();

                // Open Progress Modal
                const progressModalEl = document.getElementById('streamProgressModal');
                const progressModal = new bootstrap.Modal(progressModalEl);
                progressModal.show();
                
                // Reset Progress UI
                const progressBar = document.getElementById('wiProgressBar');
                const statusText = document.getElementById('wiStatusText');
                const logArea = document.getElementById('wiLogArea');
                
                progressBar.style.width = '0%';
                progressBar.innerText = '0%';
                statusText.innerText = 'Initializing stream...';
                logArea.innerHTML = '';
                logArea.classList.remove('d-none');

                // Prepare Items for Stream (List of AUFNRs)
                const proItems = [];
                window.latestAllocations.forEach(alloc => {
                    alloc.pro_items.forEach(item => {
                        if (!proItems.some(p => p.aufnr === item.aufnr)) {
                            proItems.push({ aufnr: item.aufnr });
                        }
                    });
                });

                try {
                    // 2. Start Fetch Stream
                    const response = await fetch('{{ route("create-wi.stream-schedule") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            plant_code: plantCode,
                            date: dateInput,
                            time: timeInput,
                            items: proItems
                        })
                    });

                    console.log('Stream Response Status:', response.status);
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Server Error (${response.status}): ${errorText}`);
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n\n');
                        buffer = lines.pop(); // Keep incomplete chunk

                        for (const line of lines) {
                            if (line.startsWith('data: ')) {
                                const jsonStr = line.substring(6);
                                try {
                                    const data = JSON.parse(jsonStr);
                                    
                                    // Update UI
                                    if (data.progress !== undefined) {
                                        progressBar.style.width = data.progress + '%';
                                        progressBar.innerText = data.progress + '%';
                                    }
                                    if (data.message) {
                                        statusText.innerText = data.message;
                                        const logEntry = document.createElement('div');
                                        logEntry.innerText = `> ${data.message}`;
                                        if (data.status === 'error') logEntry.classList.add('text-danger');
                                        else if (data.status === 'success') logEntry.classList.add('text-success');
                                        
                                        logArea.appendChild(logEntry);
                                        logArea.scrollTop = logArea.scrollHeight;
                                    }
                                    
                                    if (data.completed) {
                                        statusText.innerText = "All items processed. Saving final document...";
                                    }

                                } catch (e) {
                                    console.error("Stream parse error", e);
                                }
                            }
                        }
                    }

                    // 3. Final Save (Create WI Document)
                    statusText.innerText = "Finalizing...";
                    
                    // We call the original save endpoint
                    const saveResponse = await fetch('{{ route("wi.save") }}', {
                         method: 'POST',
                         headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                         },
                         body: JSON.stringify({
                            plant_code: plantCode,
                            document_date: dateInput,
                            document_time: timeInput,
                            workcenter_allocations: window.latestAllocations
                         })
                    });
                    
                    const saveResult = await saveResponse.json();
                    
                    if (saveResponse.ok) {
                         progressBar.classList.remove('bg-primary');
                         progressBar.classList.add('bg-success');
                         statusText.innerText = "Work Instructions Created Successfully!";
                         
                         setTimeout(() => {
                            // Redirect
                            window.location.href = "{{ route('wi.history', $kode) }}";
                         }, 1500);
                    } else {
                         throw new Error(saveResult.message || "Save failed.");
                    }

                } catch (error) {
                    console.error(error);
                    statusText.innerText = "Error: " + error.message;
                    statusText.classList.add('text-danger');
                    progressBar.classList.add('bg-danger');
                }
            };

            function showPreviewModal(data, totalWcCount) {
                console.log('showPreviewModal called', data, totalWcCount);
                // Save data globally for save function
                window.latestAllocations = data;

                const content = document.getElementById('previewContent');
                const emptyWarning = document.getElementById('emptyPreviewWarning');
                const dateInput = document.getElementById('wiDocumentDate');
                const timeInput = document.getElementById('wiDocumentTime');
                
                // Set default time logic (sama)
                const now = new Date();
                dateInput.value = now.toISOString().split('T')[0];
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                if(timeInput) timeInput.value = `${hours}:${minutes}`;

                content.innerHTML = '';

                if (totalWcCount === 0) {
                    emptyWarning.classList.remove('d-none');
                    const confirmBtn = document.getElementById('confirmSaveBtn');
                    if(confirmBtn) confirmBtn.disabled = true;
                    
                    // Force Re-init to be safe
                    const previewEl = document.getElementById('previewModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(previewEl);
                    modal.show();
                    return;
                }

                emptyWarning.classList.add('d-none');
                const confirmBtn = document.getElementById('confirmSaveBtn');
                if(confirmBtn) confirmBtn.disabled = false;

                let hasCapacityError = false;
                let html = '';
                
                data.forEach(wc => {
                    const wcCard = document.querySelector(`[data-wc-id="${wc.workcenter}"]`);
                    const maxLoad = wcCard ? Math.ceil(parseFloat(wcCard.dataset.kapazWc) * 60) : 0;
                    
                    // Capacity Check
                    const isOverCapacity = wc.load_mins > (maxLoad + 0.1); 
                    if (isOverCapacity) hasCapacityError = true;

                    const badgeClass = isOverCapacity ? 'bg-danger text-white border-danger' : 'bg-light text-primary border-primary border-opacity-25';
                    const loadText = `Load: ${wc.load_mins} / ${maxLoad} Min`;

                    html += `
                        <div class="col-lg-4 col-md-6"> 
                            <div class="card border-0 shadow-sm h-100 ${isOverCapacity ? 'border border-danger' : ''}">
                                <div class="card-header bg-white border-bottom pt-3 pb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold text-dark">${wc.workcenter}</h6>
                                        <span class="badge ${badgeClass} border">
                                            ${loadText} ${isOverCapacity ? '(Exceeded!)' : ''}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush small">
                    `;

                    wc.pro_items.forEach(item => {
                        const targetWcName = item.child_workcenter ? 
                            `<i class="fa-solid fa-arrow-right-long mx-1 text-muted"></i> <span class="text-primary fw-bold">${item.child_workcenter}</span>` : '';

                        html += `
                            <li class="list-group-item px-3 py-2 border-bottom-0 border-top">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-dark">${item.aufnr}</div>
                                        <div class="text-xs text-muted">${targetWcName}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 mb-1">
                                            ${item.assigned_qty.toLocaleString('id-ID')}
                                        </div>
                                        <div class="text-xs text-muted">${item.name}</div>
                                    </div>
                                </div>
                            </li>
                        `;
                    });

                    html += `
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                });

                content.innerHTML = html;

                if (typeof hasCapacityError !== 'undefined' && hasCapacityError) {
                    const confirmBtn = document.getElementById('confirmSaveBtn');
                    if(confirmBtn) confirmBtn.disabled = true;
                    // Inject warning alert
                    content.insertAdjacentHTML('afterbegin', `
                        <div class="col-12 mb-3">
                            <div class="alert alert-danger fw-bold shadow-sm">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> 
                                Terdapat Workcenter yang melebihi kapasitas! Silakan kurangi beban kerja sebelum menyimpan.
                            </div>
                        </div>
                    `);
                } else {
                    const confirmBtn = document.getElementById('confirmSaveBtn');
                    if(confirmBtn) confirmBtn.disabled = false;
                }
                
                // Force Re-init to be safe
                const previewEl = document.getElementById('previewModal');
                const modal = bootstrap.Modal.getOrCreateInstance(previewEl);
                console.log('Showing modal via getOrCreateInstance');
                modal.show();
            }
            // 1. Tombol "Change WC" diklik
            function requestChangeWc() {
                // [UPDATED] Logic for Global Select All
                if (window.isSelectAllActive) {
                    // Fetch ALL IDs from Backend
                    const currentSearch = document.getElementById('searchInput').value; 
                    const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all'; // Get filter from URL/Global
                    
                    // Advanced Search Params
                    const advIds = ['advAufnr', 'advMatnr', 'advMaktx', 'advArbpl', 'advSo'];
                    let advQuery = '';
                    advIds.forEach(id => {
                        const val = document.getElementById(id)?.value?.trim();
                        if(val) {
                            const key = id.replace(/([A-Z])/g, '_$1').toLowerCase(); 
                            advQuery += `&${key}=${encodeURIComponent(val)}`;
                        }
                    });

                    Swal.fire({
                        title: 'Memuat Semua Data...',
                        text: 'Mengambil semua data untuk perubahan massal...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(`{{ route('wi.fetch-all-ids', $kode) }}?search=${encodeURIComponent(currentSearch)}&filter=${currentFilter}${advQuery}`)
                    .then(res => res.json())
                    .then(res => {
                        if(!res.success) throw new Error("Gagal mengambil data.");
                        
                        const allData = res.data; // [{proCode, oper, pwwrk}, ...]
                        
                        window.selectedProsForChange = allData;
                        document.getElementById('selectedCountMsg').innerText = `${allData.length} PRO terpilih (Semua).`;
                        
                        Swal.close();
                        const modal = new bootstrap.Modal(document.getElementById('changeWcModal'));
                        modal.show();
                    })
                    .catch(e => {
                        console.error(e);
                        Swal.fire('Error', 'Gagal memuat semua data.', 'error');
                    });

                    return;
                }

                // Cek item yang dicentang di tabel sumber (Existing Logic)
                const checkboxes = document.querySelectorAll('.source-table .form-check-input:checked:not(#selectAll)');
                if (checkboxes.length === 0) {
                    Swal.fire('Info', 'Pilih setidaknya satu PRO dari tabel kiri.', 'info');
                    return;
                }

                document.getElementById('selectedCountMsg').innerText = `${checkboxes.length} PRO terpilih.`;
                
                // Simpan data terpilih ke variabel global sementara
                window.selectedProsForChange = [];
                checkboxes.forEach(cb => {
                    const tr = cb.closest('tr');
                    if(tr) {
                        window.selectedProsForChange.push({
                            proCode: tr.dataset.aufnr ?? '', 
                            oper: tr.dataset.vornr ?? '0010', // Default if missing
                            pwwrk: tr.dataset.pwwrk ?? ''
                        });
                    }
                });

                const modal = new bootstrap.Modal(document.getElementById('changeWcModal'));
                modal.show();
            }

            // 2. Tombol "Mulai Proses" diklik
            async function startBulkChangeStream() {
                const targetWc = document.getElementById('targetWcSelect').value;
                if (!targetWc) {
                    Swal.fire('Error', 'Silakan pilih Workcenter tujuan.', 'error');
                    return;
                }

                // Tutup modal selection
                bootstrap.Modal.getInstance(document.getElementById('changeWcModal')).hide();
                
                // Execute Stream
                await executeBulkChangeStream(targetWc, window.selectedProsForChange);
            }

            // CORE STREAMING LOGIC (Reusable)
            async function executeBulkChangeStream(targetWc, prosList) {
                // Validasi data
                if (!prosList || prosList.length === 0) {
                    Swal.fire('Error', 'Data PRO tidak valid.', 'error');
                    return;
                }

                // Buka Modal Progress
                const progressModal = new bootstrap.Modal(document.getElementById('bulkProgressModal'));
                progressModal.show();

                // Reset UI Progress
                const logContainer = document.getElementById('streamLogContainer');
                const progressBar = document.getElementById('streamProgressBar');
                const statusText = document.getElementById('streamStatusText');
                const btnClose = document.getElementById('btnCloseStreamModal');
                const percentText = document.getElementById('streamPercent');

                logContainer.innerHTML = '';
                progressBar.style.width = '0%';
                progressBar.classList.add('progress-bar-animated', 'bg-primary');
                progressBar.classList.remove('bg-success', 'bg-danger');
                statusText.innerText = 'Memulai koneksi...';
                btnClose.classList.add('d-none');
                percentText.innerText = '0%';

                const kode = "{{ $kode }}"; // Blade Variable
                const url = `/changeWCBulkStream/${kode}/${targetWc}`;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            bulk_pros: JSON.stringify(prosList)
                        })
                    });

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder("utf-8");
                    
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        
                        const chunk = decoder.decode(value, { stream: true });
                        buffer += chunk;
                        
                        const lines = buffer.split('\n\n');
                        buffer = lines.pop(); // Keep the remaining part

                        for (const line of lines) {
                            if (line.startsWith('data: ')) {
                                try {
                                    const jsonStr = line.substring(6);
                                    const data = JSON.parse(jsonStr);
                                    handleStreamData(data, logContainer, progressBar, statusText, percentText, btnClose);
                                } catch (e) {
                                    console.error('Error parsing JSON stream', e);
                                }
                            }
                        }
                    }

                } catch (error) {
                    logContainer.innerHTML += `<div class="text-danger mb-1"><i class="fa-solid fa-circle-xmark me-2"></i>Network Error: ${error.message}</div>`;
                    progressBar.classList.remove('bg-primary');
                    progressBar.classList.add('bg-danger');
                    progressBar.classList.remove('progress-bar-animated');
                    btnClose.classList.remove('d-none');
                }
            }

            // 3. Handle Data Stream
            function handleStreamData(data, container, bar, statusLabel, percentLabel, closeBtn) {
                const type = data.type;
                let icon = '';
                let color = 'text-dark';

                if (type === 'progress') {
                    icon = '<i class="fa-solid fa-spinner fa-spin text-primary me-2"></i>';
                    statusLabel.innerText = 'Memproses...';
                } else if (type === 'success') {
                    icon = '<i class="fa-solid fa-check text-success me-2"></i>';
                    color = 'text-success';
                } else if (type === 'failure' || type === 'error' || type === 'warning') {
                    icon = type === 'warning' ? '<i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>' : '<i class="fa-solid fa-circle-xmark text-danger me-2"></i>';
                    color = type === 'warning' ? 'text-warning' : 'text-danger';
                } else if (type === 'complete') {
                    bar.style.width = '100%';
                    bar.classList.remove('progress-bar-animated');
                    bar.classList.add('bg-success');
                    statusLabel.innerText = 'Selesai!';
                    percentLabel.innerText = '100%';
                    closeBtn.classList.remove('d-none');
                    container.scrollTop = container.scrollHeight;
                    
                    // Show completion message in log
                    const item = document.createElement('div');
                    item.className = `text-success fw-bold mb-1 pt-2 border-top`;
                    item.innerHTML = `<i class="fa-solid fa-flag-checkered me-2"></i> ${data.message}`;
                    container.appendChild(item);
                    return; 
                }

                // Update Progress
                if (data.progress !== undefined) {
                    bar.style.width = `${data.progress}%`;
                    percentLabel.innerText = `${data.progress}%`;
                }

                // Append Log
                if (data.message && type !== 'complete') {
                    const item = document.createElement('div');
                    item.className = `${color} mb-1 border-bottom pb-1 border-light`;
                    item.innerHTML = `${icon} <span class="fw-bold me-1">${data.pro ? data.pro + ':' : ''}</span> ${data.message}`;
                    container.appendChild(item);
                    container.scrollTop = container.scrollHeight;
                }
            }
        </script>
    @endpush

    {{-- MODAL CHANGE WC SELECTION --}}
    <div class="modal fade" id="changeWcModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold">Pindah Workcenter Massal</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">TARGET WORKCENTER</label>
                        <select class="form-select" id="targetWcSelect">
                            <option value="" selected disabled>-- Pilih Workcenter Tujuan --</option>
                            @foreach ($workcenters as $wc)
                                <option value="{{ $wc->kode_wc }}">{{ $wc->kode_wc }} - {{ $wc->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <span id="selectedCountMsg">0 PRO terpilih.</span> Proses ini akan dilakukan secara bertahap.
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal Preview --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-file-contract me-2"></i>Review & Confirm WI
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <!-- Added Date/Time Inputs -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label text-xs fw-bold text-muted text-uppercase">Tanggal Document</label>
                                    <input type="text" class="form-control form-control-sm fw-bold text-dark flatpickr-date" id="wiDocumentDate" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-xs fw-bold text-muted text-uppercase">Waktu Mulai WI</label>
                                    <input type="text" class="form-control form-control-sm fw-bold text-dark flatpickr-time" id="wiDocumentTime" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center text-danger small bg-danger bg-opacity-10 p-2 rounded">
                                        <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                                        <div>Dokumen akan kadaluarsa <strong>12 Jam</strong> sesuai jadwal. Pastikan jadwal operator sudah benar.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="emptyPreviewWarning" class="alert alert-warning d-none text-center border-0 shadow-sm mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Tidak ada data yang dimasukkan, silahkan cek kembali tim anda
                    </div>

                    <div id="previewContent">
                        {{-- Preview Cards injected here --}}
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 d-flex justify-content-between">
                     <div>
                        <small class="text-muted fst-italic"><i class="fa-solid fa-circle-info me-1"></i> PASTIKAN DATA SUDAH BENAR SEBELUM MENYIMPAN.</small>
                     </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary px-4 me-2 shadow-sm rounded-pill" data-bs-dismiss="modal">
                             <i class="fa-solid fa-xmark me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success px-5 shadow-lg rounded-pill" id="confirmSaveBtn">
                             <i class="fa-solid fa-check-double me-2"></i>Confirm & Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- NEW: Stream Progress Modal --}}
    <div class="modal fade" id="streamProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-body p-4 text-center">
                    <h5 class="fw-bold mb-3 text-dark">Processing Schedule...</h5>
                    
                    <div class="progress mb-3" style="height: 20px; border-radius: 10px;">
                        <div id="wiProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    
                    <p id="wiStatusText" class="text-muted small mb-0">Initializing...</p>
                    
                    <div id="wiLogArea" class="mt-3 text-start small text-muted overflow-auto border rounded p-2 bg-light d-none" style="max-height: 100px; font-size: 0.75rem;">
                        {{-- Logs assigned here --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PROGRESS STREAMING --}}
    <div class="modal fade" id="bulkProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark">Memproses Perubahan...</h6>
                </div>
                <div class="modal-body p-4">
                    {{-- Progress Bar --}}
                    <div class="progress mb-3" style="height: 10px; border-radius: 5px;">
                        <div id="streamProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    {{-- Status Text --}}
                    <div class="d-flex justify-content-between small text-muted fw-bold mb-3">
                        <span id="streamStatusText">Menyiapkan...</span>
                        <span id="streamPercent">0%</span>
                    </div>

                    {{-- Log Area --}}
                    <div class="p-3 bg-light rounded-3 border custom-scrollbar" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;" id="streamLogContainer">
                        {{-- Log items will be appended here --}}
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold d-none" id="btnCloseStreamModal" data-bs-dismiss="modal">
                        <i class="fa-solid fa-check me-1"></i> Selesai & Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function refreshData() {
            Swal.fire({
                title: 'Refreshing Data...',
                text: 'Sedang mengambil data terbaru dari SAP. Mohon tunggu.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("{{ route('create-wi.refresh', $kode) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload data table
                        if (typeof setupSearch === 'function') {
                            setupSearch(); 
                        } else {
                            location.reload(); 
                        }
                    });
                } else {
                    throw new Error(data.message || 'Gagal refresh data.');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message
                });
            });
        }
    </script>
    @endpush
</x-layouts.app>