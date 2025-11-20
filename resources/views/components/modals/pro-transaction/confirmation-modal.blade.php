<div id="bulkActionModal" class="modal-overlay">
    <div class="modal-content-search-pro">
        
        <div id="modalHeader" class="modal-header-search-pro">
            <span id="modalIcon"></span>
            <h2 id="modalTitle">Konfirmasi Aksi</h2>
        </div>
        
        <div class="modal-body-search-pro">
            <p id="modalDescription">Apakah Anda yakin?</p>
            
            <ul id="modalProList" class="mb-3"></ul>

            <div id="scheduleInputs" style="display: none;">
                <hr>
                <div class="mb-3">
                    <label for="modalScheduleDate" class="form-label fw-bold">Tanggal Penjadwalan:</label>
                    <input type="date" class="form-control" id="modalScheduleDate">
                </div>
                <div>
                    <label for="modalScheduleTime" class="form-label fw-bold">Waktu Penjadwalan:</label>
                    <input type="time" class="form-control" id="modalScheduleTime" value="08:00">
                </div>
            </div>
            <div id="changePvInputs" style="display: none; margin-top: 15px;">
                <label for="changePvInput" class="form-label fw-bold">Pilih Production Version Baru:</label>
                <select id="changePvInput" name="PROD_VERSION" class="form-select" required>
                    <option value="">-- Pilih Production Version --</option>
                    <option value="0001">PV 0001</option>
                    <option value="0002">PV 0002</option>
                    <option value="0003">PV 0003</option>
                </select>
            </div>
        </div>

        <div class="modal-actions-search-pro">
            <button id="modalBtnBatal" class="btn btn-secondary">Batal</button>
            <button id="modalBtnLanjutkan" class="btn btn-primary">Lanjutkan</button>
        </div>
    </div>
</div>