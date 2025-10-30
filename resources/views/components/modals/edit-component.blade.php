@push('styles')
<style>
    /* Kustomisasi Modal */
    .modal-content { border: none; border-radius: 0.75rem; box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important; }
    .modal-header { border-bottom: 1px solid #e9ecef; padding: 1.5rem; }
    .modal-title-wrapper { display: flex; align-items: center; gap: 0.75rem; }
    .modal-title-wrapper .icon-wrapper { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: var(--bs-primary-bg-subtle); color: var(--bs-primary); border-radius: 0.5rem; }
    .modal-title { font-weight: 600; margin-bottom: 0.125rem; }
    .modal-subtitle { font-size: 0.9rem; color: #6c757d; }
    .modal-body { padding: 1.5rem; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #e9ecef; padding: 1rem 1.5rem; border-bottom-left-radius: 0.75rem; border-bottom-right-radius: 0.75rem; }
    .form-label { font-weight: 500; color: #495057; margin-bottom: 0.5rem; }
    .form-label i { margin-right: 0.5rem; color: #6c757d; }
    .form-control:read-only, .form-control-readonly { background-color: #e9ecef; opacity: 1; cursor: not-allowed; }
    .toggle-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .toggle-slider { background-color: var(--bs-primary); }
    input:focus + .toggle-slider { box-shadow: 0 0 1px var(--bs-primary); }
    input:checked + .toggle-slider:before { transform: translateX(22px); }
</style>
@endpush

<!-- Modal -->
<div class="modal fade" id="dataEntryModal" tabindex="-1" aria-labelledby="dataEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <div class="icon-wrapper"><i class="fa-solid fa-puzzle-piece"></i></div>
                    <div>
                        <h5 class="modal-title" id="dataEntryModalLabel">Edit Komponen</h5>
                        <p class="modal-subtitle mb-0">Silakan ganti data komponen yang sudah ada.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="entryForm" method="POST" action="{{ route('components.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="formPro" class="form-label"><i class="fa-solid fa-hashtag"></i>Nomor PRO</label>
                            <input type="text" class="form-control form-control-readonly" id="formPro" name="aufnr" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="formRspos" class="form-label"><i class="fa-solid fa-list-ol"></i>Item Reservasi</label>
                            <input type="text" class="form-control form-control-readonly" id="formRspos" name="rspos" readonly>
                        </div>
                    </div>
                    <div class="row g-3 mt-1" >
                        <div class="col-md-6">
                            <label for="formPlant" class="form-label"><i class="fa-solid fa-industry"></i>Plant</label>
                            <input type="text" class="form-control form-control-readonly" id="formPlant" name="plant" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="formMeins" class="form-label"><i class="fa-solid fa-circle-info"></i>UoM</label>
                            <input type="text" class="form-control form-control-readonly" id="formMeins" name="meins" readonly>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="formMatnr" class="form-label"><i class="fa-solid fa-box"></i>Kode Material</label>
                        <input type="text" class="form-control" id="formMatnr" name="matnr" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="formBdmng" class="form-label">
                                <i class="fa-solid fa-boxes-stacked"></i> Req. Quantity
                            </label>
                            
                            <input type="number" 
                                class="form-control" 
                                id="formBdmng" 
                                name="bdmng" 
                                placeholder="Kosongkan jika tidak diubah"
                                step="any"> 
                        </div>
                        <div class="col-md-6">
                            <label for="formLgort" class="form-label"><i class="fa-solid fa-warehouse"></i>S. Log</label>
                            <input type="text" class="form-control" id="formLgort" name="lgort" placeholder="Kosongkan jika tidak diubah">
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-between align-items-center bg-light p-3 rounded">
                        <label for="formSobkz" class="form-label mb-0"><i class="fa-solid fa-link"></i>Ikatkan ke SO Item?</label>
                        <select class="d-none" id="formSobkz" name="sobkz">
                            <option value="" selected>Tidak diubah</option>
                            <option value="0">Tidak</option>
                            <option value="1">Ya</option>
                        </select>
                        <label class="toggle-switch">
                            <input type="checkbox" id="sobkzToggle">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="saveButton" form="entryForm">
                    <span class="default-text">Simpan Perubahan</span>
                    <span class="loading-text d-none"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const dataEntryModal = document.getElementById("dataEntryModal");
    const quantityInput = document.getElementById("formBdmng");
    const meinsInput = document.getElementById("formMeins");

    // Pastikan semua elemen ditemukan
    if (!dataEntryModal || !quantityInput || !meinsInput) {
        console.error("Satu atau lebih elemen modal (dataEntryModal, formBdmng, formMeins) tidak ditemukan.");
        return;
    }

    function validateQuantityRules() {
        const meinsValue = meinsInput.value.trim().toUpperCase();
        
        if (meinsValue === 'ST') {
            quantityInput.step = "1";
            let currentValue = parseFloat(quantityInput.value);
            if (!isNaN(currentValue) && currentValue % 1 !== 0) {
                quantityInput.value = Math.round(currentValue);
            }
        } else {
            quantityInput.step = "any";
        }
    }
    quantityInput.addEventListener("input", validateQuantityRules);
    dataEntryModal.addEventListener('show.bs.modal', function() {
        setTimeout(validateQuantityRules, 10); 
    });

});
</script>

