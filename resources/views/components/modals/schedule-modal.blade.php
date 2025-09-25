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
                            <label for="visibleDate" class="form-label">Tanggal</label>
                            
                            {{-- Input ini yang akan dilihat dan diisi pengguna --}}
                            <input type="text" id="visibleDate" placeholder="dd/mm/yyyy" class="form-control">
                            
                            {{-- Input ini tersembunyi, nilainya akan diisi otomatis untuk backend --}}
                            <input type="hidden" name="date" id="scheduleDate">
                            
                            {{-- Tempat untuk pesan error, awalnya disembunyikan --}}
                            <div id="dateError" class="invalid-feedback" style="display: none;">
                                Tanggal tidak boleh sebelum hari ini.
                            </div>
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