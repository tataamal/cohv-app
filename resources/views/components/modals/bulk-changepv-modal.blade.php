<div class="modal fade" id="bulkChangePvModal" tabindex="-1" aria-labelledby="bulkChangePvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkChangePvModalLabel">Konfirmasi Perubahan PV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Anda akan melakukan <strong>Perubahan PV</strong> dengan data berikut. Pastikan data sudah benar sebelum melanjutkan.</p>
                
                <div class="border rounded p-3 bg-light mb-3">
                    <h6>Data Plant yang akan Dikirim:</h6>
                    <p class="form-control-plaintext bg-light border rounded px-2" id="dataWorkcenter"></p>
                </div>
            
                <div class="border rounded p-3 bg-light">
                    <h6>Data PRO dan VERID yang akan Dikirim:</h6>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Production Order (PRO)</th>
                                    <th scope="col">Production Version (VERID)</th>
                                </tr>
                            </thead>
                            <tbody id="pairedDataBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmBulkChangePvBtn">Ya, Lanjutkan Proses</button>
            </div>
        </div>
    </div>
</div>