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
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15) !important;
                z-index: 10;
            }

            /* Custom Hover for Edit Qty Button */
            .btn-edit-qty:hover {
                color: #ffffff !important;
            }

            /* --- 5. TAB STYLING FOR EXPIRED --- */
            #expired-tab {
                color: var(--danger-color); /* Inactive Text Red */
                background-color: #fff;
            }
            #expired-tab.active {
                background-color: var(--danger-color) !important;
                color: #fff !important;
            }
            #expired-tab i { color: inherit; } /* Icon follows text color */
            #expired-tab .badge {
                transition: all 0.2s;
            }
            #expired-tab.active .badge {
                background-color: rgba(255,255,255,0.2) !important;
                color: #fff !important;
            }

            /* --- 6. TAB STYLING FOR COMPLETED --- */
            #completed-tab {
                color: var(--success-color); /* Inactive Text Green */
                background-color: #fff;
            }
            #completed-tab.active {
                background-color: var(--success-color) !important;
                color: #fff !important;
            }
            #completed-tab i { color: inherit; }
            #completed-tab .badge {
                transition: all 0.2s;
            }
            #completed-tab.active .badge {
                background-color: rgba(255,255,255,0.2) !important;
                color: #fff !important;
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
                <h1 class="h4 fw-bolder text-dark mb-1">Riwayat Pembuatan Document WI</h1>
                <p class="text-muted small mb-0">Kode Bagian: <b>{{ $plantCode }}</b></p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dark shadow-sm fw-bold px-3 rounded-pill" onclick="openPrintModal('log')">
                    <i class="fa-solid fa-file-pdf me-2"></i> Kirim Report ke Email
                </button>
                <a href="{{ route('wi.create', ['kode' => $plantCode]) }}" class="btn btn-white bg-white border shadow-sm fw-bold text-secondary px-4 rounded-pill">
                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                </a>
            </div>
        </div>

        {{-- FILTER CONTROL PANEL --}}
        <div class="filter-panel p-3">
            <form action="{{ route('wi.history', ['kode' => $plantCode]) }}" method="GET" id="filterForm">
                <div class="row g-2 align-items-end">
                    
                    {{-- Quick Date Filter --}}
                    <div class="col-lg-4">
                        <label class="form-label fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Tanggal Dokumen</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="date" id="dateInput" class="form-control flatpickr-range" value="{{ request('date') }}" placeholder="Pilih Rentang Tanggal...">
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="setQuickDate('today')">Hari Ini</button>
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="setQuickDate('yesterday')">Kemarin</button>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="col-lg-5">
                        <label class="form-label fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Pencarian</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-search"></i></span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Kode WI, Workcenter, atau No. PRO..." 
                                   value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold text-white"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
                        <a href="{{ route('wi.history', ['kode' => $plantCode]) }}" class="btn btn-light btn-sm border"><i class="fa-solid fa-rotate-left"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="row g-5">
            {{-- TABS NAVIGATION --}}
            <div class="col-12">
                <ul class="nav nav-tabs border-bottom-0 mb-3" id="historyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold small text-uppercase" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-content" type="button" role="tab">
                            <i class="fa-solid fa-circle-check text-white me-2"></i> Active<span class="badge bg-success bg-opacity-10 text-white ms-1">{{ $activeWIDocuments->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small text-uppercase" id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive-content" type="button" role="tab">
                            <i class="fa-solid fa-clock text-white me-2"></i> Inactive <span class="badge bg-success bg-opacity-10 text-white ms-1">{{ $inactiveWIDocuments->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small text-uppercase" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired-content" type="button" role="tab">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Expired <span class="badge bg-danger bg-opacity-10 text-danger ms-1">{{ $expiredWIDocuments->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small text-uppercase" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-content" type="button" role="tab">
                            <i class="fa-solid fa-check-double me-2"></i> Completed <span class="badge bg-success bg-opacity-10 text-white ms-1">{{ $completedWIDocuments->count() }}</span>
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="historyTabsContent">
                    {{-- TAB 1: ACTIVE --}}
                    <div class="tab-pane fade show active" id="active-content" role="tabpanel">
                        @if($activeWIDocuments->count() > 0 || (!request()->has('search')))
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Active Documents</h5>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input wi-checkbox mt-0" type="checkbox" id="selectAllActive">
                                    <label class="form-check-label ms-1 small fw-bold text-muted" for="selectAllActive">Pilih Semua</label>
                                </div>
                                <div class="d-flex gap-2" id="actionGroupActive">
                                    <button type="button" id="btnActiveDelete" class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="confirmDelete('active')">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus (<span id="countActiveDel">0</span>)
                                    </button>
                                    <button type="button" id="btnActiveAction" class="btn btn-success text-white btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="openPrintModal('active')">
                                        <i class="fa-solid fa-print me-1"></i> Cetak Terpilih (<span id="countActive">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>
                        @forelse ($activeWIDocuments as $document)
                            @php
                                $payload = $document->pro_summary['details'] ?? [];
                                $docItemsCount = count($payload);
                                
                                // Calculate Capacity Info
                                $maxMins = $document->capacity_info['max_mins'] ?? 0;
                                $usedMins = $document->capacity_info['used_mins'] ?? 0;
                                $percentage = $document->capacity_info['percentage'] ?? 0;
                            @endphp

                            <div class="wi-item-card mb-3 status-active">
                                <div class="card-header-area">
                                    <div class="row align-items-center">
                                        {{-- Checkbox --}}
                                        <div class="col-auto">
                                            <input class="form-check-input wi-checkbox cb-active" type="checkbox" value="{{ $document->wi_document_code }}">
                                        </div>
                                        <div class="col">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="fw-bold mb-1">{{ $document->wi_document_code }}</h6>
                                                <div class="text-end">
                                                    <span class="badge bg-light text-dark border">{{ $docItemsCount }} PRO</span>
                                                    <span class="badge badge-soft bg-soft-success ms-2">Active</span>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-3 mt-1 small text-muted">
                                                <span><i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($document->document_date)->format('d M Y') }}</span>
                                                <span><i class="fa-regular fa-clock me-1"></i> {{ \Carbon\Carbon::parse($document->document_time)->format('H:i') }}</span>
                                                <span><i class="fa-solid fa-stopwatch me-1"></i> Expired: {{ \Carbon\Carbon::parse($document->expired_at)->format('d M H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body-area">
                                    {{-- Capacity Bar --}}
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between text-xs fw-bold text-muted mb-1">
                                            <span>KAPASITAS WORKCENTER</span>
                                            <span>{{ number_format($usedMins, 0) }} / {{ number_format($maxMins, 0) }} Min</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $percentage > 100 ? 'bg-danger' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                    </div>

                                    {{-- Accordion --}}
                                    <div class="accordion-trigger-area">
                                        <button class="btn-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $document->id }}">
                                            Tampilkan {{ $docItemsCount }} PRO
                                        </button>
                                    </div>
                                    <div id="collapse-{{ $document->id }}" class="collapse item-list-container">
                                        @if($payload)
                                            @foreach ($payload as $item)
                                                <div class="pro-item-row p-3 mb-2 border rounded-3 bg-light">
                                                    <div class="row align-items-center mb-2">
                                                        <div class="col-lg-8">
                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                <span class="badge bg-white text-primary border border-primary-subtle shadow-sm">{{ $item['aufnr'] }}</span>
                                                                <div class="fw-bold text-dark small">{{ $item['nik'] ?? ($item['nik'] ?? '-') }}</div>
                                                                <div class="fw-bold text-dark small">{{ $item['name'] ?? ($item['name'] ?? '-') }}</div>
                                                                <span class="badge bg-success text-white">{{ $item['vornr'] ?? ($item['vornr'] ?? '-') }}</span>
                                                            </div>
                                                            <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                        </div>
                                                        <div class="col-lg-4 text-end">
                                                            <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ ($item['uom'] ?? 'EA') == 'ST' ? 'PC' : ($item['uom'] ?? 'EA') }}</span></div>
                                                            @if(($item['confirmed_qty'] ?? 0) == 0)
                                                                <button class="btn btn-sm btn-outline-primary btn-edit-qty py-0 px-2 rounded-pill small fw-bold" 
                                                                        onclick="openEditQtyModal('{{ $document->wi_document_code }}', '{{ $item['aufnr'] }}', '{{ $item['description'] ?? $item['material_desc'] }}', '{{ $item['assigned_qty'] }}', '{{ $item['qty_order'] }}', '{{ $item['uom'] ?? 'EA' }}')">
                                                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Quantity
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {{-- FULL WIDTH PROGRESS BAR --}}
                                                    <div class="d-flex justify-content-end mb-1">
                                                        <span class="text-xs fw-bold text-muted">{{ $item['confirmed_qty'] ?? 0 }} / {{ $item['assigned_qty'] }} Quantity</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $item['progress_pct'] ?? 0 }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-5 text-center text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25"></i>
                                <p>Tidak ada dokumen aktif hari ini.</p>
                            </div>
                        @endforelse
                        @endif
                    </div>

                    {{-- TAB 2: INACTIVE (FUTURE) --}}
                    <div class="tab-pane fade" id="inactive-content" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Inactive Documents</h5>
                             <div class="d-flex align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input wi-checkbox mt-0" type="checkbox" id="selectAllInactive">
                                    <label class="form-check-label ms-1 small fw-bold text-muted" for="selectAllInactive">Pilih Semua</label>
                                </div>
                                <div class="d-flex gap-2" id="actionGroupInactive">
                                    <button type="button" id="btnInactiveDelete" class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="confirmDelete('inactive')">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus (<span id="countInactiveDel">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>
                        @forelse ($inactiveWIDocuments as $document)
                            @php
                                $payload = $document->pro_summary['details'] ?? [];
                                $docItemsCount = count($payload);
                                
                                // Calculate Capacity Info (Copied from Active Tab)
                                $maxMins = $document->capacity_info['max_mins'] ?? 0;
                                $usedMins = $document->capacity_info['used_mins'] ?? 0;
                                $percentage = $document->capacity_info['percentage'] ?? 0;
                            @endphp
                            
                            <div class="wi-item-card mb-3 status-active">
                                <div class="card-header-area">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <input class="form-check-input wi-checkbox cb-inactive" type="checkbox" value="{{ $document->wi_document_code }}">
                                        </div>
                                        <div class="col">
                                                <div class="d-flex justify-content-between">
                                                <h6 class="fw-bold mb-1">{{ $document->wi_document_code }}</h6>
                                                <div class="text-end">
                                                    <span class="badge badge-soft bg-warning text-dark">INACTIVE</span>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-3 mt-1 small text-muted">
                                                <span><i class="fa-regular fa-calendar me-1"></i> Dokumen WI akan aktif pada : {{ \Carbon\Carbon::parse($document->document_date)->format('d M') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body-area">
                                    {{-- Capacity Bar --}}
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between text-xs fw-bold text-muted mb-1">
                                            <span>KAPASITAS WORKCENTER</span>
                                            <span>{{ number_format($usedMins, 0) }} / {{ number_format($maxMins, 0) }} Min</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $percentage > 100 ? 'bg-danger' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                    </div>

                                    <div class="accordion-trigger-area">
                                        <button class="btn-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-inactive-{{ $document->id }}">
                                            Tampilkan {{ $docItemsCount }} PRO
                                        </button>
                                    </div>
                                        <div id="collapse-inactive-{{ $document->id }}" class="collapse item-list-container">
                                        @foreach ($payload ?? [] as $item)
                                            <div class="pro-item-row p-3 mb-2 border rounded-3 bg-light">
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-lg-8">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span class="badge bg-white text-secondary border border-secondary-subtle shadow-sm">{{ $item['aufnr'] }}</span>
                                                            <div class="fw-bold text-dark small">{{ $item['nik'] ?? '-' }}</div>
                                                            <div class="fw-bold text-dark small">{{ $item['name'] ?? '-' }}</div>
                                                            <span class="badge bg-warning text-dark">{{ $item['vornr'] ?? '-' }}</span>
                                                        </div>
                                                        <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                    </div>
                                                    <div class="col-lg-4 text-end">
                                                        <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ ($item['uom'] ?? 'EA') == 'ST' ? 'PC' : ($item['uom'] ?? 'EA') }}</span></div>
                                                            <button class="btn btn-sm btn-outline-primary btn-edit-qty py-0 px-2 rounded-pill small fw-bold" 
                                                                    onclick="openEditQtyModal('{{ $document->wi_document_code }}', '{{ $item['aufnr'] }}', '{{ $item['description'] ?? $item['material_desc'] }}', '{{ $item['assigned_qty'] }}', '{{ $item['qty_order'] }}', '{{ $item['uom'] ?? 'EA' }}')">
                                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Quantity
                                                            </button>
                                                    </div>
                                                </div>
                                                {{-- FULL WIDTH PROGRESS BAR --}}
                                                <div class="d-flex justify-content-end mb-1">
                                                    <span class="text-xs fw-bold text-muted">{{ $item['confirmed_qty'] ?? 0 }} / {{ $item['assigned_qty'] }} Quantity</span>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $item['progress_pct'] ?? 0 }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-5 text-center text-muted">
                                <i class="fa-solid fa-clock fa-3x mb-3 opacity-25"></i>
                                <p>Tidak ada dokumen future.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- TAB 3: EXPIRED --}}
                    <div class="tab-pane fade" id="expired-content" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Expired Documents</h5>
                             <div class="d-flex align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input wi-checkbox mt-0" type="checkbox" id="selectAllExpired">
                                    <label class="form-check-label ms-1 small fw-bold text-muted" for="selectAllExpired">Pilih Semua</label>
                                </div>
                                <div class="d-flex gap-2" id="actionGroupExpired">
                                    <button type="button" id="btnExpiredDelete" class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="confirmDelete('expired')">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus (<span id="countExpiredDel">0</span>)
                                    </button>
                                    <button type="button" id="btnExpiredAction" class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="openPrintModal('expired')">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Cetak Report (<span id="countExpired">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>
                        @forelse ($expiredWIDocuments as $document)
                             @php
                                $payload = $document->pro_summary['details'] ?? [];
                                $docItemsCount = count($payload);
                            @endphp
                             <div class="wi-item-card mb-3 status-expired">
                                 <div class="card-header-area">
                                     <div class="d-flex align-items-center gap-3">
                                         <input class="form-check-input wi-checkbox cb-expired" type="checkbox" value="{{ $document->wi_document_code }}">
                                         <div>
                                            <h6 class="fw-bold mb-0">{{ $document->wi_document_code }}</h6>
                                            <div class="small text-muted">Expired pada tanggal : {{ \Carbon\Carbon::parse($document->expired_at)->format('d M H:i') }}</div>
                                         </div>
                                         <span class="badge badge-soft bg-soft-danger ms-auto">Expired</span>
                                     </div>
                                 </div>
                                  <div class="card-body-area">
                                       <div class="accordion-trigger-area">
                                          <button class="btn-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-expired-{{ $document->id }}">
                                              Tampilkan {{ $docItemsCount }} PRO
                                          </button>
                                      </div>
                                      <div id="collapse-expired-{{ $document->id }}" class="collapse item-list-container">
                                         @foreach ($payload ?? [] as $item)
                                              <div class="pro-item-row p-3 mb-2 border rounded-3 bg-light">
                                                  <div class="row align-items-center mb-2">
                                                      <div class="col-lg-8">
                                                          <div class="d-flex align-items-center gap-2 mb-1">
                                                              <span class="badge bg-white text-danger border border-danger-subtle shadow-sm">{{ $item['aufnr'] }}</span>
                                                              <div class="fw-bold text-dark small">{{ $item['nik'] ?? ($item['nik'] ?? '-') }}</div>
                                                              <div class="fw-bold text-dark small">{{ $item['name'] ?? ($item['name'] ?? '-') }}</div>
                                                              <span class="badge bg-danger text-white">{{ $item['vornr'] ?? ($item['vornr'] ?? '-') }}</span>
                                                          </div>
                                                          <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                      </div>
                                                      <div class="col-lg-4 text-end">
                                                          <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ ($item['uom'] ?? 'EA') == 'ST' ? 'PC' : ($item['uom'] ?? 'EA') }}</span></div>
                                                      </div>
                                                  </div>
                                                  {{-- FULL WIDTH PROGRESS BAR --}}
                                                  <div class="d-flex justify-content-end mb-1">
                                                      <span class="text-xs fw-bold text-muted">{{ $item['confirmed_qty'] ?? 0 }} / {{ $item['assigned_qty'] }} Quantity</span>
                                                  </div>
                                                  <div class="progress" style="height: 6px;">
                                                      <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $item['progress_pct'] ?? 0 }}%"></div>
                                                  </div>
                                              </div>
                                          @endforeach
                                      </div>
                                  </div>
                              </div>
                        @empty
                             <p class="text-muted text-center py-5">Tidak ada dokumen expired.</p>
                        @endforelse
                    </div>

                    {{-- TAB 4: COMPLETED --}}
                    <div class="tab-pane fade" id="completed-content" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Completed Documents</h5>
                             <div class="d-flex align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input wi-checkbox mt-0" type="checkbox" id="selectAllCompleted">
                                    <label class="form-check-label ms-1 small fw-bold text-muted" for="selectAllCompleted">Pilih Semua</label>
                                </div>
                                <div class="d-flex gap-2" id="actionGroupCompleted">
                                    <button type="button" id="btnCompletedDelete" class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="confirmDelete('completed')">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus (<span id="countCompletedDel">0</span>)
                                    </button>
                                     <button type="button" id="btnCompletedAction" class="btn btn-info text-white btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="openPrintModal('completed')">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Report (<span id="countCompleted">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>
                        @forelse ($completedWIDocuments as $document)
                             @php
                                $payload = $document->pro_summary['details'] ?? [];
                                $docItemsCount = count($payload);
                            @endphp
                             <div class="wi-item-card mb-3 status-active" style="border-left-color: #0ea5e9;"> {{-- Custom Blue --}}
                                 <div class="card-header-area">
                                     <div class="d-flex align-items-center gap-3">
                                         <input class="form-check-input wi-checkbox cb-completed" type="checkbox" value="{{ $document->wi_document_code }}">
                                         <div>
                                            <h6 class="fw-bold mb-0">{{ $document->wi_document_code }}</h6>
                                            <div class="small text-muted">Completed</div>
                                         </div>
                                         <span class="badge badge-soft bg-info text-white ms-auto">Completed</span>
                                     </div>
                                 </div>
                                 <div class="card-body-area">
                                      <div class="accordion-trigger-area">
                                        <button class="btn-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-completed-{{ $document->id }}">
                                            Tampilkan {{ $docItemsCount }} PRO
                                        </button>
                                    </div>
                                    <div id="collapse-completed-{{ $document->id }}" class="collapse item-list-container">
                                         @foreach ($payload ?? [] as $item)
                                             <div class="pro-item-row p-3 mb-2 border rounded-3 bg-light">
                                                 <div class="row align-items-center mb-2">
                                                     <div class="col-lg-8">
                                                         <div class="d-flex align-items-center gap-2 mb-1">
                                                             <span class="badge bg-white text-info border border-info-subtle shadow-sm">{{ $item['aufnr'] }}</span>
                                                             <div class="fw-bold text-dark small">{{ $item['nik'] ?? ($item['nik'] ?? '-') }}</div>
                                                             <div class="fw-bold text-dark small">{{ $item['name'] ?? ($item['name'] ?? '-') }}</div>
                                                              <span class="badge bg-info text-white">{{ $item['vornr'] ?? ($item['vornr'] ?? '-') }}</span>
                                                         </div>
                                                         <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                     </div>
                                                     <div class="col-lg-4 text-end">
                                                         <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ $item['uom'] ?? 'EA' }}</span></div>
                                                         
                                                         {{-- Completed doesn't usually need edit, but keeping for consistency --}}
                                                         <button class="btn btn-sm btn-outline-info py-0 px-2 rounded-pill small fw-bold" disabled>
                                                             <i class="fa-solid fa-check-double me-1"></i> Completed
                                                         </button>
                                                     </div>
                                                 </div>
                                                 
                                                 {{-- FULL WIDTH PROGRESS BAR --}}
                                                 <div class="d-flex justify-content-end mb-1">
                                                     <span class="text-xs fw-bold text-muted">{{ $item['assigned_qty'] }} / {{ $item['assigned_qty'] }}</span>
                                                 </div>
                                                 <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                                                 </div>
                                             </div>
                                         @endforeach
                                    </div>
                                 </div>
                             </div>
                        @empty
                             <p class="text-muted text-center py-5">Tidak ada dokumen completed.</p>
                        @endforelse
                    </div>
                </div>
        </div>
    </div>

    <!-- UNIVERSAL PRINT MODAL -->
    <div class="modal fade" id="universalPrintModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg"> {{-- Changed to Large Modal --}}
            <div class="modal-content border-0 shadow-lg overflow-hidden rounded-4">
                <div class="modal-header text-white" id="modalHeaderBg" style="background: #1e293b;">
                    <h6 class="modal-title fw-bold text-uppercase ls-1" id="modalTitle">Cetak Dokumen</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="printForm" method="POST" target="_blank">
                    @csrf
                    <div class="modal-body p-4 bg-white">
                        
                        <!-- HIDDEN INPUTS -->
                        <input type="hidden" name="wi_codes" id="inputWiCodes">
                        <input type="hidden" name="filter_date" value="{{ request('date') }}">
                        <input type="hidden" name="filter_search" value="{{ request('search') }}">
                        
                        <div id="modalAlert" class="alert shadow-sm border-0 mb-4 d-flex align-items-center"></div>

                        <!-- PREVIEW CONTAINER (Initially Hidden) -->
                        <div id="previewContainer" class="d-none mb-4">
                            <h6 class="fw-bold text-muted small mb-2">PREVIEW DATA (Top 50)</h6>
                            <div class="table-responsive border rounded bg-light" style="max-height: 250px;">
                                <table class="table table-sm table-striped table-hover mb-0 font-monospace small">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th class="text-center">NO</th>
                                            <th class="text-center">KODE WI</th>
                                            <th class="text-center">EXP</th>
                                            <th class="text-center">DESKRIPSI</th>
                                            <th class="text-center">SISA</th>
                                            <th class="text-center">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewTableBody">
                                        <!-- AJAX Content -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end small text-muted mt-1 fst-italic" id="previewCountInfo"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">DICETAK OLEH</label>
                                <input type="text" name="printed_by" class="form-control fw-bold" value="{{ session('username') ?? 'User' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">BAGIAN</label>
                                <input type="text" name="department" class="form-control fw-bold" value="{{ $nama_bagian->nama_bagian ?? '-' }}" readonly>
                            </div>
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
        // Initialize Flatpickr Range
        document.addEventListener("DOMContentLoaded", function() {
            const fp = flatpickr(".flatpickr-range", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: "{{ request('date') }}"
            });

            window.setQuickDate = function(type) {
                const today = new Date();
                let start, end;

                if (type === 'today') {
                    start = today;
                    end = today;
                } else if (type === 'yesterday') {
                    const yest = new Date(today);
                    yest.setDate(yest.getDate() - 1);
                    start = yest;
                    end = yest;
                }
                fp.setDate([start, end]);
            };
        });
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
            const previewContainer = document.getElementById('previewContainer');

            // --- RESET STATE GLOBAL ---
            form.target = "_blank"; // Default back to new tab (PDF)
            form.action = "";      // Clear action
            btnSubmit.onclick = null; // Clear previous event handlers!
            btnSubmit.disabled = false;
            previewContainer.classList.add('d-none'); // Hide preview by default
            
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

            } else if (type === 'completed') {
                form.action = "{{ route('wi.print-completed-report') }}"; 
                modalTitle.innerText = 'CETAK LAPORAN COMPLETED';
                headerBg.style.background = '#0ea5e9'; // Info Blue
                
                alertMsg.className = 'alert bg-info-subtle text-info border-0 fw-bold';
                alertMsg.innerHTML = `<i class="fa-solid fa-file-invoice me-2"></i>Laporan untuk ${count} dokumen selesai.`;
                
                btnSubmit.className = 'btn btn-info text-white px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-file-export me-2"></i>Export Report';

            } else if (type === 'log') {
                // NOTE: Action will be handled via JS for Email, but kept for fallback
                form.action = "#"; // Prevent default submission
                form.target = "_self"; // No new tab
                
                modalTitle.innerText = 'EXPORT LOG & EMAIL';
                headerBg.style.background = '#ffffffff'; // Dark
                
                alertMsg.className = 'alert bg-info-subtle text-info border-0 fw-bold';
                alertMsg.innerHTML = `<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading Preview...</div>`;
                
                // Show Preview Container
                document.getElementById('previewContainer').classList.remove('d-none');
                
                btnSubmit.className = 'btn btn-dark px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Kirim Email (Excel)';
                btnSubmit.disabled = true; // Disable until preview loads

                // --- FETCH PREVIEW AJAX ---
                const previewBody = document.getElementById('previewTableBody');
                const countInfo = document.getElementById('previewCountInfo');
                previewBody.innerHTML = ''; // Clear previous
                
                const filterDate = document.querySelector('input[name="filter_date"]').value;
                const filterSearch = document.querySelector('input[name="filter_search"]').value;

                fetch(`{{ route('wi.preview-log', ['kode' => $plantCode]) }}?filter_date=${filterDate}&filter_search=${filterSearch}`)
                .then(response => response.json())
                .then(res => {
                    if(res.success) {
                        alertMsg.className = 'alert bg-secondary-subtle text-dark border-0 fw-bold';
                        alertMsg.innerHTML = `<i class="fa-solid fa-info-circle me-2"></i>Review data sebelum dikirim via email.`;
                        
                        btnSubmit.disabled = false;
                        
                        // Populate Table
                        if(res.data.length === 0) {
                             previewBody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada data ditemukan.</td></tr>`;
                             btnSubmit.disabled = true;
                        } else {
                            let no = 1;
                            res.data.forEach(row => {
                                const tr = document.createElement('tr');
                                // Color logic for status
                                let badgeClass = 'bg-secondary';
                                if(row.status === 'COMPLETED') badgeClass = 'bg-primary';
                                else if(row.status === 'ACTIVE') badgeClass = 'bg-success';
                                else if(row.status === 'NOT COMPLETED') badgeClass = 'bg-danger';
                                else if(row.status === 'INACTIVE') badgeClass = 'bg-warning text-dark';

                                tr.innerHTML = `
                                    <td>${no++}</td>
                                    <td>${row.doc_no}</td>
                                    <td>${row.expired_at}</td>
                                    <td class="text-center">${row.description.substring(0, 15)}...</td>
                                    <td class="text-center">${row.balance}</td>
                                    <td class="text-center"><span class="badge ${badgeClass}">${row.status}</span></td>
                                `;
                                previewBody.appendChild(tr);
                            });
                        }
                        const uniqueDocsCount = new Set(res.data.map(d => d.doc_no)).size;
                        countInfo.innerText = `Menampilkan ${uniqueDocsCount} dari total ${res.total_docs} dokumen.`;
                        
                        // --- CLICK HANDLER FOR EMAIL ---
                        btnSubmit.onclick = function(e) {
                            e.preventDefault();
                            if(!confirm('Apakah anda yakin ingin mengirim email report ini?')) return;
                            
                            btnSubmit.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Mengirim...';
                            btnSubmit.disabled = true;
                            
                            const departmentVal = document.querySelector('input[name="department"]').value;
                            const printedByVal = document.querySelector('input[name="printed_by"]').value;

                            fetch(`{{ route('wi.email-log', ['kode' => $plantCode]) }}?filter_date=${filterDate}&filter_search=${filterSearch}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    department: departmentVal,
                                    printed_by: printedByVal
                                })
                            })
                            .then(resp => resp.json())
                            .then(data => {
                                if(data.success) {
                                    alert('Berhasil! ' + data.message);
                                    const modalEl = document.getElementById('universalPrintModal');
                                    const modal = bootstrap.Modal.getInstance(modalEl);
                                    modal.hide();
                                } else {
                                    alert('Gagal: ' + data.message);
                                    btnSubmit.innerHTML = 'Coba Lagi';
                                    btnSubmit.disabled = false;
                                }
                            })
                            .catch(err => {
                                alert('Terjadi kesalahan koneksi.');
                                btnSubmit.disabled = false;
                            });
                        };

                    } else {
                        alertMsg.innerHTML = 'Gagal memuat preview.';
                    }
                })
                .catch(err => {
                    alertMsg.innerHTML = 'Error loading preview.';
                    console.error(err);
                });
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

            let selector;
            if (type === 'active') selector = '.cb-active:checked';
            else if (type === 'expired') selector = '.cb-expired:checked';
            else if (type === 'completed') selector = '.cb-completed:checked';
            else selector = '.cb-inactive:checked';

            const checked = document.querySelectorAll(selector);
            let codes = [];
            checked.forEach(cb => codes.push(cb.value));

            inputCodes.value = codes.join(',');
            setupModalUI(type, codes.length);

            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        };

        // --- 4.5 DELETE FUNCTION ---
        window.confirmDelete = function(type) {
            let selector;
            if (type === 'active') selector = '.cb-active:checked';
            else if (type === 'inactive') selector = '.cb-inactive:checked';
            else if (type === 'completed') selector = '.cb-completed:checked';
            else selector = '.cb-expired:checked';

            const checked = document.querySelectorAll(selector);
            let codes = [];
            checked.forEach(cb => codes.push(cb.value));

            if(codes.length === 0) return;

            Swal.fire({
                title: 'Hapus Dokumen?',
                text: `Anda akan menghapus ${codes.length} dokumen ini. (Soft Delete)`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send Request
                    fetch('{{ route('wi.delete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ wi_codes: codes })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.message) {
                            Swal.fire('Terhapus!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal', 'Gagal menghapus data', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Terjadi kesalahan server.', 'error');
                    });
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            // --- 5. HIGHLIGHT CARD LOGIC ---
            function toggleCardHighlight(checkbox) {
                const card = checkbox.closest('.wi-item-card');
                if (card) {
                    if (checkbox.checked) card.classList.add('card-selected-highlight');
                    else card.classList.remove('card-selected-highlight');
                }
            }

            // --- 6. CHECKBOX HANDLERS (Generic Helper) ---
            function setupCheckboxGroup(selectAllId, itemClass, btnActionId, btnDeleteId, countId, countDelId) {
                const selectAll = document.getElementById(selectAllId);
                if(!selectAll) return;

                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll(itemClass);
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                        toggleCardHighlight(cb);
                    });
                    updateButtons();
                });

                document.body.addEventListener('change', function(e) {
                    if (e.target.classList.contains(itemClass.replace('.',''))) {
                        toggleCardHighlight(e.target);
                        updateButtons();
                    }
                });

                function updateButtons() {
                    const checked = document.querySelectorAll(itemClass + ':checked');
                    const btnAction = document.getElementById(btnActionId);
                    const btnDel = document.getElementById(btnDeleteId);
                    const countSpan = document.getElementById(countId);
                    const countDelSpan = document.getElementById(countDelId);

                    if (checked.length > 0) {
                        if(btnAction) btnAction.classList.remove('d-none');
                        if(btnDel) btnDel.classList.remove('d-none');
                        if(countSpan) countSpan.innerText = checked.length;
                        if(countDelSpan) countDelSpan.innerText = checked.length;
                    } else {
                        if(btnAction) btnAction.classList.add('d-none');
                        if(btnDel) btnDel.classList.add('d-none');
                    }
                }
            }
            
            // Setup Groups
            setupCheckboxGroup('selectAllActive', '.cb-active', 'btnActiveAction', 'btnActiveDelete', 'countActive', 'countActiveDel');
            setupCheckboxGroup('selectAllInactive', '.cb-inactive', 'btnInactiveAction', 'btnInactiveDelete', 'countInactive', 'countInactiveDel');
            setupCheckboxGroup('selectAllExpired', '.cb-expired', 'btnExpiredAction', 'btnExpiredDelete', 'countExpired', 'countExpiredDel');
            setupCheckboxGroup('selectAllCompleted', '.cb-completed', 'btnCompletedAction', 'btnCompletedDelete', 'countCompleted', 'countCompletedDel');
        });

        // --- 7. OPEN EDIT QTY MODAL ---
        // --- 7. OPEN EDIT QTY MODAL ---
        window.openEditQtyModal = function(wiCode, aufnr, description, assignedQty, orderQty, uom) {
            // Populate Modal Fields directly
            const inputWiCode = document.getElementById('modalWiCode');
            const inputAufnr = document.getElementById('modalAufnr');
            const inputDesc = document.getElementById('modalDesc'); 
            const inputNewQty = document.getElementById('modalNewQty');
            const displayMaxQtyDiv = document.getElementById('displayMaxQty');
            const inputMaxQtyHidden = document.getElementById('modalMaxQtyDisplay');
            const displayAufnrDiv = document.getElementById('displayAufnr');
            const spanUom = document.getElementById('modalUom');

            if(inputWiCode) inputWiCode.value = wiCode;
            if(inputAufnr) inputAufnr.value = aufnr;
            if(inputDesc) inputDesc.value = description;
            if(displayAufnrDiv) displayAufnrDiv.innerText = aufnr;
            if(inputNewQty) inputNewQty.value = assignedQty;
            
            // Format Max Qty
            if(displayMaxQtyDiv) displayMaxQtyDiv.innerText = parseFloat(orderQty) + ' ' + uom;
            if(inputMaxQtyHidden) inputMaxQtyHidden.value = orderQty;
            
            if(spanUom) spanUom.innerText = uom;
            
            // Validation Logic
            const btnSave = document.getElementById('btnSaveQty');
            const errorMsg = document.getElementById('qtyErrorMsg');
            const maxVal = parseFloat(orderQty);

            if(inputNewQty) {
                // Reset State
                inputNewQty.classList.remove('is-invalid');
                if(errorMsg) errorMsg.classList.add('d-none');
                if(btnSave) btnSave.disabled = false;

                inputNewQty.oninput = function() {
                    const val = parseFloat(this.value);
                    if (val > maxVal) {
                        this.classList.add('is-invalid');
                        if(errorMsg) errorMsg.classList.remove('d-none');
                        if(btnSave) btnSave.disabled = true;
                    } else {
                        this.classList.remove('is-invalid');
                        if(errorMsg) errorMsg.classList.add('d-none');
                        if(btnSave) btnSave.disabled = false;
                    }
                };
            }

            // Show Modal
            const modalEl = document.getElementById('editQtyModal');
            if(modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        };

    </script>
    @endpush
</x-layouts.app>