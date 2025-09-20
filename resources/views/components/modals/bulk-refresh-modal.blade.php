<div class="modal fade" id="bulkRefreshModal" tabindex="-1" aria-labelledby="bulkRefreshModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkRefreshModalLabel">Konfirmasi Selected Refresh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Proses ini akan mengambil data terbaru dari SAP untuk semua PRO yang dipilih. Lanjutkan?</p>
                <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                    <ul class="list-unstyled mb-0" id="bulkRefreshProList"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="confirmBulkRefreshBtn">Ya, Refresh Data</button>
            </div>
        </div>
    </div>
</div>