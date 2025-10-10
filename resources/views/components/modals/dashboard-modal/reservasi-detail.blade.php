{{-- Ganti seluruh isi file dengan kode baru ini --}}

<div class="modal fade" id="reservasiDetailModal" tabindex="-1" aria-labelledby="reservasiDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="reservasiDetailModalLabel">
                    <i class="fas fa-file-invoice me-2 text-primary"></i>Detail Item Reservasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">No. Reservasi</small>
                        <span id="modalReservasiRsv" class="fw-medium fs-5">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Material Code</small>
                        <span id="modalReservasiMatCode" class="fw-medium">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalReservasiDesc" class="fw-medium">-</span>
                    </li>
                    
                    {{-- [UBAH] Mengelompokkan Qty, Commited & Stock dengan flexbox (row) --}}
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            {{-- [UBAH] Mengubah class dari col-6 menjadi col-4 --}}
                            <div class="col-4">
                                <small class="text-muted d-block">Req. Qty</small>
                                <span id="modalReservasiReqQty" class="fw-medium">-</span>
                            </div>
                            {{-- [UBAH] Menambahkan kolom baru untuk Req. Commited --}}
                            <div class="col-4">
                                <small class="text-muted d-block">Req. Commited</small>
                                <span id="modalReservasiReqCommited" class="fw-medium">-</span>
                            </div>
                            {{-- [UBAH] Mengubah class dari col-6 menjadi col-4 --}}
                            <div class="col-4">
                                <small class="text-muted d-block">Stock</small>
                                <span id="modalReservasiStock" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">No. Urut Data</small>
                        <span id="modalReservasiNo" class="fw-medium">-</span>
                    </li>
                </ul>
            </div>

            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
            
        </div>
    </div>
</div>

{{-- CSS tidak perlu diubah, tetap sama --}}
<style>
    @media (max-width: 767.98px) {
        .reservasi-responsive-table .clickable-row:hover {
            cursor: pointer;
            background-color: #f8f9fa;
        }
    }
    .sortable-header { cursor: pointer; user-select: none; }
    .sortable-header:hover { background-color: #e9ecef; }
    .sort-icon { margin-left: 5px; color: #aaa; }
</style>