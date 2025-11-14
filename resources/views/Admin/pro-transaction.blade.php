<x-layouts.app title="PRO Transaction">
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                        <div>
                            <h1 class="h4 mb-1">PRO Search Results</h1>
                            <p class="mb-0 text-muted">
                                Menampilkan {{ $proDetailsList->count() }} dari {{ count($proNumbersSearched) }} PRO yang dicari untuk Plant: <strong>{{ $WERKS }}</strong>
                            </p>
                        </div>
                        <a href="{{ route('manufaktur.dashboard.show', $WERKS) }}" class="btn btn-outline-secondary mt-3 mt-sm-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>

                <input type="hidden" id="kode-halaman" value="{{ $WERKS }}">

                @if(!empty($notFoundProNumbers))
                    <div class="alert alert-warning">
                        <strong>PRO Tidak Ditemukan:</strong> 
                        {{ implode(', ', $notFoundProNumbers) }}
                    </div>
                @endif

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Bulk Transactions</h6>
                    </div>
                    <div class="card-body d-flex flex-wrap" style="gap: 10px;">
                        <button class="btn btn-warning " id="bulkRescheduleBtn" disabled>
                            <i class="fas fa-calendar-alt"></i> Rechedule PRO
                        </button>
                        <button class="btn btn-info" id="bulkRefreshBtn" disabled>
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh PRO
                        </button>
                        <button class="btn btn-warning" id="bulkChangePv" disabled>
                            <i class="fa-solid fa-code-compare"></i> Change PV
                        </button>
                        <button class="btn btn-info" id="bultkReadPpBtn" disabled>
                            <i class="fas fa-book-open"></i> READ PP
                        </button>
                        <button class="btn btn-warning" id="bulkChangeQty" disabled>
                            <i class="fa-solid fa-file-pen"></i> change Quantity
                        </button>
                        <button class="btn btn-danger text-white" id="bulkTecoBtn" disabled>
                            <i class="fas fa-trash"></i> TECO
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
                                    $qty = $pro->PSMNG;
                                    $gr = $pro->WEMNG;
                                    $unit = strtoupper(trim($pro->MEINS ?? ''));

                                    // Jika satuan ST atau SET → hilangkan semua bagian desimal (termasuk titik)
                                    if (in_array($unit, ['ST', 'SET'])) {
                                        $qty = (int) $qty;
                                        $gr = (int) $gr;
                                    }
                                @endphp

                                <div class="accordion-item" data-aufnr="{{ $pro->AUFNR }}" data-psmng="{{ $qty }}">
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
                                                <div class="col-md-3">
                                                    <strong class="d-block">{{ $pro->MAKTX }}</strong>
                                                    <small class="text-muted">Material</small>
                                                </div>
                                                <div class="col-md-2">
                                                    <strong class="d-block">
                                                        {{ ctype_digit($pro->MATNR) ? ltrim($pro->MATNR, '0') : $pro->MATNR }}
                                                    </strong>
                                                    <small class="text-muted">Material Number</small>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>

                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $pro->AUFNR }}" data-bs-parent="#proAccordion">
                                        <div class="accordion-body bg-body-tertiary p-3 p-md-4">
                                            
                                            {{-- [BAGIAN 1] Detail PRO --}}
                                            <div class="card mb-4 border-0 shadow-sm">
                                                <div class="card-header bg-white border-bottom fw-bold d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Detail for PRO: {{ $pro->AUFNR }}</h6>
                                                    <span class="badge 
                                                        {{ $pro->STATS === 'REL' ? 'bg-success-subtle text-success-emphasis' : 'bg-info-subtle text-info-emphasis' }} 
                                                        rounded-pill p-2">
                                                        {{ $pro->STATS }}
                                                    </span>
                                                </div>

                                                <div class="card-body p-3">
                                                    <div class="row g-3 align-items-center text-sm">
                                                        {{-- Kiri --}}
                                                        <div class="col-6 col-md-2">
                                                            <small class="text-muted d-block">MRP</small>
                                                            <strong>{{ $pro->DISPO }}</strong>
                                                        </div>
                                                        <div class="col-6 col-md-2">
                                                            <small class="text-muted d-block">Material</small>
                                                            <strong>{{ ctype_digit($pro->MATNR) ? ltrim($pro->MATNR, '0') : $pro->MATNR }}</strong>
                                                        </div>
                                                        <div class="col-12 col-md-3">
                                                            <small class="text-muted d-block">Description</small>
                                                            <strong>{{ $pro->MAKTX }}</strong>
                                                        </div>

                                                        {{-- Tengah --}}
                                                        <div class="col-4 col-md-1">
                                                            <small class="text-muted d-block">Qty</small>
                                                            <strong>{{ $qty }}</strong>
                                                        </div>
                                                        <div class="col-4 col-md-1">
                                                            <small class="text-muted d-block">GR</small>
                                                            <strong>{{ $gr }}</strong>
                                                        </div>
                                                        <div class="col-4 col-md-1">
                                                            <small class="text-muted d-block">Outs GR</small>
                                                            <strong>{{ $pro->PSMNG - $pro->WEMNG }}</strong>
                                                        </div>

                                                        {{-- Kanan --}}
                                                        <div class="col-6 col-md-1">
                                                            <small class="text-muted d-block">Start</small>
                                                            <strong>{{ $pro->GSTRP_formatted }}</strong>
                                                        </div>
                                                        <div class="col-6 col-md-1">
                                                            <small class="text-muted d-block">Finish</small>
                                                            <strong>{{ $pro->GLTRP_formatted }}</strong>
                                                        </div>
                                                    </div>

                                                    {{-- Baris kedua, info tambahan (opsional) --}}
                                                    <div class="row g-3 mt-3 border-top pt-3 text-sm">
                                                        <div class="col-md-3 col-6">
                                                            <small class="text-muted d-block">SO - Item</small>
                                                            <strong>{{ $pro->KDAUF }}-{{ $pro->KDPOS }}</strong>
                                                        </div>
                                                        <div class="col-md-3 col-6">
                                                            <small class="text-muted d-block">Finish Size</small>
                                                            <strong>{{ $pro->GROES }}</strong>
                                                        </div>
                                                        <div class="col-md-3 col-6">
                                                            <small class="text-muted d-block">Rough Size</small>
                                                            <strong>{{ $pro->FERTH ?? '-' }}</strong>
                                                        </div>
                                                        <div class="col-md-3 col-6">
                                                            <small class="text-muted d-block">Material Bhn</small>
                                                            <strong>{{ $pro->ZEINR }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- [BAGIAN 2] Tab Komponen & Routing --}}
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-white p-0 border-bottom-0">
                                                    <ul class="nav nav-tabs nav-tabs-card" id="proTab-{{ $pro->AUFNR }}" role="tablist">
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link active py-2 px-3" id="comp-tab-{{ $pro->AUFNR }}" data-bs-toggle="tab" data-bs-target="#comp-content-{{ $pro->AUFNR }}" type="button" role="tab" aria-controls="comp-content" aria-selected="true">
                                                                <i class="fas fa-cogs me-2"></i>
                                                                <span class="me-2">Components</span>
                                                                <span class="badge bg-white text-dark border border-secondary-subtle rounded-pill">
                                                                    {{ $components->count() }}
                                                                </span>
                                                            </button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link py-2 px-3" id="route-tab-{{ $pro->AUFNR }}" data-bs-toggle="tab" data-bs-target="#route-content-{{ $pro->AUFNR }}" type="button" role="tab" aria-controls="route-content" aria-selected="false">
                                                                <i class="fas fa-route me-2"></i>
                                                                <span class="me-2">Routings</span> 
                                                                <span class="badge bg-white text-dark border border-secondary-subtle rounded-pill">
                                                                    {{ $routings->count() }}
                                                                </span>
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

    @include('components.modals.pro-transaction.confirmation-modal')
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endpush

</x-layouts.app>