<div class="modal fade" id="proDetailModal" tabindex="-1" aria-labelledby="proDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="proDetailModalLabel">
                    <i class="fas fa-cogs me-2 text-primary"></i>Detail Production Order
                </h5>
            </div>

            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-8">
                                <small class="text-muted d-block">Production Order (PRO)</small>
                                <span id="modalProPro" class="fw-medium fs-5">-</span>
                            </div>
                            <div class="col-4 text-end">
                                <small class="text-muted d-block">Status</small>
                                <span id="modalProStatus">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Sales Order (SO)</small>
                                <span id="modalProSo" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">SO Item</small>
                                <span id="modalProSoItem" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalProDescription" class="fw-medium">-</span>
                        <small class="text-muted d-block mt-2">Material Code: <span id="modalProMaterialCode" class="fw-medium text-dark">-</span></small>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Qty. Order</small>
                                <span id="modalProQtyOrder" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Qty. GR</small>
                                <span id="modalProQtyGr" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Outs. GR</small>
                                <span id="modalProOutsGr" class="fw-bold text-success">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Start Date</small>
                                <span id="modalProStartDate" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">End Date</small>
                                <span id="modalProEndDate" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light">
                         <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Plant</small>
                                <span id="modalProPlant" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">MRP</small>
                                <span id="modalProMrp" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                     <li class="list-group-item px-4 py-3 text-center">
                        <small class="text-muted d-block">No. Urut Data</small>
                        <span id="modalProNo" class="fw-medium">-</span>
                    </li>
                </ul>
            </div>
            
        </div>
    </div>
</div>

<style>
    @media (max-width: 767.98px) {
        .pro-responsive-table .clickable-row:hover {
            cursor: pointer;
            background-color: #f8f9fa;
        }
    }
    .sortable-header { cursor: pointer; user-select: none; }
    .sortable-header:hover { background-color: #e9ecef; }
    .sort-icon { margin-left: 5px; color: #aaa; }

    .modal-dialog-custom {
        max-width: 420px; 
        margin-left: 1rem;  
        margin-right: 1rem; 
    }
    
</style>