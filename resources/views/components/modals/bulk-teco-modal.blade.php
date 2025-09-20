<div class="modal fade" id="bulkTecoModal" tabindex="-1" aria-labelledby="bulkTecoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkTecoModalLabel">Konfirmasi Bulk TECO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Anda yakin ingin melakukan untuk semua PRO yang dipilih di bawah ini?</p>
                <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                    <ul class="list-unstyled mb-0" id="bulkTecoProList"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmBulkTecoBtn">Ya, Lakukan TECO</button>
            </div>
        </div>
    </div>
</div>