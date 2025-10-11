@forelse ($componentData as $aufnr => $components)
    <h6 class="mt-4 mb-2">Komponen untuk PRO: <span class="text-primary fw-bold">{{ $aufnr }}</span></h6>

    {{-- ====================================================================== --}}
    {{--     A. TAMPILAN DESKTOP (TABLE)                                        --}}
    {{-- ====================================================================== --}}
    <div class="d-none d-md-block" @if(count($components) > 6) style="max-height: 450px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: .25rem;" @endif>
        <div class="table-responsive">
            <table class="table table-bordered table-sm component-responsive-table" data-pro-id="{{ $aufnr }}">
                <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr class="align-middle small text-uppercase">
                        <th class="text-center" style="width: 3%;"><input type="checkbox" id="select-all-components-{{ $aufnr }}" class="form-check-input" onchange="toggleSelectAllComponents('{{ $aufnr }}')"></th>
                        <th class="text-center" style="width: 4%;">No.</th>
                        <th class="text-center sortable-header" data-sort-column="reservasi" data-sort-type="text">Number Reservasi <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="item" data-sort-type="text">Item Reservasi <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="material" data-sort-type="text">Material <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="description" data-sort-type="text">Description <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center" style="width: 5%;">Action</th>
                        <th class="text-center sortable-header" data-sort-column="outs_req" data-sort-type="number">Outs. Req <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="req_qty" data-sort-type="number">Req. Qty <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="qty_commited" data-sort-type="number">Qty. Commited <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="stock" data-sort-type="number">Stock <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="slog" data-sort-type="text">S.Log <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center sortable-header" data-sort-column="uom" data-sort-type="text">UOM <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                        <th class="text-center">Spec. Procurement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($components as $comp)
                        <tr class="align-middle clickable-row"
                            data-reservasi="{{ $comp->RSNUM ?? '-' }}" data-reservasi-item="{{ $comp->RSPOS ?? '-' }}"
                            data-material="{{ $comp->MATNR ? ltrim($comp->MATNR, '0') : '-' }}" data-description="{{ $comp->MAKTX ?? '-' }}"
                            data-req-qty="{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}" data-stock="{{ $comp->KALAB ?? '-' }}"
                            data-outs-req="{{ $comp->OUTSREQ ?? '-' }}" data-slog="{{ $comp->LGORT ?? '-' }}"
                            data-uom="{{ $comp->MEINS ?? '-' }}" data-spec="{{ $comp->LTEXT ?? 'No Value' }}"
                            data-qty-commited="{{ $comp->VMENG ?? '0' }}">
                            <td class="text-center"><input type="checkbox" class="component-select-{{ $aufnr }} form-check-input" data-aufnr="{{ $aufnr }}" data-rspos="{{ $comp->RSPOS ?? '' }}" onchange="handleComponentSelect('{{ $aufnr }}')"></td>
                            <td class="text-center" data-col="no">{{ $loop->iteration }}</td>
                            <td class="text-center" data-col="reservasi">{{ $comp->RSNUM ?? '-' }}</td>
                            <td class="text-center" data-col="item">{{ $comp->RSPOS ?? '-' }}</td>
                            <td class="text-center" data-col="material">{{ $comp->MATNR ? ltrim($comp->MATNR, '0') : '-' }}</td>
                            <td data-col="description">{{ $comp->MAKTX ?? '-' }}</td>
                            <td class="text-center">
                                <button type="button" title="Edit Component" class="btn btn-warning btn-sm py-1 px-2 edit-component-btn"
                                    data-aufnr="{{ $comp->AUFNR ?? '' }}" data-rspos="{{ $comp->RSPOS ?? '' }}" data-matnr="{{ $comp->MATNR ?? '' }}"
                                    data-bdmng="{{ $comp->BDMNG ?? '' }}" data-lgort="{{ $comp->LGORT ?? '' }}" data-sobkz="{{ $comp->SOBKZ ?? '' }}"
                                    data-plant="{{ $comp->WERKSX ?? 'default_plant' }}" onclick="handleEditClick(this)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                            <td class="text-center fw-bold" data-col="outs_req">{{ $comp->OUTSREQ ?? '-' }}</td>
                            <td class="text-center" data-col="req_qty">{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}</td>
                            <td class="text-center" data-col="qty_commited">{{ $comp->VMENG ?? '0' }}</td>
                            <td class="text-center" data-col="stock">{{ $comp->KALAB ?? '-' }}</td>
                            <td class="text-center" data-col="slog">{{ $comp->LGORT ?? '-' }}</td>
                            <td class="text-center" data-col="uom">{{ $comp->MEINS ?? '-' }}</td>
                            <td class="text-center">{{ $comp->LTEXT ?? "No Value" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{--     B. TAMPILAN MOBILE (CARD)                                  --}}
    {{-- ============================================================ --}}
    <div class="d-block d-md-none" @if(count($components) > 2) style="max-height: 290px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: .25rem; padding: 5px;" @endif>
        @foreach ($components as $comp)
        <div class="card mb-3 shadow-sm clickable-row"
            data-reservasi="{{ $comp->RSNUM ?? '-' }}" data-reservasi-item="{{ $comp->RSPOS ?? '-' }}"
            data-material="{{ $comp->MATNR ? ltrim($comp->MATNR, '0') : '-' }}" data-description="{{ $comp->MAKTX ?? '-' }}"
            data-req-qty="{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}" data-stock="{{ $comp->KALAB ?? '-' }}"
            data-outs-req="{{ $comp->OUTSREQ ?? '-' }}" data-slog="{{ $comp->LGORT ?? '-' }}"
            data-uom="{{ $comp->MEINS ?? '-' }}" data-spec="{{ $comp->LTEXT ?? 'No Value' }}"
            data-qty-commited="{{ $comp->VMENG ?? '0' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="form-check pt-1"><input type="checkbox" class="component-select-{{ $aufnr }} form-check-input" data-aufnr="{{ $aufnr }}" data-rspos="{{ $comp->RSPOS ?? '' }}" onchange="handleComponentSelect('{{ $aufnr }}')"></div>
                    <div class="flex-grow-1 mx-2">
                        <h6 class="mb-0 fw-bold">{{ $comp->MAKTX ?? '-' }}</h6>
                        <small class="text-muted">Material: {{ $comp->MATNR ? ltrim($comp->MATNR, '0') : '-' }}</small>
                    </div>
                    <button type="button" title="Edit Component" class="btn btn-warning btn-sm py-1 px-2 edit-component-btn"
                        data-aufnr="{{ $comp->AUFNR ?? '' }}" data-rspos="{{ $comp->RSPOS ?? '' }}" data-matnr="{{ $comp->MATNR ?? '' }}"
                        data-bdmng="{{ $comp->BDMNG ?? '' }}" data-lgort="{{ $comp->LGORT ?? '' }}" data-sobkz="{{ $comp->SOBKZ ?? '' }}"
                        data-plant="{{ $comp->WERKSX ?? 'default_plant' }}" onclick="handleEditClick(this)">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>
                <hr class="my-2">
                <div class="row g-2 small">
                    <div class="col-6"><small class="text-muted d-block">No. Reservasi</small><strong>{{ $comp->RSNUM ?? '-' }}</strong></div>
                    <div class="col-6"><small class="text-muted d-block">Item Reservasi</small><strong>{{ $comp->RSPOS ?? '-' }}</strong></div>
                    <div class="col-6 col-sm-3"><small class="text-muted d-block">Req. Qty</small><strong>{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}</strong></div>
                    <div class="col-6 col-sm-3"><small class="text-muted d-block">Qty. Commited</small><strong class="text-primary">{{ $comp->VMENG ?? '0' }}</strong></div>
                    <div class="col-6 col-sm-3"><small class="text-muted d-block">Outs. Req</small><strong class="text-success">{{ $comp->OUTSREQ ?? '-' }}</strong></div>
                    <div class="col-6 col-sm-3"><small class="text-muted d-block">Stock</small><strong>{{ $comp->KALAB ?? '-' }}</strong></div>
                    <div class="col-6 col-sm-3"><small class="text-muted d-block">S.Log</small><strong>{{ $comp->LGORT ?? '-' }}</strong></div>
                    <div class="col-6 col-sm-3"><small class="text-muted d-block">UOM</small><strong>{{ $comp->MEINS ?? '-' }}</strong></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

@empty
    <div class="alert alert-warning">Tidak ada data Komponen ditemukan.</div>
@endforelse

@once
<div class="modal fade" id="componentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-puzzle-piece me-2 text-primary"></i>Detail Komponen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalCompDescription" class="fw-medium fs-5">-</span>
                        <small class="text-muted d-block mt-2">Material Code: <span id="modalCompMaterial" class="fw-medium text-dark">-</span></small>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-6"><small class="text-muted d-block">Number Reservasi</small><span id="modalCompReservasi" class="fw-medium">-</span></div>
                            <div class="col-6"><small class="text-muted d-block">Item Reservasi</small><span id="modalCompReservasiItem" class="fw-medium">-</span></div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center g-3">
                            <div class="col-6"><small class="text-muted d-block">Req. Qty</small><span id="modalCompReqQty" class="fw-medium">-</span></div>
                            <div class="col-6"><small class="text-muted d-block">Qty. Commited</small><span id="modalCompQtyCommited" class="fw-medium text-primary">-</span></div>
                            <div class="col-6"><small class="text-muted d-block">Outs. Req</small><span id="modalCompOutsReq" class="fw-bold text-success">-</span></div>
                            <div class="col-6"><small class="text-muted d-block">Stock</small><span id="modalCompStock" class="fw-medium">-</span></div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <div class="row">
                            <div class="col-4"><small class="text-muted d-block">S.Log</small><span id="modalCompSlog" class="fw-medium">-</span></div>
                            <div class="col-4"><small class="text-muted d-block">UOM</small><span id="modalCompUom" class="fw-medium">-</span></div>
                            <div class="col-4"><small class="text-muted d-block">Spec. Procurement</small><span id="modalCompSpec" class="fw-medium">-</span></div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-dialog-custom { max-width: 450px; }
    .clickable-row:hover { cursor: pointer; background-color: #f8f9fa; }
    .component-responsive-table .sortable-header { cursor: pointer; user-select: none; }
    .component-responsive-table .sortable-header:hover { background-color: #e9ecef; }
    .component-responsive-table .sort-icon { margin-left: 5px; color: #aaa; }
</style>

<script>
    function handleEditClick(buttonElement) {
        event.stopPropagation();
        const dataEntryModalEl = document.getElementById('dataEntryModal');
        if(dataEntryModalEl) {
            document.getElementById('formPro').value = buttonElement.dataset.aufnr;
            document.getElementById('formRspos').value = buttonElement.dataset.rspos;
            document.getElementById('formMatnr').value = buttonElement.dataset.matnr;
            document.getElementById('formBdmng').value = buttonElement.dataset.bdmng;
            document.getElementById('formLgort').value = buttonElement.dataset.lgort;
            document.getElementById('formSobkz').value = buttonElement.dataset.sobkz;
            document.getElementById('formPlant').value = buttonElement.dataset.plant;
            new bootstrap.Modal(dataEntryModalEl).show();
        } else { console.error('Modal "dataEntryModal" tidak ditemukan.'); }
    }

    if (!window.componentTableScriptLoaded) {
        window.componentTableScriptLoaded = true;
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('componentDetailModal');
            if (!modalElement) return;
            const componentModal = new bootstrap.Modal(modalElement);

            document.body.addEventListener('click', function(event) {
                // Logika untuk membuka modal detail saat baris diklik
                const row = event.target.closest('.clickable-row');
                if (row && !event.target.closest('.edit-component-btn, .form-check-input, a')) {
                    document.getElementById('modalCompReservasi').textContent = row.dataset.reservasi;
                    document.getElementById('modalCompReservasiItem').textContent = row.dataset.reservasiItem;
                    document.getElementById('modalCompMaterial').textContent = row.dataset.material;
                    document.getElementById('modalCompDescription').textContent = row.dataset.description;
                    document.getElementById('modalCompReqQty').textContent = row.dataset.reqQty;
                    document.getElementById('modalCompStock').textContent = row.dataset.stock;
                    document.getElementById('modalCompOutsReq').textContent = row.dataset.outsReq;
                    document.getElementById('modalCompSlog').textContent = row.dataset.slog;
                    document.getElementById('modalCompUom').textContent = row.dataset.uom;
                    document.getElementById('modalCompSpec').textContent = row.dataset.spec;
                    document.getElementById('modalCompQtyCommited').textContent = row.dataset.qtyCommited;
                    componentModal.show();
                }

                // Logika Sorting
                const header = event.target.closest('.component-responsive-table .sortable-header');
                if (header) {
                    const table = header.closest('.component-responsive-table');
                    const tableBody = table.querySelector('tbody');
                    const column = header.dataset.sortColumn;
                    const type = header.dataset.sortType || 'text';
                    const currentDirection = header.dataset.sortDirection || 'desc';
                    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                    
                    header.dataset.sortDirection = newDirection;
                    const rows = Array.from(tableBody.querySelectorAll('tr'));

                    rows.sort((rowA, rowB) => {
                        const cellA = rowA.querySelector(`[data-col="${column}"]`);
                        const cellB = rowB.querySelector(`[data-col="${column}"]`);
                        if (!cellA || !cellB) return 0;
                        
                        let valA = cellA.textContent.trim();
                        let valB = cellB.textContent.trim();
                        
                        if (type === 'number') {
                            valA = parseFloat(valA.replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
                            valB = parseFloat(valB.replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
                            return newDirection === 'asc' ? valA - valB : valB - valA;
                        } else {
                            valA = valA.toLowerCase();
                            valB = valB.toLowerCase();
                            if (valA < valB) return newDirection === 'asc' ? -1 : 1;
                            if (valA > valB) return newDirection === 'asc' ? 1 : -1;
                            return 0;
                        }
                    });

                    tableBody.innerHTML = '';
                    rows.forEach(row => tableBody.appendChild(row));

                    table.querySelectorAll('.sortable-header').forEach(h => {
                        const icon = h.querySelector('.sort-icon i');
                        if (h === header) {
                            icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                        } else {
                            h.dataset.sortDirection = '';
                            icon.className = 'fas fa-sort';
                        }
                    });
                }
            });
        });
    }
</script>
@endonce
