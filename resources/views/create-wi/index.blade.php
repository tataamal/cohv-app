<x-layouts.app title="Create Work Instruction">

    @push('styles')
        <style>
            :root {
                --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                --glass-bg: rgba(255, 255, 255, 0.95);
                --border-color: #dfe3e8;
            }

            body {
                background-color: #f4f6f8;
            }

            body.dragging-active {
                user-select: none !important;
                cursor: grabbing !important;
            }

            body.dragging-active * {
                user-select: none !important;
            }

            /* TABLE STYLING */
            .custom-table-card {
                border: 1px solid var(--border-color);
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                background: white;
                overflow: hidden;
            }

            .table-responsive-custom {
                height: calc(100vh - 240px);
                min-height: 500px;
                overflow-y: auto;
            }

            thead.sticky-header th {
                position: sticky;
                top: 0;
                background-color: #f8f9fa;
                z-index: 10;
                border-bottom: 2px solid #e9ecef;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
                color: #64748b;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }

            tr.pro-item {
                transition: all 0.1s ease;
                border-bottom: 1px solid #f1f5f9;
            }

            tr.pro-item:hover {
                background-color: #f8fafc;
                z-index: 5;
                position: relative;
            }

            tr.selected-row {
                background-color: #eff6ff !important;
                border-left: 3px solid #3b82f6 !important;
            }

            /* DRAG HANDLE */
            .drag-handle {
                cursor: grab;
                color: #94a3b8;
                transition: color 0.2s;
                padding: 10px;
                width: 40px;
                text-align: center;
                user-select: none;
            }

            .drag-handle:hover {
                color: #3b82f6;
                background-color: #f1f5f9;
            }

            .drag-handle:active {
                cursor: grabbing;
            }

            /* DRAG PREVIEW */
            tr.sortable-drag {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                display: block !important;
                width: auto !important;
                opacity: 1 !important;
            }

            tr.sortable-drag td {
                display: none !important;
            }

            tr.sortable-drag td.preview-container {
                display: block !important;
                border: none !important;
                padding: 0 !important;
                background: transparent !important;
            }

            tr.sortable-drag td.preview-container>.original-content {
                display: none !important;
            }

            tr.sortable-drag .drag-preview-icon {
                display: flex !important;
                align-items: center;
                gap: 12px;
                background: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                border: 1px solid #e2e8f0;
                width: max-content;
                transform: rotate(2deg) scale(1.02);
                pointer-events: none;
            }

            .sortable-ghost {
                opacity: 0.2;
                background-color: #3b82f6 !important;
            }

            /* SIDEBAR WORKCENTER STYLING */
            .workcenter-sidebar {
                height: calc(100vh - 170px);
                overflow-y: auto;
                padding-right: 5px;
                padding-bottom: 20px;
            }

            .workcenter-sidebar::-webkit-scrollbar,
            .table-responsive-custom::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            .workcenter-sidebar::-webkit-scrollbar-thumb,
            .table-responsive-custom::-webkit-scrollbar-thumb {
                background-color: #cbd5e0;
                border-radius: 3px;
            }

            .wc-card-container {
                transition: all 0.2s;
                border: 1px solid var(--border-color);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .wc-card-container:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
                border-color: #b0b8c3;
            }

            .wc-drop-zone {
                min-height: 100px;
                max-height: 300px;
                overflow-y: auto;
                background-color: #f8fafc;
                border: 2px dashed #cbd5e1;
                border-radius: 8px;
            }

            .wc-drop-zone.drag-over {
                background-color: #eff6ff;
                border-color: #3b82f6;
                box-shadow: inset 0 0 0 4px rgba(59, 130, 246, 0.1);
            }

            /* Item inside Drop Zone */
            .wc-drop-zone .pro-item-card {
                display: block;
                width: 100%;
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 8px 12px;
                margin-bottom: 6px;
                cursor: grab;
                position: relative;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            }

            .wc-drop-zone .pro-item-card:hover {
                border-color: #cbd5e1;
            }

            /* PERBAIKAN VISUAL PENTING */
            .wc-drop-zone .pro-item-card .table-col,
            .wc-drop-zone .pro-item-card .drag-handle,
            .wc-drop-zone .pro-item-card .preview-container .original-content,
            .wc-drop-zone .pro-item-card .row-checkbox {
                display: none !important;
            }

            .wc-drop-zone .pro-item-card .card-view-content {
                display: block !important;
            }

            .wc-drop-zone .pro-item-card .drag-preview-icon,
            .wc-drop-zone .pro-item .drag-preview-icon {
                display: none !important;
            }

            .source-table .pro-item .card-view-content {
                display: none;
            }

            .source-table .pro-item .drag-preview-icon {
                display: none;
            }
        </style>
    @endpush

    {{-- HAPUS: Blok PHP Kondisional PV2/PV3 --}}

    <div class="container-fluid p-3 p-lg-4">

        <div class="alert alert-info py-1 mb-3 rounded-3 border-0 bg-info bg-opacity-10">
            <marquee class="small text-info fw-medium">
                <i class="fa-solid fa-circle-info me-2"></i> Document WI hanya digunakan untuk tim Produksi bekerja,
                untuk melihat perubahan quantity GR anda bisa lihat dengan menekan history WI.
            </marquee>
        </div>

        {{-- HEADER SECTION --}}
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i>Work Instruction
                </h1>
                <p class="text-muted small mb-0">Manage and assign Production Orders efficiently.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark btn-sm shadow-sm fw-medium">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> History WI
                </button>

                <button class="btn btn-white btn-sm text-danger border shadow-sm fw-medium"
                    onclick="resetAllAllocations()">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset Allocation
                </button>
                {{-- PERUBAHAN: Tombol Review & Save --}}
                <button class="btn btn-primary btn-sm shadow-sm px-4 fw-semibold" onclick="saveAllocation(true)">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Review & Save
                </button>
            </div>
        </div>

        <div class="row g-4">
            {{-- KOLOM KIRI: TABLE SUMBER --}}
            <div class="col-lg-9 col-md-8">
                <div class="card custom-table-card h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="row align-items-center g-2">
                            <div class="col-md-5">
                                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center">
                                    <span
                                        class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center me-2"
                                        style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                                    Unassigned Orders
                                </h6>
                            </div>
                            <div class="col-md-7">
                                <div class="input-group input-group-sm shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 ps-3 text-muted"><i
                                            class="fa-solid fa-search"></i></span>
                                    <input type="text" id="searchInput"
                                        class="form-control border-start-0 ps-0 bg-white"
                                        placeholder="Type to search PRO, Material, SO...">

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive table-responsive-custom">
                            <table class="table mb-0 align-middle small source-table" id="proTable">
                                <thead class="sticky-header">
                                    <tr>
                                        <th class="text-center p-2 ps-3" width="40"><input
                                                class="form-check-input pointer" type="checkbox" id="selectAll"></th>
                                        <th class="p-2 ps-3">PRO (AUFNR)</th>
                                        <th class="text-center p-2">SO - Item</th>
                                        <th class="p-2">Material / Description</th>
                                        <th class="text-center p-2">WC</th>
                                        <th class="text-center p-2">Op. Key</th>
                                        <th class="text-center p-2">Qty Oper</th>
                                        <th class="text-center p-2">Conf-Oper</th>
                                        <th class="text-center p-2">Sisa Qty</th>
                                        <th class="text-center bg-light" width="40"><i
                                                class="fa-solid fa-grip-lines text-muted"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="source-list" class="sortable-list" data-group="shared-pro">
                                    @foreach ($tData1 as $item)
                                    @php
                                        $soItem = ltrim($item->KDAUF, '0') . ' - ' . ltrim($item->KDPOS, '0');
                                        $matnr = ctype_digit($item->MATNR)
                                            ? ltrim($item->MATNR, '0')
                                            : $item->MATNR;
                                        $sisaQty = $item->real_sisa_qty ?? ($item->MGVRG2 - $item->LMNGA); 
                                    @endphp

                                        <tr class="pro-item draggable-item" data-id="{{ $item->id }}"
                                            data-aufnr="{{ $item->AUFNR }}" data-vgw01="{{ $item->VGW01 }}"
                                            data-vge01="{{ $item->VGE01 }}" data-psmng="{{ $item->PSMNG }}"
                                            data-sisa-qty="{{ $sisaQty }}" data-conf-opr="{{ $item->LMNGA }}"
                                            data-qty-opr="{{ $item->MGVRG2 }}" data-assigned-qty="0"
                                            data-employee-nik="" data-employee-name="" data-child-wc=""
                                            data-assigned-child-wcs='[]' data-arbpl="{{ $item->ARBPL }}" data-matnr="{{ $item->MATNR }}"
                                            data-maktx="{{ $item->MAKTX }}" data-meins="{{ $item->MEINS }}" data-vornr="{{ $item->VORNR }}">
                                            {{-- TAMBAHAN: ARBPL --}}

                                            <td class="text-center table-col ps-3"><input
                                                    class="form-check-input row-checkbox pointer" type="checkbox"></td>

                                            <td class="table-col preview-container ps-3">
                                                <div class="original-content">
                                                    <span class="fw-bold text-primary">{{ $item->AUFNR }}</span>
                                                </div>

                                                <div class="drag-preview-icon">
                                                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm"
                                                        style="width: 40px; height: 40px;">
                                                        <i class="fa-solid fa-file-invoice fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark fs-6">{{ $item->AUFNR }}</div>
                                                        <div class="text-muted small" style="font-size: 0.75rem;">Moving
                                                            to Workcenter...</div>
                                                    </div>
                                                    <div class="ms-3 ps-3 border-start">
                                                        <span
                                                            class="badge bg-light text-dark border">{{ $item->STEUS }}</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-center table-col text-muted">{{ $soItem }}</td>
                                            <td class="table-col">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark">{{ $matnr }}</span>
                                                    <span class="text-muted text-truncate"
                                                        style="max-width: 200px; font-size: 0.7rem;">{{ $item->MAKTX }}</span>
                                                </div>
                                            </td>

                                            <td class="text-center table-col text-dark">{{ $item->ARBPL }}
                                            </td>
                                            <td class="text-center table-col"><span
                                                    class="badge bg-light text-dark border">{{ $item->STEUS }}</span>
                                            </td>
                                            <td class="text-center table-col">
                                                {{ number_format($item->MGVRG2, 0, ',', '.') }}</td>
                                            <td class="text-center table-col">
                                                {{ number_format($item->LMNGA, 0, ',', '.') }}</td>
                                            <td
                                                class="text-center fw-bold table-col col-sisa-qty {{ $sisaQty > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($sisaQty, 0, ',', '.') }}
                                            </td>

                                            <td class="text-center table-col drag-handle" title="Hold to drag">
                                                <i class="fa-solid fa-grip-vertical"></i>
                                            </td>

                                            {{-- TAMPILAN KARTU (SAAT MASUK KANAN) --}}
                                            <td class="card-view-content" colspan="9">
                                                <div class="d-flex align-items-center gap-1 mb-1">
                                                    <span
                                                        class="badge bg-primary text-white border border-primary bg-opacity-75"
                                                        style="font-size: 0.7rem;">
                                                        {{ $item->AUFNR }}
                                                    </span>
                                                    <span
                                                        class="badge bg-light text-dark border border-secondary assigned-qty-badge"
                                                        style="font-size: 0.7rem;">
                                                        Qty: -
                                                    </span>
                                                </div>
                                                <div class="employee-info-display ps-1">
                                                    <div class="text-muted small employee-name-text text-truncate"
                                                        style="font-size: 0.75rem; max-width: 200px;">
                                                        -
                                                    </div>
                                                    {{-- Tampilkan Child WC jika ada --}}
                                                    <div class="text-primary fw-medium small child-wc-display"
                                                        style="font-size: 0.75rem;">

                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: WORKCENTER LIST --}}
            <div class="col-lg-3 col-md-4">
                <div class="d-flex align-items-center mb-3 ps-1">
                    <span
                        class="bg-dark text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-sm"
                        style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                    <h6 class="mb-0 fw-bold">Target Workcenters</h6>
                </div>

                <div class="workcenter-sidebar custom-scrollbar pe-2">
                    @foreach ($workcenters as $wc)
                        @php
                            $refItem = $tData1->firstWhere('ARBPL', $wc->kode_wc);
                            $rawKapaz = $refItem ? $refItem->KAPAZ : 0;
                            $kapazHours = (float) str_replace(',', '.', (string) $rawKapaz);
                            $isUnknown = $kapazHours <= 0;
                        @endphp

                        @if (!$isUnknown)
                            <div class="card mb-3 wc-card-container rounded-4 overflow-hidden"
                                data-wc-id="{{ $wc->kode_wc }}" data-kapaz-wc="{{ $kapazHours }}">

                                <div class="card-header bg-white pt-3 pb-2 border-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">{{ $wc->kode_wc }}</h6>
                                            <small class="text-muted"
                                                style="font-size: 0.7rem;">{{ Str::limit($wc->description, 20) }}</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-light text-secondary border fw-normal"
                                                id="label-cap-{{ $wc->kode_wc }}" style="font-size: 0.65rem;">0 / 0
                                                Min</span>
                                        </div>
                                    </div>
                                    <div class="progress mt-2 rounded-pill bg-light" style="height: 6px;">
                                        <div id="progress-{{ $wc->kode_wc }}"
                                            class="progress-bar bg-success rounded-pill" role="progressbar"
                                            style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="card-body p-2 bg-light border-top">
                                    <div id="zone-{{ $wc->kode_wc }}" class="wc-drop-zone p-2 sortable-list"
                                        data-group="shared-pro">
                                        <div class="text-center text-muted py-4 empty-placeholder">
                                            <div class="mb-2 opacity-50"><i class="fa-solid fa-arrow-down-long"></i>
                                            </div>
                                            <small style="font-size: 0.75rem;">Drop Here</small>
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

    {{-- MODAL ASSIGNMENT --}}
    <div class="modal fade" id="assignmentModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0 pt-3 px-3">
                    <h5 class="modal-title fw-bold fs-6">Assign Details (<span id="modalProAufnr"></span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div id="bulkWarning"
                        class="alert alert-warning d-none small p-2 mb-3 border-0 bg-warning bg-opacity-10 text-warning rounded-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <strong>Bulk Move:</strong> Applied to all items.
                    </div>
                    <div id="remainingQtyWarning"
                        class="alert alert-info small p-2 mb-3 border-0 bg-info bg-opacity-10 text-info rounded-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Sisa Qty: <strong id="remainingQtyDisplay">0</strong>
                    </div>

                    {{-- Split Input Section --}}
                    <div class="card mb-3 p-3 bg-light rounded-3">
                        <h6 class="small fw-bold text-uppercase text-muted mb-2"
                            style="font-size: 0.7rem; letter-spacing: 0.5px;">New Split Allocation</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted"
                                style="font-size: 0.7rem; letter-spacing: 0.5px;">Operator (NIK)</label>
                            <select class="form-select form-select-sm" id="employeeSelect">
                                <option value="" selected disabled>Select Operator...</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp['pernr'] }}" data-name="{{ $emp['stext'] }}">
                                        {{ $emp['pernr'] }} - {{ $emp['stext'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- PERUBAHAN: Field Workcenter Anak (Child Workcenter) --}}
                        <div class="mb-3" id="childWorkcenterField">
                            <label class="form-label small fw-bold text-uppercase text-muted"
                                style="font-size: 0.7rem; letter-spacing: 0.5px;">Target Line/Sub-WC</label>
                            <select class="form-select form-select-sm" id="childWorkcenterSelect">
                                <option value="" selected disabled>Pilih Workcenter Anak...</option>
                                {{-- Opsi akan diisi oleh JavaScript --}}
                            </select>
                            <div id="childWorkcenterHelp" class="form-text small text-muted d-none">
                                Workcenter ini adalah Induk, wajib pilih Sub-WC.
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-uppercase text-muted"
                                style="font-size: 0.7rem; letter-spacing: 0.5px;">Quantity</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="inputAssignQty" min="1"
                                    placeholder="Qty">
                                <span class="input-group-text bg-light text-muted" id="maxQtyLabel"
                                    style="font-size: 0.7rem;">Max: -</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary w-100 mt-2" id="btnAddSplit" disabled>
                            <i class="fa-solid fa-plus me-1"></i> Add Split
                        </button>
                    </div>

                    {{-- Split Review Section --}}
                    <div id="splitReviewSection" class="mt-3 d-none">
                        <h6 class="small fw-bold text-uppercase text-muted mb-2"
                            style="font-size: 0.7rem; letter-spacing: 0.5px;">Current Splits (<span
                                id="totalSplitsCount">0</span>)</h6>
                        <ul class="list-group list-group-flush small" id="tempSplitList">
                            {{-- Split items will be added here --}}
                        </ul>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-3 d-flex flex-column">
                    <button type="button" class="btn btn-danger btn-sm w-100 mb-2 rounded-3"
                        id="btnCancelDrop">Cancel & Reset</button>
                    <button type="button" class="btn btn-primary btn-sm w-100 m-0 rounded-3 shadow-sm"
                        id="btnConfirmFinalAssignment" disabled>Confirm Assignment</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW (BARU) --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">Review Work Instructions (WI)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    {{-- === FIELD INPUT TANGGAL DAN JAM BARU === --}}
                    <div class="row mb-3 align-items-center g-3">
                        <div class="col-md-3">
                            <label for="wiDocumentDate" class="form-label fw-bold small text-muted text-uppercase" style="font-size: 0.7rem;">Tanggal Dokumen WI</label>
                            <input type="date" class="form-control form-control-sm" id="wiDocumentDate" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="wiDocumentTime" class="form-label fw-bold small text-muted text-uppercase" style="font-size: 0.7rem;">Jam Mulai Efektif</label>
                            <input type="time" class="form-control form-control-sm" id="wiDocumentTime" required>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-info small p-2 mt-4 mb-0 rounded-3 border-0 bg-info bg-opacity-10 text-info">
                                <i class="fa-solid fa-clock me-1"></i> WI akan **expired** 12 jam setelah Jam Mulai Efektif ini.
                            </div>
                        </div>
                    </div>
    
                    <p class="text-muted small">
                        Pastikan semua PRO sudah terisi NIK dan Quantity yang benar sebelum melanjutkan.
                    </p>
                    <div id="previewContent" class="row g-3">
                        {{-- Content will be injected here --}}
                    </div>
                    <div id="emptyPreviewWarning" class="alert alert-info d-none mt-3 text-center">
                        Tidak ada Workcenter yang memiliki PRO yang di-assign. Silakan drag PRO terlebih dahulu.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close & Edit</button>
                    <button type="button" class="btn btn-success fw-semibold" id="btnFinalSave">
                        <i class="fa-solid fa-file-export me-2"></i>Generate & Save WI Documents
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
                    animation: 0,
                    handle: '.drag-handle',
                    forceFallback: true,
                    fallbackClass: "sortable-drag",
                    ghostClass: "sortable-ghost",
                    selectedClass: 'selected-row',
                    sort: false,
                    onStart: function(evt) {
                        document.body.classList.add('dragging-active');
                    },
                    onEnd: function(evt) {
                        document.body.classList.remove('dragging-active');
                    }
                });

                wcZones.forEach(zone => {
                    new Sortable(zone, {
                        group: 'shared-pro',
                        animation: 0,
                        forceFallback: true,
                        onStart: function(evt) {
                            document.body.classList.add('dragging-active');
                        },
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
                        text: `Tidak dapat memproses. Total beban dari alokasi baru akan melebihi kapasitas Workcenter (${Math.ceil(maxMins)} menit).`,
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
                const parseNum = (str) => parseFloat(String(str).replace(/\./g, '').replace(/,/g, '.')) || 0;
                const vgw01 = parseNum(row.dataset.vgw01);
                const vge01 = row.dataset.vge01 || '';
                const qty = (qtyOverride !== null) ? qtyOverride : (parseFloat(row.dataset.sisaQty) || 0);

                if (vgw01 > 0 && qty > 0) {
                    let totalRaw = vgw01 * qty;
                    const unit = vge01.toUpperCase();

                    if (unit === 'S') {
                        return totalRaw / 60; // Second -> Minute
                    } else if (unit === 'MIN') {
                        return totalRaw; // Minute -> Minute
                    } else if (unit === 'H') {
                        return totalRaw * 60; // Hour -> Minute
                    } else {
                        // Default berdasarkan kode sebelumnya: Asumsi Jam
                        return totalRaw * 60;
                    }
                }
                return 0;
            }

            function calculateAllRows() {
                // Ini untuk memastikan semua data-attributes terisi saat page load
                document.querySelectorAll('#source-list tr.pro-item').forEach(row => {
                    const sisaQty = parseFloat(row.dataset.sisaQty) || 0;
                    const mins = calculateItemMinutes(row, sisaQty);
                    row.dataset.calculatedMins = mins;
                    row.dataset.currentQty = sisaQty; // Simpan Qty awal untuk reset
                });

                // Perbarui kapasitas semua WC saat load
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
                    bar.className = 'progress-bar rounded-pill ' + (pct < 70 ? 'bg-success' : pct < 95 ? 'bg-warning' :
                        'bg-danger');
                }

                // LOGGING: Kapasitas
                // console.log(`Capacity WC ${wcId}: Load ${Math.ceil(currentLoad)} / ${Math.ceil(maxMins)} Mins (${pct.toFixed(1)}%)`);

                const placeholder = cardContainer.querySelector('.empty-placeholder');
                const hasItems = cardContainer.querySelectorAll('.pro-item-card').length > 0;
                if (placeholder) placeholder.style.display = hasItems ? 'none' : 'block';
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
                    const visibleRows = Array.from(document.querySelectorAll('#source-list tr.draggable-item')).filter(
                        r => r.style.display !== 'none');
                    visibleRows.forEach(row => {
                        const cb = row.querySelector('.row-checkbox');
                        if (cb) {
                            cb.checked = this.checked;
                            this.checked ? row.classList.add('selected-row') : row.classList.remove(
                                'selected-row');
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
                                qty_order: qtyOrder,                       
                                uom: item.dataset.meins || 'EA',             
                                vornr: item.dataset.vornr || 'N/A',           
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
                // LOGGING: Preview Modal
                console.log(`Showing Preview: ${totalWcCount} Workcenters have assigned items.`);

                const content = document.getElementById('previewContent');
                const emptyWarning = document.getElementById('emptyPreviewWarning');
                const dateInput = document.getElementById('wiDocumentDate');
                const timeInput = document.getElementById('wiDocumentTime'); 
                
                const now = new Date();
                
                // Logika Tanggal
                const today = now.toISOString().split('T')[0];
                
                // Logika Jam
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const currentTime = `${hours}:${minutes}`;

                // --- Mengatur Nilai Default ---
                dateInput.value = today;
                if (timeInput) { // Cek keamanan tambahan
                    timeInput.value = currentTime;
                }
                // ----------------------------

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

                    // Ganti col-md-6 menjadi col-lg-4 di sini
                    html += `
                        <div class="col-lg-4 col-md-6"> 
                            <div class="card border-primary border-3 shadow-sm rounded-3">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">${wc.workcenter}</h6>
                                    <span class="badge bg-light text-primary border border-primary">
                                        Load: ${wc.load_mins} / ${maxLoad} Min
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <p class="small fw-bold text-muted border-bottom pb-1 mb-2">PRO Assignments:</p>
                                    <ul class="list-group list-group-flush small">
                    `;

                    wc.pro_items.forEach(item => {
                        // Tentukan nama Workcenter yang ditampilkan
                        const targetWcName = item.child_workcenter ?
                            `<i class="fa-solid fa-arrow-right-long mx-1"></i> ${item.child_workcenter}` :
                            '';

                        html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center px-1">
                                <div>
                                    <i class="fa-solid fa-tag me-2 text-primary"></i>
                                    <strong>${item.aufnr}</strong>
                                    <span class="text-secondary small">${targetWcName}</span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success me-2">Qty: ${item.assigned_qty.toLocaleString('id-ID')}</span>
                                    <span class="text-muted" style="font-size: 0.7rem;">(${item.name})</span>
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
