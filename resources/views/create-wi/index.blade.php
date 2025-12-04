<x-layouts.app title="Create Work Instruction">
    
    @push('styles')
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --border-color: #dfe3e8; /* Warna border dipertegas */
        }

        body {
            background-color: #f4f6f8; /* Background sedikit lebih gelap */
        }

        /* --- CLASS TAMBAHAN UNTUK MENCEGAH SELEKSI --- */
        body.dragging-active {
            user-select: none !important; 
            cursor: grabbing !important;
        }
        
        body.dragging-active * {
            user-select: none !important;
        }

        /* --- 1. TABLE & ROW STYLING --- */
        .custom-table-card {
            border: 1px solid var(--border-color); /* Border ditambahkan */
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

        /* --- 2. DRAG PREVIEW --- */
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

        tr.sortable-drag td.preview-container > .original-content {
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

        /* --- 3. SIDEBAR WORKCENTER STYLING --- */
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
            border: 1px solid var(--border-color); /* Border ditambahkan */
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 6px;
            cursor: grab;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .wc-drop-zone .pro-item-card:hover {
            border-color: #cbd5e1;
        }

        /* Helpers Visibility */
        .wc-drop-zone .pro-item-card .hide-in-card { display: none; }
        .wc-drop-zone .pro-item-card .card-view-content { display: block; }
        .source-table .pro-item .card-view-content { display: none; }
        .source-table .pro-item .drag-preview-icon { display: none; } 

    </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4">
        
        {{-- HEADER SECTION --}}
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i>Work Instruction
                </h1>
                <p class="text-muted small mb-0">Manage and assign Production Orders efficiently.</p>
            </div>
            <div class="d-flex gap-2">
                {{-- TOMBOL RESET --}}
                <button class="btn btn-white btn-sm text-danger border shadow-sm fw-medium" onclick="resetAllAllocations()">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset Allocation
                </button>
                <button class="btn btn-primary btn-sm shadow-sm px-4 fw-semibold" onclick="saveAllocation()">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
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
                                    <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                                    Unassigned Orders
                                </h6>
                            </div>
                            <div class="col-md-7">
                                <div class="input-group input-group-sm shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 ps-3 text-muted"><i class="fa-solid fa-search"></i></span>
                                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0 bg-white" placeholder="Type to search PRO, Material, SO...">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive table-responsive-custom">
                            <table class="table mb-0 align-middle small source-table" id="proTable">
                                <thead class="sticky-header">
                                    <tr>
                                        <th class="text-center p-2 ps-3" width="40"><input class="form-check-input pointer" type="checkbox" id="selectAll"></th>
                                        <th class="p-2 ps-3">PRO (AUFNR)</th>
                                        <th class="text-center p-2">SO - Item</th>
                                        <th class="p-2">Material / Description</th>
                                        <th class="text-center p-2">Op. Key</th>
                                        <th class="text-center p-2">Qty Oper</th>
                                        <th class="text-center p-2">Sisa Qty</th>
                                        <th class="text-center p-2">PV1</th>
                                        <th class="text-center bg-light" width="40"><i class="fa-solid fa-grip-lines text-muted"></i></th> 
                                    </tr>
                                </thead>
                                <tbody id="source-list" class="sortable-list" data-group="shared-pro">
                                    @foreach($tData1 as $item)
                                        @php
                                            $soItem = ltrim($item->KDAUF, '0') . ' - ' . ltrim($item->KDPOS, '0');
                                            $matnr = ctype_digit($item->MATNR) ? ltrim($item->MATNR, '0') : $item->MATNR;
                                            $sisaQty = $item->MGVRG2 - $item->LMNGA;
                                        @endphp

                                        <tr class="pro-item draggable-item" 
                                            data-id="{{ $item->id }}"
                                            data-aufnr="{{ $item->AUFNR }}"
                                            data-vgw01="{{ $item->VGW01 }}" 
                                            data-vge01="{{ $item->VGE01 }}" 
                                            data-psmng="{{ $item->PSMNG }}"
                                            data-sisa-qty="{{ $sisaQty }}"
                                            data-current-qty="{{ $sisaQty }}" 
                                            data-assigned-qty="0" 
                                            data-employee-nik="" 
                                            data-employee-name="">
                                            
                                            <td class="text-center table-col ps-3"><input class="form-check-input row-checkbox pointer" type="checkbox"></td>
                                            
                                            <td class="table-col preview-container ps-3">
                                                <div class="original-content">
                                                    <span class="fw-bold text-primary">{{ $item->AUFNR }}</span>
                                                </div>

                                                <div class="drag-preview-icon">
                                                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                                                        <i class="fa-solid fa-file-invoice fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark fs-6">{{ $item->AUFNR }}</div>
                                                        <div class="text-muted small" style="font-size: 0.75rem;">Moving to Workcenter...</div>
                                                    </div>
                                                    <div class="ms-3 ps-3 border-start">
                                                        <span class="badge bg-light text-dark border">{{ $item->STEUS }}</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-center table-col text-muted">{{ $soItem }}</td>
                                            <td class="table-col">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark">{{ $matnr }}</span>
                                                    <span class="text-muted text-truncate" style="max-width: 200px; font-size: 0.7rem;">{{ $item->MAKTX }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center table-col"><span class="badge bg-light text-dark border">{{ $item->STEUS }}</span></td>
                                            <td class="text-center table-col">{{ number_format($item->MGVRG2, 0, ',', '.') }}</td>
                                            <td class="text-center fw-bold table-col col-sisa-qty {{ $sisaQty > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($sisaQty, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center table-col">{{ $item->PV1 }}</td>

                                            <td class="text-center table-col drag-handle" title="Hold to drag">
                                                <i class="fa-solid fa-grip-vertical"></i>
                                            </td>

                                            {{-- TAMPILAN KARTU (SAAT MASUK KANAN) --}}
                                            <td class="card-view-content" colspan="11">
                                                <div class="d-flex align-items-center gap-1 mb-1">
                                                    <span class="badge bg-primary text-white border border-primary bg-opacity-75" style="font-size: 0.7rem;">
                                                        {{ $item->AUFNR }}
                                                    </span>
                                                    <span class="badge bg-light text-dark border border-secondary assigned-qty-badge" style="font-size: 0.7rem;">
                                                        Qty: -
                                                    </span>
                                                </div>
                                                <div class="employee-info-display d-none ps-1">
                                                    <div class="text-muted small employee-name-text text-truncate" style="font-size: 0.75rem; max-width: 200px;">
                                                        -
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
                    <span class="bg-dark text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-sm" style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                    <h6 class="mb-0 fw-bold">Target Workcenters</h6>
                </div>

                <div class="workcenter-sidebar custom-scrollbar pe-2">
                    @foreach($workcenters as $wc)
                        @php
                            $refItem = $tData1->firstWhere('ARBPL', $wc->kode_wc);
                            $rawKapaz = $refItem ? $refItem->KAPAZ : 0;
                            $kapazHours = (float) str_replace(',', '.', (string)$rawKapaz);
                            $isUnknown = $kapazHours <= 0;
                        @endphp

                        @if(!$isUnknown)
                            <div class="card mb-3 wc-card-container rounded-4 overflow-hidden" 
                                 data-wc-id="{{ $wc->kode_wc }}"
                                 data-kapaz-wc="{{ $kapazHours }}"> 
                                
                                <div class="card-header bg-white pt-3 pb-2 border-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">{{ $wc->kode_wc }}</h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ Str::limit($wc->description, 20) }}</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-light text-secondary border fw-normal" id="label-cap-{{ $wc->kode_wc }}" style="font-size: 0.65rem;">0 / 0 Min</span>
                                        </div>
                                    </div>
                                    <div class="progress mt-2 rounded-pill bg-light" style="height: 6px;">
                                        <div id="progress-{{ $wc->kode_wc }}" class="progress-bar bg-success rounded-pill" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="card-body p-2 bg-light border-top">
                                    <div id="zone-{{ $wc->kode_wc }}" class="wc-drop-zone p-2 sortable-list" data-group="shared-pro">
                                        <div class="text-center text-muted py-4 empty-placeholder">
                                            <div class="mb-2 opacity-50"><i class="fa-solid fa-arrow-down-long"></i></div>
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
                    <h5 class="modal-title fw-bold fs-6">Assign Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    
                    <div id="bulkWarning" class="alert alert-warning d-none small p-2 mb-3 border-0 bg-warning bg-opacity-10 text-warning rounded-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <strong>Bulk Move:</strong> Applied to all items.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.5px;">Operator (NIK)</label>
                        <select class="form-select form-select-sm" id="employeeSelect">
                            <option value="" selected disabled>Select Operator...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp['pernr'] }}" data-name="{{ $emp['stext'] }}">
                                    {{ $emp['pernr'] }} - {{ $emp['stext'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.5px;">Quantity</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" id="inputAssignQty" min="1" placeholder="Qty">
                            <span class="input-group-text bg-light text-muted" id="maxQtyLabel" style="font-size: 0.7rem;">Max: -</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-3">
                    <button type="button" class="btn btn-light btn-sm w-100 mb-2 rounded-3" id="btnCancelDrop">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm w-100 m-0 rounded-3 shadow-sm" id="btnConfirmDrop" disabled>Confirm Assignment</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- SweetAlert untuk Popup Kapasitas --}}
    
    <script>
        let draggedItemsCache = [];
        let targetContainerCache = null;
        let sourceContainerCache = null;
        let assignmentModalInstance = null;

        document.addEventListener('DOMContentLoaded', function () {
            assignmentModalInstance = new bootstrap.Modal(document.getElementById('assignmentModal'));
            calculateAllRows(); 
            setupSearch();
            setupCheckboxes();
            setupDragAndDrop();
            setupModalLogic();
        });

        function setupDragAndDrop() {
            const sourceList = document.getElementById('source-list');
            const wcZones = document.querySelectorAll('.wc-drop-zone');

            new Sortable(sourceList, {
                group: 'shared-pro',
                animation: 0, // Matikan animasi
                handle: '.drag-handle', 
                forceFallback: true,    
                fallbackClass: "sortable-drag", 
                ghostClass: "sortable-ghost",
                selectedClass: 'selected-row',
                sort: false, 
                onStart: function(evt) { document.body.classList.add('dragging-active'); },
                onEnd: function (evt) { document.body.classList.remove('dragging-active'); }
            });

            wcZones.forEach(zone => {
                new Sortable(zone, {
                    group: 'shared-pro',
                    animation: 0, // Matikan animasi
                    forceFallback: true,
                    onStart: function(evt) { document.body.classList.add('dragging-active'); },
                    onAdd: function (evt) {
                        document.body.classList.remove('dragging-active');
                        handleDropToWc(evt);
                    },
                    onRemove: function (evt) {
                        document.body.classList.remove('dragging-active');
                        handleReturnToTable(evt.item, evt.from);
                    },
                    onEnd: function(evt) { document.body.classList.remove('dragging-active'); }
                });
                checkEmptyPlaceholder(zone);
            });
        }

        function handleDropToWc(evt) {
            const item = evt.item;
            const toList = evt.to;
            const fromList = evt.from;
            
            targetContainerCache = toList;
            sourceContainerCache = fromList;
            draggedItemsCache = [];

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

            const empSelect = document.getElementById('employeeSelect');
            const qtyInput = document.getElementById('inputAssignQty');
            const bulkWarning = document.getElementById('bulkWarning');
            const maxQtyLabel = document.getElementById('maxQtyLabel');

            empSelect.value = "";
            qtyInput.value = ""; 
            
            if (draggedItemsCache.length > 1) {
                bulkWarning.classList.remove('d-none');
                maxQtyLabel.innerText = "Bulk Mode";
                qtyInput.placeholder = "Qty per Item";
            } else {
                bulkWarning.classList.add('d-none');
                const sisa = parseFloat(item.dataset.sisaQty) || 0;
                maxQtyLabel.innerText = "Max: " + sisa;
                qtyInput.value = sisa; 
                qtyInput.max = sisa;
            }

            document.getElementById('btnConfirmDrop').disabled = true;
            assignmentModalInstance.show();
        }

        function setupModalLogic() {
            const empSelect = document.getElementById('employeeSelect');
            const qtyInput = document.getElementById('inputAssignQty');
            const btnConfirm = document.getElementById('btnConfirmDrop');
            const btnCancel = document.getElementById('btnCancelDrop');

            function validateForm() {
                const hasEmp = empSelect.value !== "";
                const hasQty = qtyInput.value !== "" && parseFloat(qtyInput.value) > 0;
                btnConfirm.disabled = !(hasEmp && hasQty);
            }
            empSelect.addEventListener('change', validateForm);
            qtyInput.addEventListener('input', validateForm);

            // LOGIC CONFIRM DENGAN VALIDASI KAPASITAS
            btnConfirm.addEventListener('click', function() {
                const nik = empSelect.value;
                const name = empSelect.options[empSelect.selectedIndex].dataset.name;
                const inputQty = parseFloat(qtyInput.value);

                // --- VALIDASI KAPASITAS ---
                // Hitung estimasi penambahan beban
                let addedMinutes = 0;
                draggedItemsCache.forEach(row => {
                    const currentSisa = parseFloat(row.dataset.sisaQty) || 0;
                    let qtyToAssign = inputQty > currentSisa ? currentSisa : inputQty;
                    addedMinutes += calculateItemMinutes(row, qtyToAssign);
                });

                const wcContainer = targetContainerCache.closest('.wc-card-container');
                const kapazHours = parseFloat(wcContainer.dataset.kapazWc) || 0;
                const maxMins = kapazHours * 60;
                
                // Hitung beban saat ini di kotak
                let currentLoadMins = 0;
                wcContainer.querySelectorAll('.pro-item-card').forEach(item => {
                    currentLoadMins += parseFloat(item.dataset.calculatedMins) || 0;
                });

                if ((currentLoadMins + addedMinutes) > maxMins) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Over Capacity!',
                        text: `Cannot add items. Total load will exceed ${maxMins} minutes.`,
                        confirmButtonText: 'Understood'
                    });
                    cancelDrop();
                    return; // Stop process
                }
                // --- END VALIDASI ---

                draggedItemsCache.forEach(row => {
                    const currentSisa = parseFloat(row.dataset.sisaQty) || 0;
                    let qtyToAssign = inputQty;
                    if(inputQty > currentSisa) qtyToAssign = currentSisa;

                    const isVisualItem = (row.parentNode === targetContainerCache);
                    
                    if (qtyToAssign >= currentSisa) {
                        if (!isVisualItem) targetContainerCache.appendChild(row);
                        row.dataset.assignedQty = qtyToAssign;
                        row.dataset.sisaQty = 0;
                        updateRowUI(row, nik, name, qtyToAssign);
                        transformToCardView(row);
                    } else {
                        const clonedRow = row.cloneNode(true);
                        clonedRow.dataset.assignedQty = qtyToAssign;
                        clonedRow.dataset.sisaQty = 0; 
                        
                        const newSisa = currentSisa - qtyToAssign;
                        row.dataset.sisaQty = newSisa;
                        row.dataset.currentQty = newSisa;
                        row.querySelector('.col-sisa-qty').innerText = newSisa.toLocaleString('id-ID');

                        targetContainerCache.appendChild(clonedRow);
                        updateRowUI(clonedRow, nik, name, qtyToAssign);
                        transformToCardView(clonedRow);

                        if (isVisualItem) {
                            sourceContainerCache.appendChild(row);
                            transformToTableView(row); 
                        }
                    }

                    const cb = row.querySelector('.row-checkbox');
                    if(cb) { cb.checked = false; row.classList.remove('selected-row'); }
                });

                document.getElementById('selectAll').checked = false;
                updateCapacity(targetContainerCache.closest('.wc-card-container'));
                checkEmptyPlaceholder(targetContainerCache);
                assignmentModalInstance.hide();
            });

            btnCancel.addEventListener('click', cancelDrop);
            
            document.getElementById('assignmentModal').addEventListener('hidden.bs.modal', function () {
                if (draggedItemsCache.length > 0) {
                    const firstItem = draggedItemsCache[0];
                    if (firstItem.parentNode === targetContainerCache && !firstItem.dataset.employeeNik) {
                        cancelDrop();
                    }
                }
            });
        }

        function cancelDrop() {
            draggedItemsCache.forEach(row => {
                if(sourceContainerCache) sourceContainerCache.appendChild(row);
                transformToTableView(row);
            });
            assignmentModalInstance.hide();
        }

        // FUNGSI RESET (REFRESH)
        function resetAllAllocations() {
            if(!confirm('Are you sure want to reset all assigned PROs to table?')) return;

            document.querySelectorAll('.wc-drop-zone').forEach(zone => {
                const items = Array.from(zone.querySelectorAll('.pro-item-card'));
                items.forEach(item => {
                    // Logic sederhana: kembalikan ke source list
                    // Note: jika ada clone (partial), logic ini hanya visual reset sederhana
                    // Idealnya menghapus clone dan mengembalikan qty ke parent, tapi untuk UI prototype:
                    document.getElementById('source-list').appendChild(item);
                    handleReturnToTable(item, zone);
                });
                updateCapacity(zone.closest('.wc-card-container'));
            });
        }

        function handleReturnToTable(item, fromContainer) {
            item.dataset.employeeNik = "";
            item.dataset.employeeName = "";
            item.dataset.assignedQty = 0;
            
            transformToTableView(item);
            if(fromContainer) {
                updateCapacity(fromContainer.closest('.wc-card-container'));
                checkEmptyPlaceholder(fromContainer);
            }
        }

        function updateRowUI(row, nik, name, qty) {
            row.dataset.employeeNik = nik;
            row.dataset.employeeName = name;
            row.querySelector('.employee-name-text').innerText = `${name}`; 
            row.querySelector('.assigned-qty-badge').innerText = 'Qty: ' + qty.toLocaleString('id-ID');
            row.querySelector('.employee-info-display').classList.remove('d-none');
            
            const mins = calculateItemMinutes(row, qty);
            row.dataset.calculatedMins = mins;
        }

        function transformToCardView(row) {
            row.classList.remove('draggable-item');
            row.classList.add('pro-item-card');
            row.querySelectorAll('.table-col').forEach(el => el.style.display = 'none');
            row.querySelector('.card-view-content').style.display = 'block';
            const handle = row.querySelector('.drag-handle');
            if(handle) handle.style.display = 'none';
        }

        function transformToTableView(row) {
            row.classList.add('draggable-item');
            row.classList.remove('pro-item-card');
            row.querySelectorAll('.table-col').forEach(el => el.style.display = '');
            row.querySelector('.card-view-content').style.display = 'none';
            const handle = row.querySelector('.drag-handle');
            if(handle) handle.style.display = '';
        }

        function calculateItemMinutes(row, qtyOverride = null) {
            const parseNum = (str) => parseFloat(String(str).replace(/\./g, '').replace(/,/g, '.')) || 0;
            const vgw01 = parseNum(row.dataset.vgw01);
            const vge01 = row.dataset.vge01 || '';
            const qty = (qtyOverride !== null) ? qtyOverride : (parseFloat(row.dataset.sisaQty) || 0);

            if (vgw01 > 0 && qty > 0) {
                let totalRaw = vgw01 * qty;
                return (vge01 === 'S') ? totalRaw / 60 : totalRaw;
            }
            return 0;
        }

        function calculateAllRows() { }

        function updateCapacity(cardContainer) {
            if(!cardContainer) return;
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

            if(lbl) lbl.innerText = `${Math.ceil(currentLoad)} / ${Math.ceil(maxMins)} Min`;
            if(bar) {
                bar.style.width = Math.min(pct, 100) + "%";
                bar.className = 'progress-bar rounded-pill ' + (pct < 70 ? 'bg-success' : pct < 95 ? 'bg-warning' : 'bg-danger');
            }
            
            const placeholder = cardContainer.querySelector('.empty-placeholder');
            const hasItems = cardContainer.querySelectorAll('.pro-item-card').length > 0;
            if(placeholder) placeholder.style.display = hasItems ? 'none' : 'block';
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
                    if(cb) {
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

        function saveAllocation() {
            let data = [];
            document.querySelectorAll('.wc-card-container').forEach(card => {
                const wcId = card.dataset.wcId;
                card.querySelectorAll('.pro-item-card').forEach(item => {
                    data.push({
                        workcenter: wcId,
                        aufnr: item.dataset.aufnr,
                        nik: item.dataset.employeeNik,
                        assigned_qty: item.dataset.assignedQty
                    });
                });
            });
            console.log("Payload:", data);
            alert("Ready to POST data (Check Console)");
        }
    </script>
    @endpush

</x-layouts.app>