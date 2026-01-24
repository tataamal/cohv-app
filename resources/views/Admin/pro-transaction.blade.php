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
                            <i class="fas fa-calendar-alt"></i> Rechedule
                        </button>
                        <button class="btn btn-info" id="bulkRefreshBtn" disabled>
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                        <button class="btn btn-warning" id="bulkChangePv" disabled>
                            <i class="fa-solid fa-code-compare"></i> Change PV
                        </button>
                        <button class="btn btn-info" id="bultkReadPpBtn" disabled>
                            <i class="fas fa-book-open"></i> READ PP
                        </button>
                        <button class="btn btn-warning" id="bulkChangeQty" disabled>
                            <i class="fa-solid fa-file-pen"></i> Change Quantity
                        </button>
                        <button class="btn btn-danger text-white" id="bulkTecoBtn" disabled>
                            <i class="fas fa-trash"></i> TECO
                        </button>
                        <button class="btn btn-success text-white" id="bulkReleaseBtn" disabled>
                            <i class="fas fa-check"></i> Release
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
                                            <input class="form-check-input pro-checkbox" type="checkbox" value="{{ $pro->AUFNR }}" data-stats="{{ $pro->STATS }}" id="{{ $proId }}">
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
                                                                @include('Admin.partials.routing-table', ['routings' => $routings, 'parentPro' => $pro])
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
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const bulkReleaseBtn = document.getElementById('bulkReleaseBtn');
                
                if (bulkReleaseBtn) {
                    bulkReleaseBtn.addEventListener('click', async function() {
                        const selectedCheckboxes = document.querySelectorAll('.pro-checkbox:checked');
                        let validPros = [];
                        let invalidPros = [];
                        
                        selectedCheckboxes.forEach(cb => {
                            const aufnr = cb.value;
                            const stats = cb.getAttribute('data-stats');
                            
                            if (stats === 'CRTD') {
                                validPros.push(aufnr);
                            } else {
                                invalidPros.push({ aufnr: aufnr, stats: stats });
                            }
                        });

                        if (validPros.length === 0 && invalidPros.length === 0) {
                            Swal.fire('Info', 'Please select at least one PRO.', 'info');
                            return;
                        }

                        if (invalidPros.length > 0) {
                            const invalidList = invalidPros.map(p => `PRO ${p.aufnr} (Status: ${p.stats})`).join('<br>');
                            const result = await Swal.fire({
                                icon: 'warning',
                                title: 'Invalid Status Detected',
                                html: `The following PROs are not in <b>CRTD</b> status and will be skipped:<br><br><small>${invalidList}</small><br><br>Do you want to proceed with the remaining <b>${validPros.length}</b> valid PROs?`,
                                showCancelButton: true,
                                confirmButtonText: 'Yes, Proceed',
                                cancelButtonText: 'Cancel'
                            });

                            if (!result.isConfirmed) return;
                        }

                        if (validPros.length === 0) {
                            Swal.fire('Info', 'No valid PROs to release.', 'info');
                            return;
                        }

                        // Modern UI Setup
                        const total = validPros.length;
                        let processed = 0;
                        
                        // Show Modal (Non-blocking)
                        Swal.fire({
                            title: 'Processing Bulk Release',
                            html: `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">Progress</span>
                                        <span class="text-muted small" id="swal-progress-text">0 / ${total}</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div id="swal-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div id="swal-log-container" class="border rounded bg-dark text-light p-2 text-start small font-monospace" style="height: 200px; overflow-y: auto; font-size: 0.85em;">
                                    <div class="text-secondary">> Initializing process...</div>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false, // Initially hidden
                            didOpen: () => {
                                // Swal.showLoading(); 
                            }
                        }).then((result) => {
                            // Reload page when user finally clicks "Close/OK"
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });

                        try {
                            const response = await fetch("{{ route('bulk.release') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                                },
                                body: JSON.stringify({
                                    pro_list: validPros,
                                    plant: "{{ $WERKS }}"
                                })
                            });

                            const reader = response.body.getReader();
                            const decoder = new TextDecoder();
                            let buffer = '';

                            while (true) {
                                const { done, value } = await reader.read();
                                if (done) break;
                                
                                buffer += decoder.decode(value, { stream: true });
                                const lines = buffer.split('\n');
                                buffer = lines.pop(); // Keep incomplete line

                                for (const line of lines) {
                                    if (!line.trim()) continue;
                                    try {
                                        const data = JSON.parse(line);
                                        const container = Swal.getHtmlContainer();
                                        const logDiv = container ? container.querySelector('#swal-log-container') : null;
                                        const progressBar = container ? container.querySelector('#swal-progress-bar') : null;
                                        const progressText = container ? container.querySelector('#swal-progress-text') : null;

                                        let logColor = 'text-light'; // Default white
                                        if(data.type === 'error') logColor = 'text-danger';
                                        if(data.type === 'success') logColor = 'text-success';
                                        if(data.type === 'progress') logColor = 'text-info';

                                        // Update counts on completion events
                                        if (['success', 'error'].includes(data.type)) {
                                            processed++;
                                        }

                                        // Update Log
                                        if (logDiv) {
                                            const time = new Date().toLocaleTimeString('id-ID');
                                            logDiv.innerHTML += `<div class="${logColor}">[${time}] ${data.message}</div>`;
                                            logDiv.scrollTop = logDiv.scrollHeight;
                                        }

                                        // Update Progress Bar
                                        if (progressBar && progressText) {
                                            const percent = Math.min(100, Math.round((processed / total) * 100));
                                            progressBar.style.width = percent + '%';
                                            progressText.textContent = `${processed} / ${total}`;
                                        }

                                        if (data.type === 'summary') {
                                            // Preserve current HTML content to prevent wipe on re-render
                                            const currentContent = Swal.getHtmlContainer().innerHTML;

                                            Swal.update({
                                                icon: data.failed > 0 ? 'warning' : 'success',
                                                title: data.failed > 0 ? 'Completed with Errors' : 'Process Completed',
                                                showConfirmButton: true,
                                                confirmButtonText: 'Close & Refresh',
                                                html: currentContent
                                            });
                                            
                                            // Re-select log div after update/render
                                            const newContainer = Swal.getHtmlContainer();
                                            const newLogDiv = newContainer ? newContainer.querySelector('#swal-log-container') : null;

                                            if (newLogDiv) {
                                                newLogDiv.innerHTML += `<div class="text-warning mt-2 border-top pt-1">> ${data.message}</div>`;
                                                newLogDiv.scrollTop = newLogDiv.scrollHeight;
                                            }
                                        }
                                    } catch (e) {
                                        console.error('JSON Parse Error', e);
                                    }
                                }
                            }

                        } catch (error) {
                            Swal.update({
                                icon: 'error',
                                title: 'System Error',
                                showConfirmButton: true,
                                confirmButtonText: 'Close'
                            });
                             const container = Swal.getHtmlContainer();
                             const logDiv = container ? container.querySelector('#swal-log-container') : null;
                             if (logDiv) {
                                logDiv.innerHTML += `<div class="text-danger fw-bold">!! NETWORK/SERVER ERROR: ${error.message} !!</div>`;
                             }
                        }
                    });
                }
            });
        </script>
    @endpush

</x-layouts.app>