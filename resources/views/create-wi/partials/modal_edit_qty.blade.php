{{-- MODAL EDIT QTY --}}
<div class="modal fade" id="editQtyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm-custom" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom-0 bg-primary text-white p-4">
                <div class="d-flex flex-column w-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="bg-white bg-opacity-25 p-2 rounded-3 d-inline-flex">
                            <i class="fa-solid fa-pen-to-square fa-lg"></i>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <h5 class="modal-title fw-bold">Perbarui Quantity</h5>
                    <p class="mb-0 small text-white-50">Sesuaikan jumlah quantity WI untuk PRO ini.</p>
                </div>
            </div>
            
            <form action="{{ route('history-wi.update-qty') }}" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="wi_code" id="modalWiCode">
                    <input type="hidden" name="aufnr" id="modalAufnr">

                    {{-- Card Material --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <label class="text-uppercase text-muted text-xs fw-bold mb-1">Material</label>
                            <input type="text" class="form-control-plaintext fw-bold text-dark p-0" id="modalDesc" readonly style="font-size: 0.95rem;">
                             <label class="text-uppercase text-muted text-xs fw-bold mt-2 mb-0">PRO</label>
                             <div class="fw-bold text-dark" id="displayAufnr"></div>
                        </div>
                    </div>

                    <div class="row g-2">
                         {{-- Max Qty --}}
                         <div class="col-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3 text-center">
                                    <label class="text-uppercase text-danger text-xs fw-bold mb-1 d-block">Max Qty</label>
                                    <div class="fs-5 fw-bold text-danger" id="displayMaxQty"></div>
                                    <input type="hidden" id="modalMaxQtyDisplay">
                                    <small class="text-xs text-danger">Quantity WI</small>
                                </div>
                            </div>
                        </div>

                        {{-- New Qty --}}
                         <div class="col-6">
                            <div class="card border-primary shadow-sm h-100 bg-white">
                                <div class="card-body p-3">
                                    <label class="text-uppercase text-primary text-center text-xs fw-bold mb-1 d-block">New Qty</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.001" name="new_qty" id="modalNewQty" class="form-control fw-bold border-0 p-0 fs-5 text-primary text-center" placeholder="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="form-text text-danger text-center small mt-2 d-none fw-bold" id="qtyErrorMsg">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Cannot exceed Max Qty!
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0 bg-light">
                    <div class="row w-100 m-0">
                        <div class="col-6 ps-0">
                             <button type="button" class="btn btn-light w-100 fw-bold rounded-pill text-muted" data-bs-dismiss="modal">Batal</button>
                        </div>
                        <div class="col-6 pe-0">
                            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm" id="btnSaveQty">
                                Simpan Update
                            </button>
                        </div>
                    </div>
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
                
                // Update New UI Fields
                const dispAufnr = document.getElementById('displayAufnr');
                if(dispAufnr) dispAufnr.innerText = aufnr;

                const dispMax = document.getElementById('displayMaxQty');
                if(dispMax) dispMax.innerText = maxQty;

                // Setup Max Validation
                document.getElementById('modalMaxQtyDisplay').value = maxQty;
                
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