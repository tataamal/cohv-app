<x-layouts.app title="Work Instruction History">
    @push('styles')
        <style>
            /* --- 1. GLOBAL VARIABLES & THEME --- */
            :root {
                --bg-app: #eef2f6; 
                --card-header-bg: #f8fafc;
                --border-color: #e2e8f0;
                --primary-dark: #1e293b;
                --text-secondary: #64748b;
                --success-color: #10b981;
                --danger-color: #ef4444;
                --info-color: #3b82f6;
            }

            body { background-color: var(--bg-app) !important; color: var(--primary-dark); }
            
            /* --- 2. FILTER CONTROL PANEL --- */
            .filter-panel {
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                border: 1px solid var(--border-color);
                margin-bottom: 2rem;
            }

            /* --- 3. MODERN CARD DESIGN (THE TICKET LOOK) --- */
            .wi-item-card {
                background: #ffffff;
                border-radius: 10px;
                border: 1px solid var(--border-color);
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                transition: transform 0.2s, box-shadow 0.2s;
                overflow: hidden; 
                position: relative;
            }
            .wi-item-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            }

            /* Status Indicators (Border Kiri Tebal) */
            .status-active { border-left: 5px solid var(--success-color); }
            .status-expired { border-left: 5px solid var(--danger-color); }

            /* Card Header */
            .card-header-area {
                background-color: var(--card-header-bg);
                padding: 12px 20px;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Card Body */
            .card-body-area { padding: 15px 20px; }

            /* Accordion Toggle */
            .accordion-trigger-area {
                background-color: #ffffff;
                border-top: 1px dashed var(--border-color);
                padding: 0;
            }
            .btn-accordion-toggle {
                width: 100%;
                text-align: center;
                background: none;
                border: none;
                padding: 10px;
                font-size: 0.8rem;
                font-weight: 600;
                color: var(--text-secondary);
                transition: all 0.2s;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .btn-accordion-toggle:hover {
                background-color: #f1f5f9;
                color: var(--primary-dark);
            }
            .btn-accordion-toggle::after {
                content: '\f078'; /* FontAwesome Chevron Down */
                font-family: "Font Awesome 6 Free";
                font-weight: 900;
                margin-left: 8px;
                transition: transform 0.3s;
                display: inline-block;
            }
            .btn-accordion-toggle[aria-expanded="true"]::after {
                transform: rotate(180deg);
            }

            /* Item List inside Accordion */
            .item-list-container {
                background-color: #f8fafc;
                border-top: 1px solid var(--border-color);
                padding: 15px 20px;
            }
            
            /* --- ITEM CARD DI DALAM ACCORDION (LEBIH DETIL) --- */
            .pro-item-row {
                background: white;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 12px;
                margin-bottom: 10px;
                position: relative;
            }
            
            /* Progress Bar Styling */
            .progress-label {
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--text-secondary);
                margin-bottom: 2px;
                display: block;
            }
            .progress-custom {
                height: 6px;
                background-color: #e2e8f0;
                border-radius: 3px;
                overflow: hidden;
            }

            /* --- 4. UTILITIES --- */
            .wi-checkbox { 
                width: 22px; height: 22px; cursor: pointer; 
                border: 2px solid #cbd5e1; border-radius: 6px;
                margin-top: 20px; 
            }
            .wi-row-wrapper { display: flex; gap: 15px; margin-bottom: 20px; }
            .badge-soft { padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; }
            .bg-soft-success { background-color: #d1fae5; color: #065f46; }
            .bg-soft-danger { background-color: #fee2e2; color: #991b1b; }

            /* Highlight Selection */
            .card-selected-highlight {
                border: 2px solid var(--success-color) !important;
                background-color: #f0fdf4 !important;
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15) !important;
                z-index: 10;
            }
        </style>
    @endpush

    <div class="container-fluid p-4" style="max-width: 1400px; margin: 0 auto;">
        
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- PAGE HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 fw-bolder text-dark mb-1">Production History Log</h1>
                <p class="text-muted small mb-0">Plant Access: <b>{{ $plantCode }}</b></p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dark shadow-sm fw-bold px-3 rounded-pill" onclick="openPrintModal('log')">
                    <i class="fa-solid fa-file-pdf me-2"></i> Export Log
                </button>
                <a href="{{ route('wi.create', ['kode' => $plantCode]) }}" class="btn btn-white bg-white border shadow-sm fw-bold text-secondary px-4 rounded-pill">
                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                </a>
            </div>
        </div>

        {{-- FILTER CONTROL PANEL --}}
        <div class="filter-panel p-4">
            <form action="{{ route('wi.history', ['kode' => $plantCode]) }}" method="GET" id="filterForm">
                <div class="row g-3 align-items-end">
                    
                    {{-- Quick Date Filter --}}
                    <div class="col-lg-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Tanggal Dokumen</label>
                        <div class="input-group">
                            <input type="date" name="date" id="dateInput" class="form-control" value="{{ request('date') }}">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="setFilterDate(0)">Hari Ini</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="setFilterDate(-1)">Kemarin</button>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="col-lg-5">
                        <label class="form-label small fw-bold text-uppercase text-muted">Pencarian</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-search"></i></span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Kode WI, Workcenter, atau No. PRO..." 
                                   value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
                        <a href="{{ route('wi.history', ['kode' => $plantCode]) }}" class="btn btn-light border"><i class="fa-solid fa-rotate-left"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="row g-5">
            {{-- --- SECTION 1: ACTIVE DOCUMENTS --- --}}
            @if($activeWIDocuments->count() > 0 || (!request()->has('search')))
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-circle-check text-success me-2"></i>Active Documents</h5>
                        <span class="badge bg-secondary rounded-pill">{{ $activeWIDocuments->count() }}</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check">
                            <input class="form-check-input wi-checkbox mt-0" type="checkbox" id="selectAllActive">
                            <label class="form-check-label ms-1 small fw-bold text-muted" for="selectAllActive">Pilih Semua</label>
                        </div>
                        {{-- PRINT SELECTED (BULK) --}}
                        <button type="button" id="btnActiveAction" class="btn btn-success btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="openPrintModal('active')">
                            <i class="fa-solid fa-print me-1"></i> Cetak Terpilih (<span id="countActive">0</span>)
                        </button>
                    </div>
                </div>

                {{-- LOOP CARD ACTIVE --}}
                @forelse ($activeWIDocuments as $document)
                <div class="wi-row-wrapper">
                    <div class="pt-2">
                        <input type="checkbox" class="form-check-input wi-checkbox cb-active" value="{{ $document->wi_document_code }}">
                    </div>
                    
                    <div class="wi-item-card flex-grow-1 status-active">
                        {{-- HEADER AREA --}}
                        <div class="card-header-area">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem; font-weight: 700;">WORKCENTER</small>
                                    <span class="h5 fw-bolder text-dark mb-0">{{ $document->workcenter_code }}</span>
                                </div>
                                <div style="border-left: 1px solid #cbd5e1; height: 30px; margin: 0 10px;"></div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-primary" style="letter-spacing: 0.5px;">{{ $document->wi_document_code }}</span>
                                        <span class="badge-soft bg-soft-success">ACTIVE</span>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fa-regular fa-clock me-1"></i> {{ \Carbon\Carbon::parse($document->created_at)->format('H:i') }}
                                        <span class="mx-1">•</span> 
                                        {{ \Carbon\Carbon::parse($document->document_date)->format('d M Y') }}
                                    </div>
                                </div>
                            </div>

                            {{-- PRINT SINGLE (Active) --}}
                            <button type="button" 
                                    class="btn btn-light bg-white border shadow-sm btn-sm fw-bold text-dark" 
                                    onclick="setupSinglePrint('{{ $document->wi_document_code }}', 'active')">
                                <i class="fa-solid fa-print me-1"></i> Print
                            </button>
                        </div>

                        {{-- BODY AREA (Logic Capacity) --}}
                        <div class="card-body-area">
                            @php
                                $payload = is_array($document->payload_data) ? $document->payload_data : json_decode($document->payload_data, true);
                                $itemCount = $payload ? count($payload) : 0;
                            @endphp
                             <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                                <span>Capacity Load</span>
                                <span>{{ $itemCount }} Items Queued</span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 40%"></div>
                            </div>
                        </div>

                        {{-- ACCORDION TRIGGER --}}
                        <div class="accordion-trigger-area">
                            <button class="btn-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $document->id }}" aria-expanded="false">
                                Lihat Rincian Produksi
                            </button>
                        </div>

                        {{-- ACCORDION CONTENT (DENGAN PROGRESS BAR PER ITEM) --}}
                        <div class="collapse item-list-container" id="collapse-{{ $document->id }}">
                            @if($payload)
                                @foreach($payload as $item)
                                    @php
                                        // --- HITUNG PROGRESS CONFIRMATION ---
                                        $confirmed = isset($item['confirmed_qty']) ? (float)$item['confirmed_qty'] : 0;
                                        $assigned = isset($item['assigned_qty']) ? (float)$item['assigned_qty'] : 0;
                                        
                                        $percentage = ($assigned > 0) ? ($confirmed / $assigned) * 100 : 0;
                                        $percentage = min(100, max(0, $percentage)); // Clamp 0-100
                                        
                                        $barColor = $percentage >= 100 ? 'bg-success' : 'bg-primary';
                                        $textClass = $percentage >= 100 ? 'text-success' : 'text-primary';
                                    @endphp

                                    <div class="pro-item-row">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-dark" style="font-size: 0.65rem;">{{ $item['aufnr'] ?? 'N/A' }}</span>
                                                    <span class="fw-bold text-dark small">{{ $item['material_desc'] ?? 'No Description' }}</span>
                                                </div>
                                                <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                                    Mat: {{ $item['material_number'] ?? '-' }} • SO: {{ $item['kdauf'] ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-primary">{{ $assigned }}</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">TARGET</div>
                                            </div>
                                        </div>

                                        {{-- PROGRESS BAR CONFIRMATION (RE-ADDED) --}}
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between align-items-end mb-1">
                                                <span class="progress-label">Production Progress</span>
                                                <span class="small fw-bold {{ $textClass }}">
                                                    {{ $confirmed }} / {{ $assigned }} ({{ round($percentage) }}%)
                                                </span>
                                            </div>
                                            <div class="progress-custom">
                                                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                    <div class="text-center py-5 border rounded-3 bg-white border-dashed">
                        <i class="fa-solid fa-clipboard-check fs-1 text-secondary mb-3 opacity-25"></i>
                        <h6 class="text-muted">Tidak ada dokumen aktif</h6>
                    </div>
                @endforelse
            </div>
            @endif

            {{-- --- SECTION 2: EXPIRED DOCUMENTS --- --}}
            @if($expiredWIDocuments->count() > 0 || (!request()->has('search')))
            <div class="col-12 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i>Expired Documents</h5>
                        <span class="badge bg-secondary rounded-pill">{{ $expiredWIDocuments->count() }}</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                         <div class="form-check">
                            <input class="form-check-input wi-checkbox mt-0" type="checkbox" id="selectAllExpired">
                            <label class="form-check-label ms-1 small fw-bold text-muted" for="selectAllExpired">Pilih Semua</label>
                        </div>
                        {{-- PRINT SELECTED (EXPIRED) --}}
                        <button type="button" id="btnExpiredAction" class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="openPrintModal('expired')">
                            <i class="fa-solid fa-file-invoice me-1"></i> Report (<span id="countExpired">0</span>)
                        </button>
                    </div>
                </div>

                @forelse ($expiredWIDocuments as $document)
                <div class="wi-row-wrapper">
                    <div class="pt-2">
                        <input type="checkbox" class="form-check-input wi-checkbox cb-expired" value="{{ $document->wi_document_code }}">
                    </div>
                    
                    <div class="wi-item-card flex-grow-1 status-expired">
                        {{-- HEADER --}}
                        <div class="card-header-area">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem; font-weight: 700;">WORKCENTER</small>
                                    <span class="h5 fw-bolder text-dark mb-0">{{ $document->workcenter_code }}</span>
                                </div>
                                <div style="border-left: 1px solid #cbd5e1; height: 30px; margin: 0 10px;"></div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-dark">{{ $document->wi_document_code }}</span>
                                        <span class="badge-soft bg-soft-danger">EXPIRED</span>
                                    </div>
                                    <div class="small text-muted">
                                        Exp: {{ \Carbon\Carbon::parse($document->expired_at)->format('d M H:i') }}
                                    </div>
                                </div>
                            </div>
                             {{-- PRINT SINGLE (Expired Report) --}}
                            <button type="button" 
                                    class="btn btn-light bg-white border shadow-sm btn-sm fw-bold text-danger" 
                                    onclick="setupSinglePrint('{{ $document->wi_document_code }}', 'expired')">
                                <i class="fa-solid fa-file-invoice me-1"></i> Report
                            </button>
                        </div>

                        {{-- BODY --}}
                        <div class="card-body-area">
                            @php
                                $payload = is_array($document->payload_data) ? $document->payload_data : json_decode($document->payload_data, true);
                                $itemCount = $payload ? count($payload) : 0;
                            @endphp
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-muted">Status Produksi</span>
                                <span class="badge bg-light text-dark border">{{ $itemCount }} Item Selesai</span>
                            </div>
                        </div>

                         {{-- ACCORDION --}}
                        <div class="accordion-trigger-area">
                            <button class="btn-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-exp-{{ $document->id }}" aria-expanded="false">
                                Lihat Hasil Produksi
                            </button>
                        </div>

                        {{-- COLLAPSE CONTENT (DENGAN PROGRESS BAR PER ITEM) --}}
                        <div class="collapse item-list-container" id="collapse-exp-{{ $document->id }}">
                             @if($payload)
                                @foreach($payload as $item)
                                    @php
                                        // --- HITUNG PROGRESS CONFIRMATION ---
                                        $confirmed = isset($item['confirmed_qty']) ? (float)$item['confirmed_qty'] : 0;
                                        $assigned = isset($item['assigned_qty']) ? (float)$item['assigned_qty'] : 0;
                                        
                                        $percentage = ($assigned > 0) ? ($confirmed / $assigned) * 100 : 0;
                                        $percentage = min(100, max(0, $percentage)); 
                                        
                                        // Expired biasanya merah jika belum 100%
                                        $barColor = $percentage >= 100 ? 'bg-success' : 'bg-danger';
                                        $textClass = $percentage >= 100 ? 'text-success' : 'text-danger';
                                    @endphp

                                    <div class="pro-item-row" style="background-color: #fffafa; border-color: #fecaca;">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="fw-bold text-dark small">{{ $item['material_desc'] ?? '-' }}</div>
                                                <div class="text-muted small" style="font-size: 0.7rem;">PRO: {{ $item['aufnr'] ?? '-' }}</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-danger">{{ $confirmed }}</div>
                                                <div class="text-muted" style="font-size: 0.65rem;">ACTUAL</div>
                                            </div>
                                        </div>

                                        {{-- PROGRESS BAR EXPIRED --}}
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between align-items-end mb-1">
                                                <span class="progress-label text-danger">Final Status</span>
                                                <span class="small fw-bold {{ $textClass }}">
                                                    {{ $confirmed }} / {{ $assigned }} ({{ round($percentage) }}%)
                                                </span>
                                            </div>
                                            <div class="progress-custom">
                                                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                    <div class="text-center py-5 border rounded-3 bg-white border-dashed">
                        <i class="fa-solid fa-box-open fs-1 text-secondary mb-3 opacity-25"></i>
                        <h6 class="text-muted">Tidak ada dokumen expired</h6>
                    </div>
                @endforelse
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL PRINT UNIVERSAL (Support Active, Expired, Log) --}}
    <div class="modal fade" id="universalPrintModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header text-white" id="modalHeaderBg" style="background: #1e293b;">
                    <h6 class="modal-title fw-bold text-uppercase ls-1" id="modalTitle">Cetak Dokumen</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="printForm" method="POST" target="_blank">
                    @csrf
                    <div class="modal-body p-4 bg-white">
                        {{-- Input Hidden untuk kode WI --}}
                        <input type="hidden" name="wi_codes" id="inputWiCodes">
                        
                        {{-- Input Hidden untuk Filter Log --}}
                        <input type="hidden" name="filter_date" value="{{ request('date') }}">
                        <input type="hidden" name="filter_search" value="{{ request('search') }}">
                        
                        <div id="modalAlert" class="alert shadow-sm border-0 mb-4 d-flex align-items-center"></div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">DICETAK OLEH</label>
                            <input type="text" name="printed_by" class="form-control fw-bold" value="{{ session('username') ?? 'User' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">DEPARTEMEN / BAGIAN</label>
                            <input type="text" name="department" class="form-control" placeholder="Contoh: Produksi A" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0">
                        <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold small" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm" id="btnSubmitPrint">
                            DOWNLOAD PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @include('create-wi.partials.modal_edit_qty') 

    @push('scripts')
    <script>
        // --- 1. FILTER TANGGAL ---
        function setFilterDate(daysOffset) {
            const dateInput = document.getElementById('dateInput');
            const form = document.getElementById('filterForm');
            
            const today = new Date();
            today.setDate(today.getDate() + daysOffset);
            
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            
            dateInput.value = `${yyyy}-${mm}-${dd}`;
            form.submit();
        }

        // --- 2. SINGLE PRINT HELPER ---
        function setupSinglePrint(wiCode, type) {
            const inputCodes = document.getElementById('inputWiCodes');
            inputCodes.value = wiCode; // Set single code
            
            // Buka Modal dengan konfigurasi type tersebut
            const modalEl = document.getElementById('universalPrintModal');
            setupModalUI(type, 1);
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        // --- 3. UI SETUP FOR MODAL ---
        function setupModalUI(type, count) {
            const form = document.getElementById('printForm');
            const alertMsg = document.getElementById('modalAlert');
            const modalTitle = document.getElementById('modalTitle');
            const btnSubmit = document.getElementById('btnSubmitPrint');
            const headerBg = document.getElementById('modalHeaderBg');

            if (type === 'active') {
                form.action = "{{ route('wi.print-single') }}"; 
                modalTitle.innerText = 'CETAK WORK INSTRUCTION';
                headerBg.style.background = '#10b981'; // Green
                
                alertMsg.className = 'alert bg-success-subtle text-success border-0 fw-bold';
                alertMsg.innerHTML = `<i class="fa-solid fa-print me-2"></i>Mencetak ${count} Dokumen Kerja.`;
                
                btnSubmit.className = 'btn btn-success px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-download me-2"></i>Download WI';

            } else if (type === 'expired') {
                form.action = "{{ route('wi.print-expired-report') }}"; 
                modalTitle.innerText = 'CETAK LAPORAN HASIL';
                headerBg.style.background = '#ef4444'; // Red
                
                alertMsg.className = 'alert bg-danger-subtle text-danger border-0 fw-bold';
                alertMsg.innerHTML = `<i class="fa-solid fa-file-invoice me-2"></i>Laporan untuk ${count} dokumen selesai.`;
                
                btnSubmit.className = 'btn btn-danger px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-file-export me-2"></i>Export Report';

            } else if (type === 'log') {
                form.action = "{{ route('wi.print-pdf', ['kode' => $plantCode]) }}"; 
                modalTitle.innerText = 'EXPORT LOG HISTORY';
                headerBg.style.background = '#1e293b'; // Dark
                
                alertMsg.className = 'alert bg-secondary-subtle text-dark border-0 fw-bold';
                alertMsg.innerHTML = `<i class="fa-solid fa-table-list me-2"></i>Export log sesuai filter tanggal/pencarian saat ini.`;
                
                btnSubmit.className = 'btn btn-dark px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-file-pdf me-2"></i>Export Log';
            }
        }

        // --- 4. GLOBAL OPEN MODAL ---
        window.openPrintModal = function(type) {
            const modalEl = document.getElementById('universalPrintModal');
            const inputCodes = document.getElementById('inputWiCodes');

            if (type === 'log') {
                setupModalUI('log', 0);
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                return;
            }

            let selector = (type === 'active') ? '.cb-active:checked' : '.cb-expired:checked';
            const checked = document.querySelectorAll(selector);
            let codes = [];
            checked.forEach(cb => codes.push(cb.value));

            inputCodes.value = codes.join(',');
            setupModalUI(type, codes.length);

            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        };

        document.addEventListener('DOMContentLoaded', function() {
            // --- 5. HIGHLIGHT CARD LOGIC ---
            function toggleCardHighlight(checkbox) {
                const wrapper = checkbox.closest('.wi-row-wrapper');
                if(wrapper) {
                    const itemCard = wrapper.querySelector('.wi-item-card'); 
                    if(itemCard) {
                        if (checkbox.checked) {
                            itemCard.classList.add('card-selected-highlight');
                        } else {
                            itemCard.classList.remove('card-selected-highlight');
                        }
                    }
                }
            }

            // --- 6. CHECKBOX GROUP HANDLER ---
            function setupCheckboxGroup(selectClass, selectAllId, btnId, countId) {
                const checkboxes = document.querySelectorAll('.' + selectClass);
                const selectAll = document.getElementById(selectAllId);
                const btnAction = document.getElementById(btnId);
                const countSpan = document.getElementById(countId);

                if(!selectAll) return;

                function updateButton() {
                    const checked = document.querySelectorAll('.' + selectClass + ':checked');
                    countSpan.innerText = checked.length;
                    
                    if(checked.length > 0) {
                        btnAction.classList.remove('d-none');
                    } else {
                        btnAction.classList.add('d-none');
                    }
                }

                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                        toggleCardHighlight(cb);
                    });
                    updateButton();
                });

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        toggleCardHighlight(this);
                        if(!this.checked) selectAll.checked = false;
                        updateButton();
                    });
                });
            }

            setupCheckboxGroup('cb-active', 'selectAllActive', 'btnActiveAction', 'countActive');
            setupCheckboxGroup('cb-expired', 'selectAllExpired', 'btnExpiredAction', 'countExpired');
        });
    </script>
    @endpush
</x-layouts.app>