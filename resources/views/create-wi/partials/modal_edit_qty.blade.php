{{-- MODAL EDIT QTY (INDUSTRIAL REDESIGN) --}}
<div class="modal fade" id="editQtyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-0 overflow-hidden font-sans">
            
            {{-- INDUSTRIAL HEADER --}}
            <div class="modal-header bg-dark text-white p-3 border-bottom border-dark border-opacity-50">
                <div class="d-flex align-items-center w-100 justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-white bg-opacity-10 rounded-1 d-flex align-items-center justify-content-center border border-white border-opacity-10" style="width: 32px; height: 32px;">
                            <i class="fa-solid fa-pen-to-square fa-sm"></i>
                        </div>
                        <div class="lh-1">
                            <h6 class="modal-title font-monospace fw-bold text-uppercase mb-0 ls-1" style="font-size: 0.9rem;">UPDATE QUANTITY</h6>
                            <span class="text-xs text-white-50 font-monospace" style="font-size: 0.65rem;">SYS.MOD.QTY.v2</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('history-wi.update-qty') }}" method="POST">
                @csrf
                <div class="modal-body p-0 bg-light">
                    <input type="hidden" name="wi_code" id="modalWiCode">
                    <input type="hidden" name="aufnr" id="modalAufnr">
                    <input type="hidden" name="nik" id="modalNik">
                    <input type="hidden" name="vornr" id="modalVornr">

                    {{-- TECHNICAL INFO PANEL --}}
                    <div class="bg-white p-3 border-bottom">
                        <div class="row g-0">
                            <div class="col-12 mb-2">
                                <label class="text-uppercase text-muted font-monospace fw-bold d-block mb-1" style="font-size: 0.65rem;">REFERENCE NO (PRO)</label>
                                <div class="badge bg-light text-dark border rounded-0 font-monospace text-wrap text-start fs-6 px-2 py-1 w-100" id="displayAufnr">
                                    -
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="text-uppercase text-muted font-monospace fw-bold d-block mb-1" style="font-size: 0.65rem;">MATERIAL DESCRIPTION</label>
                                <div class="fw-bold text-dark text-truncate" id="modalDesc" style="font-size: 0.85rem;">-</div>
                            </div>
                        </div>
                    </div>

                    {{-- INPUT & LIMIT ZONE --}}
                    <div class="p-3">
                        <div class="row g-3 h-100">
                            {{-- REAL LIMIT (GAUGE STYLE) --}}
                            <div class="col-5">
                                <div class="border border-danger border-opacity-25 bg-danger bg-opacity-10 p-2 text-center h-100 d-flex flex-column justify-content-center position-relative">
                                    <div class="position-absolute top-0 start-0 p-1">
                                        <i class="fa-solid fa-gauge-high text-danger opacity-50" style="font-size: 0.7rem;"></i>
                                    </div>
                                    <label class="text-uppercase text-danger font-monospace fw-bold mb-0 mt-2" style="font-size: 0.65rem;">REAL LIMIT</label>
                                    <div class="fs-3 fw-bold text-danger lh-1 my-1" id="displayMaxQty">0</div>
                                    <input type="hidden" id="modalMaxQtyDisplay">
                                    <span class="text-danger opacity-75 font-monospace" style="font-size: 0.65rem;">AVAILABLE</span>
                                </div>
                            </div>

                            {{-- INPUT FIELD --}}
                            <div class="col-7">
                                <div class="border border-primary bg-white p-2 h-100 shadow-sm position-relative d-flex flex-column justify-content-between">
                                    <label class="text-uppercase text-primary font-monospace fw-bold mb-1 d-block" style="font-size: 0.65rem;">NEW QUANTITY</label>
                                    <input type="number" step="1" name="new_qty" id="modalNewQty" 
                                           class="form-control form-control-lg border-0 fw-bold text-end pe-1 display-4 text-primary p-0 my-auto" 
                                           style="font-size: 2.5rem;"
                                           placeholder="0" required>
                                    <div class="text-end text-muted font-monospace mt-1" style="font-size: 0.65rem;">UNIT: PC</div>
                                </div>
                            </div>
                        </div>

                        {{-- CAPACITY BAR (PROGRESS) --}}
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="text-uppercase text-muted font-monospace fw-bold" style="font-size: 0.65rem;">LOAD CAPACITY</label>
                                <span class="fw-bold font-monospace text-dark" id="capacityPercentText" style="font-size: 0.7rem;">0%</span>
                            </div>
                            <div class="progress rounded-0 bg-secondary bg-opacity-10" style="height: 8px;">
                                <div id="capacityProgressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted font-monospace" style="font-size: 0.65rem;">REQ: <span id="modalTotalTime" class="fw-bold text-dark">0</span> MIN</span>
                                <span class="text-muted font-monospace" style="font-size: 0.65rem;">MAX: 570 MIN</span>
                            </div>
                            <input type="hidden" id="modalVgw01" value="0">
                        </div>

                        {{-- WARNING ALERT --}}
                        <div class="alert alert-warning border-0 rounded-0 d-flex align-items-center p-2 mt-3 mb-0 d-none bg-warning bg-opacity-10" id="modalTimeWarning">
                            <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                            <div class="lh-1">
                                <span class="fw-bold text-dark font-monospace d-block" style="font-size: 0.7rem;">OVERLOAD WARNING</span>
                                <span class="text-dark opacity-75" style="font-size: 0.65rem;">Max Capacity (570m) Exceeded. Auto-adjusted.</span>
                            </div>
                        </div>
                         <div class="alert alert-danger border-0 rounded-0 d-flex align-items-center p-2 mt-3 mb-0 d-none bg-danger bg-opacity-10" id="qtyErrorMsg">
                            <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i> 
                            <span class="text-danger fw-bold font-monospace" style="font-size: 0.7rem;">EXCEEDS REAL LIMIT!</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-white p-2 justify-content-between">
                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted font-monospace text-uppercase" data-bs-dismiss="modal" style="font-size: 0.75rem;">CANCEL</button>
                    <button type="submit" class="btn btn-dark rounded-0 px-4 font-monospace fw-bold text-uppercase d-flex align-items-center" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-floppy-disk me-2"></i>SAVE UPDATE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>