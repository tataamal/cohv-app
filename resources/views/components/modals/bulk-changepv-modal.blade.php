<div class="modal fade" id="bulkChangePvModal" tabindex="-1" aria-labelledby="bulkChangePvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkChangePvModalLabel">Ubah PV Massal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Semua PRO di bawah ini akan diubah menggunakan PV Tujuan yang Anda pilih.</p>
                
                <div class="mb-3">
                    <label for="targetVeridSelect" class="form-label fw-semibold">PV Tujuan (VERID):</label>
                    <select class="form-select" id="targetVeridSelect" name="target_verid">
                        <option value="0001">0001</option>
                        <option value="0002">0002</option>
                        <option value="0003">0003</option>
                    </select>
                </div>

                <div class="border rounded p-3 bg-light">
                    <h6 class="mb-2">PRO yang akan diproses:</h6>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 5%;">#</th>
                                    <th scope="col">Production Order (PRO)</th>
                                </tr>
                            </thead>
                            <tbody id="bulkChangeProList"></tbody>
                        </table>
                    </div>
                </div>

                 <div class="border rounded p-3 bg-light mt-3">
                    <h6 class="mb-2">Data Plant yang Digunakan:</h6>
                    <p class="form-control-plaintext mb-0" id="dataWorkcenter"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmBulkChangePvBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>