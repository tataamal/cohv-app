<x-layouts.app title="Create Work Instruction">

    @push('styles')
        <style>
            /* --- 1. THEME VARIABLES (MATCHING HISTORY PAGE) --- */
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

            /* --- 2. GLOBAL UTILITIES --- */
            .icon-box {
                width: 32px; height: 32px;
                display: flex; align-items: center; justify-content: center;
                border-radius: 8px;
            }
            .fw-bolder { font-weight: 700 !important; }
            .text-xs { font-size: 0.75rem; }
            
            /* PREVENT TEXT SELECTION DURING DRAG (FIXED) */
            body.dragging-active, 
            body.dragging-active * {
                user-select: none !important;
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                cursor: grabbing !important;
            }

            /* --- 3. SOURCE TABLE CARD (LEFT COLUMN) --- */
            .source-panel {
                background: var(--card-bg);
                border-radius: 12px;
                border: 1px solid var(--border-color);
                box-shadow: var(--shadow-soft);
                height: calc(100vh - 140px); /* Fixed height for dashboard feel */
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

            /* Custom Input Search */
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

            /* Table Area */
            .table-scroll-area {
                flex-grow: 1;
                overflow-y: auto;
                background-color: #ffffff;
            }
            
            /* Table Styling */
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
            
            /* Row Styling */
            tr.pro-item {
                border-bottom: 1px solid #f1f5f9;
                transition: background 0.15s;
                cursor: grab; /* Kursor tangan terbuka */
                position: relative;
            }

            /* Agar text tetap bisa dicopy/highlight saat tidak sedang dragging */
            tr.pro-item * {
                user-select: text; 
            }

            /* Saat sedang dragging, ubah kursor */
            tr.pro-item:active {
                cursor: grabbing;
            }

            /* --- 4. TARGET WORKCENTER CARDS (RIGHT COLUMN) --- */
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

            /* Drop Zone Styling */
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

            /* Empty Placeholder */
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

            /* --- 5. DROPPED ITEM CARD (MINI CARD) --- */
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

            /* --- 6. DRAG PREVIEW (GHOST) --- */
            .sortable-ghost {
                opacity: 0.4;
                background-color: #e2e8f0 !important;
                border: 2px dashed var(--text-secondary) !important;
            }
            .sortable-drag { opacity: 1 !important; background: transparent; }
            
            /* Custom Drag Preview Element */
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

            /* --- 7. HIDE/SHOW LOGIC (LOGIC JS DEPENDENCY) --- */
            /* Ini css kritis agar logika JS sebelumnya tetap jalan */
            .wc-drop-zone .pro-item-card .table-col,
            .wc-drop-zone .pro-item-card .drag-handle,
            .wc-drop-zone .pro-item-card .preview-container .original-content,
            .wc-drop-zone .pro-item-card .row-checkbox { display: none !important; }

            .wc-drop-zone .pro-item-card .card-view-content { display: block !important; }
            .wc-drop-zone .pro-item-card .drag-preview-icon { display: none !important; }

            .source-table .pro-item .card-view-content { display: none; }
            .source-table .pro-item .drag-preview-icon { display: none; }

            /* Scrollbar Refinement */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

            /* FIX EMPTY TABLE DRAG BUG */
            #source-list {
                /* table-row-group ignores height, so we use block when empty or rely on content */
            }
            #source-list:empty {
               display: block;
               min-height: 150px;
               width: 100%;
               background-image: linear-gradient(45deg, #f8fafc 25%, transparent 25%, transparent 75%, #f8fafc 75%, #f8fafc), linear-gradient(45deg, #f8fafc 25%, transparent 25%, transparent 75%, #f8fafc 75%, #f8fafc);
               background-size: 20px 20px;
               background-position: 0 0, 10px 10px;
            }
            /* Add content to tell user they can drop back */
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
                min-height: 200px;
            }
        </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4" style="max-width: 1600px;">

        {{-- PAGE HEADER & ACTIONS --}}
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h4 fw-bolder text-dark mb-1">Create Work Instruction</h1>
                <p class="text-muted small mb-0">Drag PRO from the list and drop into target Workcenters.</p>
            </div>
            
            <div class="d-flex gap-2 bg-white p-1 rounded-pill shadow-sm border">
                <a href="{{ route('wi.history', ['kode' => $kode]) }}" class="btn btn-white text-secondary fw-bold rounded-pill px-3 btn-sm">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>History
                </a>
                <div class="vr my-1"></div>
                <button onclick="resetAllAllocations()" class="btn btn-white text-danger fw-bold rounded-pill px-3 btn-sm">
                    <i class="fa-solid fa-arrow-rotate-left me-2"></i>Reset
                </button>
                <button onclick="saveAllocation(true)" class="btn btn-primary fw-bold rounded-pill px-4 btn-sm shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Review & Save
                </button>
            </div>
        </div>

        <div class="row g-4">
            
            {{-- COLUMN 1: SOURCE LIST (PANEL) --}}
            <div class="col-lg-9 col-md-8">
                <div class="source-panel">
                    
                    {{-- Panel Header (Search & Stats) --}}
                    <div class="source-header d-flex justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-list-ul"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">Unassigned Orders</h6>
                            </div>
                        </div>

                        <div class="search-input-group px-3 py-1 d-flex align-items-center" style="width: 350px;">
                            <i class="fa-solid fa-magnifying-glass text-muted me-2"></i>
                            <input type="text" id="searchInput" class="form-control form-control-sm p-0" placeholder="Search Material, PRO, or SO...">
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

                    {{-- Table Content --}}
                    <div class="table-scroll-area custom-scrollbar" style="max-height: 700px; overflow-y: auto;">
                        <table class="table table-hover table-striped source-table mb-0 w-100" style="font-size: 0.8rem;">
                            <thead class="bg-light sticky-top" style="z-index: 5;">
                                <tr>
                                    <th class="text-center ps-3" width="40">
                                        <input class="form-check-input pointer" type="checkbox" id="selectAll">
                                    </th>
                                    <th class="ps-3">PRO Number</th>
                                    <th>SO-Item</th>
                                    <th>Material Description</th>
                                    <th class="text-center">WC</th>
                                    <th class="text-center">Op.Key</th>
                                    <th class="text-center">Qty Opt</th>
                                    <th class="text-center">Qty Conf</th>
                                    <th class="text-center">Qty WI</th> {{-- NEW COLUMN --}}
                                    <th class="text-center">Tak Time</th>
                                </tr>
                            </thead>
                            <tbody id="source-list" class="sortable-list" data-group="shared-pro">
                                @foreach ($tData1 as $item)
                                    @php
                                        $soItem = ltrim($item->KDAUF, '0') . ' - ' . ltrim($item->KDPOS, '0');
                                        $matnr = ctype_digit($item->MATNR) ? ltrim($item->MATNR, '0') : $item->MATNR;
                                        $sisaQty = $item->real_sisa_qty ?? $item->MGVRG2 - $item->LMNGA;
                                    @endphp

                                    <tr class="pro-item draggable-item" 
                                        data-id="{{ $item->id }}"
                                        data-aufnr="{{ $item->AUFNR }}"
                                        data-vgw01="{{ number_format($item->VGW01, 3, '.', '') }}"
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
                                        data-kapaz="{{ $item->KAPAZ }}"> 

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
                                            $decimals = in_array($item->MEINS, ['ST', 'SET']) ? 0 : 2;
                                        @endphp
                                        <td class="text-center table-col fw-bold text-dark">{{ number_format($item->MGVRG2, $decimals, ',', '.') }} {{ $showUnit }}</td>
                                        <td class="text-center table-col text-muted">{{ number_format($item->LMNGA, $decimals, ',', '.') }} {{ $showUnit }}</td>
                                        
                                        {{-- NEW COLUMN QTY WI --}}
                                        <td class="text-center table-col text-primary fw-bold">{{ number_format($item->qty_wi, $decimals, ',', '.') }} {{ $showUnit }}</td>
                                        
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
                                        {{-- 5. CARD VIEW CONTENT (Hanya muncul saat di-drop ke kanan) --}}
                                        <td class="card-view-content" colspan="9">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10">{{ $item->AUFNR }}</span>
                                                        <span class="badge bg-white text-dark border assigned-qty-badge">Qty: -</span>
                                                    </div>
                                                    <div class="d-flex flex-column ps-1">
                                                        <span class="text-dark fw-bold small employee-name-text">-</span>
                                                        <span class="text-primary fw-bold text-xs child-wc-display mt-1"></span>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <button type="button" class="btn btn-link p-0 text-muted" onclick="handleReturnToTable(this.closest('.pro-item-card'), this.closest('.wc-drop-zone'))">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="bg-light p-2 rounded small text-secondary text-truncate">
                                                {{ $item->MAKTX }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- COLUMN 2: TARGET WORKCENTERS --}}
            <div class="col-lg-3 col-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box bg-dark text-white shadow-sm" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-bullseye text-xs"></i>
                        </div>
                        <h6 class="mb-0 fw-bold">Target Lines</h6>
                    </div>
                    <span class="badge bg-white border text-muted rounded-pill">{{ count($workcenters) }} WC</span>
                </div>

                <div class="target-scroll-area custom-scrollbar">
                    @foreach ($workcenters as $wc)
                        @php
                            $refItem = $tData1->firstWhere('ARBPL', $wc->kode_wc);
                            $rawKapaz = $refItem ? $refItem->KAPAZ : 0;
                            $kapazHours = (float) str_replace(',', '.', (string) $rawKapaz);
                            $isUnknown = $kapazHours <= 0;
                        @endphp

                        {{-- @if (!$isUnknown) --}}
                            <div class="wc-card-container" data-wc-id="{{ $wc->kode_wc }}" data-kapaz-wc="{{ $kapazHours }}">
                                {{-- Card Header --}}
                                <div class="wc-header">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">{{ $wc->kode_wc }}</h6>
                                            <div class="text-muted text-xs text-truncate" style="max-width: 150px;">{{ $wc->description }}</div>
                                        </div>
                                        <span class="badge bg-white text-dark border" id="label-cap-{{ $wc->kode_wc }}">0 / 0 Min</span>
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
                            <label class="small fw-bold text-muted mb-1">Current WC</label>
                            <input type="text" class="form-control bg-light" id="mismatchCurrentWC" readonly>
                        </div>
                        <div class="col-2 text-center">
                            <i class="fa-solid fa-arrow-right text-muted"></i>
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-primary mb-1">New Target WC</label>
                            <input type="text" class="form-control border-primary bg-primary bg-opacity-10 text-primary fw-bold" id="mismatchTargetWC" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-2">
                    <button type="button" class="btn btn-white text-muted fw-bold btn-sm shadow-sm border" id="btnCancelMismatch">
                        Cancel Drop
                    </button>
                    <button type="button" class="btn btn-warning fw-bold btn-sm shadow-sm" id="btnChangeWC">
                        Change Workcenter
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL ASSIGNMENT (STYLE UPDATE) --}}
    <div class="modal fade" id="assignmentModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Assign Operator</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light p-3">
                    <div class="bg-white p-3 rounded-3 shadow-sm border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10" id="modalProAufnr"></span>
                            <span class="text-xs text-muted fw-bold">REMAINING: <span id="remainingQtyDisplay" class="text-dark">0</span></span>
                        </div>
                        
                        <div id="bulkWarning" class="alert alert-warning d-none text-xs p-2 mb-0 border-0 bg-warning bg-opacity-10 text-warning rounded fw-bold">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Bulk Assignment Mode
                        </div>
                    </div>

                    {{-- Form --}}
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-uppercase text-muted">Select Operator</label>
                        <select class="form-select form-select-sm" id="employeeSelect">
                            <option value="" selected disabled>Choose Person...</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp['pernr'] }}" data-name="{{ $emp['stext'] }}">
                                    {{ $emp['pernr'] }} - {{ $emp['stext'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="childWorkcenterField">
                        <label class="form-label text-xs fw-bold text-uppercase text-muted">Target Line (Sub-WC)</label>
                        <select class="form-select form-select-sm" id="childWorkcenterSelect">
                            <option value="" selected disabled>Select Sub-WC...</option>
                            {{-- Option akan diisi oleh Javascript --}}
                        </select>
                        <div class="form-text text-xs text-info">
                            <i class="fa-solid fa-circle-info me-1"></i> WC Induk ini membutuhkan pemilihan Line/Mesin.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-uppercase text-muted">Quantity</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" id="inputAssignQty" min="1" placeholder="0">
                            <span class="input-group-text bg-white text-muted" id="maxQtyLabel">Max: -</span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-dark btn-sm w-100 mb-3" id="btnAddSplit" disabled>
                        <i class="fa-solid fa-plus-circle me-1"></i> Add Allocation
                    </button>

                    {{-- Splits List --}}
                    <div id="splitReviewSection" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                            <span class="text-xs fw-bold text-muted">ALLOCATIONS</span>
                            <span class="badge bg-secondary rounded-pill" id="totalSplitsCount">0</span>
                        </div>
                        <div class="bg-white rounded border overflow-hidden">
                            <ul class="list-group list-group-flush small" id="tempSplitList"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-2 bg-white d-flex">
                    <button type="button" class="btn btn-light btn-sm flex-fill text-muted fw-bold" id="btnCancelDrop">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm flex-fill fw-bold shadow-sm" id="btnConfirmFinalAssignment" disabled>Confirm</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW (STYLE UPDATE) --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-clipboard-check me-2"></i>Review & Finalize</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label text-xs fw-bold text-muted text-uppercase">Document Date</label>
                                    <input type="date" class="form-control form-control-sm fw-bold text-dark" id="wiDocumentDate" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-xs fw-bold text-muted text-uppercase">Effective Time</label>
                                    <input type="time" class="form-control form-control-sm fw-bold text-dark" id="wiDocumentTime" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center text-info small bg-info bg-opacity-10 p-2 rounded">
                                        <i class="fa-solid fa-circle-info me-2 fs-5"></i>
                                        <div>Document expires <strong>12 hours</strong> after the effective time. Ensure operator shifts are covered.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3 ps-1">Assignment Summary</h6>
                    <div id="previewContent" class="row g-3">
                        {{-- Content Injected Here --}}
                    </div>
                    
                    <div id="emptyPreviewWarning" class="alert alert-warning d-none mt-3 text-center border-0 shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> No Work Instructions generated. Please drag PROs to Workcenters first.
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 py-3">
                    <button type="button" class="btn btn-light fw-bold text-muted" data-bs-dismiss="modal">Go Back</button>
                    <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnFinalSave">
                        <i class="fa-solid fa-paper-plane me-2"></i>Generate & Save WI
                    </button>
                </div>
            </div>
        </div>
    </div>

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


            document.addEventListener('DOMContentLoaded', function() {
                assignmentModalInstance = new bootstrap.Modal(document.getElementById('assignmentModal'));
                previewModalInstance = new bootstrap.Modal(document.getElementById('previewModal'));
                mismatchModalInstance = new bootstrap.Modal(document.getElementById('mismatchModal')); // Init New Modal

                calculateAllRows();
                setupSearch();
                setupCheckboxes();
                setupDragAndDrop();
                setupModalLogic();
                setupMismatchLogic(); // New Logic

                document.getElementById('btnFinalSave').addEventListener('click', function() {
                    const finalData = saveAllocation(false); // False = get data only
                    sendFinalAllocation(finalData);
                });
                
                // Reset modal state on close
                document.getElementById('assignmentModal').addEventListener('hide.bs.modal', function() {
                    if(draggedItemsCache.length > 0 && tempSplits.length === 0) {
                        cancelDrop();
                    }
                });
            });

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
                    onStart: function(evt) { document.body.classList.add('dragging-active'); },
                    onAdd: function(evt) {
                        // FIX 3: HANDLE DRAG BACK TO TABLE
                        document.body.classList.remove('dragging-active');
                        handleReturnToTable(evt.item, null); // null source container, logic handled inside
                    },
                    onEnd: function(evt) { document.body.classList.remove('dragging-active'); }
                });

                // CONFIG 2: TARGET ZONES
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
                        // onRemove removed, logic moved to onAdd in sourceList to prevent conflict
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

                // 1. Cek item yang sudah di-assign di DOM (di semua drop zones)
                document.querySelectorAll(`.wc-drop-zone .pro-item-card[data-aufnr="${aufnr}"]`).forEach(item => {
                    if (item.dataset.childWc) {
                        if (!assignedWCs.includes(item.dataset.childWc)) {
                            assignedWCs.push(item.dataset.childWc);
                        }
                    }
                });

                // 2. Tambahkan Child WC dari tracking data-assigned-child-wcs (di item sumber)
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

                // 3. Tambahkan Child WC dari split sementara (di modal)
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
                const targetWcId = toList.closest('.wc-card-container').dataset.wcId;
                const originWc = item.dataset.arbpl; // Ambil Origin WC

                // FIX 1: MISMATCH CHECK
                if (originWc && targetWcId && originWc !== targetWcId) {
                    // Simpan state untuk mismatch modal
                    pendingMismatchItem = item;
                    targetContainerCache = toList; // Cache needed to revert if cancelled
                    sourceContainerCache = fromList;
                    draggedItemsCache = [item]; // Hack agar cancelDrop() bekerja
                    
                    document.getElementById('mismatchCurrentWC').value = originWc;
                    document.getElementById('mismatchTargetWC').value = targetWcId;
                    
                    mismatchModalInstance.show();
                    return; // Stop execution
                }

                // Normal Flow
                processDrop(evt, item, toList, fromList, targetWcId);
            }

            // Extracted logic to support resume after mismatch check
            function processDrop(evt, item, toList, fromList, targetWcId) {
                const proAufnr = item.dataset.aufnr;
                
                targetContainerCache = toList;
                sourceContainerCache = fromList;
                draggedItemsCache = [];
                tempSplits = []; 
                currentSisaQty = parseFloat(item.dataset.sisaQty) || 0;
                document.getElementById('modalProAufnr').innerText = proAufnr;
                document.getElementById('remainingQtyDisplay').innerText = currentSisaQty.toLocaleString('id-ID');
                
                transformToCardView(item);
                
                const checkbox = item.querySelector('.row-checkbox');
                if (checkbox && checkbox.checked) {
                    document.querySelectorAll('#source-list .pro-item .row-checkbox:checked').forEach(cb => {
                        draggedItemsCache.push(cb.closest('.pro-item'));
                    });
                    if (!draggedItemsCache.includes(item)) draggedItemsCache.push(item);
                } else {
                    draggedItemsCache.push(item);
                }
                
                const normalizedTargetWc = targetWcId.toUpperCase();
                const childField = document.getElementById('childWorkcenterField');
                const childSelect = document.getElementById('childWorkcenterSelect');
                childSelect.innerHTML = '<option value="" selected disabled>Select Sub-WC...</option>';
                
                if (PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWc)) {
                    childField.classList.remove('d-none');
                    const children = PARENT_WORKCENTERS[normalizedTargetWc];
                    children.forEach(child => {
                        const opt = document.createElement('option');
                        opt.value = child.code; 
                        opt.innerText = `${child.code} - ${child.name}`; 
                        childSelect.appendChild(opt);
                    });
                } else {
                    childField.classList.add('d-none');
                }

                document.getElementById('employeeSelect').value = "";
                document.getElementById('inputAssignQty').value = currentSisaQty;
                document.getElementById('inputAssignQty').max = currentSisaQty;
                document.getElementById('maxQtyLabel').innerText = "Max: " + currentSisaQty;
                document.getElementById('btnAddSplit').disabled = true;

                // Show Modal
                assignmentModalInstance.show();
            }

            function setupMismatchLogic() {
                // Button Cancel di Mismatch Modal
                document.getElementById('btnCancelMismatch').addEventListener('click', function() {
                    mismatchModalInstance.hide();
                    cancelDrop(); // Reuse existing cancel logic
                });

                // Button Change WC
                document.getElementById('btnChangeWC').addEventListener('click', function() {
                    alert('Fitur sedang dalam masa pengembangan');
                    mismatchModalInstance.hide();
                    cancelDrop(); // Tetap batalkan drop setelah alert
                });
            }

            function updateChildWCDropdown(normalizedTargetWcId, proAufnr) {
                const childWcSelect = document.getElementById('childWorkcenterSelect');
                childWcSelect.innerHTML = '<option value="" selected disabled>Pilih Workcenter Anak...</option>';
                childWcSelect.disabled = false;

                // AMBIL SEMUA CHILD WC YANG SUDAH DIALOKASIKAN/DIPILIH SEMENTARA
                const usedChildWCs = getAssignedChildWCs(proAufnr);
                let optionsAdded = 0;

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

                if (optionsAdded === 0) {
                    childWcSelect.innerHTML =
                        '<option value="" selected disabled>Semua Sub-WC sudah dialokasikan untuk PRO ini</option>';
                    childWcSelect.disabled = true;
                }
            }

            function updateTempSplitList() {
                const list = document.getElementById('tempSplitList');
                const reviewSec = document.getElementById('splitReviewSection');
                const countBadge = document.getElementById('totalSplitsCount');
                const btnConfirm = document.getElementById('btnConfirmFinalAssignment');

                list.innerHTML = '';
                countBadge.innerText = tempSplits.length;

                if (tempSplits.length > 0) {
                    reviewSec.classList.remove('d-none');
                    btnConfirm.disabled = false;
                } else {
                    reviewSec.classList.add('d-none');
                    btnConfirm.disabled = true;
                }
                document.getElementById('remainingQtyDisplay').innerText = currentSisaQty.toLocaleString('id-ID');
                document.getElementById('inputAssignQty').max = currentSisaQty;

                tempSplits.forEach((split, index) => {
                    const childWcBadge = split.child_wc ? `<span class="badge bg-info text-dark ms-1">${split.child_wc}</span>` : '';
                    
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center px-0';
                    li.innerHTML = `
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center">
                                <strong>Qty: ${split.qty}</strong> 
                                ${childWcBadge}
                            </div>
                            <span class="text-muted" style="font-size: 0.75rem;">${split.name}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-danger border-0" onclick="removeSplit(${index})"><i class="fa-solid fa-times"></i></button>
                    `;
                    list.appendChild(li);
                });
            }

            function removeSplit(index) {
                // Kembalikan Qty, gunakan floating point fix
                currentSisaQty = parseFloat((currentSisaQty + tempSplits[index].qty).toFixed(3));
                tempSplits.splice(index, 1);
                document.getElementById('inputAssignQty').disabled = false;
                document.getElementById('inputAssignQty').value = currentSisaQty;
                updateTempSplitList();
            }


            function setupModalLogic() {
                const empSelect = document.getElementById('employeeSelect');
                const qtyInput = document.getElementById('inputAssignQty');
                const btnAddSplit = document.getElementById('btnAddSplit');
                const btnConfirm = document.getElementById('btnConfirmFinalAssignment');
                const btnCancel = document.getElementById('btnCancelDrop');
                const childSelect = document.getElementById('childWorkcenterSelect'); 
                const validateForm = () => {
                    const hasEmp = empSelect.value !== "";
                    const hasQty = qtyInput.value !== "" && parseFloat(qtyInput.value) > 0;
                    const isChildRequired = !document.getElementById('childWorkcenterField').classList.contains('d-none');
                    const hasChild = isChildRequired ? childSelect.value !== "" : true;

                    if (hasEmp && hasQty && hasChild) {
                        btnAddSplit.disabled = false;
                    } else {
                        btnAddSplit.disabled = true;
                    }
                };

                empSelect.addEventListener('change', validateForm);
                qtyInput.addEventListener('input', validateForm);
                childSelect.addEventListener('change', validateForm); 
                btnAddSplit.addEventListener('click', function() {
                    const inputQty = parseFloat(qtyInput.value);
                    
                    // FIX 2: BUG QTY - Floating point precision fix
                    // Menggunakan toFixed(3) dan Number() untuk memastikan perbandingan akurat
                    const safeSisa = Number(currentSisaQty.toFixed(3));
                    
                    if(inputQty > safeSisa) {
                        Swal.fire('Error', 'Qty melebihi sisa!', 'warning'); return;
                    }

                    const nik = empSelect.value;
                    const name = empSelect.options[empSelect.selectedIndex].dataset.name;
                    const childWcValue = childSelect.value; 
                    tempSplits.push({
                        nik: nik,
                        name: name,
                        qty: inputQty,
                        child_wc: childWcValue
                    });
                    
                    // Update sisa qty dengan presisi
                    currentSisaQty = parseFloat((currentSisaQty - inputQty).toFixed(3));

                    empSelect.value = "";
                    qtyInput.value = currentSisaQty;
                    
                    updateTempSplitList();
                    validateForm(); 

                    if (currentSisaQty <= 0) {
                        qtyInput.disabled = true;
                    }
                });

                btnConfirm.addEventListener('click', confirmFinalAssignment);
                btnCancel.addEventListener('click', cancelDrop);
            }

            function confirmFinalAssignment() {
                if(tempSplits.length === 0) return;
                const originalRow = draggedItemsCache[0]; 
                let isFirst = true;

                tempSplits.forEach(split => {
                    let rowNode;
                    if(isFirst) {
                        rowNode = originalRow;
                        isFirst = false;
                    } else {
                        rowNode = originalRow.cloneNode(true); 
                        rowNode.dataset.id = Date.now() + Math.random(); 
                        targetContainerCache.appendChild(rowNode);
                    }

                    rowNode.dataset.assignedQty = split.qty;
                    rowNode.dataset.employeeNik = split.nik;
                    rowNode.dataset.employeeName = split.name;
                    rowNode.dataset.childWc = split.child_wc || ''; // Simpan Child WC di atribut

                    updateRowUI(rowNode, split.name, split.qty, split.child_wc);
                });

                if(currentSisaQty > 0) {
                    const remainingRow = originalRow.cloneNode(true);
                    remainingRow.dataset.id = Date.now() + Math.random();
                    remainingRow.dataset.sisaQty = currentSisaQty;
                    remainingRow.dataset.assignedQty = 0;
                    
                    // Reset visual ke tampilan tabel
                    transformToTableView(remainingRow);
                    
                    // Update kolom sisa qty text
                    // Note: Pastikan struktur HTML Anda punya class/id untuk update text kolom ini jika diperlukan
                    const sisaCell = remainingRow.querySelector('.col-sisa-qty') || remainingRow.children[7]; // Asumsi index kolom
                    // Jika Anda punya class spesifik untuk sisa qty di HTML awal, gunakan itu. 
                    // Di kode ini saya update data attribut, tampilan biasanya dirender ulang atau pakai DOM manipulation
                    
                    document.getElementById('source-list').appendChild(remainingRow);
                }

                // Cleanup
                tempSplits = [];
                currentSisaQty = 0;
                draggedItemsCache = [];
                document.getElementById('selectAll').checked = false;
                updateCapacity(targetContainerCache.closest('.wc-card-container'));
                checkEmptyPlaceholder(targetContainerCache);
                
                assignmentModalInstance.hide();
            }

            /**
             * Memperbarui atribut data-assigned-child-wcs pada SEMUA row dengan AUFNR yang sama.
             * Ini penting untuk filtering dropdown pada split selanjutnya.
             */
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

                    // Simpan kembali sebagai string JSON
                    row.dataset.assignedChildWcs = JSON.stringify(assignedWCs);
                });
            }


            window.cancelDrop = function() { // Exposed globally for button onclick
                // LOGGING: Drop Dibatalkan
                console.log("Assignment cancelled. Returning items to source and resetting splits.");

                // 1. Tambahkan Qty dari tempSplits kembali ke currentSisaQty
                const qtyToReturn = tempSplits.reduce((sum, split) => sum + split.qty, 0);
                currentSisaQty += qtyToReturn;
                tempSplits = [];

                // Dapatkan item asli (hanya ada 1 di draggedItemsCache saat single drop)
                const item = draggedItemsCache[0];

                // 2. Kembalikan item yang di-drag ke tabel sumber
                if (sourceContainerCache) sourceContainerCache.appendChild(item);

                // Reset status data yang mungkin sudah terubah di handleDropToWc
                item.dataset.employeeNik = "";
                item.dataset.employeeName = "";
                item.dataset.childWc = "";
                item.dataset.assignedQty = 0;
                const originalQtyForModal = (parseFloat(item.dataset.sisaQty) || 0) + qtyToReturn;

                item.dataset.sisaQty = originalQtyForModal;

                // Perbarui tampilan di tabel sumber
                const sisaCell = item.querySelector('.col-sisa-qty');
                if (sisaCell) {
                    sisaCell.innerText = originalQtyForModal.toLocaleString('id-ID');
                    sisaCell.classList.remove('text-success');
                    sisaCell.classList.add('text-danger');
                }

                transformToTableView(item);

                // Clear cache
                draggedItemsCache = [];
                currentSisaQty = 0;

                // 3. Tutup modal
                assignmentModalInstance.hide();
            }

            function resetAllAllocations() {
                if (!confirm('Are you sure want to reset all assigned PROs to table?')) return;

                console.log("Resetting ALL allocations.");

                const allWcContainers = Array.from(document.querySelectorAll('.wc-card-container'));

                allWcContainers.forEach(wcContainer => {
                    const zone = wcContainer.querySelector('.wc-drop-zone');
                    const items = Array.from(zone.querySelectorAll('.pro-item-card'));
                    const sourceList = document.getElementById('source-list');

                    items.forEach(item => {
                        const originalQtyOpr = parseFloat(item.dataset.qtyOpr) || 0;
                        const aufnr = item.dataset.aufnr;

                        // Cari item yang tersisa/asli di source list
                        let existingSourceItem = sourceList.querySelector(`tr.pro-item[data-aufnr="${aufnr}"]`);

                        if (existingSourceItem) {
                            // Kasus 1: Item asli sudah dikloning kembali sebagai sisa Qty di sourceList.
                            // Tambahkan Qty item yang dihapus ke Sisa Qty item yang ADA di source list.
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

                            // Hapus klon/asli yang ada di WC
                            item.remove();

                        } else {
                            // Kasus 2: Item asli ADA di WC (terjadi jika tidak ada split, atau item yang ada di WC adalah yang pertama/asli).
                            // Kembalikan ke source list
                            sourceList.appendChild(item);

                            // Reset data atribut
                            item.dataset.employeeNik = "";
                            item.dataset.employeeName = "";
                            item.dataset.childWc = "";
                            item.dataset.assignedQty = 0;
                            item.dataset.assignedChildWcs = '[]';
                            item.dataset.sisaQty = originalQtyOpr; // Set Qty kembali ke Qty penuh PRO

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
                // Modifikasi: Mendukung pemanggilan dari onAdd Sortable (dimana item sudah pindah secara DOM)
                // Jika dipanggil dari onAdd, item sudah ada di source-list, jadi tidak perlu appendChild lagi.
                
                const wcId = fromContainer ? fromContainer.closest('.wc-card-container').dataset.wcId : 'Unknown';
                const returnedChildWc = item.dataset.childWc;
                // Cari apakah ada item induk/sisa lain di source list
                const existingSourceItems = document.querySelectorAll(`#source-list .pro-item[data-aufnr="${item.dataset.aufnr}"]`);
                let sourceItem = null;
                
                // Cari item lain selain item yang sedang di-return (jika ada)
                existingSourceItems.forEach(el => {
                    if(el !== item) sourceItem = el;
                });

                const returnedQty = parseFloat(item.dataset.assignedQty) || 0;
                
                // Jika item sudah di table (via drag back), kita update logicnya
                const targetItemToUpdate = sourceItem || item;
                let sisaQtyOriginal = parseFloat(targetItemToUpdate.dataset.sisaQty) || 0;
                
                // Jangan tambah Qty jika targetnya adalah item itu sendiri dan qty-nya belum dikurangi (masih full)
                // Tapi logika di sini asumsinya item yang balik adalah item 'Allocated' yang assignedQty-nya > 0
                if (returnedQty > 0) {
                     sisaQtyOriginal = parseFloat((sisaQtyOriginal + returnedQty).toFixed(3));
                } else if (targetItemToUpdate === item && returnedQty === 0) {
                     // Ini kasus item baru di drag tapi belum di assign (misal cancel modal), biasanya dicover cancelDrop
                     // Tapi jika via drag back, kita pastikan qty sisa benar.
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
                    item.remove(); // Merge ke item yang sudah ada
                } else {
                    // Reset item ini menjadi item source normal
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

                    // Simpan kembali sebagai string JSON
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
                
                // Hitung ulang menit beban
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
                    bar.className = 'progress-bar rounded-pill ' + (pct < 70 ? 'bg-success' : pct < 95 ? 'bg-warning' : 'bg-danger');
                }
                
                const placeholder = cardContainer.querySelector('.empty-placeholder');
                const hasItems = cardContainer.querySelectorAll('.pro-item-card').length > 0;
                if (placeholder) placeholder.style.display = hasItems ? 'none' : 'flex'; // Changed to flex for centering
            }

            function checkEmptyPlaceholder(container) {
                const placeholder = container.querySelector('.empty-placeholder');
                const hasItems = container.querySelectorAll('.pro-item, .pro-item-card').length > 0;
                if (placeholder) placeholder.style.display = hasItems ? 'none' : 'block';
            }

            function setupSearch() {
                document.getElementById('searchInput').addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    document.querySelectorAll('#source-list tr.draggable-item').forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }

            function setupCheckboxes() {
                const selectAll = document.getElementById('selectAll');
                selectAll.addEventListener('change', function() {
                    const visibleRows = Array.from(document.querySelectorAll('#source-list tr.draggable-item')).filter(r => r.style.display !== 'none');
                    visibleRows.forEach(row => {
                        const cb = row.querySelector('.row-checkbox');
                        if (cb) {
                            cb.checked = this.checked;
                            this.checked ? row.classList.add('selected-row') : row.classList.remove('selected-row');
                        }
                    });
                });
                document.getElementById('source-list').addEventListener('change', (e) => {
                    if (e.target.classList.contains('row-checkbox')) {
                        const row = e.target.closest('tr');
                        e.target.checked ? row.classList.add('selected-row') : row.classList.remove('selected-row');
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
                                // Data Wajib (dari data drag/modal)
                                aufnr: item.dataset.aufnr,
                                nik: item.dataset.employeeNik,
                                name: item.dataset.employeeName,
                                assigned_qty: assignedQty,
                                material_number: item.dataset.matnr || 'N/A',
                                material_desc: item.dataset.maktx || 'N/A',
                                qty_order: item.dataset.sisaQty,
                                confirmed_qty: 0,
                                uom: item.dataset.meins || 'EA',
                                vornr: item.dataset.vornr || 'N/A',
                                kdauf: item.dataset.kdauf || 'N/A',
                                kdpos: item.dataset.kdpos || 'N/A',
                                dispo: item.dataset.dispo || 'N/A',
                                steus: item.dataset.steus || 'N/A',
                                sssld: item.dataset.sssld || 'N/A',
                                ssavd: item.dataset.ssavd || 'N/A',
                                kapaz: item.dataset.kapaz || '0',
                                vgw01: item.dataset.vgw01 || '0',
                                vge01: item.dataset.vge01 || '',
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
                    showPreview(allocationData, totalWcCount);
                }

                return allocationData;
            }

            function sendFinalAllocation(data) {
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
                Swal.fire({
                    title: 'Menyimpan WI...',
                    text: 'Mohon tunggu, data sedang diproses. Jangan tutup jendela ini.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                const finalPayload = {
                    plant_code: PLANT_CODE,
                    document_date: documentDate,
                    document_time: documentTime,
                    workcenter_allocations: data
                };

                const url = '{{ route('wi.save') }}'; 

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(finalPayload)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(error => {
                                throw new Error(error.message || `Gagal menyimpan (Status: ${response.status})`);
                            });
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.documents) {
                            Swal.fire({
                                icon: 'success',
                                title: 'WI Berhasil Disimpan!',
                                html: `Total **${result.documents.length}** dokumen WI berhasil dibuat.`,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', result.message || 'Gagal menyimpan data ke server.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('AJAX Error:', error);
                        Swal.fire('Error Koneksi', error.message || 'Terjadi kesalahan jaringan atau server.', 'error');
                    });
            }

            function showPreview(data, totalWcCount) {
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
                    document.getElementById('btnFinalSave').disabled = true;
                    previewModalInstance.show();
                    return;
                }

                emptyWarning.classList.add('d-none');
                document.getElementById('btnFinalSave').disabled = false;

                let html = '';
                data.forEach(wc => {
                    const wcCard = document.querySelector(`[data-wc-id="${wc.workcenter}"]`);
                    const maxLoad = wcCard ? Math.ceil(parseFloat(wcCard.dataset.kapazWc) * 60) : 0;

                    html += `
                        <div class="col-lg-4 col-md-6"> 
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white border-bottom pt-3 pb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold text-dark">${wc.workcenter}</h6>
                                        <span class="badge bg-light text-primary border border-primary border-opacity-25">
                                            Load: ${wc.load_mins} / ${maxLoad} Min
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
                previewModalInstance.show();
            }
        </script>
    @endpush

</x-layouts.app>