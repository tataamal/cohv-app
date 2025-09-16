<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> {{-- Dibuat lebih besar (modal-lg) --}}
        <div class="modal-content">
            <form id="addComponentForm"> 
                @csrf
                {{-- Input hidden ini akan diisi oleh JavaScript saat modal terbuka --}}
                <input type="hidden" id="addComponentAufnr" name="iv_aufnr">
                <input type="hidden" id="addComponentVornr" name="iv_vornr">
                <input type="hidden" id="addComponentPlant" name="iv_werks">

                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">Add Component to Production Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-3">Menambahkan komponen untuk PRO: <strong id="displayAufnr" class="text-primary"></strong></p>
                    <div class="row g-3 mb-4 p-3 border rounded bg-light">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Production Order (AUFNR)</label>
                            <input type="text" id="displayAufnr" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Operation (VORNR)</label>
                            <input type="text" id="displayVornr" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Plant</label>
                            <input type="text" id="displayPlant" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="row g-3">
                        {{-- Baris 1: Material & Quantity --}}
                        <div class="col-md-8">
                            <label for="materialInput" class="form-label">Material <span class="text-danger">*</span></label>
                            <input type="text" id="materialInput" name="iv_matnr" class="form-control" required placeholder="Masukkan nomor material">
                        </div>
                        <div class="col-md-4">
                            <label for="quantityInput" class="form-label">Requirement Qty <span class="text-danger">*</span></label>
                            <input type="number" id="quantityInput" name="iv_bdmng" class="form-control" required step="0.001" min="0">
                        </div>

                        {{-- Baris 2: UOM & Storage Location --}}
                        <div class="col-md-6">
                            <label for="uomSelect" class="form-label">Unit of Measure (UoM) <span class="text-danger">*</span></label>
                            <select id="uomSelect" name="iv_meins" class="form-select" required>
                                <option value="">-- Pilih Unit --</option>
                                <option value="ST">PC - Piece</option>
                                <option value="KG">KG - Kilogram</option>
                                <option value="M">M - Meter</option>
                                {{-- Tambahkan unit lain yang relevan --}}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="storageLocationInput" class="form-label">Storage Location <span class="text-danger">*</span></label>
                            <input type="text" id="storageLocationInput" name="iv_lgort" class="form-control" required placeholder="e.g., 0001">
                        </div>

                        {{-- Baris 3: Item Category (POSTP) --}}
                        <div class="col-12">
                             <label for="itemCategorySelect" class="form-label">Item Category <span class="text-danger">*</span></label>
                             <select id="itemCategorySelect" name="iv_postp" class="form-select" required>
                                 <option value="L" selected>L - Stock item</option>
                                 {{-- Tambahkan kategori lain jika diperlukan --}}
                             </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="confirmAddComponentBtn" class="btn btn-primary">Simpan Komponen</button>
                </div>
            </form>
        </div>
    </div>
</div>