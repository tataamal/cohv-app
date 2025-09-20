<div class="modal fade" id="changePvModal" tabindex="-1" aria-labelledby="changePvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <form action="{{ route('change-pv') }}" method="POST" id="changePvForm">
                @csrf
                <div class="modal-header">
                <h5 class="modal-title" id="changePvModalLabel">Change Production Version (PV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                {{-- Hidden yang akan diisi saat modal dibuka --}}
                <input type="hidden" id="changePvAufnr" name="AUFNR">
                <input type="hidden" id="changePvWerks" name="plant">

                <label for="changePvInput" class="form-label">Production Version (PV)</label>
                <select id="changePvInput" name="PROD_VERSION" class="form-select" required>
                    <option value="">-- Pilih Production Version --</option>
                    <option value="0001">PV 0001</option>
                    <option value="0002">PV 0002</option>
                    <option value="0003">PV 0003</option>
                </select>
                <div id="changePvCurrent" class="form-text"></div>
                </div>

                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" id="changePvSubmitBtn" class="btn btn-primary">Simpan</button>
                </div>
            </form>
            </div>
        </div>
    </div>