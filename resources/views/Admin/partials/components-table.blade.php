{{-- resources/views/admin/partials/components-table.blade.php --}}

{{-- ======================================================= --}}
{{--      BAGIAN 1: STRUKTUR TABEL (LOOP)                    --}}
{{-- ======================================================= --}}
@forelse ($componentData as $aufnr => $components)
    <h6 class="mt-4 mb-2">Komponen untuk PRO: <span class="text-primary fw-bold">{{ $aufnr }}</span></h6>
    <div class="table-responsive border rounded mb-4">
        <table class="table table-bordered table-sm component-responsive-table" data-pro-id="{{ $aufnr }}">
            <thead class="bg-light">
                <tr class="align-middle small text-uppercase">
                    <th class="text-center d-none d-md-table-cell" style="width: 3%;"><input type="checkbox" id="select-all-components-{{ $aufnr }}" class="form-check-input" onchange="toggleSelectAllComponents('{{ $aufnr }}')"></th>
                    <th class="text-center d-none d-md-table-cell" style="width: 4%;">No.</th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="reservasi" data-sort-type="text">Number Reservasi <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="item" data-sort-type="text">Item Reservasi <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center sortable-header" data-sort-column="material" data-sort-type="text">Material <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center sortable-header" data-sort-column="description" data-sort-type="text">Description <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center" style="width: 5%;">Action</th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="outs_req" data-sort-type="number">Outs. Req <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="req_qty" data-sort-type="number">Req. Qty <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="stock" data-sort-type="number">Stock <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="slog" data-sort-type="text">S.Log <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="uom" data-sort-type="text">UOM <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell">Spec. Procurement</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($components as $comp)
                    <tr class="align-middle clickable-row"
                        data-reservasi="{{ $comp->RSNUM ?? '-' }}"
                        data-reservasi-item="{{ $comp->RSPOS ?? '-' }}"
                        data-material="{{ $comp->MATNR ? trim($comp->MATNR, '0') : '-' }}"
                        data-description="{{ $comp->MAKTX ?? '-' }}"
                        data-req-qty="{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}"
                        data-stock="{{ $comp->KALAB ?? '-' }}"
                        data-outs-req="{{ $comp->OUTSREQ ?? '-' }}"
                        data-slog="{{ $comp->LGORT ?? '-' }}"
                        data-uom="{{ $comp->MEINS ?? '-' }}"
                        data-spec="{{ $comp->LTEXT ?? 'No Value' }}">
                        <td class="text-center d-none d-md-table-cell"><input type="checkbox" class="component-select-{{ $aufnr }} form-check-input" data-aufnr="{{ $aufnr }}" data-rspos="{{ $comp->RSPOS ?? '' }}" data-material="{{ $comp->MATNR ? trim($comp->MATNR, '0') : '' }}" onchange="handleComponentSelect('{{ $aufnr }}')"></td>
                        <td class="text-center d-none d-md-table-cell" data-col="no">{{ $loop->iteration }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="reservasi">{{ $comp->RSNUM ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="item">{{ $comp->RSPOS ?? '-' }}</td>
                        <td class="text-center" data-col="material">{{ $comp->MATNR ? trim($comp->MATNR, '0') : '-' }}</td>
                        <td data-col="description">{{ $comp->MAKTX ?? '-' }}</td>
                        <td class="text-center">
                            <button type="button" 
                                    title="Edit Component" 
                                    class="btn btn-warning btn-sm py-1 px-2 edit-component-btn"
                                    data-aufnr="{{ $comp->AUFNR ?? '' }}"
                                    data-rspos="{{ $comp->RSPOS ?? '' }}"
                                    data-matnr="{{ $comp->MATNR ?? '' }}"
                                    data-bdmng="{{ $comp->BDMNG ?? '' }}"
                                    data-lgort="{{ $comp->LGORT ?? '' }}"
                                    data-sobkz="{{ $comp->SOBKZ ?? '' }}"
                                    data-plant="{{ $comp->WERKSX ?? 'default_plant' }}"
                                    onclick="handleEditClick(this)">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                        <td class="text-center d-none d-md-table-cell fw-bold" data-col="outs_req">{{ $comp->OUTSREQ ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="req_qty">{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="stock">{{ $comp->KALAB ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="slog">{{ $comp->LGORT ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="uom">{{ $comp->MEINS ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell">{{ $comp->LTEXT ?? "No Value" }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="alert alert-warning">Tidak ada data Komponen ditemukan.</div>
@endforelse


{{-- ======================================================= --}}
{{--      BAGIAN 2: MODAL DETAIL (DILUAR LOOP)               --}}
{{-- ======================================================= --}}
@once
<div class="modal fade" id="componentDetailModal" tabindex="-1" aria-labelledby="componentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="componentDetailModalLabel">
                    <i class="fas fa-puzzle-piece me-2 text-primary"></i>Detail Komponen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <div class="col-6">
                                <small class="text-muted d-block">Number Reservasi</small>
                                <span id="modalCompReservasi" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Item Reservasi</small>
                                <span id="modalCompReservasiItem" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Req. Qty</small>
                                <span id="modalCompReqQty" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Stock</small>
                                <span id="modalCompStock" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Outs. Req</small>
                                <span id="modalCompOutsReq" class="fw-bold text-success">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <div class="row">
                            <div class="col-4">
                                <small class="text-muted d-block">S.Log</small>
                                <span id="modalCompSlog" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">UOM</small>
                                <span id="modalCompUom" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Spec. Procurement</small>
                                <span id="modalCompSpec" class="fw-medium">-</span>
                            </div>
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
@endonce


{{-- ======================================================= --}}
{{--      BAGIAN 3: CSS & JAVASCRIPT                         --}}
{{-- ======================================================= --}}
@once
<style>
    .modal-dialog-custom { max-width: 450px; margin-left: 1rem; margin-right: 1rem; }
    @media (max-width: 767.98px) {
        .component-responsive-table .clickable-row:hover { cursor: pointer; background-color: #f8f9fa; }
    }
    .component-responsive-table .sortable-header { cursor: pointer; user-select: none; }
    .component-responsive-table .sortable-header:hover { background-color: #e9ecef; }
    .component-responsive-table .sort-icon { margin-left: 5px; color: #aaa; }
</style>

<script>
    // Fungsi handleEditClick didefinisikan secara global agar bisa diakses oleh `onclick`
    function handleEditClick(buttonElement) {
        const aufnr = buttonElement.dataset.aufnr;
        const rspos = buttonElement.dataset.rspos;
        const matnr = buttonElement.dataset.matnr;
        const bdmng = buttonElement.dataset.bdmng;
        const lgort = buttonElement.dataset.lgort;
        const sobkz = buttonElement.dataset.sobkz;
        const plant = buttonElement.dataset.plant;

        console.log('✅ Tombol Edit Diklik via onclick!');
        
        const dataEntryModalEl = document.getElementById('dataEntryModal');
        if(dataEntryModalEl) {
            document.getElementById('formPro').value = aufnr;
            document.getElementById('formRspos').value = rspos;
            document.getElementById('formMatnr').value = matnr;
            document.getElementById('formBdmng').value = bdmng;
            document.getElementById('formLgort').value = lgort;
            document.getElementById('formSobkz').value = sobkz;
            document.getElementById('formPlant').value = plant;

            const dataModal = new bootstrap.Modal(dataEntryModalEl);
            dataModal.show();
        } else {
            console.error('Modal dengan ID "dataEntryModal" tidak ditemukan.');
        }
    }

    // Skrip lain dibungkus agar tidak berjalan ganda
    if (!window.componentTableScriptLoaded) {
        window.componentTableScriptLoaded = true;

        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('componentDetailModal');
            if (!modalElement) return;
            const componentModal = new bootstrap.Modal(modalElement);

            document.body.addEventListener('click', function(event) {
                // Logika untuk membuka modal detail saat baris diklik
                const row = event.target.closest('.component-responsive-table .clickable-row');
                if (row && !event.target.closest('.edit-component-btn, .form-check-input')) {
                    if (window.innerWidth < 768) {
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
                        componentModal.show();
                    }
                }

                // Logika untuk sorting header
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