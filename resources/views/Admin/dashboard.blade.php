<x-layouts.app title="Dashboard Plant">

    @push('styles')
    <style>
        .table-container-scroll {
            max-height: 60vh; /* Atur tinggi maksimal sesuai kebutuhan */
            overflow-y: auto;
        }


        /* 2. ATUR SEMUA HEADER (th) AGAR STICKY */
        thead.table-light th {
            /* --- Properti untuk STICKY --- */
            position: sticky;
            top: 0;
            z-index: 10; /* Pastikan header selalu di lapisan teratas */

            /* Beri warna latar belakang agar tidak transparan saat scroll */
            background-color: #f8f9fa; 
        }
         /* 3. ATUR POSISI IKON AGAR TEKS RATA TENGAH */
        /* Targetkan hanya header yang bisa di-sort */
        thead.table-light th.sortable-header {
        /* Beri ruang di kanan agar teks tidak tertimpa ikon */
            padding-right: 25px; 
        }

        /* Posisikan ikonnya secara absolut di sebelah kanan */
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
                    <h1 class="h3 fw-bold text-dark">Dashboard - {{ $nama_bagian }} - {{ $sub_kategori }} </h1>
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
                    {{-- [DIUBAH] Kartu ini sekarang bisa diklik --}}
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
                                <p class="text-muted mb-0">Outgoing PRO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $ongoingPRO ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <form id="searchPROForm">
                <div class="row g-2">
                    {{-- Input Hidden untuk Data Header dari Laravel/Blade --}}
                    <input type="hidden" name="werks_code" id="werksCode" value="{{ $kode }}">
                    <input type="hidden" name="bagian_name" id="bagianName" value="{{ $nama_bagian }}">
                    <input type="hidden" name="categories_name" id="categoriesName" value="{{ $kategori }}">
                    
                    <div class="col-md-9 col-lg-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control border-start-0" 
                                id="proNumber" 
                                name="proNumber" 
                                placeholder="Enter PRO number (e.g., PRO-2024-001)"
                                required
                                autocomplete="off">
                        </div>
                        <small class="text-muted d-block mt-2">Enter the PRO number to view detailed information</small>
                    </div>
                
                    <div class="col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right me-2"></i>
                            <span>Go To PRO</span>
                        </button>
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
                            <h3 class="h5 fw-semibold text-dark mb-1">List of Ongoing PRO</h3>
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

        {{-- [BARU] Seksi Tabel untuk Total PRO --}}
        
        <div id="totalProSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
    
                    {{-- Header Card & Filter (Tidak Berubah) --}}
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
                            <button id="backToDashboardBtnTotalPro" class="btn btn-outline-secondary flex-shrink-0">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </button>
                        </div>
                    </div>
                    <div class="mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInputTotalPro" placeholder="Cari SO, PRO, Material..." class="form-control border-start-0">
                        </div>
                    </div>
    
                    {{-- Tabel Responsif --}}
                    <div class="table-container-scroll">
                        {{-- [UBAH] Tambahkan class unik 'total-pro-responsive-table' --}}
                        <table class="table table-hover table-striped table-sm align-middle mb-0 total-pro-responsive-table">
                            <thead class="table-light">
                                <tr class="small text-uppercase align-middle">
                                    {{-- [UBAH] Sembunyikan semua kolom di mobile kecuali PRO dan Status --}}
                                    <th class="text-center d-none d-md-table-cell">No.</th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so" data-sort-type="text">SO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so_item" data-sort-type="text">SO. Item <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                    
                                    {{-- Kolom yang TERLIHAT di mobile --}}
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
                                    <tr><td colspan="14" class="text-center p-5 text-muted">Tidak ada data PRO yang ditemukan.</td></tr>
                                @endforelse
                                <tr id="noResultsTotalProRow" style="display: none;"><td colspan="14" class="text-center p-5 text-muted">Tidak ada data yang cocok.</td></tr>
                            </tbody>
                        </table>
                    </div>
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
        </script>
    @endpush

</x-layouts.app>
