<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel aria-hidden="true"">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="#" method="POST" id="scheduleForm">
                    @csrf
                    <input type="hidden" name="aufnr" id="scheduleAufnr">
                    <div class="modal-header">
                        <h5 class="modal-title" id="scheduleModalLabel">Schedule Production Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="scheduleDate" class="form-label">Tanggal</label>
                            <input type="date" name="date" id="scheduleDate" required class="form-control">
                        </div>
                        <div>
                            <label for="scheduleTime" class="form-label">Jam (HH.MM.SS)</label>
                            <input type="text" name="time" id="scheduleTime" placeholder="00.00.00" required pattern="^\d{2}[\.:]\d{2}[\.:]\d{2}$" class="form-control">
                            <div class="form-text">Format 24 jam, contoh: 13.30.00</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success" id="confirmScheduleBtn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>