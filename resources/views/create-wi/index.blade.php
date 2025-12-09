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
                cursor: grab; /* Show grab cursor */
            }
            tr.pro-item:hover { background-color: #f8fafc; }
            tr.pro-item:last-child { border-bottom: none; }
            
            tr.selected-row {
                background-color: #eff6ff !important; /* Light Blue */
                border-left: 3px solid var(--primary-blue);
            }

            /* Drag Handle */
            .drag-handle {
                color: #cbd5e1;
                cursor: grab;
                transition: color 0.2s;
            }
            tr.pro-item:hover .drag-handle { color: var(--text-secondary); }
            .drag-handle:active { cursor: grabbing; color: var(--primary-blue); }

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
                                <small class="text-muted">Production Request Orders (PRO)</small>
                            </div>
                        </div>

                        <div class="search-input-group px-3 py-1 d-flex align-items-center" style="width: 350px;">
                            <i class="fa-solid fa-magnifying-glass text-muted me-2"></i>
                            <input type="text" id="searchInput" class="form-control form-control-sm p-0" placeholder="Search Material, PRO, or SO...">
                        </div>
                    </div>

                    {{-- Table Content --}}
                    <div class="table-scroll-area">
                        <table class="table table-custom mb-0 w-100 align-middle source-table" id="proTable">
                            <thead class="sticky-top">
                                <tr>
                                    <th class="text-center ps-3" width="40">
                                        <input class="form-check-input pointer" type="checkbox" id="selectAll">
                                    </th>
                                    <th class="ps-3">PRO Number</th>
                                    <th>Sales Order</th>
                                    <th>Material Description</th>
                                    <th class="text-center">Origin WC</th>
                                    <th class="text-center">Op. Key</th>
                                    <th class="text-center">Qty Total</th>
                                    <th class="text-center">Qty Confirmed</th>
                                    <th class="text-center bg-white" width="40"></th>
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

                                        {{-- 3. Info Columns --}}
                                        <td class="table-col text-muted small">{{ $soItem }}</td>
                                        <td class="table-col">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark small">{{ $matnr }}</span>
                                                <span class="text-secondary text-truncate small" style="max-width: 200px;">{{ $item->MAKTX }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center table-col"><span class="badge bg-light text-dark border">{{ $item->ARBPL }}</span></td>
                                        <td class="text-center table-col"><span class="badge bg-light text-secondary border">{{ $item->STEUS }}</span></td>
                                        <td class="text-center table-col fw-bold text-dark">{{ number_format($item->MGVRG2, 2, ',', '.') }}</td>
                                        <td class="text-center table-col text-muted">{{ number_format($item->LMNGA, 2, ',', '.') }}</td>
                                        
                                        {{-- 4. Drag Handle --}}
                                        <td class="text-center table-col drag-handle">
                                            <i class="fa-solid fa-grip-vertical"></i>
                                        </td>

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

                        @if (!$isUnknown)
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
                        @endif
                    @endforeach
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

                    <div class="mb-3" id="childWorkcenterField">
                        <label class="form-label text-xs fw-bold text-uppercase text-muted">Target Line (Sub-WC)</label>
                        <select class="form-select form-select-sm" id="childWorkcenterSelect">
                            <option value="" selected disabled>Select Sub-WC...</option>
                        </select>
                        <div id="childWorkcenterHelp" class="form-text text-xs d-none text-info">
                            <i class="fa-solid fa-circle-info me-1"></i> Parent WC requires Sub-WC selection.
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
            console.groupCollapsed("INITIAL DATA LOAD & PROCESSING COUNTS");
            console.log("1. Final Workcenter Hierarchy (from Backend):", Object.keys(PARENT_WORKCENTERS).length,
                PARENT_WORKCENTERS);
            console.log("2. Total Unassigned Orders (from Table, tData1):", document.querySelectorAll(
                '#source-list tr.pro-item').length);
            console.groupEnd();

            let draggedItemsCache = [];
            let targetContainerCache = null;
            let sourceContainerCache = null;
            let assignmentModalInstance = null;
            let previewModalInstance = null;
            let tempSplits = [];
            let currentSisaQty = 0;


            document.addEventListener('DOMContentLoaded', function() {
                console.groupCollapsed("INITIAL DATA LOAD & PROCESSING COUNTS");
                console.log("1. Final Workcenter Hierarchy (from Backend):", Object.keys(PARENT_WORKCENTERS).length,
                    PARENT_WORKCENTERS);
                console.log("2. Total Unassigned Orders (from Table, tData1):", document.querySelectorAll(
                    '#source-list tr.pro-item').length);
                console.groupEnd();

                assignmentModalInstance = new bootstrap.Modal(document.getElementById('assignmentModal'));
                previewModalInstance = new bootstrap.Modal(document.getElementById('previewModal'));

                calculateAllRows();
                setupSearch();
                setupCheckboxes();
                setupDragAndDrop();
                setupModalLogic();

                document.getElementById('btnFinalSave').addEventListener('click', function() {
                    const finalData = saveAllocation(false);
                    console.groupCollapsed("FINAL DATA PAYLOAD");
                    console.log("Final Allocation Data:", finalData);
                    console.log("Total Assigned Workcenters:", finalData.length);
                    console.groupEnd();

                    sendFinalAllocation(finalData);
                });

                document.getElementById('assignmentModal').addEventListener('hide.bs.modal', function() {
                    if (draggedItemsCache.length > 0 && tempSplits.length === 0) {
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
                    handle: '.drag-handle',
                    forceFallback: true,
                    fallbackClass: "sortable-drag",
                    ghostClass: "sortable-ghost",
                    selectedClass: 'selected-row',
                    sort: false,
                    onStart: function(evt) { document.body.classList.add('dragging-active'); },
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
                        onRemove: function(evt) {
                            document.body.classList.remove('dragging-active');
                            handleReturnToTable(evt.item, evt.from);
                        },
                        onEnd: function(evt) {
                            document.body.classList.remove('dragging-active');
                            updateCapacity(zone.closest('.wc-card-container'));
                        }
                    });
                    checkEmptyPlaceholder(zone);
                });
            }

            /**
             * Mengumpulkan Workcenter Anak yang sudah di-assign untuk PRO spesifik (aufnr)
             * dari SEMUA Workcenter drop zone dan dari tempSplits.
             */
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
                const normalizedTargetWcId = targetWcId.toUpperCase();
                const proAufnr = item.dataset.aufnr;
                const proArbpl = item.dataset.arbpl; // Ambil ARBPL dari data-attribute

                // 1. VALIDASI ARBPL (Pastikan PRO hanya bisa di-drop ke WC yang sesuai dengan ARBPL-nya)
                const normalizedProArbpl = proArbpl.toUpperCase();

                if (normalizedProArbpl !== normalizedTargetWcId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Workcenter Tidak Cocok',
                        text: `PRO ini berasal dari Workcenter ${proArbpl}. Tidak dapat dialokasikan ke Workcenter ${targetWcId}.`,
                        confirmButtonText: 'OK'
                    });
                    // Kembalikan item ke tempat asal
                    if (fromList) fromList.appendChild(item);
                    transformToTableView(item);
                    return;
                }


                targetContainerCache = toList;
                sourceContainerCache = fromList;
                draggedItemsCache = [];
                tempSplits = []; // RESET SPLIT SEMENTARA

                // Set Qty Awal untuk split
                currentSisaQty = parseFloat(item.dataset.sisaQty) || 0;
                document.getElementById('modalProAufnr').innerText = proAufnr;

                // SEGERA UBAH TAMPILAN JADI CARD SUPAYA RAPI DI KOTAK
                transformToCardView(item);

                const checkbox = item.querySelector('.row-checkbox');
                if (checkbox && checkbox.checked) {
                    const allChecked = document.querySelectorAll('#source-list .pro-item .row-checkbox:checked');
                    allChecked.forEach(cb => {
                        draggedItemsCache.push(cb.closest('.pro-item'));
                    });
                    if (!draggedItemsCache.includes(item)) draggedItemsCache.push(item);
                } else {
                    draggedItemsCache.push(item);
                }

                // Tampilkan Sisa Qty awal
                document.getElementById('remainingQtyDisplay').innerText = currentSisaQty.toLocaleString('id-ID');


                // --- LOGIC UNTUK WORKCENTER ANAK ---
                const isParentWC = PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWcId);
                const childWcField = document.getElementById('childWorkcenterField');
                const childWcSelect = document.getElementById('childWorkcenterSelect');
                const childWcHelp = document.getElementById('childWorkcenterHelp');
                const qtyInput = document.getElementById('inputAssignQty');

                childWcSelect.innerHTML = '<option value="" selected disabled>Pilih Workcenter Anak...</option>';
                childWcSelect.disabled = true;

                // Logic Multi-Split hanya bekerja untuk Single Item Drop
                const isSingleItemDrop = draggedItemsCache.length === 1;

                if (isParentWC && isSingleItemDrop) {
                    childWcField.classList.remove('d-none');
                    childWcHelp.classList.remove('d-none');
                    childWcSelect.disabled = false;

                    updateChildWCDropdown(normalizedTargetWcId, proAufnr); // Panggil fungsi pembaruan dropdown

                } else {
                    childWcField.classList.add('d-none');
                    childWcHelp.classList.add('d-none');

                    // Disable Add Split dan Confirm untuk Bulk Mode (saat ini tidak didukung)
                    if (!isSingleItemDrop) {
                        document.getElementById('btnAddSplit').disabled = true;
                        document.getElementById('btnConfirmFinalAssignment').disabled = true;
                        qtyInput.disabled = true;
                    }

                    // LOGGING: Workcenter Drop
                    console.log(`Drop ke WC Non-Induk (${targetWcId}). No children dropdown needed.`);
                }
                // --- END LOGIC UNTUK WORKCENTER ANAK ---

                const empSelect = document.getElementById('employeeSelect');
                const bulkWarning = document.getElementById('bulkWarning');
                const maxQtyLabel = document.getElementById('maxQtyLabel');

                empSelect.value = "";
                childWcSelect.value = ""; // Reset Child WC selection
                qtyInput.value = currentSisaQty; // Set Qty default ke sisa

                if (!isSingleItemDrop) {
                    bulkWarning.classList.remove('d-none');
                    maxQtyLabel.innerText = "Bulk Mode";
                    qtyInput.placeholder = "Qty per Item";
                    document.getElementById('btnAddSplit').disabled = true;

                    // LOGGING: Bulk Drop
                    console.log(`Drop Mode: BULK (${draggedItemsCache.length} items)`);
                } else {
                    bulkWarning.classList.add('d-none');
                    maxQtyLabel.innerText = "Max: " + currentSisaQty.toLocaleString('id-ID'); // Format angka
                    qtyInput.max = currentSisaQty;
                    document.getElementById('btnAddSplit').disabled = true; // Akan divalidasi oleh validateForm

                    // LOGGING: Single Drop
                    console.log(`Drop Mode: SINGLE. PRO: ${item.dataset.aufnr}, Sisa Qty: ${currentSisaQty}`);
                }

                // Sembunyikan review section jika tidak ada split
                document.getElementById('splitReviewSection').classList.add('d-none');
                document.getElementById('btnConfirmFinalAssignment').disabled = true;

                assignmentModalInstance.show();
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
                const countSpan = document.getElementById('totalSplitsCount');
                list.innerHTML = '';
                countSpan.innerText = tempSplits.length;

                if (tempSplits.length > 0) {
                    document.getElementById('splitReviewSection').classList.remove('d-none');
                } else {
                    document.getElementById('splitReviewSection').classList.add('d-none');
                }

                // PERBAIKAN: Tombol konfirmasi aktif jika ada split (tidak perlu Qty habis)
                document.getElementById('btnConfirmFinalAssignment').disabled = (tempSplits.length === 0);


                tempSplits.forEach((split, index) => {
                    const listItem = document.createElement('li');
                    listItem.className = 'list-group-item d-flex justify-content-between align-items-center px-0';
                    listItem.innerHTML = `
                        <div class="d-flex flex-column">
                            <strong>Qty: ${split.qty.toLocaleString('id-ID')}</strong> 
                            <span class="text-muted" style="font-size: 0.75rem;">Opr: ${split.name} (${split.nik})</span>
                            ${split.childWc ? `<span class="text-primary small">Line: ${split.childWc}</span>` : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" data-index="${index}" onclick="removeSplit(${index})">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    `;
                    list.appendChild(listItem);
                });

                // Update sisa Qty display
                document.getElementById('remainingQtyDisplay').innerText = currentSisaQty.toLocaleString('id-ID');

                // Update Max Qty label pada input
                document.getElementById('maxQtyLabel').innerText = "Max: " + currentSisaQty.toLocaleString('id-ID');
                document.getElementById('inputAssignQty').max = currentSisaQty;

                // Re-validate Add Split button
                validateForm();
            }

            function removeSplit(index) {
                // Tambahkan Qty kembali ke currentSisaQty
                const removedQty = tempSplits[index].qty;
                currentSisaQty += removedQty;

                // Hapus split dari array
                tempSplits.splice(index, 1);

                // Update UI dan tracking
                updateTempSplitList();

                // Jika Workcenter Induk, update dropdown WC Anak
                const item = draggedItemsCache[0];
                const targetWcId = targetContainerCache.closest('.wc-card-container').dataset.wcId;
                const normalizedTargetWcId = targetWcId.toUpperCase();
                const isParentWC = PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWcId);

                if (isParentWC && item) {
                    // Logic ini akan diatasi oleh updateChildWCDropdown yang memanggil getAssignedChildWCs
                    updateChildWCDropdown(normalizedTargetWcId, item.dataset.aufnr);
                }

                // Jika Qty sudah habis, nonaktifkan input Qty
                const qtyInput = document.getElementById('inputAssignQty');
                if (currentSisaQty > 0) {
                    qtyInput.disabled = false;
                    qtyInput.value = currentSisaQty;
                } else {
                    qtyInput.disabled = true;
                    qtyInput.value = 0;
                }

                console.log(`Split removed. Returned Qty: ${removedQty}. New Sisa Qty: ${currentSisaQty}`);
            }


            function setupModalLogic() {
                const empSelect = document.getElementById('employeeSelect');
                const qtyInput = document.getElementById('inputAssignQty');
                const btnAddSplit = document.getElementById('btnAddSplit');
                const btnConfirmFinalAssignment = document.getElementById('btnConfirmFinalAssignment');
                const btnCancel = document.getElementById('btnCancelDrop');
                const childWcSelect = document.getElementById('childWorkcenterSelect');

                window.validateForm = function() { // Exposed globally for use in updateTempSplitList
                    const hasEmp = empSelect.value !== "";
                    const hasQty = qtyInput.value !== "" && parseFloat(qtyInput.value) > 0;

                    // Handle single item drop only
                    const isSingleItemDrop = draggedItemsCache.length === 1;
                    if (!isSingleItemDrop) return; // Nonaktifkan validasi form jika bulk drop

                    const targetWcId = targetContainerCache.closest('.wc-card-container').dataset.wcId;
                    const isParentWC = PARENT_WORKCENTERS.hasOwnProperty(targetWcId.toUpperCase());

                    let hasChildWc = true;
                    if (isParentWC) {
                        // Wajib pilih Child WC jika Induk dan masih ada opsi yang tersedia
                        hasChildWc = childWcSelect.value !== "" && !childWcSelect.disabled;
                    }

                    const isValidInput = hasEmp && hasQty && hasChildWc;

                    // Batasi Qty agar tidak melebihi sisa
                    const requestedQty = parseFloat(qtyInput.value);
                    const isQtyValidRange = requestedQty <= currentSisaQty;

                    // Tombol Add Split hanya aktif jika input valid dan Qty dalam jangkauan
                    btnAddSplit.disabled = !(isValidInput && isQtyValidRange);

                    // PERBAIKAN: Tombol Confirm Assignment aktif jika ada split (tidak perlu Qty habis)
                    btnConfirmFinalAssignment.disabled = (tempSplits.length === 0);
                }

                empSelect.addEventListener('change', validateForm);
                qtyInput.addEventListener('input', validateForm);
                childWcSelect.addEventListener('change', validateForm);

                // Menghindari konflik dengan btnConfirmDrop lama, ganti ID ke btnAddSplit
                btnAddSplit.addEventListener('click', function() {
                    if (btnAddSplit.disabled) return;

                    const inputQty = parseFloat(qtyInput.value);
                    const maxQty = currentSisaQty;

                    if (inputQty > maxQty) {
                        Swal.fire({
                            icon: 'warning',
                            text: 'Kuantitas melebihi sisa yang tersedia.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        return;
                    }

                    const nik = empSelect.value;
                    const name = empSelect.options[empSelect.selectedIndex].dataset.name;
                    const selectedChildWc = childWcSelect.value || '';

                    // Simpan Split Sementara
                    tempSplits.push({
                        nik: nik,
                        name: name,
                        qty: inputQty,
                        childWc: selectedChildWc
                    });

                    // Update Sisa Qty
                    currentSisaQty -= inputQty;

                    // Reset Input Form
                    empSelect.value = "";
                    // childWcSelect.value = ""; // JANGAN reset WC Anak agar bisa menambah split ke WC Anak yang sama
                    qtyInput.value = currentSisaQty > 0 ? currentSisaQty : 0;

                    // Update tampilan list split dan dropdown WC Anak
                    updateTempSplitList();

                    const item = draggedItemsCache[0];
                    const targetWcId = targetContainerCache.closest('.wc-card-container').dataset.wcId;
                    const normalizedTargetWcId = targetWcId.toUpperCase();

                    // Update dropdown untuk Workcenter Induk
                    if (PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWcId)) {
                        updateChildWCDropdown(normalizedTargetWcId, item.dataset.aufnr);
                    }

                    // Jika Qty sudah habis, nonaktifkan input Qty
                    if (currentSisaQty === 0) {
                        qtyInput.disabled = true;
                        qtyInput.value = 0;
                    } else {
                        qtyInput.disabled = false;
                    }

                    console.log(`Split added. Remaining Qty: ${currentSisaQty}`);
                });

                // Tombol Confirm Final Assignment
                btnConfirmFinalAssignment.addEventListener('click', confirmFinalAssignment);

                // Tombol Cancel Drop
                btnCancel.addEventListener('click', cancelDrop);

            }

            function confirmFinalAssignment() {
                // TIDAK PERLU CEK currentSisaQty > 0 lagi, karena partial split diperbolehkan
                if (tempSplits.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak Ada Alokasi',
                        text: `Harap tambahkan setidaknya satu alokasi Quantity sebelum konfirmasi.`,
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Ambil item PRO asli yang di-drag (hanya satu, karena mode single split)
                const originalRow = draggedItemsCache[0];
                const originalSisaQty = parseFloat(originalRow.dataset.sisaQty) || 0;
                const targetWcId = targetContainerCache.closest('.wc-card-container').dataset.wcId;

                // Menghitung total added minutes untuk pengecekan kapasitas final
                let totalAddedMinutes = 0;
                tempSplits.forEach(split => {
                    totalAddedMinutes += calculateItemMinutes(originalRow, split.qty);
                });

                const wcContainer = targetContainerCache.closest('.wc-card-container');
                const kapazHours = parseFloat(wcContainer.dataset.kapazWc) || 0;
                const maxMins = kapazHours * 60;
                let currentLoadMins = 0;

                wcContainer.querySelectorAll('.pro-item-card').forEach(item => {
                    currentLoadMins += parseFloat(item.dataset.calculatedMins) || 0;
                });

                if ((currentLoadMins + totalAddedMinutes) > maxMins) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Over Capacity!',
                        text: `Tidak dapat memproses. Total beban dari alokasi baru (${Math.ceil(currentLoadMins)} menit) akan melebihi kapasitas Workcenter (${Math.ceil(maxMins)} menit).`,
                        confirmButtonText: 'OK'
                    });
                    return; // Jangan lanjutkan jika melebihi kapasitas
                }


                // --- Proses Implementasi Split ---

                let firstSplit = true;
                let finalAssignedChildWcs = [];
                let totalAssignedQty = 0;

                tempSplits.forEach(split => {
                    let rowToUpdate;
                    totalAssignedQty += split.qty;

                    if (firstSplit) {
                        // BARIS ASLI (Diperlakukan sebagai split pertama)
                        rowToUpdate = originalRow;
                        targetContainerCache.appendChild(originalRow); // Pindahkan baris asli ke drop zone
                        firstSplit = false;

                        // Karena baris asli pindah, kita perlu mereset status baris di tabel sumber
                        originalRow.classList.remove('selected-row');
                        const cb = originalRow.querySelector('.row-checkbox');
                        if (cb) cb.checked = false;

                    } else {
                        // BARIS CLONE (Untuk split kedua dst.)
                        const clonedRow = originalRow.cloneNode(true);
                        clonedRow.dataset.id = Date.now() + Math.random(); // Beri ID unik
                        clonedRow.dataset.currentQty = originalRow.dataset.qtyOpr; // Quantity total PRO tetap sama
                        clonedRow.dataset.assignedChildWcs = originalRow.dataset
                            .assignedChildWcs; // Warisi tracking WC Anak

                        targetContainerCache.appendChild(clonedRow);
                        rowToUpdate = clonedRow;
                    }

                    // Update data-attributes untuk baris saat ini (asli atau clone)
                    rowToUpdate.dataset.assignedQty = split.qty;
                    // rowToUpdate.dataset.sisaQty = 0; // TIDAK PERLU DISET 0 DI SINI
                    rowToUpdate.dataset.childWc = split.childWc;

                    // PERBAIKAN BUG BADGE TIDAK MUNCUL:
                    // Pastikan transformToCardView sudah diterapkan dan data di-update di sini
                    transformToCardView(rowToUpdate);
                    updateRowUI(rowToUpdate, split.nik, split.name, split.qty, split.childWc);

                    // Kumpulkan Workcenter Anak yang digunakan
                    if (split.childWc && !finalAssignedChildWcs.includes(split.childWc)) {
                        finalAssignedChildWcs.push(split.childWc);
                    }
                });

                // UPDATE TRACKING CHILD WC PADA SEMUA ITEM DENGAN AUFNR YANG SAMA
                finalAssignedChildWcs.forEach(wc => updateAssignedChildWCs(originalRow.dataset.aufnr, wc));

                // LOGIKA PENTING UNTUK PARTIAL SPLIT
                const finalSisaQty = currentSisaQty;
                const sourceItemInTable = document.querySelector(
                    `#source-list .pro-item[data-aufnr="${originalRow.dataset.aufnr}"]`);

                if (finalSisaQty > 0 && sourceItemInTable) {
                    // Jika masih ada sisa, kloning item ASLI kembali ke tabel sumber dengan sisa Qty
                    const remainingRow = originalRow.cloneNode(true);
                    remainingRow.dataset.id = Date.now() + Math.random() + 1;
                    remainingRow.dataset.sisaQty = finalSisaQty;
                    remainingRow.dataset.assignedQty = 0; // Tidak ada yang di-assign di sini
                    remainingRow.dataset.assignedChildWcs = originalRow.dataset
                        .assignedChildWcs; // Pertahankan tracking WC Anak

                    // Reset data assignment untuk item sisa
                    remainingRow.dataset.employeeNik = "";
                    remainingRow.dataset.employeeName = "";
                    remainingRow.dataset.childWc = "";

                    // Pastikan item sisa kembali ke Table View
                    transformToTableView(remainingRow);

                    // Perbarui tampilan sisa qty di item sisa
                    const sisaCell = remainingRow.querySelector('.col-sisa-qty');
                    if (sisaCell) {
                        sisaCell.innerText = finalSisaQty.toLocaleString('id-ID');
                        sisaCell.classList.remove('text-success');
                        sisaCell.classList.add('text-danger');
                    }

                    // Masukkan kembali ke table sumber
                    document.getElementById('source-list').appendChild(remainingRow);

                }

                // Clear splits dan tutup modal
                tempSplits = [];
                currentSisaQty = 0; // Reset state global
                draggedItemsCache = [];

                document.getElementById('selectAll').checked = false;
                updateCapacity(wcContainer);
                checkEmptyPlaceholder(targetContainerCache);
                assignmentModalInstance.hide();

                console.log(
                    `Final Assignment Confirmed. Total Qty Assigned: ${totalAssignedQty}. Qty Remaining: ${finalSisaQty}`
                );
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
                const wcId = fromContainer ? fromContainer.closest('.wc-card-container').dataset.wcId : 'Unknown';
                const returnedChildWc = item.dataset.childWc;
                const sourceItem = document.querySelector(`#source-list .pro-item[data-aufnr="${item.dataset.aufnr}"]`);
                const returnedQty = parseFloat(item.dataset.assignedQty) || 0;
                if (fromContainer && fromContainer.id !== 'source-list') {
                    const targetItemToUpdate = sourceItem || item;
                    let sisaQtyOriginal = parseFloat(targetItemToUpdate.dataset.sisaQty) || 0;
                    sisaQtyOriginal += returnedQty;

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

                    // LOGGING: Child WC Removed
                    console.log(
                        `Tracking update for PRO ${aufnr}: Removed Child WC ${childWcToRemove}. Remaining: ${assignedWCs.join(', ')}`
                    );
                });
            }

            function updateRowUI(row, nik, name, qty, childWc, childWcName) {
                row.dataset.employeeNik = nik;
                row.dataset.employeeName = name;
                row.dataset.childWc = childWc; // Simpan kode Child WC

                // PERBAIKAN BUG BADGE: Pastikan data untuk badge diisi
                row.querySelector('.employee-name-text').innerText = `${name} (${nik || '-'})`;
                row.querySelector('.assigned-qty-badge').innerText = 'Qty: ' + qty.toLocaleString('id-ID');

                const childWcDisplay = row.querySelector('.child-wc-display');
                if (childWc) {
                    // Tampilkan Child WC jika ada
                    childWcDisplay.innerText = `Line: ${childWc}`;
                    childWcDisplay.classList.remove('d-none');
                } else {
                    childWcDisplay.innerText = '';
                    childWcDisplay.classList.add('d-none');
                }

                const mins = calculateItemMinutes(row, qty);
                row.dataset.calculatedMins = mins;

                // LOGGING: Update UI/Data
                // console.log(`PRO ${row.dataset.aufnr} updated. Qty: ${qty}, Mins: ${mins.toFixed(2)}, Child WC: ${childWc || 'None'}`);
            }

            function transformToCardView(row) {
                // LOGGING: Perubahan Tampilan
                // console.log(`PRO ${row.dataset.aufnr} transitioned to Card View.`);
                row.classList.remove('draggable-item');
                row.classList.add('pro-item-card');
                row.querySelectorAll('.table-col').forEach(el => el.style.display = 'none');
                row.querySelector('.card-view-content').style.display = 'block';
                const handle = row.querySelector('.drag-handle');
                if (handle) handle.style.display = 'none';
            }

            function transformToTableView(row) {
                // LOGGING: Perubahan Tampilan
                // console.log(`PRO ${row.dataset.aufnr} transitioned to Table View.`);
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

                if (!documentDate || !documentTime) { // <-- Validasi Tanggal DAN Jam
                    Swal.fire('Perhatian!', 'Tanggal dan Jam Dokumen harus diisi.', 'warning');
                    return; // Hentikan proses
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

                const url = '{{ route('wi.save') }}'; // Menggunakan helper route Laravel

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
