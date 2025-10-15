<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="stockModalLabel">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Stock Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <div class="d-flex align-items-center text-muted small mb-1">
                        <i class="fa-solid fa-tag fa-fw me-2"></i>
                        <span>MATERIAL NUMBER</span>
                    </div>
                    <h6 class="fw-bold ms-4" id="modal-matnr">Loading...</h6>
                    
                    <div class="d-flex align-items-center text-muted small mb-1 mt-2">
                         <i class="fa-solid fa-align-left fa-fw me-2"></i>
                        <span>MATERIAL DESCRIPTION</span>
                    </div>
                    <h6 class="ms-4" id="modal-maktx">Loading...</h6>
                </div>
                <div id="stock-locations-list">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>