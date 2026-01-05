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
                    <div class="col-lg-3">
                        <label class="form-label fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Tanggal Dokumen</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="date" id="dateInput" class="form-control flatpickr-range" value="{{ request('date') }}" placeholder="Pilih Rentang Tanggal..." onchange="document.getElementById('filterForm').submit()">
                            {{-- Removed Quick Buttons to save space or keep them if they fit --}}
                             <button type="button" class="btn btn-outline-secondary px-2" onclick="setQuickDate('today'); document.getElementById('filterForm').submit()">Hari Ini</button>
                             <button type="button" class="btn btn-outline-secondary px-2" onclick="setQuickDate('yesterday'); document.getElementById('filterForm').submit()">Kemarin</button>
                        </div>
                    </div>

                    {{-- Workcenter Filter (NEW) --}}
                    <div class="col-lg-2">
                        <label class="form-label fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Workcenter</label>
                        <select name="workcenter" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="all">Semua WC</option>
                            @foreach($wcNames as $code => $name)
                                <option value="{{ $code }}" {{ request('workcenter') == $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Search --}}
                    <div class="col-lg-3">
                        <label class="form-label fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Pencarian</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-search"></i></span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Kode WI, PRO..." 
                                   value="{{ request('search') }}"
                                   onkeypress="if(event.keyCode == 13) { document.getElementById('filterForm').submit(); return false; }">
                        </div>
                    </div>

                    {{-- Status Filter --}}
                    <div class="col-lg-2">
                        <label class="form-label fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Status</label>
                        <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Status</option>
                            <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            <option value="NOT COMPLETED" {{ request('status') == 'NOT COMPLETED' ? 'selected' : '' }}>Expired</option>
                            <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    {{-- Action --}}
                    <div class="col-lg-2 d-flex gap-2">
                        <a href="{{ route('wi.history', ['kode' => $plantCode]) }}" class="btn btn-light btn-sm border w-100"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
                    </div>
                </div>
            </form>
    </div>

        <div class="row g-5">
            {{-- TABS NAVIGATION --}}
            <div class="col-12">
                @php
                    $reqStatus = request('status');
                    // Determined Active Tab
                    $activeTab = 'active'; // Default
                    if ($reqStatus == 'INACTIVE') $activeTab = 'inactive';
                    elseif ($reqStatus == 'NOT COMPLETED') $activeTab = 'expired';
                    elseif ($reqStatus == 'COMPLETED') $activeTab = 'completed';
                    elseif ($reqStatus == 'ACTIVE') $activeTab = 'active';
                @endphp

                <ul class="nav nav-tabs border-bottom-0 mb-3" id="historyTabs" role="tablist">
                    @if(!$reqStatus || $reqStatus == 'ACTIVE')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'active' ? 'active' : '' }} fw-bold small text-uppercase" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-content" type="button" role="tab">
                            <i class="fa-solid fa-circle-check text-white me-2"></i> Active<span class="badge bg-success bg-opacity-10 text-white ms-1">{{ $activeWIDocuments->count() }}</span>
                        </button>
                    </li>
                    @endif

                    @if(!$reqStatus || $reqStatus == 'INACTIVE')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'inactive' ? 'active' : '' }} fw-bold small text-uppercase" id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive-content" type="button" role="tab">
                            <i class="fa-solid fa-clock text-white me-2"></i> Inactive <span class="badge bg-success bg-opacity-10 text-white ms-1">{{ $inactiveWIDocuments->count() }}</span>
                        </button>
                    </li>
                    @endif

                    @if(!$reqStatus || $reqStatus == 'NOT COMPLETED')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'expired' ? 'active' : '' }} fw-bold small text-uppercase" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired-content" type="button" role="tab">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Expired <span class="badge bg-danger bg-opacity-10 text-danger ms-1">{{ $expiredWIDocuments->count() }}</span>
                        </button>
                    </li>
                    @endif

                    @if(!$reqStatus || $reqStatus == 'COMPLETED')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab == 'completed' ? 'active' : '' }} fw-bold small text-uppercase" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-content" type="button" role="tab">
                            <i class="fa-solid fa-check-double me-2"></i> Completed <span class="badge bg-success bg-opacity-10 text-white ms-1">{{ $completedWIDocuments->count() }}</span>
                        </button>
                    </li>
                    @endif
                </ul>
                
                <div class="tab-content" id="historyTabsContent">
                    {{-- TAB 1: ACTIVE --}}
                    <div class="tab-pane fade {{ $activeTab == 'active' ? 'show active' : '' }}" id="active-content" role="tabpanel">
                        
                        {{-- CAPACITY SUMMARY BAR (NEW) --}}
                        @if(isset($activeWorkcenterCapacities) && count($activeWorkcenterCapacities) > 0)
                        <div class="card border-0 shadow-sm mb-4 bg-white" style="border-left: 5px solid #3b82f6 !important;">
                            <div class="card-body py-3 px-4">
                                <h6 class="fw-bold text-dark mb-3 text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-chart-pie me-2 text-primary"></i> Kapasitas Workcenter (Active)
                                </h6>
                                <div class="row g-3">
                                    @foreach($activeWorkcenterCapacities as $cap)
                                        <div class="col-lg-4 col-md-6">
                                            <div class="p-2 border rounded-3 bg-light position-relative overflow-hidden">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div class="text-truncate pe-2">
                                                        <span class="fw-bold text-dark text-uppercase" style="font-size: 0.75rem;">{{ $cap['code'] }}</span>
                                                        <span class="text-muted small ms-1" style="font-size: 0.7rem;">{{ \Illuminate\Support\Str::limit($cap['name'], 20) }}</span>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-white text-dark border small fw-bold" style="font-size: 0.65rem;">
                                                            {{ number_format($cap['used_mins'], 0, ',', '.') }} / {{ number_format($cap['max_mins'], 0, ',', '.') }} M
                                                        </span>
                                                    </div>
                                                </div>
                                                @php
                                                    $diff = $cap['used_mins'] - $cap['max_mins'];
                                                    $absDiff = abs($diff);
                                                    
                                                    // Default Pas
                                                    $diffText = "Pas";
                                                    $diffClass = "text-success"; 
                                                    $progressBarClass = "bg-success";
                                                    $progressWidth = 100; // Full bar if perfect

                                                    if ($diff > 0) {
                                                        $diffText = "Kelebihan " . number_format($absDiff, 0, ',', '.') . " Menit";
                                                        $diffClass = "text-danger"; 
                                                        $progressBarClass = "bg-danger";
                                                        // If over, bar is full red
                                                        $progressWidth = 100; 
                                                    } elseif ($diff < 0) {
                                                        $diffText = "Kurang " . number_format($absDiff, 0, ',', '.') . " Menit";
                                                        $diffClass = "text-warning"; 
                                                        $progressBarClass = "bg-warning";
                                                        // Usage percentage for bar visual
                                                        $progressWidth = ($cap['max_mins'] > 0) ? ($cap['used_mins'] / $cap['max_mins']) * 100 : 0;
                                                    }
                                                @endphp
                                                
                                                <div class="progress" style="height: 6px; background-color: #e2e8f0;">
                                                    <div class="progress-bar {{ $progressBarClass }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $progressWidth }}%">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end mt-1">
                                                     <span class="fw-bold {{ $diffClass }}" style="font-size: 0.65rem;">
                                                        {{ $diffText }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
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
                                    <button type="button" id="btnActiveDelete" class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm d-none" onclick="confirmDelete('active')">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus (<span id="countActiveDel">0</span>)
                                    </button>
                                    
                                    <div class="dropdown d-none" id="btnActiveAction">
                                        <button class="btn btn-success text-white btn-sm px-3 rounded-pill fw-bold shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-print me-1"></i> Cetak (<span id="countActive">0</span>)
                                        </button>
                                        <ul class="dropdown-menu shadow border-0 rounded-4">
                                            <li><a class="dropdown-item small fw-bold py-2" href="#" onclick="openPrintModal('active', 'document')"><i class="fa-solid fa-file-invoice me-2 text-success"></i>By Document</a></li>
                                            <li><a class="dropdown-item small fw-bold py-2" href="#" onclick="openPrintModal('active', 'nik')"><i class="fa-solid fa-users-viewfinder me-2 text-primary"></i>By NIK</a></li>
                                        </ul>
                                    </div>
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
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="fw-bold mb-1 d-inline-block align-middle me-2">{{ $document->wi_document_code }}</h6>
                                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold shadow-sm py-0 ms-2" style="font-size: 0.75rem;"
                                                        onclick="setupSinglePrint('{{ $document->wi_document_code }}', 'active')">
                                                        <i class="fa-solid fa-print me-1"></i> Cetak
                                                    </button>
                                                    <button class="btn btn-sm btn-success text-white rounded-pill px-3 fw-bold shadow-sm py-0 ms-2" style="font-size: 0.75rem;" 
                                                        onclick="openAddItemModal('{{ $document->wi_document_code }}', '{{ $document->workcenter_code }}')">
                                                        <i class="fa-solid fa-plus me-1"></i> Tambah Item
                                                    </button>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-light text-dark border">{{ $docItemsCount }} PRO</span>
                                                    <span class="badge badge-soft bg-soft-success ms-2">Active</span>
                                                </div>
                                            </div>
                                            <div class="mt-1">
                                                <span class="text-xs fw-bold text-secondary">
                                                    {{ $document->workcenter_code }} - {{ $wcNames[strtoupper($document->workcenter_code)] ?? '' }}
                                                </span>
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
                                    <!-- <div class="mb-3">
                                        <div class="d-flex justify-content-between text-xs fw-bold text-muted mb-1">
                                            <span>KAPASITAS KERJA HARIAN</span>
                                            <span>{{ number_format($usedMins, 1, ',', '.') }} / {{ number_format($maxMins, 0, ',', '.') }} Min</span>
                                        </div>
                                        <div class="progress bg-secondary bg-opacity-10" style="height: 6px;">
                                            <div class="progress-bar {{ $percentage > 100 ? 'bg-danger' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                    </div> -->

                                    {{-- Accordion --}}
                                    <div class="accordion-trigger-area d-flex justify-content-between align-items-center pe-3">
                                        <button class="btn-accordion-toggle collapsed flex-grow-1 text-center py-1 bg-light text-muted fw-bold rounded-3 small" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $document->id }}" style="border: 1px dashed #ccc;">
                                            Tampilkan / Sembunyikan {{ $docItemsCount }} PRO
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
                                                            @php
                                                                $kdaufHist = $item['kdauf'] ?? '';
                                                                $matKdaufHist = $item['mat_kdauf'] ?? '';
                                                                $isMakeStockHist = (strcasecmp($kdaufHist, 'Make Stock') === 0) || (strcasecmp($matKdaufHist, 'Make Stock') === 0);
                                                                $kdposHist = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                                                                $soItemHist = $isMakeStockHist ? $kdaufHist : ($kdaufHist . ($kdposHist ? ' - ' . $kdposHist : ''));
                                                            @endphp
                                                            <div class="text-muted text-xs text-truncate ps-1">
                                                                {{ $soItemHist }}
                                                                <span class="ms-1 text-primary">Time Required: {{ number_format($item['item_mins'] ?? 0, 2) }} min</span>
                                                            </div>
                                                            <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                        </div>
                                                        <div class="col-lg-4 text-end">
                                                            <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ ($item['uom'] ?? '-') == 'ST' ? 'PC' : ($item['uom'] ?? '-') }}</span></div>
                                                            <div class="d-flex gap-1 justify-content-end">
                                                                @if(($item['confirmed_qty'] ?? 0) == 0)
                                                                    <button class="btn btn-sm btn-outline-primary btn-edit-qty py-0 px-2 rounded-pill small fw-bold" 
                                                                            onclick="openEditQtyModal('{{ $document->wi_document_code }}', '{{ $item['aufnr'] }}', '{{ $item['description'] ?? $item['material_desc'] }}', '{{ $item['assigned_qty'] }}', '{{ $item['qty_order'] }}', '{{ $item['uom'] ?? '-' }}', '{{ $item['vgw01'] ?? 0 }}', '{{ $item['vge01'] ?? '' }}', '{{ $item['nik'] ?? '' }}', '{{ $item['vornr'] ?? '' }}', '{{ $maxMins }}', '{{ $usedMins }}', '{{ $item['item_mins'] ?? 0 }}')">
                                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                                    </button>
                                                                    <!-- <button class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill small fw-bold" 
                                                                            onclick="confirmRemoveItem('{{ $document->wi_document_code }}', '{{ $item['aufnr'] }}', '{{ $item['vornr'] ?? '' }}', '{{ $item['nik'] ?? '' }}', '{{ $item['assigned_qty'] ?? 0 }}')">
                                                                        <i class="fa-solid fa-trash"></i>
                                                                    </button> -->
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end mb-1">
                                                        <span class="text-xs fw-bold text-muted">
                                                             Sukses: {{ $item['confirmed_qty'] ?? 0 }}
                                                            @if(($item['remark_qty'] ?? 0) > 0)
                                                                <span class="text-danger"> Gagal: {{ $item['remark_qty'] }}</span>
                                                            @endif
                                                             / {{ $item['assigned_qty'] }} Quantity
                                                        </span>
                                                    </div>
                                                    @php
                                                        $rQty = $item['remark_qty'] ?? 0;
                                                        $aQty = $item['assigned_qty'] > 0 ? $item['assigned_qty'] : 1;
                                                        $rPct = ($rQty / $aQty) * 100;
                                                    @endphp
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $item['progress_pct'] ?? 0 }}%"></div>
                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $rPct }}%"></div>
                                                    </div>
                                                    @if(!empty($item['remark_history']) && is_array($item['remark_history']))
                                                        <div class="mt-2">
                                                            <div class="fw-bold text-danger" style="font-size: 0.75rem;">Riwayat Remark:</div>
                                                            <ul class="list-unstyled mb-0 ps-1 mt-1">
                                                                @foreach($item['remark_history'] as $h)
                                                                    <li class="mb-1">
                                                                         <span class="badge bg-danger text-wrap text-start" style="font-size: 0.7rem;">
                                                                            <strong>Jumlah Item Gagal: {{ floatval($h['qty'] ?? 0) }}</strong> - {{ $h['remark'] ?? '-' }}
                                                                         </span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @elseif(!empty($item['remark']))
                                                        <div class="mt-2 text-danger" style="font-size: 0.75rem;">
                                                            @foreach(explode(';', $item['remark']) as $r)
                                                                @if(trim($r))
                                                                    @php
                                                                        $parts = explode(':', $r, 2);
                                                                        $qtyMsg = '';
                                                                        $remarkMsg = trim($r);
                                                                        if (count($parts) == 2 && stripos($parts[0], 'Qty') !== false) {
                                                                            // Parse 'Qty X' to 'X'
                                                                            $qtyVal =  trim(str_ireplace('Qty', '', $parts[0]));
                                                                            $remarkMsg = trim($parts[1]);
                                                                            $qtyDisplay = "Jumlah Item Gagal: " . $qtyVal;
                                                                        } else {
                                                                            // No Qty prefix found, maybe just message
                                                                            $qtyDisplay = "Remark"; 
                                                                        }
                                                                    @endphp
                                                                    <div class="mb-1">
                                                                        <span class="badge bg-danger text-wrap text-start" style="font-size: 0.7rem;">
                                                                            @if(!empty($qtyDisplay) && $qtyDisplay !== 'Remark')
                                                                                <strong>{{ $qtyDisplay }}</strong> - {{ $remarkMsg }}
                                                                            @else
                                                                                {{ $remarkMsg }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
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
                    <div class="tab-pane fade {{ $activeTab == 'inactive' ? 'show active' : '' }}" id="inactive-content" role="tabpanel">
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
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="fw-bold mb-1 d-inline-block align-middle me-2">{{ $document->wi_document_code }}</h6>
                                                    <button class="btn btn-sm btn-success text-white rounded-pill px-3 fw-bold shadow-sm py-0 ms-2" style="font-size: 0.75rem;" 
                                                        onclick="openAddItemModal('{{ $document->wi_document_code }}', '{{ $document->workcenter_code }}')">
                                                        <i class="fa-solid fa-plus me-1"></i> Tambah Item
                                                    </button>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-secondary">Inactive</span>
                                                    <span class="badge bg-light text-dark border">{{ $docItemsCount }} PRO</span>
                                                </div>
                                            </div>
                                            <div class="mt-1">
                                                <span class="text-xs fw-bold text-secondary">
                                                    {{ $document->workcenter_code }} - {{ $wcNames[strtoupper($document->workcenter_code)] ?? '' }}
                                                </span>
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
                                        <div class="progress bg-secondary bg-opacity-10" style="height: 6px;">
                                            <div class="progress-bar {{ $percentage > 100 ? 'bg-danger' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                    </div>

                                    <div class="accordion-trigger-area d-flex justify-content-between align-items-center pe-3">
                                        <button class="btn-accordion-toggle collapsed flex-grow-1 text-center py-1 bg-light text-muted fw-bold rounded-3 small" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-inactive-{{ $document->id }}" style="border: 1px dashed #ccc;">
                                            Tampilkan / Sembunyikan {{ $docItemsCount }} PRO
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
                                                        <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ ($item['uom'] ?? '-') == 'ST' ? 'PC' : ($item['uom'] ?? '-') }}</span></div>
                                                            <div class="d-flex gap-1 justify-content-end">
                                                                <button class="btn btn-sm btn-outline-primary btn-edit-qty py-0 px-2 rounded-pill small fw-bold" 
                                                                        onclick="openEditQtyModal('{{ $document->wi_document_code }}', '{{ $item['aufnr'] }}', '{{ $item['description'] ?? $item['material_desc'] }}', '{{ $item['assigned_qty'] }}', '{{ $item['qty_order'] }}', '{{ $item['uom'] ?? '-' }}', '{{ $item['vgw01'] ?? 0 }}', '{{ $item['vge01'] ?? '' }}', '{{ $item['nik'] ?? '' }}', '{{ $item['vornr'] ?? '' }}', '{{ $maxMins }}')">
                                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                                </button>
                                                                @if(($item['confirmed_qty'] ?? 0) <= 0)
                                                                    <button class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill small fw-bold" 
                                                                            onclick="confirmRemoveItem('{{ $document->wi_document_code }}', '{{ $item['aufnr'] }}', '{{ $item['vornr'] ?? '' }}', '{{ $item['nik'] ?? '' }}', '{{ $item['assigned_qty'] ?? 0 }}')">
                                                                        <i class="fa-solid fa-trash"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
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
                    <div class="tab-pane fade {{ $activeTab == 'expired' ? 'show active' : '' }}" id="expired-content" role="tabpanel">
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
                                    <div class="dropdown d-none" id="btnExpiredAction">
                                        <button class="btn btn-danger btn-sm px-3 rounded-pill fw-bold shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-file-invoice me-1"></i> Report (<span id="countExpired">0</span>)
                                        </button>
                                        <ul class="dropdown-menu shadow border-0 rounded-4">
                                            <li><a class="dropdown-item small fw-bold py-2" href="#" onclick="openPrintModal('expired', 'document')"><i class="fa-solid fa-file-export me-2 text-danger"></i>Result Report</a></li>
                                            <li><a class="dropdown-item small fw-bold py-2" href="#" onclick="openPrintModal('expired', 'nik')"><i class="fa-solid fa-users-viewfinder me-2 text-primary"></i>By NIK</a></li>
                                        </ul>
                                    </div>
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
                                                          @php
                                                              $kdaufHist = $item['kdauf'] ?? '';
                                                              $matKdaufHist = $item['mat_kdauf'] ?? '';
                                                              $isMakeStockHist = (strcasecmp($kdaufHist, 'Make Stock') === 0) || (strcasecmp($matKdaufHist, 'Make Stock') === 0);
                                                              $kdposHist = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                                                              $soItemHist = $isMakeStockHist ? $kdaufHist : ($kdaufHist . ($kdposHist ? ' - ' . $kdposHist : ''));
                                                          @endphp
                                                          <div class="text-muted text-xs text-truncate ps-1">
                                                              {{ $soItemHist }}
                                                              <span class="ms-1 fw-bold text-primary" style="font-size: 0.65rem;">(Tak: {{ number_format($item['item_mins'] ?? 0, 2) }} min)</span>
                                                          </div>
                                                          <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                      </div>
                                                      <div class="col-lg-4 text-end">
                                                          <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ ($item['uom'] ?? '-') == 'ST' ? 'PC' : ($item['uom'] ?? '-') }}</span></div>
                                                      </div>
                                                  </div>
                                                  {{-- FULL WIDTH PROGRESS BAR --}}
                                                  {{-- FULL WIDTH PROGRESS BAR --}}
                                                  <div class="d-flex justify-content-end mb-1">
                                                      <span class="text-xs fw-bold text-muted">
                                                          {{ $item['confirmed_qty'] ?? 0 }}
                                                          @if(($item['remark_qty'] ?? 0) > 0)
                                                              <span class="text-danger"> + {{ $item['remark_qty'] }} (Remark)</span>
                                                          @endif
                                                           / {{ $item['assigned_qty'] }} Quantity
                                                      </span>
                                                  </div>
                                                  @php
                                                      $rQty = $item['remark_qty'] ?? 0;
                                                      $aQty = $item['assigned_qty'] > 0 ? $item['assigned_qty'] : 1;
                                                      $rPct = ($rQty / $aQty) * 100;
                                                  @endphp
                                                  <div class="progress" style="height: 6px;">
                                                      <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $item['progress_pct'] ?? 0 }}%"></div>
                                                      <div class="progress-bar bg-danger border-start border-white" role="progressbar" style="width: {{ $rPct }}%"></div> {{-- Border to distinguish if main is also red --}}
                                                  </div>
                                                  @if(!empty($item['remark']))
                                                      <div class="mt-2 text-danger" style="font-size: 0.75rem; font-weight: 600;">
                                                          @foreach(explode(';', $item['remark']) as $r)
                                                              @if(trim($r))
                                                                  <div class="d-flex align-items-start gap-1">
                                                                      <i class="fa-solid fa-circle-exclamation mt-1" style="font-size: 8px;"></i> 
                                                                      <span>{{ trim($r) }}</span>
                                                                  </div>
                                                              @endif
                                                          @endforeach
                                                      </div>
                                                  @endif
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
                    <div class="tab-pane fade {{ $activeTab == 'completed' ? 'show active' : '' }}" id="completed-content" role="tabpanel">
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
                                     <div class="dropdown d-none" id="btnCompletedAction">
                                        <button class="btn btn-info text-white btn-sm px-3 rounded-pill fw-bold shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-file-invoice me-1"></i> Report (<span id="countCompleted">0</span>)
                                        </button>
                                        <ul class="dropdown-menu shadow border-0 rounded-4">
                                            <li><a class="dropdown-item small fw-bold py-2" href="#" onclick="openPrintModal('completed', 'document')"><i class="fa-solid fa-file-export me-2 text-info"></i>Completed Report</a></li>
                                            <li><a class="dropdown-item small fw-bold py-2" href="#" onclick="openPrintModal('completed', 'nik')"><i class="fa-solid fa-users-viewfinder me-2 text-primary"></i>By NIK</a></li>
                                        </ul>
                                    </div>
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
                                                         @php
                                                             $kdaufHist = $item['kdauf'] ?? '';
                                                             $matKdaufHist = $item['mat_kdauf'] ?? '';
                                                             $isMakeStockHist = (strcasecmp($kdaufHist, 'Make Stock') === 0) || (strcasecmp($matKdaufHist, 'Make Stock') === 0);
                                                             $kdposHist = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                                                             $soItemHist = $isMakeStockHist ? $kdaufHist : ($kdaufHist . ($kdposHist ? ' - ' . $kdposHist : ''));
                                                         @endphp
                                                         <div class="text-muted text-xs text-truncate ps-1">
                                                             {{ $soItemHist }}
                                                             <span class="ms-1 fw-bold text-primary" style="font-size: 0.65rem;">(Tak: {{ number_format($item['item_mins'] ?? 0, 2) }} min)</span>
                                                         </div>
                                                         <div class="text-muted text-xs text-truncate ps-1">{{ $item['material'] ?? '' }}</div>
                                                     </div>
                                                     <div class="col-lg-4 text-end">
                                                         <div class="fw-bold text-dark fs-6">{{ $item['assigned_qty'] }} <span class="text-xs text-muted">{{ $item['uom'] ?? '-' }}</span></div>
                                                         
                                                         {{-- Completed doesn't usually need edit, but keeping for consistency --}}
                                                         <button class="btn btn-sm btn-outline-info py-0 px-2 rounded-pill small fw-bold" disabled>
                                                             <i class="fa-solid fa-check-double me-1"></i> Completed
                                                         </button>
                                                     </div>
                                                 </div>
                                                 
                                                 {{-- FULL WIDTH PROGRESS BAR --}}
                                                 {{-- FULL WIDTH PROGRESS BAR --}}
                                                 <div class="d-flex justify-content-end mb-1">
                                                     <span class="text-xs fw-bold text-muted">
                                                         {{ $item['confirmed_qty'] ?? 0 }} {{-- Usually full in completed, but good to check --}}
                                                          @if(($item['remark_qty'] ?? 0) > 0)
                                                              <span class="text-danger"> + {{ $item['remark_qty'] }} (Remark)</span>
                                                          @endif
                                                          / {{ $item['assigned_qty'] }}
                                                     </span>
                                                 </div>
                                                 @php
                                                      $rQty = $item['remark_qty'] ?? 0;
                                                      $aQty = $item['assigned_qty'] > 0 ? $item['assigned_qty'] : 1;
                                                      $confQty = $item['confirmed_qty'] ?? 0;
                                                      $confPct = ($confQty / $aQty) * 100;
                                                      $rPct = ($rQty / $aQty) * 100;
                                                      
                                                      // Handle total > 100 visual cap? Bootstrap progress handles overflow by stacking or capping if using multiple bars.
                                                 @endphp
                                                 <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $confPct }}%"></div>
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $rPct }}%"></div>
                                                 </div>
                                                 @if(!empty($item['remark']))
                                                      <div class="mt-2 text-danger" style="font-size: 0.75rem; font-weight: 600;">
                                                          @foreach(explode(';', $item['remark']) as $r)
                                                              @if(trim($r))
                                                                  <div class="d-flex align-items-start gap-1">
                                                                      <i class="fa-solid fa-circle-exclamation mt-1" style="font-size: 8px;"></i> 
                                                                      <span>{{ trim($r) }}</span>
                                                                  </div>
                                                              @endif
                                                          @endforeach
                                                      </div>
                                                  @endif
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

                        <!-- RECIPIENT CHECKLIST (For Email Log) -->
                        <div id="emailRecipientsContainer" class="d-none mb-4">
                            <h6 class="fw-bold text-muted small mb-2"><i class="fa-solid fa-users-viewfinder me-1"></i> PILIH PENERIMA EMAIL</h6>
                            <div class="card p-3 bg-light border">
                                <div class="mb-2" style="max-height: 150px; overflow-y: auto;">
                                @if(isset($defaultRecipients) && count($defaultRecipients) > 0)
                                    @foreach($defaultRecipients as $email)
                                    <div class="form-check">
                                        <input class="form-check-input email-recipient-cb" type="checkbox" value="{{ $email }}" id="email_{{ $loop->index }}" checked>
                                        <label class="form-check-label small font-monospace" for="email_{{ $loop->index }}">
                                            {{ $email }}
                                        </label>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="text-muted small fst-italic">Tidak ada daftar email default.</div>
                                @endif
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fa-solid fa-plus"></i></span>
                                    <input type="email" id="newRecipientInput" class="form-control" placeholder="Tambah email manual...">
                                    <button class="btn btn-outline-secondary" type="button" id="btnAddEmailManual">Add</button>
                                </div>
                                <div id="manualEmailsList" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">DICETAK OLEH</label>
                                <input type="text" name="printed_by" class="form-control fw-bold" value="{{ session('username') ?? 'User' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">BAGIAN</label>
                                <input type="text" name="department" class="form-control fw-bold" value="{{ $nama_bagian->nama_bagian ?? '-' }}" readonly>
                                <input type="hidden" name="filter_status" value="">
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

    <!-- Sticky Glass Footer for Remark Search -->
    <div class="fixed-bottom p-3" style="z-index: 1040; margin-left: 260px; backdrop-filter: blur(10px); background: linear-gradient(to right, rgba(16, 185, 129, 0.8), rgba(5, 150, 105, 0.8)); border-top: 1px solid rgba(255,255,255,0.2); box-shadow: 0 -4px 20px rgba(0,0,0,0.1);">
        <div class="container-fluid d-flex justify-content-center">
            <div class="input-group shadow rounded-pill overflow-hidden" style="max-width: 600px; border: 1px solid rgba(255,255,255,0.3);">
                <span class="input-group-text bg-white border-0 ps-3 text-success"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="searchRemarkAufnr" class="form-control border-0 shadow-none bg-white text-dark" placeholder="Cari Remark (Input AUFNR)..." style="font-size: 1rem;">
                <button class="btn btn-light px-4 fw-bold text-success" type="button" id="btnSearchRemark">
                    <i class="fa-solid fa-search me-1"></i> Cari
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Search Result -->
    <div class="modal fade" id="remarkSearchModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                <div class="modal-header bg-success text-white border-bottom-0 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <span class="bg-white bg-opacity-25 p-2 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                        Riwayat Remark
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="bg-success text-white px-3 pb-3 small ms-0 mt-0">
                    <div class="bg-white bg-opacity-20 px-3 fw-bold text-dark py-2 rounded d-inline-block">
                        PRO: <span id="searchedAufnr" class="fw-light text-dark font-monospace"></span>
                    </div>
                </div>
                <div class="modal-body p-4 bg-light" id="remarkSearchResultBody" style="min-height: 200px; max-height: 60vh; overflow-y: auto;">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    {{-- MODAL EDIT QTY --}}

    
    {{-- MODAL ADD ITEM --}}
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold text-uppercase fs-6" id="addItemTitle"><i class="fa-solid fa-plus-circle me-2 text-success"></i> Tambah Item ke Dokumen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light p-0">
                    <div class="sticky-top bg-light px-4 pt-4 pb-1 border-bottom shadow-sm" style="z-index: 1020;">
                        {{-- Hidden Elements --}}
                        <input type="hidden" id="add_wi_code">
                        <select id="filter_wc_add" class="d-none"></select>
                        
                        {{-- 1. SEARCH BAR --}}
                        <div class="input-group mb-3 shadow-sm border-0 position-relative">
                            <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="fa-solid fa-search"></i></span>
                            <input type="text" class="form-control border-0 py-2" id="search_available_item" placeholder="Search Part Number, Material, or Name..." onkeyup="fetchAvailableItems()">
                            <button class="btn btn-dark fw-bold px-4" type="button" onclick="fetchAvailableItems()">CARI</button>
                        </div>

                        {{-- 2. DASHBOARD CAPACITIES (ACCORDION) --}}
                        <div class="accordion mb-3 shadow-sm border border-light d-none" id="capacityAccordionWrapper">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="headingCapacity">
                                    <button class="accordion-button collapsed fw-bold text-dark py-2 small bg-white shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCapacity" aria-expanded="false" aria-controls="collapseCapacity" style="border: 1px solid #dee2e6;">
                                        <i class="fa-solid fa-chart-pie me-2 text-primary"></i> Capacity Distribution
                                    </button>
                                </h2>
                                <div id="collapseCapacity" class="accordion-collapse collapse" aria-labelledby="headingCapacity" data-bs-parent="#capacityAccordionWrapper">
                                    <div class="accordion-body p-0">
                                        <div id="childWcDashboard" class="p-3 bg-white">
                                            <!-- Populated by JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3. AVAILABLE ITEMS HEADER --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-uppercase text-muted small tracking-wide mb-0">Item Details & Assignments</h6>
                            <span class="badge bg-secondary text-white rounded-pill px-3">List PRO Available</span>
                        </div>
                    </div>
                    
                    <div class="px-4 py-3">
                        <div id="availableItemsContainer" style="min-height: 200px;">
                            <!-- JS renders item cards here -->
                            <div class="text-center py-5 text-muted invisible">
                                <i class="fa-solid fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Menunggu Pencarian...</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- REMOVE OLD TABLE FOOTER -->
                </div>
            </div>
        </div>
    </div>
    <!-- Email Log Modal -->
