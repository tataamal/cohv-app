<x-layouts.app title="PRO Transaction">
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                
                {{-- 1. HEADER HALAMAN --}}
                <div class="card shadow-sm mb-4">
                    {{-- [PERUBAHAN] Menambahkan d-flex untuk menampung tombol --}}
                    <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                        <div>
                            <h1 class="h4 mb-1">PRO Search Results</h1>
                            <p class="mb-0 text-muted">
                                Menampilkan {{ $proDetailsList->count() }} dari {{ count($proNumbersSearched) }} PRO yang dicari untuk Plant: <strong>{{ $WERKS }}</strong>
                            </p>
                        </div>
                        {{-- [BARU] Tombol kembali ke Dashboard Plant --}}
                        <a href="{{ route('manufaktur.dashboard.show', $WERKS) }}" class="btn btn-outline-secondary mt-3 mt-sm-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>

                {{-- 2. TAMPILKAN PRO YANG TIDAK DITEMUKAN --}}
                @if(!empty($notFoundProNumbers))
                    <div class="alert alert-warning">
                        <strong>PRO Tidak Ditemukan:</strong> 
                        {{ implode(', ', $notFoundProNumbers) }}
                    </div>
                @endif

                {{-- 3. CARD TOMBOL TRANSAKSI BULK --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Bulk Transactions</h6>
                    </div>
                    <div class="card-body d-flex flex-wrap" style="gap: 10px;">
                        <button class="btn btn-success-subtle text-success-emphasis border-success-subtle" id="bulkEditBtn" disabled>
                            <i class="fas fa-edit me-2"></i> Bulk Edit Components
                        </button>
                        <button class="btn btn-secondary-subtle text-secondary-emphasis border-secondary-subtle" id="bulkPrintBtn" disabled>
                            <i class="fas fa-print me-2"></i> Bulk Print PRO
                        </button>
                    </div>
                </div>

                {{-- 4. TABEL ACCORDION UNTUK DETAIL PRO --}}
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Production Order Details</h6>
                        <div class="form-check custom-checkbox-container">
                            <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                            <label class="form-check-label" for="selectAllCheckbox">
                                Select All
                            </label>
                        </div>
                    </div>
                    
                    @if($proDetailsList->isEmpty())
                        <div class="card-body text-center">
                            <p class="text-muted">Tidak ada data PRO yang ditemukan.</p>
                        </div>
                    @else
                        <div class="accordion accordion-flush" id="proAccordion">
                            
                            @foreach($proDetailsList as $index => $item)
                                @php
                                    $pro = $item['pro_detail'];
                                    $routings = $item['routings'];
                                    $components = $item['components'];
                                    $collapseId = "collapse-" . $pro->AUFNR;
                                    $proId = "pro-check-" . $pro->AUFNR;
                                @endphp

                                <div class="accordion-item">
                                    <h2 class="accordion-header d-flex align-items-center" id="heading-{{ $pro->AUFNR }}">
                                        
                                        {{-- Checkbox individual dengan style kustom --}}
                                        <div class="form-check custom-checkbox-container px-3">
                                            <input class="form-check-input pro-checkbox" type="checkbox" value="{{ $pro->AUFNR }}" id="{{ $proId }}">
                                            <label class="form-check-label" for="{{ $proId }}"></label>
                                        </div>

                                        {{-- Tombol accordion sekarang mengambil sisa ruang --}}
                                        <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                            <div class="row w-100">
                                                <div class="col-md-2">
                                                    <strong class="d-block">{{ $pro->AUFNR }}</strong>
                                                    <small class="text-muted">PRO Number</small>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong class="d-block">{{ $pro->NAME1 ?? '-' }}</strong>
                                                    <small class="text-muted">Buyer</small>
                                                </div>
                                                <div class="col-md-2">
                                                    <strong class="d-block">{{ $pro->KDAUF }}-{{ $pro->KDPOS }}</strong>
                                                    <small class="text-muted">SO - Item</small>
                                                </div>
                                                <div class="col-md-5">
                                                    <strong class="d-block">{{ $pro->MAKTX }}</strong>
                                                    <small class="text-muted">Material</small>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>

                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $pro->AUFNR }}" data-bs-parent="#proAccordion">
                                        <div class="accordion-body bg-body-tertiary p-3 p-md-4">
                                            
                                            {{-- [BAGIAN 1] Detail PRO --}}
                                            <div class="card mb-4 border-0 shadow-sm">
                                                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Detail for PRO: {{ $pro->AUFNR }}</h6>
                                                    <span class="badge {{ $pro->STATS === 'REL' ? 'bg-success-subtle text-success-emphasis' : 'bg-info-subtle text-info-emphasis' }}">{{ $pro->STATS }}</span>
                                                </div>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item">
                                                        <div class="row g-3">
                                                            <div class="col-md-6 col-lg-3">
                                                                <small class="text-muted d-block">Start Date</small>
                                                                <strong class="d-block">{{ $pro->GSTRP_formatted }}</strong>
                                                            </div>
                                                            <div class="col-md-6 col-lg-3">
                                                                <small class="text-muted d-block">Finish Date</small>
                                                                <strong class="d-block">{{ $pro->GLTRP_formatted }}</strong>
                                                            </div>
                                                            <div class="col-md-6 col-lg-3">
                                                                <small class="text-muted d-block">Order Qty</small>
                                                                <strong class="d-block">{{ $pro->PSMNG }} {{ $pro->MEINS }}</strong>
                                                            </div>
                                                            <div class="col-md-6 col-lg-3">
                                                                <small class="text-muted d-block">Material Group</small>
                                                                <strong class="d-block">{{ $pro->MATKL }}</strong>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>

                                            {{-- [BAGIAN 2] Tab Komponen & Routing --}}
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-white p-0 border-bottom-0">
                                                    <ul class="nav nav-tabs nav-tabs-card" id="proTab-{{ $pro->AUFNR }}" role="tablist">
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link active" id="comp-tab-{{ $pro->AUFNR }}" data-bs-toggle="tab" data-bs-target="#comp-content-{{ $pro->AUFNR }}" type="button" role="tab" aria-controls="comp-content" aria-selected="true">
                                                                <i class="fas fa-cogs me-2"></i> Components 
                                                                <span class="badge bg-success rounded-pill ms-2">{{ $components->count() }}</span>
                                                            </button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link" id="route-tab-{{ $pro->AUFNR }}" data-bs-toggle="tab" data-bs-target="#route-content-{{ $pro->AUFNR }}" type="button" role="tab" aria-controls="route-content" aria-selected="false">
                                                                <i class="fas fa-route me-2"></i> Routings 
                                                                <span class="badge bg-secondary rounded-pill ms-2">{{ $routings->count() }}</span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <div class="card-body"> 
                                                    <div class="tab-content" id="proTabContent-{{ $pro->AUFNR }}">
                                                        
                                                        <div class="tab-pane fade show active" id="comp-content-{{ $pro->AUFNR }}" role="tabpanel" aria-labelledby="comp-tab">
                                                            @if($components->isEmpty())
                                                                <p class="text-muted p-4 text-center">No components found.</p>
                                                            @else
                                                                @include('Admin.partials.components-table', ['components' => $components])
                                                            @endif
                                                        </div>

                                                        <div class="tab-pane fade" id="route-content-{{ $pro->AUFNR }}" role="tabpanel" aria-labelledby="route-tab">
                                                            @if($routings->isEmpty())
                                                                <p class="text-muted p-4 text-center">No routings found.</p>
                                                            @else
                                                                @include('Admin.partials.routing-table', ['routings' => $routings])
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif 
                </div>
            </div>
        </div>
    </div>
    
    @push('styles')
    <style>
        /* ========================================= */
        /* == STYLE CHECKBOX KUSTOM == */
        /* ========================================= */
        .custom-checkbox-container {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .custom-checkbox-container .form-check-input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .custom-checkbox-container .form-check-label {
            position: relative;
            padding-left: 30px; 
            cursor: pointer;
            line-height: 20px; 
            display: inline-block;
            transition: color 0.15s ease-in-out;
        }
        .custom-checkbox-container:hover .form-check-label {
            color: var(--bs-success);
        }
        .custom-checkbox-container .form-check-label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: 2px solid #adb5bd; 
            border-radius: 0.25rem;
            background-color: #fff;
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .custom-checkbox-container:hover .form-check-label::before {
             border-color: var(--bs-success);
             box-shadow: 0 0 0 0.25rem rgba(25,135,84,.25);
        }
        .custom-checkbox-container .form-check-input:checked + .form-check-label::before {
            background-color: var(--bs-success);
            border-color: var(--bs-success);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 70%;
        }
        .custom-checkbox-container .form-check-input:indeterminate + .form-check-label::before {
            background-color: var(--bs-success);
            border-color: var(--bs-success);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M5 10h10'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 70%;
        }
        .accordion-header .custom-checkbox-container .form-check-label {
            padding-left: 20px;
        }

        /* ========================================= */
        /* == STYLE LAYOUT == */
        /* ========================================= */
        .accordion-button {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .accordion-button:not(.collapsed) {
            background-color: #eef5ff; 
            box-shadow: none;
            color: #0a58ca;
        }
        .accordion-button.collapsed .row .col-md-5 {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .accordion-header {
            background-color: #fff;
            border-bottom: 1px solid var(--bs-border-color); 
        }
        
        .nav-tabs-card {
            border-bottom: 1px solid var(--bs-border-color); 
        }
        .nav-tabs-card .nav-link {
            border: 0;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            padding: 1rem 1.25rem;
            font-weight: 500;
        }
        .nav-tabs-card .nav-link.active {
            border-bottom-color: var(--bs-success);
            color: var(--bs-success);
            background-color: transparent;
        }
        
        .accordion-body .table tbody td {
            color: var(--bs-body-color, #212529); 
            vertical-align: middle;
        }
        
        .accordion-body .card-body .table {
            margin-bottom: 0;
        }
        .accordion-body .card-body .table thead th {
            border-top: 0;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAllCheckbox');
            const proCheckboxes = document.querySelectorAll('.pro-checkbox');
            const bulkEditBtn = document.getElementById('bulkEditBtn');
            const bulkPrintBtn = document.getElementById('bulkPrintBtn');

            if (!selectAll || !proCheckboxes.length || !bulkEditBtn || !bulkPrintBtn) {
                console.warn('Satu atau lebih elemen (checkbox, tombol bulk) tidak ditemukan. Fungsionalitas bulk mungkin tidak bekerja.');
                return;
            }

            function updateSelectionState() {
                const checkedCount = document.querySelectorAll('.pro-checkbox:checked').length;
                const totalCount = proCheckboxes.length;
                const anySelected = checkedCount > 0;

                bulkEditBtn.disabled = !anySelected;
                bulkPrintBtn.disabled = !anySelected;

                if (checkedCount === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                } else if (checkedCount === totalCount) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else {
                    selectAll.checked = false;
                    selectAll.indeterminate = true;
                }
            }

            selectAll.addEventListener('change', function() {
                proCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelectionState();
            });

            proCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectionState();
                });
            });

            function getSelectedPros() {
                const selectedPros = [];
                proCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedPros.push(checkbox.value);
                    }
                });
                return selectedPros;
            }

            bulkEditBtn.addEventListener('click', function() {
                const selectedPros = getSelectedPros();
                if (selectedPros.length > 0) {
                    console.log('PROs selected for bulk edit:', selectedPros);
                    alert('PROs yang dipilih (lihat console): ' + selectedPros.join(', '));
                }
            });

            bulkPrintBtn.addEventListener('click', function() {
                const selectedPros = getSelectedPros();
                if (selectedPros.length > 0) {
                    console.log('PROs selected for bulk print:', selectedPros);
                    alert('PROs yang dipilih (lihat console): ' + selectedPros.join(', '));
                }
            });

            updateSelectionState();
        });
    </script>
    @endpush

</x-layouts.app>