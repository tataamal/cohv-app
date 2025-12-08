<x-layouts.app title="Work Instruction History">
    @push('styles')
        <style>
            .wi-card-active { border-left: 5px solid #198754; }
            .wi-card-expired { border-left: 5px solid #dc3545; }
            .wi-document-list { max-height: 600px; overflow-y: auto; }

            /* Hover effect pada baris item agar user tahu bisa berinteraksi */
            .item-row:hover {
                background-color: #f8f9fa !important;
            }
            .btn-edit-qty {
                opacity: 0.5;
                transition: opacity 0.2s;
            }
            .item-row:hover .btn-edit-qty {
                opacity: 1;
            }

            /* Print CSS */
            @media print {
                .no-print, header, footer, .sidebar, .btn, .navbar, .modal { display: none !important; }
                .card { border: 1px solid #000 !important; break-inside: avoid; box-shadow: none !important; }
                .wi-document-list { max-height: none !important; overflow: visible !important; }
                * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            }
        </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4">
        
        {{-- Flash Message (Success/Error) --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-dark mb-0">
                <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>WI History - Plant {{ $plantCode }}
            </h1>
            <button onclick="window.print()" class="btn btn-dark no-print shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Print Report
            </button>
        </div>

        {{-- FILTER --}}
        <div class="card mb-4 border-0 shadow-sm no-print">
            <div class="card-body p-3">
                <form action="{{ route('wi.history', ['kode' => $plantCode]) }}" method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Tanggal Dokumen</label>
                            <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted">Pencarian</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari Kode WI, Workcenter, atau AUFNR..." 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('wi.history', ['kode' => $plantCode]) }}" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            {{-- BAGIAN 1: ACTIVE WI --}}
            @if($activeWIDocuments->count() > 0 || (!request()->has('search') && !request()->has('date')))
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-success text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-hourglass-start me-1"></i> Active Documents</span>
                        <span class="badge bg-white text-success">{{ $activeWIDocuments->count() }}</span>
                    </div>
                    <div class="card-body p-3 wi-document-list bg-light">
                        @forelse ($activeWIDocuments as $document)
                            @include('create-wi.partials.wi_card', ['document' => $document, 'statusClass' => 'wi-card-active'])
                        @empty
                            {{-- EMPTY STATE COMPONENT --}}
                            <div class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="Empty" style="width: 120px; opacity: 0.6;">
                                <h6 class="mt-3 text-muted fw-bold">Tidak ada dokumen aktif</h6>
                                <p class="text-muted small">Coba ubah filter tanggal atau pencarian Anda.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            {{-- BAGIAN 2: EXPIRED WI --}}
            @if($expiredWIDocuments->count() > 0 || (!request()->has('search') && !request()->has('date')))
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-hourglass-end me-1"></i> Expired Documents</span>
                        <span class="badge bg-white text-danger">{{ $expiredWIDocuments->count() }}</span>
                    </div>
                    <div class="card-body p-3 wi-document-list bg-light">
                        @forelse ($expiredWIDocuments as $document)
                            @include('create-wi.partials.wi_card', ['document' => $document, 'statusClass' => 'wi-card-expired'])
                        @empty
                            {{-- EMPTY STATE COMPONENT --}}
                            <div class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486803.png" alt="Empty" style="width: 120px; opacity: 0.6;">
                                <h6 class="mt-3 text-muted fw-bold">Tidak ada dokumen expired</h6>
                                <p class="text-muted small">Semua dokumen masih berjalan atau belum ada riwayat.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL EDIT QTY (Hidden by default) --}}
    <div class="modal fade" id="editQtyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Update Quantity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
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

    @push('scripts')
    <script>
        // SCRIPT UNTUK HANDLE MODAL EDIT
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
    @endpush
</x-layouts.app>