{{-- resources/views/admin/partials/pro-overview-table.blade.php --}}

{{-- ======================================================= --}}
{{--      BAGIAN 1: STRUKTUR TABEL                           --}}
{{-- ======================================================= --}}
<div class="table-responsive">
    {{-- [UBAH] Tambahkan class unik 'pro-overview-responsive-table' --}}
    <table class="table table-striped table-hover table-sm pro-overview-responsive-table" id="tdata3-table">
        <thead class="bg-primary text-white">
            <tr class="align-middle">
                {{-- [UBAH] Tambahkan class responsif untuk menyembunyikan kolom di mobile --}}
                <th class="text-center d-none d-md-table-cell" style="width: 5%;">No</th>
                
                {{-- Kolom yang Tampil di Mobile --}}
                <th class="text-center" style="width: 15%;">PRO Number</th>
                <th class="text-center" style="width: 10%;">Status</th>
                
                <th class="text-center d-none d-md-table-cell" style="width: 15%;">Material</th>
                <th class="text-center d-none d-md-table-cell">Description</th>
                <th class="text-center d-none d-md-table-cell" style="width: 10%;">Required Qty</th>
                <th class="text-center d-none d-md-table-cell" style="width: 10%;">GR Qty</th>
                <th class="text-center d-none d-md-table-cell" style="width: 10%;">Outs. Qty</th>
                <th class="text-center d-none d-md-table-cell" style="width: 10%;">Start Date</th>
                <th class="text-center d-none d-md-table-cell" style="width: 10%;">Finish Date</th>
            </tr>
        </thead>
        <tbody id="tdata3-body">
            @forelse ($proOrders->flatten() as $d3) 
                @php
                    // Logika Status dan Perhitungan tidak berubah
                    $statusClass = 'bg-light text-muted';
                    if (isset($d3->STATS)) {
                        $stats = strtoupper($d3->STATS);
                        if ($stats === 'CRTD') $statusClass = 'bg-secondary';
                        elseif (str_contains($stats, 'REL') || $stats === 'PCNF') $statusClass = 'bg-warning text-dark';
                        elseif ($stats === 'TECO') $statusClass = 'bg-success';
                    }
                    $psmng = (float) ($d3->PSMNG ?? 0);
                    $wemng = (float) ($d3->WEMNG ?? 0);
                    $outsQty = $psmng - $wemng;
                    $formatDateSafe = function($date) {
                        return (!empty($date) && $date != '00000000') ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '-';
                    };
                @endphp
                
                {{-- [UBAH] Tambahkan class 'clickable-row' dan semua atribut data-* untuk modal --}}
                <tr data-aufnr="{{ $d3->AUFNR }}" class="align-middle clickable-row"
                    data-no="{{ $loop->iteration }}"
                    data-pro-number="{{ $d3->AUFNR ?? '-' }}"
                    data-status="{{ $d3->STATS ?? '-' }}"
                    data-status-class="{{ $statusClass }}"
                    data-material="{{ $d3->MATNR ? trim($d3->MATNR, '0') : '-' }}"
                    data-description="{{ $d3->MAKTX ?? '-' }}"
                    data-req-qty="{{ number_format($psmng, 0, ',', '.') }}"
                    data-gr-qty="{{ number_format($wemng, 0, ',', '.') }}"
                    data-outs-qty="{{ number_format($outsQty, 0, ',', '.') }}"
                    data-start-date="{{ $formatDateSafe($d3->GSTRP) }}"
                    data-finish-date="{{ $formatDateSafe($d3->GLTRP) }}">

                    <td class="text-center d-none d-md-table-cell">{{ $loop->iteration }}</td>
                    <td class="text-center">
                        <span class="fw-medium">{{ $d3->AUFNR ?? '-' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-center badge {{ $statusClass }}">{{ $d3->STATS ?? '-' }}</span>
                    </td>
                    <td class="text-center d-none d-md-table-cell">{{ $d3->MATNR ? trim($d3->MATNR, '0') : '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $d3->MAKTX ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ number_format($psmng, 0, ',', '.') }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ number_format($wemng, 0, ',', '.') }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ number_format($outsQty, 0, ',', '.') }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $formatDateSafe($d3->GSTRP) }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $formatDateSafe($d3->GLTRP) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center p-4 text-muted">Tidak ada Production Order (PRO) ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Elemen tambahan (tidak berubah) --}}
<div id="additional-data-container" class="mt-4"></div>


{{-- ======================================================= --}}
{{--      BAGIAN 2: MODAL DETAIL                             --}}
{{-- ======================================================= --}}
@once
<div class="modal fade" id="proOverviewDetailModal" tabindex="-1" aria-labelledby="proOverviewDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="proOverviewDetailModalLabel">
                    <i class="fas fa-cogs me-2 text-primary"></i>Detail PRO Overview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-8">
                                <small class="text-muted d-block">Production Order (PRO)</small>
                                <span id="modalProOverviewNumber" class="fw-medium fs-5">-</span>
                            </div>
                            <div class="col-4 text-end">
                                <small class="text-muted d-block">Status</small>
                                <span id="modalProOverviewStatus">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalProOverviewDescription" class="fw-medium">-</span>
                        <small class="text-muted d-block mt-2">Material Code: <span id="modalProOverviewMaterial" class="fw-medium text-dark">-</span></small>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Required Qty</small>
                                <span id="modalProOverviewReqQty" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">GR Qty</small>
                                <span id="modalProOverviewGrQty" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Outs. Qty</small>
                                <span id="modalProOverviewOutsQty" class="fw-bold text-success">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Start Date</small>
                                <span id="modalProOverviewStartDate" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Finish Date</small>
                                <span id="modalProOverviewFinishDate" class="fw-medium">-</span>
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
        .pro-overview-responsive-table .clickable-row:hover { cursor: pointer; background-color: #f8f9fa; }
    }
</style>

<script>
    if (!window.proOverviewScriptLoaded) {
        window.proOverviewScriptLoaded = true;

        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('proOverviewDetailModal');
            if (!modalElement) return;
            const proOverviewModal = new bootstrap.Modal(modalElement);

            const tableBody = document.getElementById('tdata3-body');
            if (tableBody) {
                tableBody.addEventListener('click', function(event) {
                    const row = event.target.closest('.clickable-row');
                    if (row) {
                        if (window.innerWidth < 768) {
                            // Ambil data dari atribut data-* baris yang diklik
                            document.getElementById('modalProOverviewNumber').textContent = row.dataset.proNumber;
                            document.getElementById('modalProOverviewStatus').innerHTML = `<span class="badge ${row.dataset.statusClass}">${row.dataset.status}</span>`;
                            document.getElementById('modalProOverviewMaterial').textContent = row.dataset.material;
                            document.getElementById('modalProOverviewDescription').textContent = row.dataset.description;
                            document.getElementById('modalProOverviewReqQty').textContent = row.dataset.reqQty;
                            document.getElementById('modalProOverviewGrQty').textContent = row.dataset.grQty;
                            document.getElementById('modalProOverviewOutsQty').textContent = row.dataset.outsQty;
                            document.getElementById('modalProOverviewStartDate').textContent = row.dataset.startDate;
                            document.getElementById('modalProOverviewFinishDate').textContent = row.dataset.finishDate;
                            
                            // Tampilkan modal
                            proOverviewModal.show();
                        }
                    }
                });
            }
        });
    }
</script>
@endonce