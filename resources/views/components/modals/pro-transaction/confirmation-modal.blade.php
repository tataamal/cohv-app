<div id="bulkActionModal" class="modal-overlay">
    <div class="modal-content">
        
        <div id="modalHeader" class="modal-header">
            <span id="modalIcon"></span>
            <h2 id="modalTitle">Konfirmasi Aksi</h2>
        </div>
        
        <div class="modal-body">
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
            </div>

        <div class="modal-actions">
            <button id="modalBtnBatal" class="btn btn-secondary">Batal</button>
            <button id="modalBtnLanjutkan" class="btn btn-primary">Lanjutkan</button>
        </div>
    </div>
</div>