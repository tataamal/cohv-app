{{-- Ganti seluruh isi file dengan kode baru ini --}}

<div class="modal fade" id="soDetailModal" tabindex="-1" aria-labelledby="soDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="soDetailModalLabel">
                    {{-- Ikon yang sesuai untuk Sales Order --}}
                    <i class="fas fa-receipt me-2 text-primary"></i>Detail Sales Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Order (SO)</small>
                        {{-- Diberi font lebih besar karena ini adalah ID utama --}}
                        <span id="modalSoOrder" class="fw-medium fs-5">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Item</small>
                        <span id="modalSoItem" class="fw-medium">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Material FG</small>
                        <span id="modalSoMaterial" class="fw-medium">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Description</small>
                        <span id="modalSoDescription" class="fw-medium">-</span>
                    </li>
                </ul>
            </div>

            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
            
        </div>
    </div>
</div>

{{-- CSS untuk fungsionalitas tabel (tidak berubah) --}}
<style>
    @media (max-width: 767.98px) {
        .responsive-so-table .clickable-row:hover {
            cursor: pointer;
            background-color: #f8f9fa;
        }
    }
    .sortable-header {
        cursor: pointer;
        user-select: none;
    }
    .sortable-header:hover {
        background-color: #e9ecef;
    }
    .sort-icon {
        margin-left: 5px;
        color: #aaa;
    }
</style>