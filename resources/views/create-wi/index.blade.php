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
                min-height: 150px;
            }
        </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4" style="max-width: 1600px;">

        {{-- PAGE HEADER & ACTIONS --}}
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h4 fw-bolder text-dark mb-1">Work Instruction</h1>
                <p class="text-muted small mb-0">Drag PRO untuk membuat Work Instruction.</p>
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
                        {{-- Loading Spinner --}}
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
                        <option value="{{ $emp['pernr'] }}" data-name="{{ $emp['stext'] }}">
                            {{ $emp['pernr'] }} - {{ $emp['stext'] }}
                        </option>
                    @endforeach
                </select>
                <div class="modal-body bg-light p-3">
                    <div class="bg-white p-3 rounded-3 shadow-sm border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                             <div class="fw-bold" id="modalProDetails"></div>
                        </div>
                        <div id="bulkWarning" class="alert alert-info d-none text-xs p-2 mb-0 border-0 bg-info bg-opacity-10 text-info rounded fw-bold">
                            <i class="fa-solid fa-list-check me-1"></i> Mode Pemetaan Massal
                        </div>
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

    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-clipboard-check me-2"></i>Tinjau ulang pemetaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
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

                    <h6 class="fw-bold text-dark mb-3 ps-1">Ringkasan Pembuatan Dokumen WI</h6>
                    <div id="previewContent" class="row g-3">
                        {{-- Content Injected Here --}}
                    </div>
                    
                    <div id="emptyPreviewWarning" class="alert alert-warning d-none mt-3 text-center border-0 shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Tidak ada data yang dimasukkan, silahkan cek kembali tim anda
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 py-3">
                    <button type="button" class="btn btn-light fw-bold text-muted" data-bs-dismiss="modal">Kembali</button>
                    <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnFinalSave">
                        <i class="fa-solid fa-paper-plane me-2"></i>Buat dan simpan WI
                    </button>
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


            document.addEventListener('DOMContentLoaded', function() {
                assignmentModalInstance = new bootstrap.Modal(document.getElementById('uniqueAssignmentModal'));
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
                document.getElementById('uniqueAssignmentModal').addEventListener('hide.bs.modal', function() {
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
                    onStart: function(evt) { 
                        document.body.classList.add('dragging-active');
                                               // CUSTOM VISUAL FOR BULK DRAG
                        const checked = document.querySelectorAll('#source-list .pro-item .row-checkbox:checked');
                        const draggedIsChecked = evt.item.querySelector('.row-checkbox')?.checked;
                        
                        // FIX: Populate draggedItemsCache based on selection
                        if (checked.length > 0 && draggedIsChecked) {
                            draggedItemsCache = Array.from(checked).map(cb => cb.closest('.pro-item'));
                        } else {
                            draggedItemsCache = [evt.item];
                        }
                        
                        // FIX: Count Logic - Only use checked length if dragged item is checked. 
                        // If it matches, the count is accurate because Sortable 'ghost' is in the list, but 'drag' mirror is NOT.
                        // So checking #source-list is correct.
                        
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
                    
                    // FIX: Don't overwrite cache if it was populated by Bulk Drag (onStart)
                    if (!draggedItemsCache || draggedItemsCache.length === 0) {
                        draggedItemsCache = [item]; 
                    }
                    
                    document.getElementById('mismatchCurrentWC').value = originWc;
                    document.getElementById('mismatchTargetWC').value = targetWcId;
                    
                    mismatchModalInstance.show();
                    return; // Stop execution
                }

                // Normal Flow
                processDrop(evt, item, toList, fromList, targetWcId);
            } // End setupDragAndDrop

            // Extracted logic to support resume after mismatch check
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

                // Setup Modal Title
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

                // CLEAR CONTAINER
                const container = document.getElementById('assignmentCardContainer');
                container.innerHTML = '';

                // NEW: CAPACITY INFO CARD (TOP OF MODAL)
                // Determine if Parent and get children data
                const normalizedTargetWc = targetWcId.toUpperCase();
                const hasChildren = PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWc);
                
                let childOptionsHtml = '<option value="">- Induk/None -</option>';
                let capacityInfoHtml = '';

                if (hasChildren) {
                    const children = PARENT_WORKCENTERS[normalizedTargetWc];
                    
                    // Capacity Logic
                    const wcContainer = document.querySelector(`.wc-card-container[data-wc-id="${targetWcId}"]`);
                    const kapazHours = parseFloat(wcContainer ? wcContainer.dataset.kapazWc : 0) || 0;
                    const totalMins = kapazHours * 60;
                    const childLimitMins = children.length > 0 ? (totalMins / children.length) : 0;
                    
                    // Build Child Options
                    children.forEach(child => {
                        childOptionsHtml += `<option value="${child.code}">${child.code}</option>`;
                    });
                    
                    // Build Top Info Card
                    capacityInfoHtml = `
                        <div class="card mb-3 border-secondary bg-light border-opacity-25">
                            <div class="card-body p-2"> 
                                <h6 class="text-uppercase fw-bold small text-muted mb-2">
                                    <i class="fa-solid fa-network-wired me-1"></i> ${targetWcId} Capacity Distribution
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-dark text-white pt-1 pb-1">Total: ${totalMins.toLocaleString()} Min</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                    <span class="badge bg-success text-white pt-1 pb-1">Limit/Child: ${childLimitMins.toLocaleString('en-US', {maximumFractionDigits: 1})} Min</span>
                                </div>
                                <div class="row g-2" id="topCapacityContainer">
                                    <!-- Child Status Bars Injected Here Dynamically via updateCardSummary -->
                                    ${children.map(child => `
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between text-xs fw-bold mb-1">
                                                <span>${child.code}</span>
                                                <span id="cap-text-${child.code}">0 / ${childLimitMins.toFixed(1)} Min</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div id="cap-bar-${child.code}" class="progress-bar bg-secondary" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Inject Top Card
                if (capacityInfoHtml) {
                    const capDiv = document.createElement('div');
                    capDiv.innerHTML = capacityInfoHtml;
                    container.appendChild(capDiv);
                }

                // Helper for Employee Options
                const empOptions = document.getElementById('employeeTemplateSelect').innerHTML;
                
                // GENERATE PRO CARDS
                draggedItemsCache.forEach((row, index) => {
                    const rAufnr = row.dataset.aufnr;
                    const rSisa = parseFloat(row.dataset.sisaQty) || 0;
                    // NEW: Time Calculation Data
                    const rVgw01 = parseFloat(row.dataset.vgw01) || 0; // Base time per 1 Qty
                    const rVge01 = row.dataset.vge01 || ''; // Unit (e.g. S)

                    // Create Card Element
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
                            <span class="fw-bold small"><i class="fa-solid fa-box me-1"></i> ${rAufnr}</span>
                            <span class="badge bg-secondary border border-light">Max: ${rSisa}</span>
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
                                        <select class="form-select form-select-sm child-select shadow-none border-secondary" onchange="updateCardSummary('${rAufnr}')" ${!hasChildren ? 'disabled' : ''}>
                                            ${childOptionsHtml}
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small text-muted fw-bold mb-0 d-none d-md-block">Qty</label>
                                        <input type="number" class="form-control form-control-sm qty-input shadow-none border-secondary fw-bold text-center" 
                                            value="${rSisa}" max="${rSisa}" min="0.1" step="any" oninput="updateCardSummary('${rAufnr}')" ${hasChildren ? 'disabled title="Pilih Sub-WC terlebih dahulu"' : ''}>
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
                    updateCardSummary(rAufnr); // Init logic
                });

                assignmentModalInstance.show();
            }
            window.addAssignmentRow = function(aufnr) {
                // Find Card
                const card = document.querySelector(`.pro-card[data-ref-aufnr="${aufnr}"]`);
                if (!card) return;

                const rowsContainer = card.querySelector('.assignment-rows');
                const lastRow = rowsContainer.lastElementChild;
                
                // Clone last row (to keep select options)
                const newRow = lastRow.cloneNode(true);
                
                // Reset inputs in new row
                const empSelect = newRow.querySelector('.emp-select');
                empSelect.value = "";
                empSelect.onchange = function() { updateEmpOptions(aufnr); };
                
                const childSelect = newRow.querySelector('.child-select');
                childSelect.value = "";
                childSelect.disabled = false;
                childSelect.classList.remove('border-danger', 'text-danger');
                childSelect.onchange = function() { updateCardSummary(aufnr); }; // Changed from updateChildWCOptions to updateCardSummary which calls it if needed or just updates logic

                const qtyInput = newRow.querySelector('.qty-input');
                qtyInput.value = ""; 
                qtyInput.placeholder = "0";
                qtyInput.classList.remove('is-invalid', 'text-danger');
                qtyInput.oninput = function() { updateCardSummary(aufnr); };
                
                // Check if card has children logic (Sub WC required logic)
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
                
                // Append
                rowsContainer.appendChild(newRow);
                
                // Update Logic
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

            // GLOBAL UPDATE & VALIDATION
            window.updateCardSummary = function(TriggerAufnr) {
                // Determine context
                const allCards = document.querySelectorAll('#assignmentCardContainer .pro-card');
                let globalChildUsage = {}; 
                let targetWcId = null;
                
                allCards.forEach(card => {
                    targetWcId = card.dataset.targetWc; 
                    const vgw01 = parseFloat(card.dataset.vgw01) || 0;
                    const vge01 = card.dataset.vge01 || '';
                    const hasChildren = card.dataset.hasChildren === 'true';
                    const maxQty = parseFloat(card.dataset.maxQty) || 0;

                    const rows = card.querySelectorAll('.assignment-row');
                    
                    // First pass: Calculate current total for this card to check Max Qty overflow
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
                                return; // Skip calc for this row
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
                                
                                // RESET VALUE
                                qtyInp.value = allowed; // This might be an integer or decimal depending on input
                                qty = allowed; // Update local var for time calc
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

                // 2. UPDATE TOP CAPACITY CARD (If exists)
                if (targetWcId) {
                    const normalizedTargetWc = targetWcId.toUpperCase();
                    if (PARENT_WORKCENTERS.hasOwnProperty(normalizedTargetWc)) {
                        const wcContainer = document.querySelector(`.wc-card-container[data-wc-id="${targetWcId}"]`);
                        const kapazHours = parseFloat(wcContainer ? wcContainer.dataset.kapazWc : 0) || 0;
                        const children = PARENT_WORKCENTERS[normalizedTargetWc]; // Get full list
                        const childCount = children.length;
                        const childLimitMins = childCount > 0 ? (kapazHours * 60 / childCount) : 0;
                        
                        // Update Bars for ALL children (Active and Inactive)
                        children.forEach(child => {
                            const childCode = child.code;
                            const usage = globalChildUsage[childCode] || 0;
                            const pct = childLimitMins > 0 ? (usage / childLimitMins) * 100 : 0;
                            
                            const bar = document.getElementById(`cap-bar-${childCode}`);
                            const text = document.getElementById(`cap-text-${childCode}`);
                            
                            if (bar && text) {
                                bar.style.width = Math.min(pct, 100) + '%';
                                text.innerText = `${usage.toFixed(1)} / ${childLimitMins.toFixed(1)} Min`;
                                
                                if (usage > childLimitMins) {
                                    bar.className = 'progress-bar bg-danger';
                                    text.classList.add('text-danger');
                                } else {
                                    bar.className = 'progress-bar bg-success';
                                    text.classList.remove('text-danger');
                                }
                            }
                        });
                    }
                }

                // 3. VALIDATE LOCAL CARD QTY (The Triggered Card)
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
                
                // Check 2: Capacity Limits (Red Bars)
                const redBars = document.querySelectorAll('#topCapacityContainer .bg-danger');
                if (redBars.length > 0) hasError = true;

                confirmBtn.disabled = hasError;
            };

            // REDESIGN: Unique Child WC Logic
            window.updateChildWCOptions = function(aufnr) {
                const card = document.querySelector(`.pro-card[data-ref-aufnr="${aufnr}"]`);
                if (!card) return;

                const selects = card.querySelectorAll('.child-select');
                
                // 1. Collect all currently selected values
                const selectedValues = [];
                selects.forEach(sel => {
                    if (sel.value) selectedValues.push(sel.value);
                });

                // 2. Update each select
                selects.forEach(sel => {
                    const myValue = sel.value;
                    const options = sel.querySelectorAll('option');
                    
                    options.forEach(opt => {
                        if (opt.value === "") return; // Skip placeholder
                        
                        // Disable if selected elsewhere AND not selected by me
                        if (selectedValues.includes(opt.value) && opt.value !== myValue) {
                            opt.disabled = true;
                            // Optional: style it
                        } else {
                            opt.disabled = false;
                        }
                    });
                });
            };

            function setupMismatchLogic() {
                // Button Cancel di Mismatch Modal
                document.getElementById('btnCancelMismatch').addEventListener('click', function() {
                    mismatchModalInstance.hide();
                    cancelDrop(); // Reuse existing cancel logic
                });

                // Button Change WC
                document.getElementById('btnChangeWC').addEventListener('click', async function() {
                    // Use Cache if available (Bulk Drag), otherwise single item
                    const itemsToProcess = (draggedItemsCache && draggedItemsCache.length > 0) 
                        ? draggedItemsCache 
                        : (pendingMismatchItem ? [pendingMismatchItem] : []);

                    if (itemsToProcess.length === 0) return;

                    const targetWc = document.getElementById('mismatchTargetWC').value;
                    
                    // Construct PRO data matching the structure needed by backend
                    const proData = itemsToProcess.map(item => ({
                        proCode: item.dataset.aufnr,
                        oper: item.dataset.vornr || '0010',
                        pwwrk: item.dataset.pwwrk || ''
                    }));

                    mismatchModalInstance.hide();
                    cancelDrop(); // Revert drag visual

                    // Trigger Streaming Process
                    await executeBulkChangeStream(targetWc, proData);
                    itemsToProcess.forEach(item => {
                         const pendingRows = document.querySelectorAll(`[data-aufnr="${item.dataset.aufnr}"]`);
                         pendingRows.forEach(r => {
                            r.dataset.arbpl = targetWc;
                            const badge = r.querySelector('.badge.bg-light.text-dark'); // WC Badge
                            if(badge && badge.innerText.trim() !== targetWc) badge.innerText = targetWc; 
                        });
                    });

                    // 2. Call processDrop to open assignment modal
                    // We use cached containers from the initial drag
                    // Pass the first item as 'item' argument, processDrop will check cache.
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
                // Modified: Also trigger NIK filtering when UI updates
                updateEmpOptions(proAufnr);

                const childWcSelect = document.getElementById('childWorkcenterSelect');
                if(!childWcSelect) return; // Guard clause

                childWcSelect.innerHTML = '<option value="" selected disabled>Pilih Workcenter Anak...</option>';
                childWcSelect.disabled = false;

                // AMBIL SEMUA CHILD WC YANG SUDAH DIALOKASIKAN/DIPILIH SEMENTARA
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

                const allSelects = Array.from(card.querySelectorAll('.emp-select'));

                const selectedNiks = allSelects
                    .map(sel => sel.value)
                    .filter(val => val !== "");

                allSelects.forEach(select => {
                    const currentVal = select.value;
                    const options = Array.from(select.options);

                    options.forEach(opt => {
                        if (!opt.value) return;
                        if (selectedNiks.includes(opt.value) && opt.value !== currentVal) {
                             opt.disabled = true;
                             if(!opt.innerText.includes('(Selected)')) {
                                 opt.innerText += ' (Selected)';
                             }
                        } else {
                             opt.disabled = false;
                             opt.innerText = opt.innerText.replace(' (Selected)', '');
                        }
                    });
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
                        const name = empSelect.options[empSelect.selectedIndex]?.dataset.name || ''; // Assuming options have data-name or innerText
                        // Fallback name extraction if template used value - text format
                        const rawText = empSelect.options[empSelect.selectedIndex]?.innerText || '';
                        
                        const childWc = childSelect.value;
                        const qty = parseFloat(qtyInput.value) || 0;
                        
                        // Row Validation
                        if (!nik || qty <= 0) {
                            allValid = false;
                            row.classList.add('border', 'border-danger'); // Add visual error
                        } else {
                            row.classList.remove('border', 'border-danger');
                        }
                        
                        // Collect Assignment
                        if (nik && qty > 0) {
                            const item = draggedItemsCache.find(i => i.dataset.aufnr == aufnr); 
                            
                            if (item) {
                                assignments.push({ item, nik, name: name || rawText, childWc, qty });
                            }
                        }
                        
                        currentSum += qty;
                    });
                    
                    // Card Total Validation
                    if (currentSum > maxQty) {
                        allValid = false;
                        validationErrors.push(`Total Qty for ${aufnr} exceeds limit (${currentSum} > ${maxQty})`);
                        // Highlight card border
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
                     const item = group[0].item; // Original Item
                     const sisaOriginal = parseFloat(item.dataset.sisaQty) || 0;
                     const totalAssigned = group.reduce((sum, a) => sum + a.qty, 0);
                     
                     // 1. Check Remaining
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
                     
                     // 2. Process Assignments
                     group.forEach((assign, idx) => {
                         let targetItem;
                         if (idx === 0) {
                             targetItem = item; // Use Original
                         } else {
                             targetItem = item.cloneNode(true); // Clone for split
                             targetItem.dataset.id = Date.now() + Math.random();
                         }
                         
                         // Move & Update
                         transformToCardView(targetItem);
                         targetContainerCache.appendChild(targetItem);
                         
                         targetItem.dataset.sisaQty = assign.qty; // Adjusted to assigned portion
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

                            // Hapus klon/asli yang ada di WC
                            item.remove();

                        } else {
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
                // Modified for Server-Side Search
                let timeout = null;
                const searchInput = document.getElementById('searchInput');
                const scrollArea = document.querySelector('.table-scroll-area');
                const spinner = document.getElementById('loadingSpinner');
                const endOfData = document.getElementById('endOfData');
                const tbody = document.getElementById('source-list');

                let nextPage = {{ $nextPage ?? 'null' }};
                let isLoading = false;
                let currentSearch = '';

                // 1. Search Logic (Debounced)
                searchInput.addEventListener('keyup', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        currentSearch = this.value;
                        resetAndFetch();
                    }, 500); 
                });

                 // 2. Infinite Scroll Logic
                scrollArea.addEventListener('scroll', function() {
                    if (isLoading || !nextPage) return;
                    
                    const threshold = 100; // px from bottom
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
                    if(search) url.searchParams.set('search', search);
                    else url.searchParams.delete('search');

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
                        
                        // Show "End" if no more pages
                        if (!nextPage) {
                            endOfData.classList.remove('d-none');
                        }

                        // Re-initialize Drag (Sortable) if necessary?
                        // Sortable usually observes DOM, but checks if new items are draggable.
                        // Since we append to #source-list which is the Sortable container, it should work.
                        
                    })
                    .catch(e => console.error('Load Error:', e))
                    .finally(() => {
                        isLoading = false;
                        spinner.classList.add('d-none');
                    });
                }
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
                                name1: item.dataset.name1 || 'N/A',
                                netpr: item.dataset.netpr || '0',
                                waerk: item.dataset.waerk || 'N/A',
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
            // 1. Tombol "Change WC" diklik
            function requestChangeWc() {
                // Cek item yang dicentang di tabel sumber
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
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm" onclick="startBulkChangeStream()">
                        <i class="fa-solid fa-play me-1"></i> Mulai Proses
                    </button>
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
</x-layouts.app>