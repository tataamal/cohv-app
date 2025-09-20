<div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultModalLabel">Production Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="singleView">
                        <div class="mb-3">
                            <small class="text-muted">Plant</small>
                            <p id="plantValue" class="fw-medium text-dark">-</p>
                        </div>
                        <div>
                            <small class="text-muted">Production Order</small>
                            <div id="poList" class="mt-1 d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                    <div id="batchView" class="d-none">
                        <p class="small text-muted mb-2">Converted Orders</p>
                        <div class="table-responsive border rounded-3" style="max-height: 400px;">
                            <table class="table table-sm">
                                <thead class="table-light"><tr><th>#</th><th>Planned Order</th><th>Plant</th><th>Production Order</th></tr></thead>
                                <tbody id="batchTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="resultOk" class="btn btn-primary">OK</button>
                </div>
            </div>
        </div>
    </div>