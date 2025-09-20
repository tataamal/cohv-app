<div class="modal fade" id="bulkScheduleModal" tabindex="-1" aria-labelledby="bulkScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="bulkScheduleForm ">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkScheduleModalLabel">Selected Schedule Production Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">PRO yang akan di-schedule:</label>
                        {{-- Daftar PRO akan ditampilkan di sini oleh JavaScript --}}
                        <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                            <ul class="list-unstyled mb-0" id="bulkScheduleProList">
                                {{-- Daftar PRO akan diisi di sini --}}
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Plant (WERKSX):</label>
                        <p class="form-control-plaintext bg-light border rounded px-2" id="bulkSchedulePlant"></p>
                    </div>
                    <div class="mb-3">
                        <label for="bulkScheduleDate" class="form-label">Set Tanggal Tujuan</label>
                        <input type="date" name="date" id="bulkScheduleDate" required class="form-control">
                    </div>
                    <div>
                        <label for="bulkScheduleTime" class="form-label">Set Jam Tujuan (HH.MM.SS)</label>
                        <input type="text" name="time" id="bulkScheduleTime" value="00.00.00" placeholder="00.00.00" required pattern="^\d{2}[\.:]\d{2}[\.:]\d{2}$" class="form-control">
                        <div class="form-text">Gunakan format 24 jam, contoh: 13.30.00</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="confirmBulkScheduleBtn">Simpan & Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>