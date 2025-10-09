{{-- Ganti seluruh isi file partial Anda dengan kode ini --}}

{{-- ======================================================= --}}
{{--      BAGIAN 1: STRUKTUR TABEL (LOOP)                    --}}
{{-- ======================================================= --}}
@forelse ($routingData as $aufnr => $routes)
    <h6 class="mt-4 mb-2">Routing untuk PRO: <span class="text-primary fw-bold">{{ $aufnr }}</span></h6>
    <div class="table-responsive border rounded mb-4">
        {{-- [UBAH] Tambahkan class unik 'routing-responsive-table' --}}
        <table class="table table-bordered table-sm routing-responsive-table">
            <thead class="bg-light">
                <tr class="align-middle small text-uppercase">
                    {{-- [UBAH] Sembunyikan semua kolom di mobile kecuali PV1, PV2, PV3 --}}
                    <th class="text-center d-none d-md-table-cell" style="width: 4%;">No.</th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="activity" data-sort-type="text">Activity <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="control_key" data-sort-type="text">Control Key <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="description" data-sort-type="text">Description <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="work_center" data-sort-type="text">Work Center <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="time_capacity" data-sort-type="number">Time Capacity (Hours) <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="item_day" data-sort-type="number">Item/Day <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    
                    {{-- Kolom yang Tampil di Mobile --}}
                    <th class="text-center sortable-header" data-sort-column="pv1" data-sort-type="text">PV 1 <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center sortable-header" data-sort-column="pv2" data-sort-type="text">PV 2 <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th class="text-center sortable-header" data-sort-column="pv3" data-sort-type="text">PV 3 <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($routes as $i => $route)
                    @php
                        // Logika perhitungan tidak berubah
                        $kapazStr = $route->KAPAZ ?? '0';
                        $vgw01Str = $route->VGW01 ?? '0';
                        $hasilPerHari = '-';
                        $kapazNum = (float) $kapazStr;
                        $vgw01Num = (float) $vgw01Str;
                        if ($kapazNum > 0 && $vgw01Num > 0) {
                            $multiplier = ($route->VGE01 === 'S') ? 3600 : 60;
                            $result = ($kapazNum * $multiplier) / $vgw01Num;
                            $hasilPerHari = floor($result);
                        }
                    @endphp
                    
                    {{-- [UBAH] Tambahkan class dan semua atribut data-* --}}
                    <tr class="align-middle clickable-row"
                        data-no="{{ $i + 1 }}"
                        data-activity="{{ $route->VORNR ?? '-' }}"
                        data-control-key="{{ $route->STEUS ?? '-' }}"
                        data-description="{{ $route->KTEXT ?? '-' }}"
                        data-work-center="{{ $route->ARBPL ?? '-' }}"
                        data-time-capacity="{{ $route->KAPAZ ?? '-' }}"
                        data-item-day="{{ $hasilPerHari }}"
                        data-pv1="{{ $route->PV1 ?? '-' }}"
                        data-pv2="{{ $route->PV2 ?? '-' }}"
                        data-pv3="{{ $route->PV3 ?? '-' }}">

                        <td class="text-center d-none d-md-table-cell">{{ $i + 1 }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="activity">{{ $route->VORNR ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="control_key">{{ $route->STEUS ?? '-' }}</td>
                        <td class="d-none d-md-table-cell" data-col="description">{{ $route->KTEXT ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="work_center">{{ $route->ARBPL ?? '-' }}</td> 
                        <td class="text-center d-none d-md-table-cell" data-col="time_capacity">{{ $route->KAPAZ ?? '-' }}</td>
                        <td class="text-center d-none d-md-table-cell" data-col="item_day">{{ $hasilPerHari }}</td>
                        <td class="text-center" data-col="pv1">{{ $route->PV1 ?? '-' }}</td>
                        <td class="text-center" data-col="pv2">{{ $route->PV2 ?? '-' }}</td>
                        <td class="text-center" data-col="pv3">{{ $route->PV3 ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="alert alert-warning">Tidak ada data Routing yang ditemukan.</div>
@endforelse


{{-- ======================================================= --}}
{{--      BAGIAN 2: MODAL DETAIL (DILUAR LOOP)               --}}
{{-- ======================================================= --}}
@once
<div class="modal fade" id="routingDetailModal" tabindex="-1" aria-labelledby="routingDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="routingDetailModalLabel">
                    <i class="fas fa-route me-2 text-primary"></i>Detail Routing
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Description</small>
                        <span id="modalRouteDescription" class="fw-medium fs-5">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Activity</small>
                                <span id="modalRouteActivity" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Work Center</small>
                                <span id="modalRouteWorkCenter" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted d-block">Time Capacity (Hours)</small>
                                <span id="modalRouteTimeCapacity" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Item/Day</small>
                                <span id="modalRouteItemDay" class="fw-bold text-success">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <div class="row">
                             <div class="col-6">
                                <small class="text-muted d-block">Control Key</small>
                                <span id="modalRouteControlKey" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">No. Urut</small>
                                <span id="modalRouteNo" class="fw-medium">-</span>
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
        .routing-responsive-table .clickable-row:hover { cursor: pointer; background-color: #f8f9fa; }
    }
    .routing-responsive-table .sortable-header { cursor: pointer; user-select: none; }
    .routing-responsive-table .sortable-header:hover { background-color: #e9ecef; }
    .routing-responsive-table .sort-icon { margin-left: 5px; color: #aaa; }
</style>

<script>
    if (!window.routingTableScriptLoaded) {
        window.routingTableScriptLoaded = true;

        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('routingDetailModal');
            if (!modalElement) return;
            const routingModal = new bootstrap.Modal(modalElement);

            document.body.addEventListener('click', function(event) {
                const row = event.target.closest('.routing-responsive-table .clickable-row');
                
                if (row) {
                    if (window.innerWidth < 768) {
                        document.getElementById('modalRouteNo').textContent = row.dataset.no;
                        document.getElementById('modalRouteActivity').textContent = row.dataset.activity;
                        document.getElementById('modalRouteControlKey').textContent = row.dataset.controlKey;
                        document.getElementById('modalRouteDescription').textContent = row.dataset.description;
                        document.getElementById('modalRouteWorkCenter').textContent = row.dataset.workCenter;
                        document.getElementById('modalRouteTimeCapacity').textContent = row.dataset.timeCapacity;
                        document.getElementById('modalRouteItemDay').textContent = row.dataset.itemDay;
                        routingModal.show();
                    }
                }

                const header = event.target.closest('.routing-responsive-table .sortable-header');
                if (header) {
                    const table = header.closest('.routing-responsive-table');
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