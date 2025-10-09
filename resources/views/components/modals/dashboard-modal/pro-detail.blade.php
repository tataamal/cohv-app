<div class="modal fade" id="totalProDetailModal" tabindex="-1" aria-labelledby="totalProDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="totalProDetailModalLabel">
                    <i class="fas fa-cogs me-2 text-primary"></i>Detail Production Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-8">
                                <small class="text-muted d-block">Production Order (PRO)</small>
                                <span id="modalTotalProPro" class="fw-medium fs-5">-</span>
                            </div>
                            <div class="col-4 text-end">
                                <small class="text-muted d-block">Status</small>
                                <span id="modalTotalProStatus">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Sales Order (SO)</small>
                                <span id="modalTotalProSo" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">SO Item</small>
                                <span id="modalTotalProSoItem" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalTotalProDescription" class="fw-medium">-</span>
                        <small class="text-muted d-block mt-2">Material Code: <span id="modalTotalProMaterialCode" class="fw-medium text-dark">-</span></small>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Qty. Order</small>
                                <span id="modalTotalProQtyOrder" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Qty. GR</small>
                                <span id="modalTotalProQtyGr" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Outs. GR</small>
                                <span id="modalTotalProOutsGr" class="fw-bold text-success">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Start Date</small>
                                <span id="modalTotalProStartDate" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">End Date</small>
                                <span id="modalTotalProEndDate" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Plant</small>
                                <span id="modalTotalProPlant" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">MRP</small>
                                <span id="modalTotalProMrp" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                     <li class="list-group-item px-4 py-3 text-center">
                        <small class="text-muted d-block">No. Urut Data</small>
                        <span id="modalTotalProNo" class="fw-medium">-</span>
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
    /* CSS untuk modal dan tabel ini saja */
    .modal-dialog-custom {
        max-width: 420px;
        margin-left: 1rem;
        margin-right: 1rem;
    }
    @media (max-width: 767.98px) {
        .total-pro-responsive-table .clickable-row:hover {
            cursor: pointer;
            background-color: #f8f9fa;
        }
    }
    .total-pro-responsive-table .sortable-header { cursor: pointer; user-select: none; }
    .total-pro-responsive-table .sortable-header:hover { background-color: #e9ecef; }
    .total-pro-responsive-table .sort-icon { margin-left: 5px; color: #aaa; }
</style>