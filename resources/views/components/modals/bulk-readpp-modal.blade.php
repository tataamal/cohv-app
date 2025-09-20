<div class="modal fade" id="bulkReadPpModal" tabindex="-1" aria-labelledby="bulkReadPpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkReadPpModalLabel">Konfirmasi Selected READ PP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Anda akan melakukan READ PP untuk PRO berikut. Proses ini berpotensi memberikan perubahan para masing-masing item PRO.</p>
                <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                    <ul class="list-unstyled mb-0" id="bulkReadPpProList"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmBulkReadPpBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>