<div class="modal fade" id="emailLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-envelope me-2"></i>Kirim Email Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    Report akan mencakup data sesuai filter yang aktif (Tanggal & Pencarian).
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Penerima Email <span class="text-danger">*</span></label>
                    <div class="form-text text-muted mb-2">Pisahkan dengan baris baru (Enter) untuk banyak email.</div>
                    <textarea class="form-control font-monospace" id="emailRecipients" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <div>
                     <button type="button" class="btn btn-outline-secondary" onclick="previewEmailLog()">
                        <i class="fa-solid fa-eye me-1"></i> Preview PDF
                     </button>
                </div>
                <div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-dark" onclick="sendEmailLog()" id="btnSendEmailLog">
                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
            setupModalUI(type, 1, 'document'); // Default to document mode for single print
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        // --- 3. UI SETUP FOR MODAL ---
        function setupModalUI(type, count, mode = 'document') {
            const form = document.getElementById('printForm');
            const alertMsg = document.getElementById('modalAlert');
            const modalTitle = document.getElementById('modalTitle');
            const btnSubmit = document.getElementById('btnSubmitPrint');
            const headerBg = document.getElementById('modalHeaderBg');
            const previewContainer = document.getElementById('previewContainer');
            
            // Sync Hidden Inputs
            const dateInput = document.querySelector('input[name="filter_date"]');
            const searchInput = document.querySelector('input[name="filter_search"]');
            const statusInput = document.querySelector('input[name="filter_status"]'); // Hidden input
            
            const mainDate = document.getElementById('dateInput').value;
            const mainSearch = document.querySelector('input[name="search"]').value;
            const mainStatus = document.querySelector('select[name="status"]').value;

            if(dateInput) dateInput.value = mainDate;
            if(searchInput) searchInput.value = mainSearch;
            if(statusInput) statusInput.value = mainStatus;

            // --- RESET STATE GLOBAL ---
            form.target = "_blank"; // Default back to new tab (PDF)
            form.action = "";      // Clear action
            btnSubmit.onclick = null; // Clear previous event handlers!
            btnSubmit.disabled = false;
            previewContainer.classList.add('d-none'); // Hide preview by default
            const recipientContainer = document.getElementById('emailRecipientsContainer');
            if(recipientContainer) recipientContainer.classList.add('d-none'); // Hide recipients by default
            
            // Manual Email Logic Block (Keep existing if needed or rely on existing initialization)
            
            // Handle Print Modes
            if (mode === 'nik') {
                form.action = "{{ route('wi.print-log-nik', ['kode' => $plantCode]) }}";
                modalTitle.innerText = 'CETAK LOG BY NIK';
                headerBg.style.background = '#6366f1'; // Indigo/Purple
                
                alertMsg.className = 'alert bg-primary-subtle text-primary border-0 fw-bold';
                alertMsg.innerHTML = `<i class="fa-solid fa-users me-2"></i>Mencetak Log History berdasarkan NIK untuk ${count} dokumen.`;
                
                btnSubmit.className = 'btn btn-primary px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-print me-2"></i>Print by NIK';
                return; // Exit early as mode overrides standard types
            }

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
                
                // Show Preview Container & Recipient Container
                if(previewContainer) previewContainer.classList.remove('d-none');
                if(recipientContainer) recipientContainer.classList.remove('d-none');
                
                btnSubmit.className = 'btn btn-dark px-4 rounded-pill fw-bold shadow-sm';
                btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Kirim Email';
                btnSubmit.disabled = true; // Disable until preview loads

                // --- FETCH PREVIEW AJAX ---
                const previewBody = document.getElementById('previewTableBody');
                const countInfo = document.getElementById('previewCountInfo');
                previewBody.innerHTML = ''; 
                
                const filterDate = mainDate;
                const filterSearch = mainSearch;
                const filterStatus = mainStatus;
                
                console.log("Fetching preview with:", { filterDate, filterSearch, filterStatus });

                // Pass status in URL
                fetch(`{{ route('wi.preview-log', ['kode' => $plantCode]) }}?filter_date=${filterDate}&filter_search=${filterSearch}&filter_status=${filterStatus}`)
                .then(response => response.json())
                .then(res => {
                        // ... logic ...
                        console.log("Preview Loaded", res);

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
                                console.log("Email Submit Clicked");
                                
                                // Collect Recipients
                                const checkedRecipients = document.querySelectorAll('.email-recipient-cb:checked');
                                let recipients = [];
                                checkedRecipients.forEach(cb => recipients.push(cb.value));
                                
                                if (recipients.length === 0) {
                                    Swal.fire('Peringatan', 'Pilih minimal satu penerima email.', 'warning');
                                    return;
                                }
    
                                Swal.fire({
                                    title: 'Kirim Email Report?',
                                    text: `Report akan dikirim ke ${recipients.length} alamat email terpilih.`,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Ya, Kirim!',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        btnSubmit.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Mengirim...';
                                        btnSubmit.disabled = true;
                                        
                                        const departmentVal = document.querySelector('input[name="department"]').value;
                                        const printedByVal = document.querySelector('input[name="printed_by"]').value;
    
                                        fetch(`{{ route('wi.email-log', ['kode' => $plantCode]) }}?filter_date=${filterDate}&filter_search=${filterSearch}&filter_status=${filterStatus}`, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                department: departmentVal,
                                                printed_by: printedByVal,
                                                recipients: recipients
                                            })
                                        })
                                        .then(resp => resp.json())
                                        .then(data => {
                                            if(data.success) {
                                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                                    const modalEl = document.getElementById('universalPrintModal');
                                                    const modal = bootstrap.Modal.getInstance(modalEl);
                                                    modal.hide();
                                                });
                                            } else {
                                                Swal.fire('Gagal', data.message, 'error');
                                                btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Kirim Email';
                                                btnSubmit.disabled = false;
                                            }
                                        })
                                        .catch(err => {
                                            console.error(err);
                                            Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
                                            btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Kirim Email';
                                            btnSubmit.disabled = false;
                                        });
                                    }
                                });
                            };
    
                        } else {
                            alertMsg.innerHTML = 'Gagal memuat preview.';
                        }
                })
                .catch(err => { /* ... */ });


            }
        }

        // --- DEBUG EXECUTION ---
        console.log("History Script: Checkpoint 1 - Modal Setup Complete");

        // Serialize Mappings with Safety
        let wcMappings = [];
        try {
            wcMappings = @json($workcenterMappings ?? []);
        } catch(e) { console.error("Error parsing wcMappings", e); }
        
        console.log("History Script: Checkpoint 2 - wcMappings Loaded", wcMappings.length);

        let wiCapacityMap = [];
        try {
             wiCapacityMap = @json($wiCapacityMap ?? []);
        } catch(e) { console.error("Error parsing wiCapacityMap", e); }

        let availableItemsMap = {}; // Cache for validation
        
        // Helper: Calculate Minutes
        function calculateItemMinutes(vgw01, vge01, qty) {
            const val = parseFloat(vgw01) || 0;
            const q = parseFloat(qty) || 0;
            const unit = (vge01 || '').toUpperCase();
            
            if (val > 0 && q > 0) {
                let totalRaw = val * q;
                if (unit === 'S' || unit === 'SEC') return totalRaw / 60;
                else if (unit === 'MIN') return totalRaw;
                else if (unit === 'H' || unit === 'HUR') return totalRaw * 60;
                else return totalRaw * 60; // Default to Hours? No, usually MIN or H. Assume H fallback if unknown? Let's stick to knowns.
            }
            return 0;
        }

        // --- 3.5 ADD / REMOVE ITEM LOGIC (BATCH SPLIT SUPPORT) ---
        let currentAddWiCode = '';
        window.hasAddedItems = false; // Track changes
        
        // Initialize Modal Listener for Reload on Close
        document.addEventListener('DOMContentLoaded', function() {
            const addItemModalEl = document.getElementById('addItemModal');
             if(addItemModalEl) {
                addItemModalEl.addEventListener('hidden.bs.modal', function () {
                    if(window.hasAddedItems) {
                        Swal.fire({
                            title: 'Memuat ulang...',
                            timer: 1000,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        }).then(() => location.reload());
                    }
                });
            }
        });

        window.openAddItemModal = function(wiCode, wcCode) {
            currentAddWiCode = wiCode;
            window.hasAddedItems = false; // Reset flag
            availableItemsMap = {};
            
            const addWiInput = document.getElementById('add_wi_code');
            const addTitle = document.getElementById('addItemTitle');
            const searchInput = document.getElementById('search_available_item'); 
            
            if(addWiInput) addWiInput.value = wiCode;
            if(addTitle) addTitle.innerText = 'Dokumen: ' + wiCode;
            if(searchInput) searchInput.value = ''; // Clear search
            
            // --- POPULATE CHILD WC DASHBOARD ---
            const dashboardContainer = document.getElementById('childWcDashboard');
            if(dashboardContainer) {
                
                dashboardContainer.innerHTML = '';
                const parentCode = wcCode.toUpperCase();
                const children = wcMappings.filter(m => (m.wc_induk || '').toUpperCase() === parentCode);
                
                if(children.length > 0) {
                    let html = `<div class="row g-2">`;
                        
                    children.forEach(c => {
                        const childCode = c.workcenter;
                        const childName = c.nama_workcenter || '';
                        const ref = refWorkcentersData[childCode];
                        let maxMins = 570; 
                        if(ref && ref.kapaz) maxMins = parseFloat(ref.kapaz) || 570;
                        
                        html += `
                        <div class="col-md-6 col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs fw-bold text-dark text-truncate" style="max-width: 65%;">
                                    ${childCode} ${childName ? '- ' + childName : ''}
                                </span>
                                <span class="text-xs fw-bold text-muted" id="dashboard_val_${childCode}" data-max="${maxMins}">${(0).toLocaleString('id-ID', {minimumFractionDigits: 2})} / ${maxMins.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2})} Min</span>
                            </div>
                            <div class="progress mb-2" style="height: 5px;">
                                <div class="progress-bar bg-success" id="dashboard_bar_${childCode}" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>`;
                    });
                    html += '</div>';
                    dashboardContainer.innerHTML = html;
                    document.getElementById('capacityAccordionWrapper').classList.remove('d-none');
                } else {
                    document.getElementById('capacityAccordionWrapper').classList.add('d-none');
                }
            }
            
            const sel = document.getElementById('filter_wc_add');
            sel.innerHTML = ''; 
            const target = wcCode.toUpperCase();
            const o = document.createElement('option');
            o.value = target;
            o.innerText = target;
            sel.appendChild(o);
            sel.value = target;
            
            // Fetch Employees FIRST to ensure dropdowns are populated
            fetchEmployees().then(() => {
                fetchAvailableItems();
                const modalEl = document.getElementById('addItemModal');
                if(modalEl) new bootstrap.Modal(modalEl).show();
            });
        };

        let employeesList = []; // Init empty
        
        // Fetch Function
        window.fetchEmployees = function() {
            return new Promise((resolve, reject) => {
                if (employeesList.length > 0) {
                    resolve(); // Already loaded
                    return;
                }
                
                // For now just fetch.
                fetch('{{ route("wi.get-employees", ["kode" => $plantCode]) }}')
                    .then(res => res.json())
                    .then(res => {
                        if(res.success) {
                            employeesList = res.data;
                            resolve();
                        } else {
                            console.error('Failed to load employees:', res.message);
                            resolve();
                        }
                    })
                    .catch(e => {
                        console.error('Fetch Employees Error:', e);
                        resolve();
                    });
            });
        };
        const workcentersData = @json($workcenters ?? []); 
        const refWorkcentersData = @json($refWorkcenters ?? []);

        function getOperatorOptions(wcCode, excludeNiks = []) {
            const targetWc = (wcCode || '').toUpperCase();
            let opts = '<option value="">-- Pilih Operator --</option>';
            let count = 0;
            
            employeesList.forEach(e => {
                const nik = e.pernr;
                const name = e.stext; 
                
                if (excludeNiks.includes(nik.toString())) {
                     opts += `<option value="${nik}" disabled style="color:#ccc;">${nik} - ${name} (Terpakai)</option>`;
                } else {
                        opts += `<option value="${nik}" data-name="${name}">${nik} - ${name}</option>`;
                        count++;
                    }
            });
            
            if (count === 0 && excludeNiks.length > 0) {
                 opts += '<option disabled>Semua operator tersedia telah dialokasikan</option>';
            } else if (count === 0) {
                 opts += '<option disabled>Tidak ada operator di WC ini</option>';
            }
            return opts;
        }
        
        function getUsedNiksInBatch(container, excludeRow) {
            let used = [];
            if(!container) return used;
            container.querySelectorAll('.split-row-item').forEach(r => {
                if(r !== excludeRow) {
                    const sel = r.querySelector('.emp-select');
                    if(sel && sel.value) used.push(sel.value.toString());
                }
            });
            return used;
        }

        window.filterOperators = function(wcSelect, targetId) {
            const wcCode = wcSelect.value;
            const targetSelect = document.getElementById(targetId);
            
            if(targetSelect) { 
                const row = wcSelect.closest('.split-row-item');
                let excludeNiks = [];
                if(row) {
                    const container = row.parentElement;
                    excludeNiks = getUsedNiksInBatch(container, row);
                    
                    const qtyInput = row.querySelector('.qty-input');
                    const empSelect = row.querySelector('.emp-select');
                    
                    if(wcCode) {
                        if(qtyInput) qtyInput.disabled = false;
                    } else {
                         if(qtyInput) { qtyInput.disabled = true; qtyInput.value = ''; }
                    }
                }
            }
        };

        function getWcFamilyOptions(wcCode, container = null) {
            const target = (wcCode || '').toUpperCase();
            const asChild = wcMappings.find(m => (m.workcenter || '').toUpperCase() === target);
            const children = wcMappings.filter(m => (m.wc_induk || '').toUpperCase() === target);
            const isParent = children.length > 0;
            
            // Check Usage
            let batchUsage = {};
            if(container) {
                container.querySelectorAll('.split-row-item').forEach(r => {
                    const wcS = r.querySelector('.child-select');
                    const nikS = r.querySelector('.emp-select');
                    if(wcS && nikS && nikS.value) {
                         const w = wcS.value;
                         if(!batchUsage[w]) batchUsage[w] = 0;
                         batchUsage[w]++;
                    }
                });
            }

            const buildOpt = (code, name) => {
                let capLabel = '';
                const wcInfo = workcentersData.find(w => w.workcenter_code === code) || {};
                let k = parseFloat(wcInfo.kapaz) || 0;
                
                if(k === 0) {
                }

                capLabel = ` (Cap: ${k.toLocaleString()} Min)`;
                
                let totalOps = 0;
                employeesList.forEach(e => {
                    if((e.arbpl || '').toUpperCase() === code) totalOps++;
                });
                
                let used = batchUsage[code] || 0;
                let disabled = (totalOps > 0 && used >= totalOps);
                let disAttr = disabled ? 'disabled' : '';
                let disText = disabled ? ' (Penuh)' : '';

                return `<option value="${code}" ${disAttr}>${name}${capLabel}${disText}</option>`;
            };

            let opts = [];
            if (isParent) {
                 children.forEach(c => {
                   if(c.workcenter) opts.push(buildOpt(c.workcenter, c.workcenter + ' - ' + c.nama_workcenter));
                 });
            } else if (asChild) {
                 opts.push(buildOpt(target, target + ' - ' + asChild.nama_workcenter));
            } else {
                 opts.push(buildOpt(target, target));
            }
            return opts.join('');
        }

        window.fetchAvailableItems = function() {
            const wc = document.getElementById('filter_wc_add').value;
            const searchInput = document.getElementById('search_available_item');
            const searchTerm = searchInput ? searchInput.value : '';
            
            const container = document.getElementById('availableItemsContainer');
            
            if(container) container.innerHTML = '<div class="text-center p-5"><i class="fa-solid fa-spinner fa-spin text-primary fa-2x mb-2"></i><p class="text-muted">Memuat Data...</p></div>';
            
            const url = `{{ route('wi.available-items', ['kode' => $plantCode]) }}?workcenter=${wc}&search=${encodeURIComponent(searchTerm)}`;
            
            fetch(url)
                .then(res => res.json())
                .then(res => {
                    const items = res.data;
                    availableItemsMap = {};
                    
                    if(items.length === 0) {
                        if(container) container.innerHTML = '<div class="text-center p-5 border rounded-3 bg-white text-muted"><i class="fa-solid fa-box-open fa-2x mb-3 opacity-25"></i><p>Tidak ada item tersedia atau tidak cocok dengan pencarian.</p></div>';
                        return;
                    }

                    let html = '';
                    items.forEach(item => {
                        const itemKey = `${item.aufnr}_${item.vornr}`;
                        availableItemsMap[itemKey] = item;
                        
                        let displayUom = (item.uom || '-').toUpperCase();
                        if (displayUom === 'ST') displayUom = 'PC';

                        html += `
                        <div class="card mb-3 border shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-2 px-2">${item.aufnr}</span>
                                        <span class="fw-bold text-dark text-truncate" style="max-width: 400px;" title="${item.description}">${item.description}</span>
                                    </div>
                                    <span class="text-success fw-bold small"><i class="fa-solid fa-check-circle me-1"></i> Available: <span id="max_qty_${itemKey}">${parseFloat(item.available_qty)}</span> ${displayUom}</span>
                                </div>
                                <div class="small text-muted mb-0 ms-1">
                                    <span><i class="fa-solid fa-cube me-1"></i> ${item.material}</span>
                                    <span class="mx-2">•</span>
                                    <span class="fst-italic"><i class="fa-solid fa-industry me-1"></i> ${item.workcenter}</span>
                                </div>
                            </div>
                            <div class="card-body bg-light p-3">
                                <!-- Container for Split Rows -->
                                <div id="split_container_${itemKey}"></div>
                                
                                <!-- Footer Actions -->
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <button class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3" onclick="addSplitRow('${itemKey}')">
                                        <i class="fa-solid fa-code-branch me-1"></i> Split / Tambah Baris
                                    </button>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-end lh-1">
                                            <div class="text-xs text-muted fw-bold">TOTAL ASSIGNED</div>
                                            <div class="fw-bold text-dark" id="total_assign_${itemKey}">0</div>
                                        </div>
                                        <button class="btn btn-primary btn-sm fw-bold px-4 rounded-pill shadow-sm" id="btn_save_${itemKey}" onclick="submitBatchItem('${item.aufnr}', '${item.vornr}', this)">
                                            <i class="fa-solid fa-save me-1"></i> Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        `;
                    });
                    
                    if(container) container.innerHTML = html;

                    // Initialize first row for each item
                    items.forEach(item => {
                         addSplitRow(`${item.aufnr}_${item.vornr}`);
                    });
                })
                .catch(err => {
                    console.error(err);
                    if(container) container.innerHTML = '<div class="text-center text-danger p-5">Gagal memuat data. Silakan coba lagi.</div>';
                });
        };
        window.updateDashboardUsage = function() {
            let usageMap = {};
            const rows = document.querySelectorAll('#addItemModal .split-row-item');
            rows.forEach(r => {
                const wcS = r.querySelector('.child-select');
                const timeI = r.querySelector('.time-input');
                if(wcS && timeI && wcS.value) {
                    const w = wcS.value;
                    const t = parseFloat(timeI.value) || 0;
                    if(!usageMap[w]) usageMap[w] = 0;
                    usageMap[w] += t;
                }
            });
            
            for (const [wc, ref] of Object.entries(refWorkcentersData)) {
                const el = document.getElementById(`dashboard_val_${wc}`);
                if(el) {
                    const max = parseFloat(el.getAttribute('data-max')) || 0;
                    const used = usageMap[wc] || 0;
                    const pct = max > 0 ? (used / max) * 100 : 0;
                    el.innerText = `${used.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} / ${max.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} Min`;
                    
                    const elBar = document.getElementById(`dashboard_bar_${wc}`);
                    if(elBar) {
                        elBar.style.width = `${Math.min(pct, 100)}%`;
                        if(used > max) {
                            elBar.classList.remove('bg-success');
                            elBar.classList.add('bg-danger');    
                            el.classList.add('text-danger');     
                        } else {
                            elBar.classList.remove('bg-danger');
                            elBar.classList.add('bg-success');
                            el.classList.remove('text-danger');
                        }
                    }
                }
            }
        };

        window.updateChildCapacity = function(row, itemKey) {
            if(!row) return;
            const qtyInput = row.querySelector('.qty-input');
            const wcSelect = row.querySelector('.child-select');
            
            if(!qtyInput || !wcSelect) return;
            
            const wcCode = wcSelect.value;
            const qty = parseFloat(qtyInput.value) || 0;
            const item = availableItemsMap[itemKey]; 
            
            if(item) {
                 let takTime = parseFloat(item.vgw01) || 0;
                 if(item.vge01 === 'S') {
                    takTime = takTime / 60; 
                 }
                 const totalReqMins = takTime * qty;
                 
                 const timeInput = row.querySelector('.time-input');
                 if(timeInput) timeInput.value = totalReqMins.toFixed(1);
                 
                 updateDashboardUsage();
            }
        };

        window.addSplitRow = function(itemKey) {
            const container = document.getElementById(`split_container_${itemKey}`);
            if(!container) return;

            const item = availableItemsMap[itemKey];
            if(!item) return;

            const rowCount = container.children.length;
            const uniqueId = `${itemKey}_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`;
            
            const wcOptsHtml = getWcFamilyOptions(item.workcenter, container);
            
            let wcSelectHtml = `<select class="form-select form-select-sm child-select" 
                                        id="wc_${uniqueId}" 
                                        data-row-id="${uniqueId}">
                                    <option value="">- Induk/None -</option>
                                    ${wcOptsHtml}
                                </select>`;
            
            const excludeNiks = getUsedNiksInBatch(container, null);
            const opOptions = getOperatorOptions(null, excludeNiks);
            
            let empSelectHtml = `<select class="form-select form-select-sm emp-select" id="nik_${uniqueId}">${opOptions}</select>`;

            const rowDiv = document.createElement('div');
            rowDiv.className = 'row g-2 align-items-end mb-2 split-row-item border-bottom pb-2'; 
            rowDiv.id = `row_${uniqueId}`;
            rowDiv.innerHTML = `
                <div class="col-md-3">
                     <label class="form-label text-xs fw-bold text-muted mb-0">Operator</label>
                     ${empSelectHtml}
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs fw-bold text-muted mb-0">Sub-WC</label>
                    ${wcSelectHtml}
                </div>
                <div class="col-md-3">
                     <label class="form-label text-xs fw-bold text-muted mb-0">Qty</label>
                     <div class="input-group input-group-sm">
                        <input type="number" class="form-control fw-bold qty-input" id="qty_${uniqueId}" 
                           placeholder="0" step="1" min="1" disabled>
                     </div>
                </div>
                <div class="col-md-3">
                     <label class="form-label text-xs fw-bold text-muted mb-0">Time Req</label>
                     <input type="text" class="form-control form-control-sm fw-bold time-input bg-white" readonly value="0">
                </div>
                <div class="col-md-1">
                     ${rowCount > 0 ? `<button class="btn btn-outline-danger btn-sm w-100 remove-btn"><i class="fa-solid fa-trash"></i></button>` : ''}
                </div>
            `;
            container.appendChild(rowDiv);
            
            const wcSel = rowDiv.querySelector('.child-select');
            if(wcSel) {
                wcSel.addEventListener('change', function() { 
                    filterOperators(this, `nik_${uniqueId}`); 
                    updateChildCapacity(this.closest('.split-row-item'), itemKey);
                });
            }

            const qtyInp = rowDiv.querySelector('.qty-input');
            if(qtyInp) {
                qtyInp.addEventListener('input', function() {
                    enforceBatchLimit(itemKey, this);
                    updateChildCapacity(this.closest('.split-row-item'), itemKey);
                });
            }

            const remBtn = rowDiv.querySelector('.remove-btn');
            if(remBtn) {
                remBtn.addEventListener('click', function() {
                    removeSplitRow(uniqueId, itemKey);
                });
            }
            
            updateTotalBatch(itemKey);
        };

        window.removeSplitRow = function(uniqueId, itemKey) {
            const row = document.getElementById(`row_${uniqueId}`);
            if(row) {
                row.remove();
                updateTotalBatch(itemKey);
                updateDashboardUsage();
            }
        };


        window.enforceBatchLimit = function(itemKey, currentInput) {
             const maxSpan = document.getElementById(`max_qty_${itemKey}`);
             if(!maxSpan) return;
             const max = parseFloat(maxSpan.innerText.replace(/,/g, '')) || 0;
             
             const container = document.getElementById(`split_container_${itemKey}`);
             let otherTotal = 0;
             container.querySelectorAll('.qty-input').forEach(inp => {
                 if(inp !== currentInput) {
                     otherTotal += (parseFloat(inp.value) || 0);
                 }
             });
             
             const remaining = max - otherTotal;
             let val = parseFloat(currentInput.value);
             
             if(val > remaining) {
                 currentInput.value = Math.max(0, remaining); 
                 
                 const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                 });
                 Toast.fire({ icon: 'warning', title: 'Mencapai Batas Qty Available' });
             }
             
             updateTotalBatch(itemKey);
        };

        window.updateTotalBatch = function(itemKey) {
            const container = document.getElementById(`split_container_${itemKey}`);
            const totalDisplay = document.getElementById(`total_assign_${itemKey}`);
            const maxSpan = document.getElementById(`max_qty_${itemKey}`);
            
            if(!container || !totalDisplay) return;

            let total = 0;
            const inputs = container.querySelectorAll('.qty-input');
            inputs.forEach(inp => total += (parseFloat(inp.value) || 0));
            
            totalDisplay.innerText = total.toLocaleString(); 
            
            const max = parseFloat(maxSpan.innerText.replace(/,/g, '')) || 0;
            if(total > max + 0.0001) {
                 totalDisplay.className = 'fw-bold text-danger';
            } else {
                 totalDisplay.className = 'fw-bold text-dark';
            }
        };

        window.submitBatchItem = function(aufnr, vornr, btn) {
            const itemKey = `${aufnr}_${vornr}`;
            const container = document.getElementById(`split_container_${itemKey}`);
            if(!container) return;

            // Collect Data
            const rows = container.querySelectorAll('.split-row-item');
            let itemsPayload = [];
            let totalQty = 0;
            let isValid = true;
            let errorMsg = '';
            
            rows.forEach(row => {
                const wcSel = row.querySelector('.child-select');
                const nikSel = row.querySelector('.emp-select');
                const qtyInp = row.querySelector('.qty-input');
                
                const wc = wcSel.value;
                const nik = nikSel.value;
                const qtyView = qtyInp.value;
                const qty = parseFloat(qtyView) || 0;
                
                if(!wc) { isValid = false; errorMsg = 'Pilih Workcenter untuk semua baris.'; return; }
                if(!nik) { isValid = false; errorMsg = 'Pilih Operator untuk semua baris.'; return; }
                if(qty < 1) { isValid = false; errorMsg = 'Quantity minimal 1.'; return; }
                
                const name = nikSel.options[nikSel.selectedIndex].getAttribute('data-name');
                
                itemsPayload.push({
                    aufnr: aufnr,
                    vornr: vornr,
                    qty: qty,
                    nik: nik,
                    name: name,
                    target_workcenter: wc
                });
                totalQty += qty;
            });

            if(!isValid) return Swal.fire('Error', errorMsg, 'error');
            if(itemsPayload.length === 0) return Swal.fire('Error', 'Tidak ada item untuk disimpan.', 'error');
            
            const max = availableItemsMap[itemKey].available_qty;
            if(totalQty > max + 0.0001) {
                return Swal.fire('Over Capacity', `Total quantity (${totalQty}) melebihi sisa tersedia (${max}).`, 'warning');
            }

            const capInfo = wiCapacityMap[currentAddWiCode] || { max_mins: 0, used_mins: 0 };
            const itemData = availableItemsMap[itemKey];
            let additionalMins = 0;
            if(itemData) {
                additionalMins = calculateItemMinutes(itemData.vgw01, itemData.vge01, totalQty);
            }
            const projected = parseFloat(capInfo.used_mins) + additionalMins;
            const maxCap = parseFloat(capInfo.max_mins);
            
            const executeBatch = () => {
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                
                fetch('{{ route("wi.add-item-batch") }}', {
                    method: 'POST', 
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        wi_code: currentAddWiCode,
                        items: itemsPayload
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        window.hasAddedItems = true;
                        
                        let newUsed = 0; 
                        let newMax = 0;
                        if(wiCapacityMap && wiCapacityMap[currentAddWiCode]) {
                            wiCapacityMap[currentAddWiCode].used_mins += additionalMins;
                            newUsed = wiCapacityMap[currentAddWiCode].used_mins;
                            newMax = wiCapacityMap[currentAddWiCode].max_mins;
                        }
                        updateCapacityBarUI(currentAddWiCode, newUsed, newMax);

                        btn.className = 'btn btn-success btn-sm fw-bold px-4 rounded-pill shadow-sm';
                        btn.innerHTML = '<i class="fa-solid fa-check"></i> Tersimpan';
                        
                        // Disable inputs
                        container.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
                        
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: false
                        });
                        Toast.fire({ icon: 'success', title: 'Berhasil Disimpan' });
                        
                        // NO RELOAD HERE. Reload happens when modal closes (checked by window.hasAddedItems).

                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    Swal.fire('Error', err.message || 'System Error', 'error');
                });
            };

            if (maxCap > 0 && projected > maxCap) {
                return Swal.fire({
                    title: 'Over Capacity Error',
                    html: `Total penambahan ini (${additionalMins.toFixed(1)} min) akan melebihi kapasitas available!<br>Batas: ${maxCap} Min.`,
                    icon: 'error'
                });
            } else {
                executeBatch();
            }
        };

        function updateCapacityBarUI(wiCode, used, max) {
            const cards = document.querySelectorAll('.wi-item-card');
            cards.forEach(card => {
                const header = card.querySelector('.card-header-area h6');
                if(header && header.innerText.trim() === wiCode) {
                    const progress = card.querySelector('.progress-bar');
                    const textLabel = card.querySelector('.d-flex.justify-content-between.text-xs.fw-bold.text-muted span:last-child');
                    if(progress && textLabel) {
                        const pct = max > 0 ? (used / max) * 100 : 0;
                        progress.style.width = Math.min(pct, 100) + '%';
                        progress.className = pct > 100 ? 'progress-bar bg-danger' : 'progress-bar bg-primary';
                        textLabel.innerText = `${used.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} / ${max.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} Min`;
                    }
                }
            });
        }


        window.confirmRemoveItem = function(wiCode, aufnr, vornr, nik, qty) {
            Swal.fire({
                title: 'Hapus Item dari WI?',
                text: `Item spesifik ini (PRO ${aufnr} + NIK ${nik} + Qty ${qty}) akan dihapus.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route("wi.remove-item") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            wi_code: wiCode,
                            aufnr: aufnr,
                            vornr: vornr,
                            nik: nik,
                            qty: qty
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                             Swal.fire('Terhapus!', data.message, 'success').then(() => location.reload());
                        } else {
                             Swal.fire('Gagal', data.message, 'error');
                        }
                    })
                    .catch(e => Swal.fire('Error', 'Gagal menghapus item.', 'error'));
                }
            });
        };

        window.openPrintModal = function(type, mode = 'document') {
            console.log("Open Print Modal Triggered", type, mode);

            // Handle Legacy Log Type (Export Log Button)
            if (type === 'log') {
               const modalEl = document.getElementById('emailLogModal');
               if(modalEl) {
                   const field = document.getElementById('emailRecipients');
                   if(field && !field.value) {
                       const defaults = @json($defaultRecipients ?? []);
                       field.value = defaults.join('\n');
                   }
                   new bootstrap.Modal(modalEl).show();
               }
               return;
            }

            // 1. Collect Checked Items based on Type
            let ids = [];
            let selector = '';
            if (type === 'active') selector = '.cb-active:checked';
            else if (type === 'expired') selector = '.cb-expired:checked';
            else if (type === 'completed') selector = '.cb-completed:checked';
            else if (type === 'inactive') selector = '.cb-inactive:checked';
            
            if (selector) {
                document.querySelectorAll(selector).forEach(c => ids.push(c.value));
            }

            if(ids.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal satu dokumen untuk dicetak.', 'warning');
                return;
            }

            // 2. Setup Modal
            const modalEl = document.getElementById('universalPrintModal');
            const inputCodes = document.getElementById('inputWiCodes');
            inputCodes.value = ids.join(',');

            setupModalUI(type, ids.length, mode); // Pass mode
            
            // 3. Show Modal
            new bootstrap.Modal(modalEl).show();
        };

        window.previewEmailLog = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const date = urlParams.get('date') || '';
            const search = urlParams.get('search') || '';
            const status = urlParams.get('status') || '';
            
            const url = `{{ route('wi.preview-log', ['kode' => $plantCode]) }}?filter_date=${encodeURIComponent(date)}&filter_search=${encodeURIComponent(search)}&filter_status=${encodeURIComponent(status)}`;
            window.open(url, '_blank');
        };

        window.sendEmailLog = function() {
            const recipients = document.getElementById('emailRecipients').value;
            if(!recipients.trim()) {
                return Swal.fire('Error', 'Masukkan setidaknya satu email penerima.', 'error');
            }

            const btn = document.getElementById('btnSendEmailLog');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

            const urlParams = new URLSearchParams(window.location.search);
            const date = urlParams.get('date') || '';
            const search = urlParams.get('search') || '';
            const status = urlParams.get('status') || '';

            fetch('{{ route("wi.email-log", ["kode" => $plantCode]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    filter_date: date,
                    filter_search: search,
                    filter_status: status,
                    recipients: recipients
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                
                if(data.success) {
                    Swal.fire('Berhasil', data.message, 'success');
                    const modalEl = document.getElementById('emailLogModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if(modal) modal.hide();
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan saat mengirim email.', 'error');
            });
        };

        window.printSingle = function(ids) {
            // Form submit hidden
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("wi.print-single") }}';
            form.target = '_blank';
            
            const csrf = document.createElement('input'); 
            csrf.type='hidden'; csrf.name='_token'; csrf.value='{{ csrf_token() }}';
            form.appendChild(csrf);

            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'wi_codes'; input.value = JSON.stringify(ids);
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

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
        window.openEditQtyModal = function(wiCode, aufnr, description, assignedQty, orderQty, uom, vgw01, vge01, nik, vornr, maxCapacity, currentTotalUsed, currentItemLoad) {
            // Populate Modal Fields directly
            const inputWiCode = document.getElementById('modalWiCode');
            const inputAufnr = document.getElementById('modalAufnr');
            const inputDesc = document.getElementById('modalDesc'); 
            const inputNewQty = document.getElementById('modalNewQty');
            const displayMaxQtyDiv = document.getElementById('displayMaxQty');
            const inputMaxQtyHidden = document.getElementById('modalMaxQtyDisplay');
            const displayAufnrDiv = document.getElementById('displayAufnr');
            
            // New Fields (Industrial Redesign)
            const inputVgw01 = document.getElementById('modalVgw01');
            const displayTotalTime = document.getElementById('modalTotalTime');
            const warningAlert = document.getElementById('modalTimeWarning');
            const progressBar = document.getElementById('capacityProgressBar');
            const progressText = document.getElementById('capacityPercentText');

            if(inputWiCode) inputWiCode.value = wiCode;
            if(inputAufnr) inputAufnr.value = aufnr;
            if(inputDesc) inputDesc.innerText = description; 
            if(displayAufnrDiv) displayAufnrDiv.innerText = aufnr;
            if(inputNewQty) inputNewQty.value = assignedQty;
            
            // Format Max Qty
            if(displayMaxQtyDiv) displayMaxQtyDiv.innerText = parseFloat(orderQty);
            if(inputMaxQtyHidden) inputMaxQtyHidden.value = orderQty;
            
            // Populate NIK & VORNR
            if(document.getElementById('modalNik')) document.getElementById('modalNik').value = nik;
            if(document.getElementById('modalVornr')) document.getElementById('modalVornr').value = vornr;
            
            // Store VGW01 & Calc Factor
            let timeFactor = 1;
            let rawVgw = parseFloat(vgw01) || 0;
            if(vge01 && (vge01.toUpperCase() === 'S' || vge01.toUpperCase() === 'SEC')) {
                timeFactor = 1/60;
            }
            if(inputVgw01) inputVgw01.value = rawVgw * timeFactor; 
            
            // Capacity Logic
            const maxCapValue = parseFloat(maxCapacity) || 570; 
            const totalUsedValue = parseFloat(currentTotalUsed) || 0;
            const itemLoadValue = parseFloat(currentItemLoad) || 0;
            const baseLoad = Math.max(0, totalUsedValue - itemLoadValue); 

            // --- UNIT HANDLING ---
            const modalUnitText = document.getElementById('modalUnitText');
            let displayUnit = (uom || '').toUpperCase();
            
            // 1. Normalize Display
            if(displayUnit === 'ST') displayUnit = 'PC';
            if(modalUnitText) modalUnitText.innerText = `UNIT: ${displayUnit}`;
            
            // 2. Set Input Step (Integer vs Decimal)
            if(inputNewQty) {
                if(displayUnit === 'PC' || displayUnit === 'SER' || (uom||'').toUpperCase() === 'ST') {
                    inputNewQty.step = "1";
                } else {
                    inputNewQty.step = "any";
                }
            } 

            // Update Max Text
            if(document.getElementById('modalMaxCapText')) document.getElementById('modalMaxCapText').innerText = maxCapValue.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            
            // Function to Update Capacity UI
            const updateCapacity = (qty) => {
                const timePerUnit = parseFloat(inputVgw01.value) || 0;
                const newItemTime = qty * timePerUnit;
                
                // Calculate Projected Total
                const projectedTotal = baseLoad + newItemTime;
                
                if(displayTotalTime) {
                   displayTotalTime.innerText = newItemTime.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 4 });
                }

                // Progress Bar Logic (TOTAL LOAD vs MAX)
                const maxCap = maxCapValue;
                let percent = maxCap > 0 ? (projectedTotal / maxCap) * 100 : 0;
                if(percent > 100) percent = 100;

                if(progressBar) {
                    progressBar.style.width = percent + '%';
                    progressBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
                    
                    if(percent < 70) progressBar.classList.add('bg-success');
                    else if(percent < 95) progressBar.classList.add('bg-warning');
                    else progressBar.classList.add('bg-danger');
                }
                
                if(progressText) {
                    // Show percentage of TOTAL load
                    progressText.innerText = percent.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%';
                }

                // Update Max Cap Text to show usage
                if(document.getElementById('modalMaxCapText')) {
                    document.getElementById('modalMaxCapText').innerText = `${projectedTotal.toLocaleString('id-ID', {maximumFractionDigits: 2})} / ${maxCapValue.toLocaleString('id-ID', {maximumFractionDigits: 2})}`;
                }
                
                // Overload Warning
                const qtyWrapper = document.getElementById('qtyInputWrapper');
                const errorMsg = document.getElementById('qtyErrorMsg');
                const btnSave = document.getElementById('btnSaveQty');

                if(projectedTotal > maxCap) {
                        if(qtyWrapper) {
                             qtyWrapper.classList.remove('border-primary', 'bg-white');
                             qtyWrapper.classList.add('border-danger', 'bg-danger', 'bg-opacity-10');
                         }
                         if(errorMsg) {
                             errorMsg.innerText = 'EXCEEDS DAILY CAPACITY!';
                             errorMsg.classList.remove('d-none');
                         }
                         if(btnSave) btnSave.disabled = true; // STRICT BLOCKING
                } else {
                        // Reset capacity warning (but don't clear qty warning if exists)
                         if(qtyWrapper && !inputNewQty.classList.contains('text-danger')) { // Only reset if not qty error
                             qtyWrapper.classList.remove('border-danger', 'bg-danger', 'bg-opacity-10');
                             qtyWrapper.classList.add('border-primary', 'bg-white');
                         }
                         if(errorMsg && errorMsg.innerText === 'EXCEEDS DAILY CAPACITY!') {
                             errorMsg.classList.add('d-none');
                         }
                }

                return connectedTotalTime = projectedTotal; // Return projected total
            };

            // Initial Calc
            updateCapacity(parseFloat(assignedQty) || 0);
            
            // Validation Logic
            const btnSave = document.getElementById('btnSaveQty');
            const errorMsg = document.getElementById('qtyErrorMsg');
            const qtyWrapper = document.getElementById('qtyInputWrapper');
            const maxLimit = parseFloat(orderQty);

            if(inputNewQty) {
                const performValidation = (val) => {
                    // Update Capacity UI First
                    updateCapacity(val);

                    // Re-fetch elements to ensure active scope
                    const btnSaveInside = document.getElementById('btnSaveQty');
                    const errorContainer = document.getElementById('qtyErrorContainer');
                    const errorText = document.getElementById('qtyErrorText');

                    let rangeError = false;

                    // 2. Validate Input Range (Cannot be 0 or less)
                    if(val <= 0) {
                        rangeError = true;
                        inputNewQty.classList.add('text-danger');
                        if(errorContainer && errorText) {
                             errorText.innerText = 'Qty Harus Lebih dari 0!';
                             errorContainer.classList.remove('d-none');
                        }
                        if(btnSaveInside) btnSaveInside.disabled = true;
                    }
                    // 3. Max Qty Hard Limit Check (Order Qty)
                    else if(val > maxLimit) {
                         rangeError = true;
                         inputNewQty.classList.add('text-danger');
                         if(qtyWrapper) {
                             qtyWrapper.classList.remove('border-primary', 'bg-white');
                             qtyWrapper.classList.add('border-danger', 'bg-danger', 'bg-opacity-10');
                         }
                         if(errorContainer && errorText) {
                             errorText.innerText = 'EXCEEDS QUANTITY ORDER!';
                             errorContainer.classList.remove('d-none');
                         }
                         if(btnSaveInside) btnSaveInside.disabled = true;
                    }

                    if (!rangeError) {
                         inputNewQty.classList.remove('text-danger');
                         if(errorContainer) errorContainer.classList.add('d-none'); // Hide Alert

                         // Check if Capacity Error exists (updateCapacity sets it internally)
                         const capError = (errorMsg && errorMsg.innerText === 'EXCEEDS DAILY CAPACITY!' && !errorMsg.classList.contains('d-none'));
                         
                         if(!capError) {
                             if(qtyWrapper) {
                                qtyWrapper.classList.remove('border-danger', 'bg-danger', 'bg-opacity-10');
                                qtyWrapper.classList.add('border-primary', 'bg-white');
                             }
                             if(errorMsg) errorMsg.classList.add('d-none');
                             
                             // Enable Button Only if No Errors
                             if(btnSaveInside) btnSaveInside.disabled = false;
                         }
                    }
                };

                // Reset State
                inputNewQty.classList.remove('is-invalid', 'text-danger'); 
                if(qtyWrapper) {
                    qtyWrapper.classList.remove('border-danger', 'bg-danger', 'bg-opacity-10');
                    qtyWrapper.classList.add('border-primary', 'bg-white');
                }
                if(errorMsg) errorMsg.classList.add('d-none');
                
                // Bind Handler
                inputNewQty.oninput = function() {
                    performValidation(parseFloat(this.value) || 0);
                };

                // Trigger Initial Validation
                performValidation(parseFloat(inputNewQty.value) || 0);
            }

            const modalEl = document.getElementById('editQtyModal');
            if(modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchRemarkAufnr');
            const searchBtn = document.getElementById('btnSearchRemark');
            const resultModalEl = document.getElementById('remarkSearchModal');
            
            if(searchInput && searchBtn && resultModalEl) {
                const resultModal = new bootstrap.Modal(resultModalEl);
                const resultBody = document.getElementById('remarkSearchResultBody');
                const searchedAufnrSpan = document.getElementById('searchedAufnr');

                const performSearch = function() {
                    const aufnr = searchInput.value.trim();
                    if (!aufnr) {
                        alert('Silakan masukkan Nomor AUFNR.');
                        return;
                    }

                    resultBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted fw-bold">Mencari Data...</div></div>';
                    searchedAufnrSpan.innerText = aufnr;
                    resultModal.show();
                    fetch('/api/wi/remarks/get', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ aufnr: aufnr })
                    })
                    .then(response => {
                        if (!response.ok) {
                             throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            renderSearchResults(data.data);
                        } else {
                            resultBody.innerHTML = `<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center"><i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i><div><strong>Data tidak ditemukan.</strong><br>${data.message || 'Tidak ada riwayat remark untuk AUFNR ini.'}</div></div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let msg = 'Terjadi kesalahan sistem saat mengambil data.';
                        if (error.message.includes('Unexpected token')) {
                             msg = 'Terjadi kesalahan server (Response Invalid). Cek log backend.';
                        }
                        resultBody.innerHTML = `<div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-xmark me-2"></i>${msg}</div>`;
                    });
                };

                searchBtn.addEventListener('click', performSearch);
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') performSearch();
                });

                function renderSearchResults(data) {
                    if (!data || data.length === 0) {
                        resultBody.innerHTML = '<div class="alert alert-info border-0 shadow-sm"><i class="fa-solid fa-info-circle me-2"></i>Tidak ada remark ditemukan untuk AUFNR ini.</div>';
                        return;
                    }

                    let html = '<div class="list-group list-group-flush rounded-3">';
                    data.forEach(doc => {
                         html += `
                            <div class="list-group-item list-group-item-action p-3 border-bottom-0 mb-2 rounded shadow-sm bg-white border">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-primary fw-bold font-monospace"><i class="fa-solid fa-file-lines me-2"></i>${doc.wi_code}</h6>
                                    <span class="badge bg-light text-secondary border">${doc.document_date}</span>
                                </div>
                                <div class="mb-2 text-muted small row">
                                    <div class="col-md-6"><i class="fa-solid fa-box me-1"></i> Material: <span class="text-dark fw-bold">${doc.material_desc}</span></div>
                                    <div class="col-md-6"><i class="fa-solid fa-layer-group me-1"></i> VORNR: <span class="text-dark fw-bold">${doc.vornr}</span></div>
                                </div>
                                <div class="mb-2 text-muted small"><i class="fa-solid fa-user-gear me-1"></i> Operator: <span class="text-dark fw-bold">${doc.operator || '-'}</span></div>
                                
                                <div class="mt-3 ps-3 border-start border-3 border-danger bg-light p-2 rounded-end">
                                     <div class="fw-bold text-danger text-xs mb-1 text-uppercase">Log Remark</div>
                                     <ul class="list-unstyled mb-0 mt-1">`;
                                         
                         if (doc.history && doc.history.length > 0) {
                             doc.history.forEach(h => {
                                 html += `
                                    <li class="mb-2 d-flex flex-column gap-1 border-bottom pb-2 last-no-border">
                                        <span class="badge bg-danger text-wrap text-start lh-base" style="font-size: 0.8rem;">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> 
                                            <strong>Jumlah Item Gagal: ${parseFloat(h.qty || 0)}</strong> - ${h.remark || '-'}
                                        </span>
                                        <div class="text-end">
                                            <small class="text-muted fst-italic" style="font-size: 0.7rem;">
                                                ${h.created_at || ''} <span class="mx-1">•</span> <i class="fa-solid fa-user-clock text-xs"></i> ${h.created_by || 'System'}
                                            </small>
                                        </div>
                                    </li>
                                 `;
                             });
                         } else {
                             html += `<li class="text-muted small"><em>Tidak ada detail history.</em></li>`;
                         }
                         
                         html += `   </ul>
                                </div>
                            </div>
                         `;
                    });
                    html += '</div>';
                    resultBody.innerHTML = html;
                }
            }
        });

    </script>
    @endpush
</x-layouts.app>
