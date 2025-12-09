{{-- MODAL EDIT QTY --}}
<div class="modal fade" id="editQtyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Update Quantity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Pastikan Route ini sudah ada di web.php Anda --}}
            <form action="{{ route('history-wi.update-qty') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="wi_code" id="modalWiCode">
                    <input type="hidden" name="aufnr" id="modalAufnr">

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Material / Description</label>
                        <input type="text" class="form-control-plaintext fw-bold text-dark" id="modalDesc" readonly>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Order Qty (Max)</label>
                            <input type="text" class="form-control-plaintext text-secondary fw-bold" id="modalMaxQtyDisplay" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">New Assigned Qty</label>
                            <div class="input-group">
                                <input type="number" step="0.001" name="new_qty" id="modalNewQty" class="form-control fw-bold text-primary" required>
                                <span class="input-group-text" id="modalUom">EA</span>
                            </div>
                            <div class="form-text text-danger d-none" id="qtyErrorMsg">
                                Melebihi batas Order Qty!
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSaveQty">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SCRIPT PENDUKUNG MODAL --}}
{{-- Kita letakkan script di sini atau di stack scripts utama, tapi agar modular bisa taruh sini --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editQtyModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', event => {
                // Tombol yang diklik
                const button = event.relatedTarget;
                
                // Ambil data dari atribut data-*
                const wiCode = button.getAttribute('data-wi-code');
                const aufnr = button.getAttribute('data-aufnr');
                const desc = button.getAttribute('data-desc');
                const currentQty = button.getAttribute('data-current-qty');
                const maxQty = button.getAttribute('data-max-qty');
                const uom = button.getAttribute('data-uom');

                // Isi ke dalam Modal
                document.getElementById('modalWiCode').value = wiCode;
                document.getElementById('modalAufnr').value = aufnr;
                document.getElementById('modalDesc').value = desc;
                document.getElementById('modalNewQty').value = currentQty;
                document.getElementById('modalUom').innerText = uom;
                
                // Setup Max Validation
                document.getElementById('modalMaxQtyDisplay').value = maxQty + ' ' + uom;
                
                const inputQty = document.getElementById('modalNewQty');
                const btnSave = document.getElementById('btnSaveQty');
                const errorMsg = document.getElementById('qtyErrorMsg');
                const maxVal = parseFloat(maxQty);

                // Validasi Real-time saat mengetik
                inputQty.oninput = function() {
                    const val = parseFloat(this.value);
                    if (val > maxVal) {
                        this.classList.add('is-invalid');
                        errorMsg.classList.remove('d-none');
                        btnSave.disabled = true;
                    } else {
                        this.classList.remove('is-invalid');
                        errorMsg.classList.add('d-none');
                        btnSave.disabled = false;
                    }
                };
                
                // Trigger sekali di awal untuk reset state
                inputQty.dispatchEvent(new Event('input'));
            });
        }
    });
</script>