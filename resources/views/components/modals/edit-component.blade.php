<div class="modal fade" id="dataEntryModal" tabindex="-1" aria-labelledby="dataEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataEntryModalLabel">Formulir Komponen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="entryForm">
                    @csrf

                    <div class="mb-3">
                        <label for="formPro" class="form-label">Nomor PRO</label>
                        <input type="text" class="form-control" id="formPro" name="aufnr" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="formRspos" class="form-label">Item Reservasi</label>
                        <input type="text" class="form-control" id="formRspos" name="rspos" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="formMatnr" class="form-label">Material Number</label>
                        <input type="text" class="form-control" id="formMatnr" name="matnr">
                    </div>

                    <div class="mb-3">
                        <label for="formBdmng" class="form-label">Request Quantity</label>
                        <input type="number" class="form-control" id="formBdmng" name="bdmng">
                    </div>

                    <div class="mb-3">
                        <label for="formLgort" class="form-label">S. Log</label>
                        <input type="text" class="form-control" id="formLgort" name="lgort">
                    </div>

                    <div class="mb-3">
                        <label for="formSobkz" class="form-label">Apakah akan diikatkan ke SO Item?</label>
                        <select class="form-select" id="formSobkz" name="sobkz">
                            <option value="0">Tidak</option>
                            <option value="1">Ya</option>
                        </select>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary" form="entryForm">Simpan</button>
            </div>
        </div>
    </div>
</div>