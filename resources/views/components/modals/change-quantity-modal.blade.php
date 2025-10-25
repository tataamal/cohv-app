<div class="modal fade" id="changeQuantityModal" tabindex="-1" aria-labelledby="changeQuantityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeQuantityModalLabel">Change Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changeQtyForm">
                @csrf <div class="modal-body">
                    <input type="hidden" id="modal_aufnr" name="aufnr">
                    <input type="hidden" id="modal_werks" name="werks">

                    <div class="mb-3">
                        <label class="form-label">Production Order</label>
                        <input type="text" class="form-control" id="display_aufnr" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Quantity</label>
                        <input type="text" class="form-control" id="display_current_qty" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="modal_new_quantity" class="form-label fw-bold">New Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="modal_new_quantity" name="new_quantity" required>
                    </div>

                    <div id="processing-message" class="text-info" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Change Qty Process...
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitChangeQtyBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>