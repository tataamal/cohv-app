{{-- ======================================================= --}}
{{--      BAGIAN 1: STRUKTUR TABEL                           --}}
{{-- ======================================================= --}}
<div class="table-responsive">
    {{-- [UBAH] Tambahkan class unik 'pro-list-responsive-table' --}}
    <table class="table table-striped table-hover table-sm pro-list-responsive-table" id="tdata3-table">
        <thead class="bg-primary text-white">
            <tr class="align-middle">
                {{-- [UBAH] Tambahkan class responsif & sorting --}}
                <th class="text-center d-none d-md-table-cell" style="width: 5%;">No</th>
                
                {{-- Kolom yang Tampil di Mobile --}}
                <th class="text-center sortable-header" data-sort-column="so" data-sort-type="text" style="width: 15%;">SO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center sortable-header" data-sort-column="so_item" data-sort-type="text" style="width: 10%;">SO Item <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center sortable-header" data-sort-column="pro" data-sort-type="text" style="width: 15%;">PRO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                
                {{-- Kolom yang disembunyikan di mobile --}}
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="status" data-sort-type="text" style="width: 10%;">Status <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="material" data-sort-type="text" style="width: 15%;">Material <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell">Description</th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="plant" data-sort-type="text">Plant <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="mrp" data-sort-type="text">MRP <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="req_qty" data-sort-type="number" style="width: 10%;">Required Qty <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="gr_qty" data-sort-type="number" style="width: 10%;">GR Qty <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="outs_qty" data-sort-type="number" style="width: 10%;">Outs. Qty <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="start_date" data-sort-type="date" style="width: 10%;">Start Date <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="finish_date" data-sort-type="date" style="width: 10%;">Finish Date <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
            </tr>
        </thead>
        <tbody id="tdata3-body">
            @forelse ($pros as $pro) 
                @php
                    // Logika Status dan Perhitungan (tidak berubah)
                    $statusClass = 'bg-light text-muted';
                    if (isset($pro->STATS)) {
                        $stats = strtoupper($pro->STATS);
                        if ($stats === 'CRTD') $statusClass = 'bg-secondary';
                        elseif (str_contains($stats, 'REL') || $stats === 'PCNF') $statusClass = 'bg-warning text-dark';
                        elseif ($stats === 'TECO') $statusClass = 'bg-success';
                    }
                    $psmng = (float) ($pro->PSMNG ?? 0);
                    $wemng = (float) ($pro->WEMNG ?? 0);
                    $outsQty = $psmng - $wemng;
                    $formatDateSafe = function($date) {
                        return (!empty($date) && $date != '00000000') ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '-';
                    };
                @endphp
                
                {{-- [UBAH] Tambahkan class 'clickable-row' dan semua atribut data-* untuk modal --}}
                <tr class="align-middle clickable-row"
                    data-no="{{ $loop->iteration }}"
                    data-pro="{{ $pro->AUFNR ?? '-' }}"
                    data-so="{{ $pro->KDAUF ?? '-' }}"
                    data-so-item="{{ $pro->KDPOS ?? '-' }}"
                    data-status="{{ $pro->STATS ?? '-' }}"
                    data-status-class="{{ $statusClass }}"
                    data-material="{{ $pro->MATNR ? trim($pro->MATNR, '0') : '-' }}"
                    data-description="{{ $pro->MAKTX ?? '-' }}"
                    data-plant="{{ $pro->PWWRK ?? '-' }}"
                    data-mrp="{{ $pro->DISPO ?? '-' }}"
                    data-req-qty="{{ number_format($psmng, 0, ',', '.') }}"
                    data-gr-qty="{{ number_format($wemng, 0, ',', '.') }}"
                    data-outs-qty="{{ number_format($outsQty, 0, ',', '.') }}"
                    data-start-date="{{ $formatDateSafe($pro->GSTRP) }}"
                    data-finish-date="{{ $formatDateSafe($pro->GLTRP) }}">

                    <td class="text-center d-none d-md-table-cell">{{ $loop->iteration }}</td>
                    <td class="text-center" data-col="so">{{ $pro->KDAUF ?? '-' }}</td>
                    <td class="text-center" data-col="so_item">{{ $pro->KDPOS ?? '-' }}</td>
                    <td class="text-center fw-medium" data-col="pro">{{ $pro->AUFNR ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell" data-col="status"><span class="badge {{ $statusClass }}">{{ $pro->STATS ?? '-' }}</span></td>
                    <td class="text-center d-none d-md-table-cell" data-col="material">{{ $pro->MATNR ? trim($pro->MATNR, '0') : '-' }}</td>
                    <td class="d-none d-md-table-cell" data-col="description">{{ $pro->MAKTX ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell" data-col="plant">{{ $pro->PWWRK ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell" data-col="mrp">{{ $pro->DISPO ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell" data-col="req_qty">{{ number_format($psmng, 0, ',', '.') }}</td>
                    <td class="text-center d-none d-md-table-cell" data-col="gr_qty">{{ number_format($wemng, 0, ',', '.') }}</td>
                    <td class="text-center d-none d-md-table-cell fw-bold" data-col="outs_qty">{{ number_format($outsQty, 0, ',', '.') }}</td>
                    {{-- [PERBAIKAN] Logika tanggal diperbaiki dan data-sort-value ditambahkan --}}
                    <td class="text-center d-none d-md-table-cell" data-col="start_date" data-sort-value="{{ $pro->GSTRP ?? '0' }}">{{ $formatDateSafe($pro->GSTRP) }}</td>
                    <td class="text-center d-none d-md-table-cell" data-col="finish_date" data-sort-value="{{ $pro->GLTRP ?? '0' }}">{{ $formatDateSafe($pro->GLTRP) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center p-4 text-muted">Data tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


{{-- ======================================================= --}}
{{--      BAGIAN 2: MODAL DETAIL                             --}}
{{-- ======================================================= --}}
@once
<div class="modal fade" id="proListDetailModal" tabindex="-1" aria-labelledby="proListDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="proListDetailModalLabel">
                    <i class="fas fa-list-alt me-2 text-primary"></i>Detail PRO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-8">
                                <small class="text-muted d-block">Production Order (PRO)</small>
                                <span id="modalProListPro" class="fw-medium fs-5">-</span>
                            </div>
                            <div class="col-4 text-end">
                                <small class="text-muted d-block">Status</small>
                                <span id="modalProListStatus">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalProListDescription" class="fw-medium">-</span>
                        <small class="text-muted d-block mt-2">Material Code: <span id="modalProListMaterial" class="fw-medium text-dark">-</span></small>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Required Qty</small>
                                <span id="modalProListReqQty" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">GR Qty</small>
                                <span id="modalProListGrQty" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Outs. Qty</small>
                                <span id="modalProListOutsQty" class="fw-bold text-success">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Start Date</small>
                                <span id="modalProListStartDate" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Finish Date</small>
                                <span id="modalProListFinishDate" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                     <li class="list-group-item px-4 py-3 bg-light">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Plant</small>
                                <span id="modalProListPlant" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">MRP</small>
                                <span id="modalProListMrp" class="fw-medium">-</span>
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
        .pro-list-responsive-table .clickable-row:hover { cursor: pointer; background-color: #f8f9fa; }
    }
    .pro-list-responsive-table .sortable-header { cursor: pointer; user-select: none; }
    .pro-list-responsive-table .sortable-header:hover { background-color: #e9ecef; }
    .pro-list-responsive-table .sort-icon { margin-left: 5px; color: #aaa; }
</style>

<script>
    if (!window.proListScriptLoaded) {
        window.proListScriptLoaded = true;

        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('proListDetailModal');
            if (!modalElement) return;
            const proListModal = new bootstrap.Modal(modalElement);

            const table = document.querySelector('.pro-list-responsive-table');
            if(table) {
                // LOGIKA MODAL
                table.querySelector('tbody').addEventListener('click', function(event) {
                    const row = event.target.closest('.clickable-row');
                    if (row && window.innerWidth < 768) {
                        document.getElementById('modalProListPro').textContent = row.dataset.pro;
                        document.getElementById('modalProListStatus').innerHTML = `<span class="badge ${row.dataset.statusClass}">${row.dataset.status}</span>`;
                        document.getElementById('modalProListMaterial').textContent = row.dataset.material;
                        document.getElementById('modalProListDescription').textContent = row.dataset.description;
                        document.getElementById('modalProListReqQty').textContent = row.dataset.reqQty;
                        document.getElementById('modalProListGrQty').textContent = row.dataset.grQty;
                        document.getElementById('modalProListOutsQty').textContent = row.dataset.outsQty;
                        document.getElementById('modalProListStartDate').textContent = row.dataset.startDate;
                        document.getElementById('modalProListFinishDate').textContent = row.dataset.finishDate;
                        document.getElementById('modalProListPlant').textContent = row.dataset.plant;
                        document.getElementById('modalProListMrp').textContent = row.dataset.mrp;
                        proListModal.show();
                    }
                });

                // LOGIKA SORTING
                table.querySelector('thead').addEventListener('click', function(event) {
                    const header = event.target.closest('.sortable-header');
                    if (header) {
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
                            let valA, valB;

                            if (type === 'date') {
                                valA = cellA.dataset.sortValue || '0';
                                valB = cellB.dataset.sortValue || '0';
                            } else {
                                valA = cellA.textContent.trim();
                                valB = cellB.textContent.trim();
                            }
                            
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
                            if (icon) {
                                if (h === header) {
                                    icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                                } else {
                                    h.dataset.sortDirection = '';
                                    icon.className = 'fas fa-sort';
                                }
                            }
                        });
                    }
                });
            }
        });
    }
</script>
@endonce