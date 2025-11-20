<x-layouts.app title="Dashboard Plant">

    @push('styles')
    <style>
        .table-container-scroll {
            max-height: 60vh;
            overflow-y: auto;
        }
        thead.table-light th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa; 
        }
        thead.table-light th.sortable-header {
            padding-right: 25px; 
        }
        thead.table-light th.sortable-header .sort-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
    @endpush
    <div class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between">
                <div>
                    <h1 class="h3 fw-bold text-dark">Dashboard - {{ $sub_kategori }} </h1>
                    <p class="mt-1 text-muted">Welcome, here is the COHV data information in {{ $kategori }}</p>
                </div>
                <div class="mt-3 mt-sm-0 small text-muted">
                    <i class="fas fa-calendar-alt me-2"></i>{{ now()->format('l, d F Y') }}
                </div>
            </div>
        </div>

        <div id="mainDashboardContent">
            <div id="cardStatsSection" class="row g-4 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div id="card-outstanding-so" class="card border-0 shadow-sm h-100 card-interactive position-relative" style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="top"
                            data-bs-content="Menampilkan jumlah Sales Order (SO) pada bagian ini. Klik untuk detail.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle rounded-3 me-3 p-2">
                                    <svg class="text-info-emphasis" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-muted mb-0">Outstanding SO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $TData2 ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div id="card-total-pro" class="card border-0 shadow-sm h-100 card-interactive position-relative" style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="top"
                            data-bs-content="Jumlah total Production Order (PRO) pada bagian ini. Klik untuk detail.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle rounded-3 me-3 p-2">
                                    <svg class="text-primary-emphasis" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <p class="text-muted mb-0">Total PRO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $TData3 ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div id="card-outstanding-reservasi" class="card border-light-subtle shadow-sm h-100 card-interactive position-relative" style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="top"
                            data-bs-content="Jumlah material yang dibutuhkan untuk produksi tetapi stoknya belum mencukupi. Klik untuk detail.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle rounded-3 me-3 p-2">
                                    <svg class="text-danger-emphasis" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-muted mb-0">Outstanding Reservasi</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $outstandingReservasi ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div id="card-outgoing-pro" class="card border-light-subtle shadow-sm h-100 card-interactive position-relative" style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="top"
                            data-bs-content="Jumlah PRO yang basic start date nya hari ini. click untuk melihat detail...">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle rounded-3 me-3 p-2">
                                     <svg class="text-success-emphasis" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-muted mb-0">On Process PRO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $ongoingPRO ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <form id="searchPROForm" method="POST" action="{{ route('manufaktur.pro.search.submit') }}">
                @csrf
                
                {{-- Input Hidden untuk Data Header (Tetap sama) --}}
                <input type="hidden" name="werks_code" id="werksCode" value="{{ $kode }}">
                <input type="hidden" name="bagian_name" id="bagianName" value="{{ $nama_bagian }}">
                <input type="hidden" name="categories_name" id="categoriesName" value="{{ $kategori }}">
                <input type="hidden" name="pro_numbers" id="proNumbersInput">

                <div class="row g-2">
                    <div class="col-md-9 col-lg-10">
                        <label for="proInput" class="form-label small">Enter PRO Number(s)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-tag text-muted"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control border-start-0" 
                                id="proInput"
                                placeholder="Ketik PRO, pisahkan dengan spasi atau koma"
                                autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="addProButton">
                                <i class="fas fa-plus me-1"></i>Add
                            </button>
                        </div>
                    </div>
                
                    {{-- Tombol Submit Utama --}}
                    <div class="col-md-3 col-lg-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right me-2"></i>
                            <span>Go To PROs</span> 
                        </button>
                    </div>
                </div>

                {{-- AREA PREVIEW BADGE BARU --}}
                <div class="row g-2 mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small text-muted mb-0">PRO numbers to search:</label>
                            
                            <button type="button" class="btn btn-link text-danger p-0" id="clearAllProButton" style="display: none; text-decoration: none;">
                                <small><i class="fas fa-trash me-1"></i>Clear All</small>
                            </button>
                            </div>
                        <div id="proBadgeContainer" class="border p-3 rounded" style="min-height: 80px; background-color: #f8f9fa; display: flex; flex-wrap: wrap; gap: 8px;">
                            <span id="proBadgePlaceholder" class="text-muted fst-italic">Added PROs will appear here...</span>
                        </div>
                    </div>
                </div>
            </form>

            <div id="chartsSection" class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0 h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="left"
                            data-bs-content="Grafik ini membandingkan total jam kapasitas yang tersedia dengan jumlah PRO di setiap workcenter.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <h3 class="h5 fw-semibold text-dark">Workcenter Capacity Data</h3>
                            <p class="small text-muted mb-4">Comparison of the Count of PROs and Capacity at each Workcenter.</p>
                            <div class="chart-wrapper">
                                <canvas id="myBarChart" data-labels="{{ json_encode($labels ?? []) }}" data-datasets="{{ json_encode($datasets ?? []) }}" data-urls="{{ json_encode($targetUrls ?? []) }}"></canvas>
                            </div>
                            <div class="mt-4 text-center">
                                <p class="small text-muted mb-2">Press to view workcenter compatibility status</p>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                @if (!empty($labels))
                                    @foreach($labels as $index => $label)
                                        <a href="{{ $targetUrls[$index] ?? '#' }}" class="btn btn-sm btn-outline-primary">
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" 
                    id="proStatusCardContainer"
                    data-bagian="{{ $nama_bagian }}" 
                    data-kategori="{{ $kategori }}"
                    data-kode="{{ $kode }}">  
                    <div class="card shadow-sm border-0 h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="left"
                            data-bs-content="Distribusi persentase dari semua status Production Order yang ada.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4 d-flex flex-column">
                            <div id="cardHeaderContent">
                                <h3 class="h5 fw-semibold text-dark">PRO Status in - {{ $nama_bagian }} {{ $kategori }}</h3>
                                <p class="small text-muted mb-4">Distribusi status pada Production Order.</p>
                            </div>
                            
                            <div id="chartView" class="chart-wrapper flex-grow-1">
                                <canvas id="pieChart" data-labels="{{ json_encode($doughnutChartLabels ?? []) }}" data-datasets="{{ json_encode($doughnutChartDatasets ?? []) }}"></canvas>
                            </div>

                            <div id="tableView" style="display: none;" class="flex-grow-1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100 position-relative">
                         <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="left"
                            data-bs-content="5 workcenter teratas yang memiliki total jam kapasitas paling tinggi.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h5 fw-semibold text-dark">Ranked 5th Workcenter in - {{ $nama_bagian }} {{ $kategori }}</h3>
                            <p class="small text-muted mb-4">Based on the highest total capacity.</p>
                            <div class="chart-wrapper flex-grow-1">
                                <canvas id="lollipopChart" 
                                    data-labels="{{ json_encode($lolipopChartLabels ?? []) }}" 
                                    data-datasets="{{ json_encode($lolipopChartDatasets ?? []) }}">
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="outstandingSoSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
    
                    {{-- Header Card --}}
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">List of Outstanding Sales Orders (SO)</h3>
                            <p class="small text-muted mb-0">List data Outstanding SO</p>
                        </div>
                        <button id="backToDashboardBtnSo" class="btn btn-outline-secondary flex-shrink-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </button>
                    </div>
    
                    {{-- Kolom Pencarian --}}
                    <div class="mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInputSo" placeholder="Search Order, Material..." class="form-control border-start-0">
                        </div>
                    </div>
    
                    {{-- Tabel Responsif --}}
                    <div class="table-container-scroll">
                        <table class="table table-hover table-striped table-sm align-middle mb-0 responsive-so-table">
                            <thead class="table-light">
                                <tr class="small text-uppercase align-middle">
                                    <th class="text-center d-none d-md-table-cell">No.</th>
                                    <th class="text-center sortable-header" data-sort-column="order">
                                        Order <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center sortable-header" data-sort-column="item">
                                        Item <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="material">
                                        Material FG <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="d-none d-md-table-cell sortable-header" data-sort-column="description">
                                        Description <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="outstandingSoTableBody">
                                @forelse($salesOrderData ?? [] as $item)
                                <tr 
                                    class="clickable-row"
                                    data-order="{{ $item->KDAUF ?? '-' }}"
                                    data-item="{{ $item->KDPOS ?? '-' }}"
                                    data-material="{{ $item->MATFG ? ltrim((string)$item->MATFG, '0') : '-' }}"
                                    data-description="{{ $item->MAKFG ?? '-' }}"
                                    data-searchable-text="{{ strtolower(($item->KDAUF ?? '') . ' ' . ($item->MATFG ?? '') . ' ' . ($item->MAKFG ?? '')) }}"> 
                                    <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration }}</td>
                                    <td class="text-center small" data-col="order">{{ $item->KDAUF ?? '-' }}</td>
                                    <td class="small text-center" data-col="item">{{ $item->KDPOS ?? '-' }}</td>
                                    <td class="small text-center d-none d-md-table-cell" data-col="material">{{ $item->MATFG ? ltrim((string)$item->MATFG, '0') : '-' }}</td>
                                    <td class="small d-none d-md-table-cell" data-col="description">{{ $item->MAKFG ?? '-' }}</td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-5 text-muted">Tidak ada data Sales Order.</td>
                                    </tr>
                                @endforelse
                                <tr id="noResultsSoRow" style="display: none;">
                                    <td colspan="5" class="text-center p-5 text-muted">Tidak ada data yang cocok.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
    
                </div>
            </div>
        </div>


        <div id="outstandingReservasiSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
        
                    {{-- Header Card & Search --}}
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">List Item of Outstanding Reservasi</h3>
                            <p class="small text-muted mb-0">Daftar Material yang dibutuhkan dan belum terpenuhi.</p>
                        </div>
                        <button id="backToDashboardBtn" class="btn btn-outline-secondary flex-shrink-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </button>
                    </div>
                    <div class="mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInput" placeholder="Search Reservasi...." class="form-control border-start-0">
                        </div>
                    </div>
        
                    {{-- Tabel Responsif --}}
                    <div class="table-container-scroll">
                        <table class="table table-hover table-striped align-middle mb-0 reservasi-responsive-table">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="text-center d-none d-md-table-cell">No.</th>
                                    <th class="sortable-header" data-sort-column="reservasi" data-sort-type="text">
                                        No. Reservasi <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class=" text-center sortable-header" data-sort-column="material_code" data-sort-type="text">
                                        Material Code <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="description" data-sort-type="text">
                                        Material Description <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="req_qty" data-sort-type="number">
                                        Req. Qty <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    {{-- [UBAH] Menambahkan header kolom baru "Req. Commited" --}}
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="vmeng" data-sort-type="number">
                                        Req. Commited <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="stock" data-sort-type="number">
                                        Stock <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="reservasiTableBody">
                                @forelse($TData4 as $item)
                                    <tr class="clickable-row"
                                        data-no="{{ $loop->iteration }}"
                                        data-reservasi="{{ $item->RSNUM ?? '-' }}"
                                        data-material-code="{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') ?: '0' : '-' }}"
                                        data-description="{{ $item->MAKTX ?? '-' }}"
                                        data-req-qty="{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}"
                                        {{-- [UBAH] Menambahkan atribut data untuk field VMENG --}}
                                        data-req-commited="{{ number_format($item->VMENG ?? 0, 0, ',', '.') }}"
                                        data-stock="{{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}"
                                        data-searchable-text="{{ strtolower(($item->RSNUM ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">
        
                                        <td class="text-center d-none d-md-table-cell">{{ $loop->iteration }}</td>
                                        <td data-col="reservasi">{{ $item->RSNUM ?? '-' }}</td>
                                        <td data-col="material_code">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') ?: '0' : '-' }}</td>
                                        <td class="d-none d-md-table-cell" data-col="description">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="text-center d-none d-md-table-cell" data-col="req_qty">{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}</td>
                                        {{-- [UBAH] Menambahkan sel data baru untuk field VMENG --}}
                                        <td class="text-center d-none d-md-table-cell" data-col="vmeng">{{ number_format($item->VMENG ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center d-none d-md-table-cell" data-col="stock">{{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    {{-- [UBAH] Mengubah colspan dari 6 menjadi 7 --}}
                                    <tr><td colspan="7" class="text-center p-5 text-muted">Tidak ada data reservasi ditemukan.</td></tr>
                                @endforelse
                                {{-- [UBAH] Mengubah colspan dari 6 menjadi 7 --}}
                                <tr id="noResultsRow" style="display: none;"><td colspan="7" class="text-center p-5 text-muted">Tidak ada data yang cocok.</td></tr>
                            </tbody>
                        </table>
                    </div>
        
                </div>
            </div>
        </div>

        <div id="ongoingProSection" style="display: none;>
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
    
                    {{-- Header Card & Kolom Pencarian --}}
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">List of On Process PRO</h3>
                            <p class="small text-muted mb-0">Daftar Production Order yang sedang berjalan.</p>
                        </div>
                        <button id="backToDashboardBtnPro" class="btn btn-outline-secondary flex-shrink-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </button>
                    </div>
                    <div class="mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInputPro" placeholder="Search SO, PRO, Material..." class="form-control border-start-0">
                        </div>
                    </div>
    
                    {{-- Tabel Responsif --}}
                    <div class="table-container-scroll">
                        <table class="table table-hover table-striped table-sm align-middle mb-0 pro-responsive-table">
                            <thead class="table-light">
                                <tr class="small text-uppercase align-middle">
                                    {{-- [DISEMBUNYIKAN DI MOBILE] --}}
                                    <th class="text-center d-none d-md-table-cell">No.</th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so" data-sort-type="text">SO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so_item" data-sort-type="text">SO. Item <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    
                                    {{-- [TETAP TAMPIL DI MOBILE] --}}
                                    <th class="text-center sortable-header" data-sort-column="pro" data-sort-type="text">PRO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center sortable-header" data-sort-column="status" data-sort-type="text">Status <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    
                                    {{-- [DISEMBUNYIKAN DI MOBILE] --}}
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="material_code" data-sort-type="text">Material Code <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="description" data-sort-type="text">Material Description <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="plant" data-sort-type="text">Plant <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="mrp" data-sort-type="text">MRP <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="qty_order" data-sort-type="number">Qty. ORDER <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="qty_gr" data-sort-type="number">Qty. GR <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="outs_gr" data-sort-type="number">Outs. GR <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="start_date" data-sort-type="date">Start Date <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="end_date" data-sort-type="date">End Date <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                </tr>
                            </thead>
                            <tbody id="ongoingProTableBody">
                                @forelse($ongoingProData ?? [] as $item)
                                    @php
                                        $status = strtoupper($item->STATS ?? '');
                                        $badgeClass = 'bg-secondary-subtle text-secondary-emphasis';
                                        if (in_array($status, ['REL', 'PCNF', 'CNF'])) $badgeClass = 'bg-success-subtle text-success-emphasis';
                                        elseif ($status === 'CRTD') $badgeClass = 'bg-info-subtle text-info-emphasis';
                                        elseif ($status === 'TECO') $badgeClass = 'bg-dark-subtle text-dark-emphasis';
                                    @endphp
                                    <tr class="clickable-row"
                                        data-no="{{ $loop->iteration }}"
                                        data-pro="{{ $item->AUFNR ?? '-' }}"
                                        data-status="{{ $status ?: '-' }}"
                                        data-status-class="{{ $badgeClass }}"
                                        data-so="{{ $item->KDAUF ?? '-' }}"
                                        data-so-item="{{ $item->KDPOS ?? '-' }}"
                                        data-material-code="{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') : '-' }}"
                                        data-description="{{ $item->MAKTX ?? '-' }}"
                                        data-plant="{{ $item->PWWRK ?? '-' }}"
                                        data-mrp="{{ $item->DISPO ?? '-' }}"
                                        data-qty-order="{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}"
                                        data-qty-gr="{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}"
                                        data-outs-gr="{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}"
                                        data-start-date="{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}"
                                        data-end-date="{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}"
                                        data-searchable-text="{{ strtolower(($item->KDAUF ?? '') . ' ' . ($item->AUFNR ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">
                        
                                        {{-- [DISEMBUNYIKAN DI MOBILE] --}}
                                        <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration }}</td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="so">{{ $item->KDAUF ?? '-' }}</td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="so_item">{{ $item->KDPOS ?? '-' }}</td>
                                        
                                        {{-- [TETAP TAMPIL DI MOBILE] --}}
                                        <td class="text-center small" data-col="pro">{{ $item->AUFNR ?? '-' }}</td>
                                        <td class="text-center" data-col="status"><span class="badge rounded-pill {{ $badgeClass }}">{{ $status ?: '-' }}</span></td>
                                        
                                        {{-- [DISEMBUNYIKAN DI MOBILE] --}}
                                        <td class="text-center small d-none d-md-table-cell" data-col="material_code">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') : '-' }}</td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="description">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="plant">{{ $item->PWWRK ?? '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="mrp">{{ $item->DISPO ?? '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="qty_order">{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="qty_gr">{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="small text-center fw-bold d-none d-md-table-cell" data-col="outs_gr">{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="start_date" data-sort-value="{{ $item->GSTRP ?? '0' }}">{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="end_date" data-sort-value="{{ $item->GLTRP ?? '0' }}">{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="14" class="text-center p-5 text-muted">Tidak ada data Ongoing PRO.</td></tr>
                                @endforelse
                                <tr id="noResultsProRow" style="display: none;"><td colspan="14" class="text-center p-5 text-muted">Tidak ada data yang cocok.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
   
        <div id="totalProSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">List of Production Order (PRO)</h3>
                            <p class="small text-muted mb-0">Menampilkan semua PRO yang ada di plant ini.</p>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2 align-self-stretch align-items-sm-center">
                            <select id="statusFilterTotalPro" class="form-select flex-shrink-0" style="width: 150px;">
                                <option value="">Semua Status</option>
                                <option value="REL">RELEASE</option>
                                <option value="CRTD">CREATED</option>
                            </select>

                            <button id="copyProBtn" class="btn btn-outline-success flex-shrink-0" disabled>
                                <i class="fas fa-copy me-2"></i>Copy PRO
                            </button>
                            <button id="backToDashboardBtnTotalPro" class="btn btn-outline-secondary flex-shrink-0">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </button>
                        </div>
                    </div>
                    <div class="mb-3" style="max-width: 450px;"> <!-- Sedikit diperlebar -->
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInputTotalPro" placeholder="Cari SO-Item, PRO..." class="form-control">
                            
                            <!-- Tombol Multi Filter Material -->
                            <button class="btn btn-outline-secondary" type="button" id="btnMultiMatnr" data-bs-toggle="modal" data-bs-target="#multiMatnrModal" title="Filter Banyak Material">
                                <i class="fas fa-layer-group me-1"></i>
                                <span id="matnrBadge" class="badge bg-primary rounded-pill d-none">0</span>
                            </button>

                            <!-- TOMBOL BARU: CLEAR FILTER -->
                            <button class="btn btn-outline-danger" type="button" id="btnClearTotalProFilter" title="Reset Semua Filter & Pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="modal fade" id="multiMatnrModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-filter me-2"></i>Filter Multi Material</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted mb-2">Masukkan kode material (satu per baris). Anda bisa copy-paste langsung dari Excel.</p>
                                    <div class="form-group">
                                        <textarea id="multiMatnrInput" class="form-control" rows="10" placeholder="Contoh:&#10;KG-12345&#10;KG-67890&#10;..."></textarea>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-secondary" id="matnrCountInfo">0 kode terdeteksi</small>
                                        <button class="btn btn-sm btn-link text-danger text-decoration-none" id="clearMatnrFilter">Hapus Filter</button>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" id="applyMatnrFilter">Terapkan Filter</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-container-scroll">
                        <table class="table table-hover table-striped table-sm align-middle mb-0 total-pro-responsive-table">
                            <thead class="table-light">
                                <tr class="small text-uppercase align-middle">
                                    <th class="text-center" style="width: 1%;">
                                        <input class="form-check-input" type="checkbox" id="selectAllTotalProCheckbox" title="Pilih Semua">
                                    </th>
                                    <th class="text-center d-none d-md-table-cell">No.</th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so" data-sort-type="text">SO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so_item" data-sort-type="text">SO. Item <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center sortable-header" data-sort-column="pro" data-sort-type="text">PRO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center sortable-header" data-sort-column="status" data-sort-type="text">Status <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="material_code" data-sort-type="text">Material Code <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="description" data-sort-type="text">Deskripsi <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="plant" data-sort-type="text">Plant <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="mrp" data-sort-type="text">MRP <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="qty_order" data-sort-type="number">Qty. ORDER <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="qty_gr" data-sort-type="number">Qty. GR <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="outs_gr" data-sort-type="number">Outs. GR <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="start_date" data-sort-type="date">Start Date <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="end_date" data-sort-type="date">End Date <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                </tr>
                            </thead>
                            <tbody id="totalProTableBody">
                                @forelse($allProData ?? [] as $item)
                                    @php
                                        $status = strtoupper($item->STATS ?? '');
                                        $badgeClass = 'bg-secondary-subtle text-secondary-emphasis';
                                        if (in_array($status, ['REL', 'PCNF', 'CNF'])) $badgeClass = 'bg-success-subtle text-success-emphasis';
                                        elseif ($status === 'CRTD') $badgeClass = 'bg-info-subtle text-info-emphasis';
                                        elseif ($status === 'TECO') $badgeClass = 'bg-dark-subtle text-dark-emphasis';
                                        $dataStatus = in_array($status, ['REL', 'PCNF', 'CNF', 'CRTD', 'TECO']) ? $status : 'Lainnya';
                                    @endphp
                                    <tr class="clickable-row"
                                        data-status="{{ $dataStatus }}"
                                        data-no="{{ $loop->iteration }}"
                                        data-so="{{ $item->KDAUF ?? '-' }}"
                                        data-so-item="{{ $item->KDPOS ?? '-' }}"
                                        data-pro="{{ $item->AUFNR ?? '-' }}"
                                        data-status-text="{{ $status ?: '-' }}"
                                        data-status-class="{{ $badgeClass }}"
                                        data-material-code="{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') : '-' }}"
                                        data-description="{{ $item->MAKTX ?? '-' }}"
                                        data-plant="{{ $item->PWWRK ?? '-' }}"
                                        data-mrp="{{ $item->DISPO ?? '-' }}"
                                        data-qty-order="{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}"
                                        data-qty-gr="{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}"
                                        data-outs-gr="{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}"
                                        data-start-date="{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}"
                                        data-end-date="{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}"
                                    >
                                        <td class="text-center">
                                            <input class="form-check-input pro-checkbox" 
                                                type="checkbox" 
                                                value="{{ $item->AUFNR ?? '' }}" 
                                                id="pro-check-{{ $loop->iteration }}">
                                        </td>
                                        <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration }}</td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="so">{{ $item->KDAUF ?? '-' }}</td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="so_item">{{ $item->KDPOS ?? '-' }}</td>
                                        <td class="text-center small" data-col="pro">{{ $item->AUFNR ?? '-' }}</td>
                                        <td class="text-center" data-col="status"><span class="badge rounded-pill {{ $badgeClass }}">{{ $status ?: '-' }}</span></td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="material_code">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') : '-' }}</td>
                                        <td class="text-center small d-none d-md-table-cell" data-col="description">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="plant">{{ $item->PWWRK ?? '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="mrp">{{ $item->DISPO ?? '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="qty_order">{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="qty_gr">{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="outs_gr">{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="start_date" data-sort-value="{{ $item->GSTRP ?? '0' }}">{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}</td>
                                        <td class="small text-center d-none d-md-table-cell" data-col="end_date" data-sort-value="{{ $item->GLTRP ?? '0' }}">{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="15" class="text-center p-5 text-muted">Tidak ada data PRO yang ditemukan.</td></tr>
                                @endforelse
                                <tr id="noResultsTotalProRow" style="display: none;"><td colspan="15" class="text-center p-5 text-muted">Tidak ada data yang cocok.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="loadingModal" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="loadingModalLabel" 
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-0" id="loadingModalLabel">Processing Request...</h5>
                    <p class="text-muted mt-2">
                        Please wait, fetching PRO data from the server.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @include('components.modals.dashboard-modal.so-detail')
    @include('components.modals.dashboard-modal.reservasi-detail')
    @include('components.modals.dashboard-modal.ongoing-detail')
    @include('components.modals.dashboard-modal.pro-detail')
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        {{-- JavaScript untuk mengontrol modal --}}
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const loadingModalElement = document.getElementById('loadingModal');
                if (loadingModalElement) {
                    const loadingModal = new bootstrap.Modal(loadingModalElement);
                    const proForm = document.getElementById('searchPROForm');
                    const submitButton = proForm.querySelector('button[type="submit"]');
            
                    proForm.addEventListener('submit', function() {

                        loadingModal.show();
                        
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.innerHTML = `
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span>Loading...</span>
                            `;
                        }
                    });
                }
            });
            document.addEventListener('DOMContentLoaded', function () {
                const soDetailModalElement = document.getElementById('soDetailModal');
                if (soDetailModalElement) {
                    const soDetailModal = new bootstrap.Modal(soDetailModalElement);
                    const tableRows = document.querySelectorAll('#outstandingSoTableBody .clickable-row');

                    tableRows.forEach(row => {
                        row.addEventListener('click', function () {
                            if (window.innerWidth < 768) {
                                const order = this.dataset.order;
                                const item = this.dataset.item;
                                const material = this.dataset.material;
                                const description = this.dataset.description;

                                document.getElementById('modalSoOrder').textContent = order;
                                document.getElementById('modalSoItem').textContent = item;
                                document.getElementById('modalSoMaterial').textContent = material;
                                document.getElementById('modalSoDescription').textContent = description;

                                soDetailModal.show();
                            }
                        });
                    });
                }
                const table = document.querySelector('.responsive-so-table');
                const headers = table.querySelectorAll('.sortable-header');
                const tableBody = table.querySelector('#outstandingSoTableBody');

                // Tambahkan event listener untuk setiap header yang bisa di-sort
                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const column = header.dataset.sortColumn;
                        const currentDirection = header.dataset.sortDirection || 'desc';
                        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                        
                        // Simpan arah sorting baru di header
                        header.dataset.sortDirection = newDirection;

                        // Dapatkan semua baris (tr) dari tbody untuk diurutkan
                        const rows = Array.from(tableBody.querySelectorAll('tr.clickable-row'));

                        // Lakukan proses sorting
                        rows.sort((rowA, rowB) => {
                            const valA = rowA.querySelector(`[data-col="${column}"]`).textContent.trim().toLowerCase();
                            const valB = rowB.querySelector(`[data-col="${column}"]`).textContent.trim().toLowerCase();
                            
                            // Logika perbandingan string
                            if (valA < valB) {
                                return newDirection === 'asc' ? -1 : 1;
                            }
                            if (valA > valB) {
                                return newDirection === 'asc' ? 1 : -1;
                            }
                            return 0;
                        });

                        // Hapus semua baris dari tabel
                        tableBody.innerHTML = '';
                        
                        // Masukkan kembali baris yang sudah diurutkan
                        rows.forEach(row => tableBody.appendChild(row));

                        // Update ikon di semua header
                        headers.forEach(h => {
                            const icon = h.querySelector('.sort-icon i');
                            if (h === header) {
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = ''; // Reset arah sort header lain
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });

            document.addEventListener('DOMContentLoaded', function () {
                const table = document.querySelector('.reservasi-responsive-table');
                if (!table) return;

                // ===================================================================
                // LOGIKA MODAL UNTUK TABEL RESERVASI
                // ===================================================================
                const reservasiDetailModalElement = document.getElementById('reservasiDetailModal');
                if (reservasiDetailModalElement) {
                    const reservasiDetailModal = new bootstrap.Modal(reservasiDetailModalElement);
                    const tableRows = table.querySelectorAll('#reservasiTableBody .clickable-row');

                    tableRows.forEach(row => {
                        row.addEventListener('click', function () {
                            if (window.innerWidth < 768) {
                                // Ambil data dari atribut `data-*`
                                document.getElementById('modalReservasiNo').textContent = this.dataset.no;
                                document.getElementById('modalReservasiRsv').textContent = this.dataset.reservasi;
                                document.getElementById('modalReservasiMatCode').textContent = this.dataset.materialCode;
                                document.getElementById('modalReservasiDesc').textContent = this.dataset.description;
                                document.getElementById('modalReservasiReqQty').textContent = this.dataset.reqQty;
                                document.getElementById('modalReservasiReqCommited').textContent = this.dataset.reqCommited;
                                document.getElementById('modalReservasiStock').textContent = this.dataset.stock;
                                reservasiDetailModal.show();
                            }
                        });
                    });
                }

                // ===================================================================
                // LOGIKA SORTING UNTUK TABEL RESERVASI
                // ===================================================================
                const headers = table.querySelectorAll('.sortable-header');
                const tableBody = table.querySelector('#reservasiTableBody');

                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const column = header.dataset.sortColumn;
                        const type = header.dataset.sortType || 'text';
                        const currentDirection = header.dataset.sortDirection || 'desc';
                        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                        
                        header.dataset.sortDirection = newDirection;
                        const rows = Array.from(tableBody.querySelectorAll('tr.clickable-row'));

                        rows.sort((rowA, rowB) => {
                            let valA = rowA.querySelector(`[data-col="${column}"]`).textContent.trim();
                            let valB = rowB.querySelector(`[data-col="${column}"]`).textContent.trim();
                            
                            if (type === 'number') {
                                // Hapus titik ribuan, lalu ubah jadi angka
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) || 0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) || 0;
                                return newDirection === 'asc' ? valA - valB : valB - valA;
                            } else {
                                // Urutkan sebagai teks biasa
                                valA = valA.toLowerCase();
                                valB = valB.toLowerCase();
                                if (valA < valB) return newDirection === 'asc' ? -1 : 1;
                                if (valA > valB) return newDirection === 'asc' ? 1 : -1;
                                return 0;
                            }
                        });

                        tableBody.innerHTML = '';
                        rows.forEach(row => tableBody.appendChild(row));

                        headers.forEach(h => {
                            const icon = h.querySelector('.sort-icon i');
                            if (h === header) {
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.querySelector('.pro-responsive-table');
                if (!table) return;

                // LOGIKA MODAL
                const proDetailModalElement = document.getElementById('proDetailModal');
                if (proDetailModalElement) {
                    const proDetailModal = new bootstrap.Modal(proDetailModalElement);
                    const tableRows = table.querySelectorAll('#ongoingProTableBody .clickable-row');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function () {
                            if (window.innerWidth < 768) {
                                // Memasukkan semua data dari atribut data-* ke modal
                                document.getElementById('modalProNo').textContent = this.dataset.no;
                                document.getElementById('modalProSo').textContent = this.dataset.so;
                                document.getElementById('modalProSoItem').textContent = this.dataset.soItem;
                                document.getElementById('modalProPro').textContent = this.dataset.pro;
                                document.getElementById('modalProStatus').innerHTML = `<span class="badge rounded-pill ${this.dataset.statusClass}">${this.dataset.status}</span>`;
                                document.getElementById('modalProMaterialCode').textContent = this.dataset.materialCode;
                                document.getElementById('modalProDescription').textContent = this.dataset.description;
                                document.getElementById('modalProPlant').textContent = this.dataset.plant;
                                document.getElementById('modalProMrp').textContent = this.dataset.mrp;
                                document.getElementById('modalProQtyOrder').textContent = this.dataset.qtyOrder;
                                document.getElementById('modalProQtyGr').textContent = this.dataset.qtyGr;
                                document.getElementById('modalProOutsGr').textContent = this.dataset.outsGr;
                                document.getElementById('modalProStartDate').textContent = this.dataset.startDate;
                                document.getElementById('modalProEndDate').textContent = this.dataset.endDate;
                                proDetailModal.show();
                            }
                        });
                    });
                }

                // LOGIKA SORTING
                const headers = table.querySelectorAll('.sortable-header');
                const tableBody = table.querySelector('#ongoingProTableBody');
                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const column = header.dataset.sortColumn;
                        const type = header.dataset.sortType || 'text';
                        const currentDirection = header.dataset.sortDirection || 'desc';
                        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                        
                        header.dataset.sortDirection = newDirection;
                        const rows = Array.from(tableBody.querySelectorAll('tr.clickable-row'));

                        rows.sort((rowA, rowB) => {
                            const cellA = rowA.querySelector(`[data-col="${column}"]`);
                            const cellB = rowB.querySelector(`[data-col="${column}"]`);
                            let valA, valB;

                            if (type === 'date') {
                                valA = cellA.dataset.sortValue || '0';
                                valB = cellB.dataset.sortValue || '0';
                            } else {
                                valA = cellA.textContent.trim();
                                valB = cellB.textContent.trim();
                            }
                            
                            if (type === 'number') {
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) || 0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) || 0;
                                return newDirection === 'asc' ? valA - valB : valB - valA;
                            } else { // Termasuk untuk tipe 'date' dan 'text'
                                valA = valA.toLowerCase();
                                valB = valB.toLowerCase();
                                if (valA < valB) return newDirection === 'asc' ? -1 : 1;
                                if (valA > valB) return newDirection === 'asc' ? 1 : -1;
                                return 0;
                            }
                        });

                        tableBody.innerHTML = '';
                        rows.forEach(row => tableBody.appendChild(row));

                        headers.forEach(h => {
                            const icon = h.querySelector('.sort-icon i');
                            if (h === header) {
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.querySelector('.pro-responsive-table');
                if (!table) return;

                // LOGIKA MODAL
                const proDetailModalElement = document.getElementById('proDetailModal');
                if (proDetailModalElement) {
                    const proDetailModal = new bootstrap.Modal(proDetailModalElement);
                    const tableRows = table.querySelectorAll('#ongoingProTableBody .clickable-row');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function () {
                            if (window.innerWidth < 768) {
                                document.getElementById('modalProNo').textContent = this.dataset.no;
                                document.getElementById('modalProSo').textContent = this.dataset.so;
                                document.getElementById('modalProSoItem').textContent = this.dataset.soItem;
                                document.getElementById('modalProPro').textContent = this.dataset.pro;
                                document.getElementById('modalProStatus').innerHTML = `<span class="badge rounded-pill ${this.dataset.statusClass}">${this.dataset.status}</span>`;
                                document.getElementById('modalProMaterialCode').textContent = this.dataset.materialCode;
                                document.getElementById('modalProDescription').textContent = this.dataset.description;
                                document.getElementById('modalProPlant').textContent = this.dataset.plant;
                                document.getElementById('modalProMrp').textContent = this.dataset.mrp;
                                document.getElementById('modalProQtyOrder').textContent = this.dataset.qtyOrder;
                                document.getElementById('modalProQtyGr').textContent = this.dataset.qtyGr;
                                document.getElementById('modalProOutsGr').textContent = this.dataset.outsGr;
                                document.getElementById('modalProStartDate').textContent = this.dataset.startDate;
                                document.getElementById('modalProEndDate').textContent = this.dataset.endDate;
                                proDetailModal.show();
                            }
                        });
                    });
                }

                // LOGIKA SORTING
                const headers = table.querySelectorAll('.sortable-header');
                const tableBody = table.querySelector('#ongoingProTableBody');
                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const column = header.dataset.sortColumn;
                        const type = header.dataset.sortType || 'text';
                        const currentDirection = header.dataset.sortDirection || 'desc';
                        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                        
                        header.dataset.sortDirection = newDirection;
                        const rows = Array.from(tableBody.querySelectorAll('tr.clickable-row'));

                        rows.sort((rowA, rowB) => {
                            const cellA = rowA.querySelector(`[data-col="${column}"]`);
                            const cellB = rowB.querySelector(`[data-col="${column}"]`);
                            let valA, valB;

                            if (type === 'date') {
                                valA = cellA.dataset.sortValue || '0';
                                valB = cellB.dataset.sortValue || '0';
                            } else {
                                valA = cellA.textContent.trim();
                                valB = cellB.textContent.trim();
                            }
                            
                            if (type === 'number') {
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) || 0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) || 0;
                                return newDirection === 'asc' ? valA - valB : valB - valA;
                            } else {
                                valA = valA.toLowerCase();
                                valB = valB.toLowerCase();
                                if (valA < valB) return newDirection === 'asc' ? -1 : 1;
                                if (valA > valB) return newDirection === 'asc' ? 1 : -1;
                                return 0;
                            }
                        });

                        tableBody.innerHTML = '';
                        rows.forEach(row => tableBody.appendChild(row));

                        headers.forEach(h => {
                            const icon = h.querySelector('.sort-icon i');
                            if (h === header) {
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.querySelector('.total-pro-responsive-table');
                if (!table) return;

                // LOGIKA MODAL
                const modalElement = document.getElementById('totalProDetailModal');
                if (modalElement) {
                    const proModal = new bootstrap.Modal(modalElement);
                    const tableRows = table.querySelectorAll('#totalProTableBody .clickable-row');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function () {
                            if (window.innerWidth < 768) {
                                document.getElementById('modalTotalProNo').textContent = this.dataset.no;
                                document.getElementById('modalTotalProSo').textContent = this.dataset.so;
                                document.getElementById('modalTotalProSoItem').textContent = this.dataset.soItem;
                                document.getElementById('modalTotalProPro').textContent = this.dataset.pro;
                                document.getElementById('modalTotalProStatus').innerHTML = `<span class="badge rounded-pill ${this.dataset.statusClass}">${this.dataset.statusText}</span>`;
                                document.getElementById('modalTotalProMaterialCode').textContent = this.dataset.materialCode;
                                document.getElementById('modalTotalProDescription').textContent = this.dataset.description;
                                document.getElementById('modalTotalProPlant').textContent = this.dataset.plant;
                                document.getElementById('modalTotalProMrp').textContent = this.dataset.mrp;
                                document.getElementById('modalTotalProQtyOrder').textContent = this.dataset.qtyOrder;
                                document.getElementById('modalTotalProQtyGr').textContent = this.dataset.qtyGr;
                                document.getElementById('modalTotalProOutsGr').textContent = this.dataset.outsGr;
                                document.getElementById('modalTotalProStartDate').textContent = this.dataset.startDate;
                                document.getElementById('modalTotalProEndDate').textContent = this.dataset.endDate;
                                proModal.show();
                            }
                        });
                    });
                }

                // LOGIKA SORTING (sama seperti sebelumnya, disesuaikan untuk tabel ini)
                const headers = table.querySelectorAll('.sortable-header');
                const tableBody = table.querySelector('#totalProTableBody');
                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const column = header.dataset.sortColumn;
                        const type = header.dataset.sortType || 'text';
                        const currentDirection = header.dataset.sortDirection || 'desc';
                        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                        
                        header.dataset.sortDirection = newDirection;
                        const rows = Array.from(tableBody.querySelectorAll('tr[data-status]')); // Ambil semua baris yang punya data-status

                        rows.sort((rowA, rowB) => {
                            const cellA = rowA.querySelector(`[data-col="${column}"]`);
                            const cellB = rowB.querySelector(`[data-col="${column}"]`);
                            let valA, valB;

                            if (type === 'date') {
                                valA = cellA.dataset.sortValue || '0';
                                valB = cellB.dataset.sortValue || '0';
                            } else {
                                valA = cellA.textContent.trim();
                                valB = cellB.textContent.trim();
                            }
                            
                            if (type === 'number') {
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) || 0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) || 0;
                                return newDirection === 'asc' ? valA - valB : valB - valA;
                            } else {
                                valA = valA.toLowerCase();
                                valB = valB.toLowerCase();
                                if (valA < valB) return newDirection === 'asc' ? -1 : 1;
                                if (valA > valB) return newDirection === 'asc' ? 1 : -1;
                                return 0;
                            }
                        });

                        tableBody.innerHTML = '';
                        rows.forEach(row => tableBody.appendChild(row));

                        headers.forEach(h => {
                            const icon = h.querySelector('.sort-icon i');
                            if (h === header) {
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });

            document.addEventListener("DOMContentLoaded", function() {
                const form = document.getElementById('searchPROForm');
                const proInput = document.getElementById('proInput');
                const addButton = document.getElementById('addProButton');
                const badgeContainer = document.getElementById('proBadgeContainer');
                const hiddenInput = document.getElementById('proNumbersInput');
                const placeholder = document.getElementById('proBadgePlaceholder');
                const clearAllBtn = document.getElementById('clearAllProButton');
                const selectAll = document.getElementById('selectAllTotalProCheckbox');
                const copyBtn = document.getElementById('copyProBtn');
                const tableBody = document.getElementById('totalProTableBody'); 

                let proList = [];

                function updateProAdderUI() {
                    badgeContainer.innerHTML = '';
                    if (proList.length === 0) {
                        if (placeholder) badgeContainer.appendChild(placeholder);
                        if (clearAllBtn) clearAllBtn.style.display = 'none';
                    } else {
                        if (clearAllBtn) clearAllBtn.style.display = 'inline-block';
                        proList.forEach(pro => {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-primary d-inline-flex align-items-center';
                            badge.style.padding = '0.5em 0.75em';
                            badge.style.fontSize = '0.9rem';
                            badge.innerHTML = `
                                <span>${pro}</span>
                                <button type="button" class="btn-close btn-close-white ms-2" 
                                        data-pro="${pro}" 
                                        aria-label="Remove" 
                                        style="font-size: 0.7rem;"></button>
                            `;
                            badgeContainer.appendChild(badge);
                        });
                    }
                    if (hiddenInput) {
                        hiddenInput.value = JSON.stringify(proList);
                    }
                }

                function addPro() {
                    const rawInput = proInput.value.trim();
                    const proNumbers = rawInput.split(/[\s,]+/)
                                            .map(pro => pro.trim().toUpperCase())
                                            .filter(pro => pro); 
                    let itemsAdded = 0;
                    if (proNumbers.length > 0) {
                        proNumbers.forEach(proValue => {
                            if (proValue && !proList.includes(proValue)) {
                                proList.push(proValue);
                                itemsAdded++;
                            } else if (proList.includes(proValue)) {
                                console.warn(`PRO ${proValue} already in list.`);
                            }
                        });
                    }
                    if (itemsAdded > 0) updateProAdderUI();
                    proInput.value = '';
                    proInput.focus();
                }

                function removePro(proValue) {
                    proList = proList.filter(pro => pro !== proValue);
                    updateProAdderUI(); 
                    proInput.focus();
                }

                if (form) {
                    addButton.addEventListener('click', addPro);
                    proInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault(); 
                            addPro();
                        }
                    });
                    badgeContainer.addEventListener('click', function(e) {
                        if (e.target.classList.contains('btn-close')) {
                            const proToRemove = e.target.dataset.pro;
                            removePro(proToRemove);
                        }
                    });
                    clearAllBtn.addEventListener('click', function() {
                        proList = []; 
                        updateProAdderUI(); 
                        proInput.focus();
                    });
                    form.addEventListener('submit', function(e) {
                        if (proList.length === 0) {
                            e.preventDefault(); 
                            alert('Please add at least one PRO number.');
                            proInput.focus();
                            return;
                        }
                        console.log('Submitting data:', hiddenInput.value);
                    });
                    updateProAdderUI();
                }

                if (selectAll && copyBtn && tableBody) {

                    function getSelectedPros() {
                        const selected = [];
                        const allRows = tableBody.querySelectorAll('tr');
                        
                        allRows.forEach(row => {
                            if (row.style.display !== 'none') {
                                const cb = row.querySelector('.pro-checkbox');
                                if (cb && cb.checked) {
                                    selected.push(cb.value);
                                }
                            }
                        });
                        return selected;
                    }
                    function updateSelectionState() {
                        const allRows = tableBody.querySelectorAll('tr');
                        let visibleCheckboxCount = 0;
                        let selectedVisibleCount = 0;

                        allRows.forEach(row => {
                            // Cek apakah baris ini sedang ditampilkan
                            if (row.style.display !== 'none') {
                                const cb = row.querySelector('.pro-checkbox');
                                if (cb) {
                                    visibleCheckboxCount++; // Hitung total checkbox yang terlihat
                                    if (cb.checked) {
                                        selectedVisibleCount++; // Hitung checkbox yang tercentang & terlihat
                                    }
                                }
                            }
                        });

                        const totalCount = visibleCheckboxCount;
                        const selectedCount = selectedVisibleCount;

                        // Update tombol Copy
                        if (selectedCount > 0) {
                            copyBtn.disabled = false;
                            copyBtn.textContent = `Copy ${selectedCount} PRO`;
                        } else {
                            copyBtn.disabled = true;
                            copyBtn.textContent = 'Copy PRO';
                        }

                        // Update status checkbox "Select All"
                        if (totalCount === 0) {
                            selectAll.checked = false;
                            selectAll.indeterminate = false;
                            return;
                        }

                        if (selectedCount === 0) {
                            selectAll.checked = false;
                            selectAll.indeterminate = false;
                        } else if (selectedCount === totalCount) {
                            selectAll.checked = true;
                            selectAll.indeterminate = false;
                        } else {
                            // Ada yang tercentang, tapi tidak semua
                            selectAll.checked = false;
                            selectAll.indeterminate = true;
                        }
                    }

                    copyBtn.addEventListener('click', function() {
                        const selectedPros = getSelectedPros();
                        
                        if (selectedPros.length > 0) {
                            const proListString = selectedPros.join('\n');
                            navigator.clipboard.writeText(proListString).then(() => {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil Disalin',
                                        text: `${selectedPros.length} nomor PRO telah disalin ke clipboard.`,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                } else {
                                    alert(`${selectedPros.length} nomor PRO disalin!`);
                                }
                            }).catch(err => {
                                console.error('Gagal menyalin:', err);
                                alert('Gagal menyalin ke clipboard.');
                            });
                        }
                    });

                    selectAll.addEventListener('change', function() {
                        const allRows = tableBody.querySelectorAll('tr');
                        
                        allRows.forEach(row => {
                            // Cek apakah baris ini sedang ditampilkan
                            if (row.style.display !== 'none') {
                                const checkbox = row.querySelector('.pro-checkbox');
                                if (checkbox) {
                                    checkbox.checked = this.checked;
                                }
                            }
                        });
                        updateSelectionState();
                    });
                    tableBody.addEventListener('change', function(e) {
                        if (e.target.classList.contains('pro-checkbox')) {
                            updateSelectionState();
                        }
                    });
                    
                    updateSelectionState();
                    
                } else {
                    console.warn("Elemen 'Select All', 'Copy PRO', atau 'totalProTableBody' tidak ditemukan. Fungsionalitas Copy dinonaktifkan.");
                }
            });
        </script>
    @endpush

</x-layouts.app>
