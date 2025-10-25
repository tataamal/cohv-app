<div class="modal fade" id="bulkChangeQuantityModal" tabindex="-1" aria-labelledby="bulkChangeQuantityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkChangeQuantityModalLabel">Bulk Change Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="bulkChangeQtyForm">
                @csrf
                <div class="modal-body">
                    <p>Hanya item dengan kuantitas yang diubah yang akan diproses.</p>
                    
                    <div id="bulk-qty-list-container" class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        </div>

                    <div id="bulk-processing-message" class="text-info mt-3" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Processing... This may take a while. Please wait.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBulkChangeQtyBtn">Process Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>