<div class="modal fade" id="proDetailModal" tabindex="-1" aria-labelledby="proModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-dialog-custom">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="proModalTitle">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Detail PRO
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                {{-- Mengganti <table> dengan List Group untuk tampilan yang lebih bersih --}}
                <ul class="list-group list-group-flush">
                    {{-- Informasi Utama --}}
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Production Order (PRO)</small>
                        <span id="modalPro" class="fw-medium fs-5">-</span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Sales Order (SO)</small>
                                <span id="modalSo" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">SO Item</small>
                                <span id="modalSoItem" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                    
                    {{-- Informasi Material --}}
                    <li class="list-group-item px-4 py-3 bg-light">
                        <small class="text-muted d-block">Material Description</small>
                        <span id="modalMaterial" class="fw-medium">-</span>
                    </li>

                    {{-- Informasi Kuantitas --}}
                    <li class="list-group-item px-4 py-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Qty. Order</small>
                                <span id="modalPsmng" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Qty. GR</small>
                                <span id="modalWemng" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>

                    {{-- Informasi Teknis --}}
                    <li class="list-group-item px-4 py-3 bg-light">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Workcenter</small>
                                <span id="modalWc" class="fw-medium">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Operation Key</small>
                                <span id="modalOperKey" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>

                    {{-- Informasi PV --}}
                    <li class="list-group-item px-4 py-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">PV1</small>
                                <span id="modalPv1" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">PV2</small>
                                <span id="modalPv2" class="fw-medium">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">PV3</small>
                                <span id="modalPv3" class="fw-medium">-</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
            
        </div>
    </div>
</div>