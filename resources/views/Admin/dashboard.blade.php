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
                    <div id="card-outstanding-so"
                        class="card border-0 shadow-sm h-100 card-interactive position-relative"
                        style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                            data-bs-content="Menampilkan jumlah Sales Order (SO) pada bagian ini. Klik untuk detail.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div
                                    class="d-inline-flex align-items-center justify-content-center bg-info-subtle rounded-3 me-3 p-2">
                                    <svg class="text-info-emphasis" width="24" height="24" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-muted mb-0">Outstanding SO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $TData2 ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div id="card-total-pro" class="card border-0 shadow-sm h-100 card-interactive position-relative"
                        style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                            data-bs-content="Jumlah total Production Order (PRO) pada bagian ini. Klik untuk detail.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div
                                    class="d-inline-flex align-items-center justify-content-center bg-primary-subtle rounded-3 me-3 p-2">
                                    <svg class="text-primary-emphasis" width="24" height="24" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-muted mb-0">Total PRO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $TData3 ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div id="card-outstanding-reservasi"
                        class="card border-light-subtle shadow-sm h-100 card-interactive position-relative"
                        style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                            data-bs-content="Jumlah material yang dibutuhkan untuk produksi tetapi stoknya belum mencukupi. Klik untuk detail.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div
                                    class="d-inline-flex align-items-center justify-content-center bg-danger-subtle rounded-3 me-3 p-2">
                                    <svg class="text-danger-emphasis" width="24" height="24" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-muted mb-0">Outstanding Reservasi</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0"
                                data-target="{{ $outstandingReservasi ?? 0 }}">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div id="card-outgoing-pro"
                        class="card border-light-subtle shadow-sm h-100 card-interactive position-relative"
                        style="cursor: pointer;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                            data-bs-content="Jumlah PRO yang basic start date nya hari ini. click untuk melihat detail...">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div
                                    class="d-inline-flex align-items-center justify-content-center bg-success-subtle rounded-3 me-3 p-2">
                                    <svg class="text-success-emphasis" width="24" height="24" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-muted mb-0">On Process PRO</p>
                            </div>
                            <p class="stat-value h2 fw-bold text-dark mt-3 mb-0" data-target="{{ $ongoingPRO ?? 0 }}">
                                0</p>
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
                            <input type="text" class="form-control border-start-0" id="proInput"
                                placeholder="Ketik PRO, pisahkan dengan spasi atau koma" autocomplete="off">
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

                            <button type="button" class="btn btn-link text-danger p-0" id="clearAllProButton"
                                style="display: none; text-decoration: none;">
                                <small><i class="fas fa-trash me-1"></i>Clear All</small>
                            </button>
                        </div>
                        <div id="proBadgeContainer" class="border p-3 rounded"
                            style="min-height: 80px; background-color: #f8f9fa; display: flex; flex-wrap: wrap; gap: 8px;">
                            <span id="proBadgePlaceholder" class="text-muted fst-italic">Added PROs will appear
                                here...</span>
                        </div>
                    </div>
                </div>
            </form>

            <div id="chartsSection" class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0 h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="left"
                            data-bs-content="Grafik ini membandingkan total jam kapasitas yang tersedia dengan jumlah PRO di setiap workcenter.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4">
                            <h3 class="h5 fw-semibold text-dark">Workcenter Capacity Data</h3>
                            <p class="small text-muted mb-4">Comparison of the Count of PROs and Capacity at each
                                Workcenter.</p>
                            <div class="chart-wrapper">
                                <canvas id="myBarChart" data-labels="{{ json_encode($labels ?? []) }}"
                                    data-datasets="{{ json_encode($datasets ?? []) }}"
                                    data-urls="{{ json_encode($targetUrls ?? []) }}"></canvas>
                            </div>
                            <div class="mt-4 text-center">
                                <p class="small text-muted mb-2">Press to view workcenter compatibility status</p>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @if (!empty($labels))
                                        @foreach ($labels as $index => $label)
                                            <a href="{{ $targetUrls[$index] ?? '#' }}"
                                                class="btn btn-sm btn-outline-primary">
                                                {{ $label }}
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" id="proStatusCardContainer" data-bagian="{{ $nama_bagian }}"
                    data-kategori="{{ $kategori }}" data-kode="{{ $kode }}">
                    <div class="card shadow-sm border-0 h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="left"
                            data-bs-content="Distribusi persentase dari semua status Production Order yang ada.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4 d-flex flex-column">
                            <div id="cardHeaderContent">
                                <h3 class="h5 fw-semibold text-dark">PRO Status in - {{ $nama_bagian }}
                                    {{ $kategori }}</h3>
                                <p class="small text-muted mb-4">Distribusi status pada Production Order.</p>
                            </div>

                            <div id="chartView" class="chart-wrapper flex-grow-1">
                                <canvas id="pieChart" data-labels="{{ json_encode($doughnutChartLabels ?? []) }}"
                                    data-datasets="{{ json_encode($doughnutChartDatasets ?? []) }}"></canvas>
                            </div>

                            <div id="tableView" style="display: none;" class="flex-grow-1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="left"
                            data-bs-content="5 workcenter teratas yang memiliki total jam kapasitas paling tinggi.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h5 fw-semibold text-dark">Ranked 5th Workcenter in - {{ $nama_bagian }}
                                {{ $kategori }}</h3>
                            <p class="small text-muted mb-4">Based on the highest total capacity.</p>
                            <div class="chart-wrapper flex-grow-1">
                                <canvas id="lollipopChart" data-labels="{{ json_encode($lolipopChartLabels ?? []) }}"
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
                    <div
                        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
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
                        <form action="{{ url()->current() }}" method="GET">
                            @foreach(request()->except(['search_so', 'page_so']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" name="search_so" value="{{ $searchSo ?? '' }}" placeholder="Search Order... (Enter)"
                                    class="form-control border-start-0">
                            </div>
                        </form>
                    </div>

                    {{-- Tabel Responsif --}}
                    <div class="table-container-scroll" data-section="so" data-next-page="{{ $salesOrderData->currentPage() + 1 }}" data-has-more="{{ $salesOrderData->hasMorePages() ? 'true' : 'false' }}" style="max-height: 500px; overflow-y: auto;">
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
                                    <th class="text-center d-none d-md-table-cell sortable-header"
                                        data-sort-column="material">
                                        Material FG <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="d-none d-md-table-cell sortable-header" data-sort-column="description">
                                        Description <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="outstandingSoTableBody">
                                @include('Admin.partials.rows_sales_order')
                            </tbody>
                        </table>
                        <div class="text-center py-2 loading-spinner d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="scroll-sentinel"></div>
                    </div>

                </div>
            </div>
        </div>


        <div id="outstandingReservasiSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    {{-- Header Card & Search --}}
                    <div
                        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">List Item of Outstanding Reservasi</h3>
                            <p class="small text-muted mb-0">Daftar Material yang dibutuhkan dan belum terpenuhi.</p>
                        </div>
                        <button id="backToDashboardBtn" class="btn btn-outline-secondary flex-shrink-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </button>
                    </div>
                    <div class="mb-3" style="max-width: 320px;">
                        <form action="{{ url()->current() }}" method="GET">
                            @foreach(request()->except(['search_reservasi', 'page_reservasi']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" name="search_reservasi" value="{{ $searchReservasi ?? '' }}" placeholder="Search Reservasi... (Enter)"
                                    class="form-control border-start-0">
                            </div>
                        </form>
                    </div>

                    {{-- Tabel Responsif --}}
                    <div class="table-container-scroll" data-section="reservasi" data-next-page="{{ $TData4->currentPage() + 1 }}" data-has-more="{{ $TData4->hasMorePages() ? 'true' : 'false' }}" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover table-striped align-middle mb-0 reservasi-responsive-table">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="text-center d-none d-md-table-cell">No.</th>
                                    <th class="sortable-header" data-sort-column="reservasi" data-sort-type="text">
                                        No. Reservasi <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class=" text-center sortable-header" data-sort-column="material_code"
                                        data-sort-type="text">
                                        Material Code <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header"
                                        data-sort-column="description" data-sort-type="text">
                                        Material Description <span class="sort-icon"><i
                                                class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header"
                                        data-sort-column="req_qty" data-sort-type="number">
                                        Req. Qty <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    {{-- [UBAH] Menambahkan header kolom baru "Req. Commited" --}}
                                    <th class="text-center d-none d-md-table-cell sortable-header"
                                        data-sort-column="vmeng" data-sort-type="number">
                                        Req. Commited <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="text-center d-none d-md-table-cell sortable-header"
                                        data-sort-column="stock" data-sort-type="number">
                                        Stock <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="reservasiTableBody">
                                @include('Admin.partials.rows_reservasi')
                            </tbody>
                        </table>
                        <div class="text-center py-2 loading-spinner d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="scroll-sentinel"></div>
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
                    <form action="{{ url()->current() }}" method="GET">
                        @foreach(request()->except(['search_pro', 'page_pro']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" name="search_pro" value="{{ $searchPro ?? '' }}" placeholder="Search SO, PRO... (Enter)"
                                class="form-control border-start-0">
                        </div>
                    </form>
                </div>

                {{-- Tabel Responsif --}}
                <div class="table-container-scroll" data-section="pro" data-next-page="{{ $ongoingProData->currentPage() + 1 }}" data-has-more="{{ $ongoingProData->hasMorePages() ? 'true' : 'false' }}" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover table-striped table-sm align-middle mb-0 pro-responsive-table">
                        <thead class="table-light">
                            <tr class="small text-uppercase align-middle">
                                {{-- [DISEMBUNYIKAN DI MOBILE] --}}
                                <th class="text-center d-none d-md-table-cell">No.</th>
                                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so"
                                    data-sort-type="text">SO <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="so_item" data-sort-type="text">Item <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>

                                {{-- [TETAP TAMPIL DI MOBILE] --}}
                                <th class="text-center sortable-header" data-sort-column="pro" data-sort-type="text">
                                    PRO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center sortable-header" data-sort-column="status"
                                    data-sort-type="text">Status <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>

                                {{-- [DISEMBUNYIKAN DI MOBILE] --}}
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="material_code" data-sort-type="text">Material Code <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="description" data-sort-type="text">Material Description <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="plant" data-sort-type="text">Plant <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="mrp"
                                    data-sort-type="text">MRP <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="qty_order" data-sort-type="number">Qty. ORDER <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="qty_gr" data-sort-type="number">Qty. GR <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="outs_gr" data-sort-type="number">Outs. GR <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="start_date" data-sort-type="date">Start Date <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="end_date" data-sort-type="date">End Date <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                            </tr>
                        </thead>
                        <tbody id="ongoingProTableBody">
                             @include('Admin.partials.rows_ongoing_pro')
                        </tbody>
                    </table>
                    <div class="text-center py-2 loading-spinner d-none">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="scroll-sentinel"></div>
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
                <div class="mb-3" style="max-width: 450px;"> 
                    <form action="{{ url()->current() }}" method="GET">
                        @foreach(request()->except(['search_total_pro', 'page_total_pro']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" name="search_total_pro" value="{{ $searchTotalPro ?? '' }}" placeholder="Search SO, PRO... (Enter)"
                                class="form-control">
                            
                            {{-- Input Hidden untuk Multi Material Filter (Legacy, kept for compat if needed, but overshadowed by Advanced Search) --}}
                            <input type="hidden" name="multi_matnr" id="multiMatnrHiddenInput" value="{{ request('multi_matnr') }}">

                            <!-- TOMBOL BARU: CLEAR FILTER -->
                            <button type="button" class="btn btn-outline-danger" id="btnResetFilter"
                                title="Reset Semua Filter & Pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ADVANCED SEARCH UI --}}
                <div class="mb-3">
                    <button class="btn btn-sm btn-light w-100 border text-start text-secondary fw-bold d-flex align-items-center justify-content-between" 
                            type="button" 
                            onclick="toggleAdvancedSearch()"
                            style="font-size: 0.8rem;">
                        <span><i class="fas fa-sliders me-2"></i> Advanced Search (Specific Fields)</span>
                        <i class="fas fa-chevron-down text-xs" id="advSearchIcon"></i>
                    </button>
                    
                    <div class="d-none border-start border-end border-bottom rounded-bottom shadow-sm" id="advancedSearchCollapse">
                        <div class="bg-white p-3">
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="advAufnr" class="form-control" placeholder="PRO" value="{{ $advAufnr ?? '' }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advAufnr', 'PRO List')" title="Input Multiple"><i class="fas fa-list-ul"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="advMatnr" class="form-control" placeholder="Material" value="{{ $advMatnr ?? '' }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advMatnr', 'Material List')" title="Input Multiple"><i class="fas fa-list-ul"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="advMaktx" class="form-control" placeholder="Description" value="{{ $advMaktx ?? '' }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advMaktx', 'Desc. List')" title="Input Multiple"><i class="fas fa-list-ul"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="advKdauf" class="form-control" placeholder="SO (KDAUF)" value="{{ $advKdauf ?? '' }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advKdauf', 'SO List', false)" title="Input Multiple"><i class="fas fa-list-ul"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="advKdpos" class="form-control" placeholder="Item" value="{{ $advKdpos ?? '' }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMultiInput('advKdpos', 'Item List', false)" title="Input Multiple"><i class="fas fa-list-ul"></i></button>
                                    </div>
                                </div>
                                    <div class="col-md-1">
                                    <button class="btn btn-sm btn-outline-danger w-100 fw-bold" onclick="clearAdvancedSearch()" title="Reset Filters"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="text-xs text-muted mt-2 fst-italic">
                                <i class="fas fa-circle-info me-1"></i> Gunakan ini untuk pencarian spesifik per kolom. Pencarian utama diatas akan diabaikan jika kolom ini terisi.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MULTI INPUT MODAL (GENERIC) --}}
                <div class="modal fade" id="multiInputModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-0 bg-primary bg-opacity-10">
                                <h6 class="modal-title fw-bold text-primary" id="multiInputTitle">Input List Parameter</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-3">
                                <p class="text-muted small mb-2">Paste list parameter disini (dipisahkan baris baru atau koma):</p>
                                <textarea id="multiInputTextarea" class="form-control" rows="10" placeholder="Contoh:&#10;1001&#10;1002&#10;1003"></textarea>
                            </div>
                            <div class="modal-footer border-0 p-2 bg-light">
                                <button type="button" class="btn btn-white text-muted fw-bold btn-sm border" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-primary fw-bold btn-sm shadow-sm" onclick="applyMultiInput()">Isi Parameter</button>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="modal fade" id="multiMatnrModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-filter me-2"></i>Filter Multi Material</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">Masukkan kode material (satu per baris). Anda bisa
                                    copy-paste langsung dari Excel.</p>
                                <div class="form-group">
                                    <textarea id="multiMatnrInput" class="form-control" rows="10"
                                        placeholder="Contoh:&#10;KG-12345&#10;KG-67890&#10;..."></textarea>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-secondary" id="matnrCountInfo">0 kode terdeteksi</small>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none"
                                        id="clearMatnrFilter">Hapus Filter</button>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-primary" id="applyMatnrFilter">Terapkan
                                    Filter</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-container-scroll" data-section="total_pro" data-next-page="{{ $allProData->currentPage() + 1 }}" data-has-more="{{ $allProData->hasMorePages() ? 'true' : 'false' }}" style="max-height: 500px; overflow-y: auto;">
                    <table
                        class="table table-hover table-striped table-sm align-middle mb-0 total-pro-responsive-table">
                        <thead class="table-light">
                            <tr class="small text-uppercase align-middle">
                                <th class="text-center" style="width: 1%;">
                                    <input class="form-check-input" type="checkbox" id="selectAllTotalProCheckbox"
                                        title="Pilih Semua">
                                </th>
                                <th class="text-center d-none d-md-table-cell">No.</th>
                                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="so"
                                    data-sort-type="text">SO <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="so_item" data-sort-type="text">Item <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center sortable-header" data-sort-column="pro" data-sort-type="text">
                                    PRO <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center sortable-header" data-sort-column="status"
                                    data-sort-type="text">Status <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="material_code" data-sort-type="text">Material<span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="description" data-sort-type="text">Deskripsi <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="plant" data-sort-type="text">Plant <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header" data-sort-column="mrp"
                                    data-sort-type="text">MRP <span class="sort-icon"><i
                                            class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="qty_order" data-sort-type="number">Qty. ORDER <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="qty_gr" data-sort-type="number">Qty. GR <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="outs_gr" data-sort-type="number">Outs. GR <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="start_date" data-sort-type="date">Start Date <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                                <th class="text-center d-none d-md-table-cell sortable-header"
                                    data-sort-column="end_date" data-sort-type="date">End Date <span
                                        class="sort-icon"><i class="fas fa-sort"></i></span></th>
                            </tr>
                        </thead>
                        <tbody id="totalProTableBody">
                            @include('Admin.partials.rows_total_pro')
                        </tbody>
                    </table>
                    <div class="text-center py-2 loading-spinner d-none">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="scroll-sentinel"></div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="loadingModalLabel" aria-hidden="true">
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
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const scrollContainers = document.querySelectorAll('.table-container-scroll');
                let loadingStates = {};

                const observerOptions = {
                    root: null,
                    rootMargin: '200px',
                    threshold: 0.1
                };

                scrollContainers.forEach(container => {
                    const sentinel = container.querySelector('.scroll-sentinel');
                    if (!sentinel) return;

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                loadMoreData(container);
                            }
                        });
                    }, {
                        root: container,
                        rootMargin: '200px',
                        threshold: 0.1
                    });
                    
                    observer.observe(sentinel);
                });

                function loadMoreData(container) {
                    const section = container.dataset.section;
                    if (!section) return;
                    
                    const hasMore = container.dataset.hasMore === 'true';
                    let nextPage = parseInt(container.dataset.nextPage);
                    
                    if (!hasMore || loadingStates[section]) return;
                    
                    loadingStates[section] = true;
                    
                    const spinner = container.querySelector('.loading-spinner');
                    if(spinner) spinner.classList.remove('d-none');

                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('load_more', section);
                    urlParams.set('page_' + section, nextPage);

                    fetch(`${window.location.pathname}?${urlParams.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        if (html.trim().length > 0) {
                            const tbody = container.querySelector('tbody');
                            if (tbody) {
                                tbody.insertAdjacentHTML('beforeend', html);
                                container.dataset.nextPage = nextPage + 1;
                            }
                        } else {
                            container.dataset.hasMore = 'false';
                        }
                    })
                    .catch(err => {
                        console.error('Error loading more data:', err);
                        container.dataset.hasMore = 'false'; 
                    })
                    .finally(() => {
                        loadingStates[section] = false;
                        if(spinner) spinner.classList.add('d-none');
                    });
                }
                // --- REALTIME SEARCH IMPLEMENTATION ---
                const searchInputs = {
                    'so': document.querySelector('input[name="search_so"]'),
                    'reservasi': document.querySelector('input[name="search_reservasi"]'),
                    'pro': document.querySelector('input[name="search_pro"]'),
                    'total_pro': document.querySelector('input[name="search_total_pro"]')
                };

                const debounce = (func, wait) => {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                };

                Object.keys(searchInputs).forEach(section => {
                    const input = searchInputs[section];
                    if (input) {
                        // Prevent form submission on Enter
                        input.closest('form').addEventListener('submit', (e) => e.preventDefault());

                        input.addEventListener('input', debounce((e) => {
                            performSearch(section, e.target.value);
                        }, 500));
                    }
                });


                
                // Expose performSearch to global scope for other scripts to use
                window.performSearch = performSearch;

                function performSearch(section, searchTerm) {
                    const container = document.querySelector(`.table-container-scroll[data-section="${section}"]`);
                    if (!container) return;

                    const spinner = container.querySelector('.loading-spinner');
                    if (spinner) spinner.classList.remove('d-none');

                    // Reset pagination for new search
                    container.dataset.nextPage = 2; // Next page to load will be 2
                    container.dataset.hasMore = 'true';
                    container.scrollTop = 0; // Scroll to top

                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('load_more', section);
                    urlParams.set(`page_${section}`, 1); // Request page 1
                    urlParams.set(`search_${section}`, searchTerm); // Update search term
                    
                    // [NEW] Check for multi_matnr input if section is total_pro
                    if (section === 'total_pro') {
                        // 1. Multi Material (Legacy/Integrated)
                        const multiMatnrInput = document.getElementById('multiMatnrHiddenInput');
                        if (multiMatnrInput && multiMatnrInput.value) {
                             urlParams.set('multi_matnr', multiMatnrInput.value);
                        } else {
                             urlParams.delete('multi_matnr');
                        }

                        // 2. Advanced Search Params
                        const advIds = ['advAufnr', 'advMatnr', 'advMaktx', 'advKdauf', 'advKdpos'];
                        advIds.forEach(id => {
                            const el = document.getElementById(id);
                            const val = el?.value?.trim();
                            // Convert ID to param name: advAufnr -> adv_aufnr
                            const param = id.replace(/([A-Z])/g, '_$1').toLowerCase(); 
                            
                            if(val) urlParams.set(param, val);
                            else urlParams.delete(param);
                        });
                    }
                    
                    // Update URL without reloading (optional, helps if user wants to copy/paste link)
                    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
                    window.history.replaceState({}, '', newUrl);

                    fetch(newUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const tbody = container.querySelector('tbody');
                        if (tbody) {
                            tbody.innerHTML = html; // REPLACE content
                            
                            // If response is empty, no more pages
                            if (html.trim().length === 0) {
                                container.innerHTML += '<div class="text-center p-3 text-muted">No results found.</div>';
                                container.dataset.hasMore = 'false';
                            }
                        }
                    })
                    .catch(err => console.error('Search error:', err))
                    .finally(() => {
                        if (spinner) spinner.classList.add('d-none');
                        loadingStates[section] = false; // Reset loading state
                    });
                }
            });
        </script>
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
            document.addEventListener('DOMContentLoaded', function() {
                const soDetailModalElement = document.getElementById('soDetailModal');
                if (soDetailModalElement) {
                    const soDetailModal = new bootstrap.Modal(soDetailModalElement);
                    const tableRows = document.querySelectorAll('#outstandingSoTableBody .clickable-row');

                    tableRows.forEach(row => {
                        row.addEventListener('click', function() {
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
                            const valA = rowA.querySelector(`[data-col="${column}"]`)
                                .textContent.trim().toLowerCase();
                            const valB = rowB.querySelector(`[data-col="${column}"]`)
                                .textContent.trim().toLowerCase();

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
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' :
                                    'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = ''; // Reset arah sort header lain
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
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
                        row.addEventListener('click', function() {
                            if (window.innerWidth < 768) {
                                // Ambil data dari atribut `data-*`
                                document.getElementById('modalReservasiNo').textContent = this.dataset
                                    .no;
                                document.getElementById('modalReservasiRsv').textContent = this.dataset
                                    .reservasi;
                                document.getElementById('modalReservasiMatCode').textContent = this
                                    .dataset.materialCode;
                                document.getElementById('modalReservasiDesc').textContent = this.dataset
                                    .description;
                                document.getElementById('modalReservasiReqQty').textContent = this
                                    .dataset.reqQty;
                                document.getElementById('modalReservasiReqCommited').textContent = this
                                    .dataset.reqCommited;
                                document.getElementById('modalReservasiStock').textContent = this
                                    .dataset.stock;
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
                            let valA = rowA.querySelector(`[data-col="${column}"]`).textContent
                                .trim();
                            let valB = rowB.querySelector(`[data-col="${column}"]`).textContent
                                .trim();

                            if (type === 'number') {
                                // Hapus titik ribuan, lalu ubah jadi angka
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) ||
                                    0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) ||
                                    0;
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
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' :
                                    'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function() {
                const table = document.querySelector('.pro-responsive-table');
                if (!table) return;

                // LOGIKA MODAL
                const proDetailModalElement = document.getElementById('proDetailModal');
                if (proDetailModalElement) {
                    const proDetailModal = new bootstrap.Modal(proDetailModalElement);
                    const tableRows = table.querySelectorAll('#ongoingProTableBody .clickable-row');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function() {
                            if (window.innerWidth < 768) {
                                // Memasukkan semua data dari atribut data-* ke modal
                                document.getElementById('modalProNo').textContent = this.dataset.no;
                                document.getElementById('modalProSo').textContent = this.dataset.so;
                                document.getElementById('modalProSoItem').textContent = this.dataset
                                    .soItem;
                                document.getElementById('modalProPro').textContent = this.dataset.pro;
                                document.getElementById('modalProStatus').innerHTML =
                                    `<span class="badge rounded-pill ${this.dataset.statusClass}">${this.dataset.status}</span>`;
                                document.getElementById('modalProMaterialCode').textContent = this
                                    .dataset.materialCode;
                                document.getElementById('modalProDescription').textContent = this
                                    .dataset.description;
                                document.getElementById('modalProPlant').textContent = this.dataset
                                    .plant;
                                document.getElementById('modalProMrp').textContent = this.dataset.mrp;
                                document.getElementById('modalProQtyOrder').textContent = this.dataset
                                    .qtyOrder;
                                document.getElementById('modalProQtyGr').textContent = this.dataset
                                    .qtyGr;
                                document.getElementById('modalProOutsGr').textContent = this.dataset
                                    .outsGr;
                                document.getElementById('modalProStartDate').textContent = this.dataset
                                    .startDate;
                                document.getElementById('modalProEndDate').textContent = this.dataset
                                    .endDate;
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
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) ||
                                    0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) ||
                                    0;
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
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' :
                                    'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function() {
                const table = document.querySelector('.pro-responsive-table');
                if (!table) return;

                // LOGIKA MODAL
                const proDetailModalElement = document.getElementById('proDetailModal');
                if (proDetailModalElement) {
                    const proDetailModal = new bootstrap.Modal(proDetailModalElement);
                    const tableRows = table.querySelectorAll('#ongoingProTableBody .clickable-row');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function() {
                            if (window.innerWidth < 768) {
                                document.getElementById('modalProNo').textContent = this.dataset.no;
                                document.getElementById('modalProSo').textContent = this.dataset.so;
                                document.getElementById('modalProSoItem').textContent = this.dataset
                                    .soItem;
                                document.getElementById('modalProPro').textContent = this.dataset.pro;
                                document.getElementById('modalProStatus').innerHTML =
                                    `<span class="badge rounded-pill ${this.dataset.statusClass}">${this.dataset.status}</span>`;
                                document.getElementById('modalProMaterialCode').textContent = this
                                    .dataset.materialCode;
                                document.getElementById('modalProDescription').textContent = this
                                    .dataset.description;
                                document.getElementById('modalProPlant').textContent = this.dataset
                                    .plant;
                                document.getElementById('modalProMrp').textContent = this.dataset.mrp;
                                document.getElementById('modalProQtyOrder').textContent = this.dataset
                                    .qtyOrder;
                                document.getElementById('modalProQtyGr').textContent = this.dataset
                                    .qtyGr;
                                document.getElementById('modalProOutsGr').textContent = this.dataset
                                    .outsGr;
                                document.getElementById('modalProStartDate').textContent = this.dataset
                                    .startDate;
                                document.getElementById('modalProEndDate').textContent = this.dataset
                                    .endDate;
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
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) ||
                                    0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) ||
                                    0;
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
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' :
                                    'fas fa-sort-down';
                            } else {
                                h.dataset.sortDirection = '';
                                icon.className = 'fas fa-sort';
                            }
                        });
                    });
                });
            });
            document.addEventListener('DOMContentLoaded', function() {
                const table = document.querySelector('.total-pro-responsive-table');
                if (!table) return;

                // LOGIKA MODAL
                const modalElement = document.getElementById('totalProDetailModal');
                if (modalElement) {
                    const proModal = new bootstrap.Modal(modalElement);
                    const tableRows = table.querySelectorAll('#totalProTableBody .clickable-row');
                    tableRows.forEach(row => {
                        row.addEventListener('click', function() {
                            if (window.innerWidth < 768) {
                                document.getElementById('modalTotalProNo').textContent = this.dataset
                                    .no;
                                document.getElementById('modalTotalProSo').textContent = this.dataset
                                    .so;
                                document.getElementById('modalTotalProSoItem').textContent = this
                                    .dataset.soItem;
                                document.getElementById('modalTotalProPro').textContent = this.dataset
                                    .pro;
                                document.getElementById('modalTotalProStatus').innerHTML =
                                    `<span class="badge rounded-pill ${this.dataset.statusClass}">${this.dataset.statusText}</span>`;
                                document.getElementById('modalTotalProMaterialCode').textContent = this
                                    .dataset.materialCode;
                                document.getElementById('modalTotalProDescription').textContent = this
                                    .dataset.description;
                                document.getElementById('modalTotalProPlant').textContent = this.dataset
                                    .plant;
                                document.getElementById('modalTotalProMrp').textContent = this.dataset
                                    .mrp;
                                document.getElementById('modalTotalProQtyOrder').textContent = this
                                    .dataset.qtyOrder;
                                document.getElementById('modalTotalProQtyGr').textContent = this.dataset
                                    .qtyGr;
                                document.getElementById('modalTotalProOutsGr').textContent = this
                                    .dataset.outsGr;
                                document.getElementById('modalTotalProStartDate').textContent = this
                                    .dataset.startDate;
                                document.getElementById('modalTotalProEndDate').textContent = this
                                    .dataset.endDate;
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
                        const rows = Array.from(tableBody.querySelectorAll(
                            'tr[data-status]')); // Ambil semua baris yang punya data-status

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
                                valA = parseFloat(valA.replace(/\./g, '').replace(',', '.')) ||
                                    0;
                                valB = parseFloat(valB.replace(/\./g, '').replace(',', '.')) ||
                                    0;
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
                                icon.className = newDirection === 'asc' ? 'fas fa-sort-up' :
                                    'fas fa-sort-down';
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
                    console.warn(
                        "Elemen 'Select All', 'Copy PRO', atau 'totalProTableBody' tidak ditemukan. Fungsionalitas Copy dinonaktifkan."
                    );
                }

                // ===================================================================
                // LOGIKA ADVANCED SEARCH (NEW)
                // ===================================================================
                
                // 1. Toggle Advanced Search
                window.toggleAdvancedSearch = function() {
                    const box = document.getElementById('advancedSearchCollapse');
                    const icon = document.getElementById('advSearchIcon');
                    if(box) {
                        if(box.classList.contains('d-none')) {
                            box.classList.remove('d-none');
                            if(icon) icon.className = "fas fa-chevron-up text-xs";
                        } else {
                            box.classList.add('d-none');
                            if(icon) icon.className = "fas fa-chevron-down text-xs";
                        }
                    }
                };

                // 2. Clear Advanced Search
                window.clearAdvancedSearch = function() {
                    const advIds = ['advAufnr', 'advMatnr', 'advMaktx', 'advKdauf', 'advKdpos'];
                    advIds.forEach(id => {
                        const el = document.getElementById(id);
                        if(el) el.value = '';
                    });
                    
                    // Juga trigger search ulang (kosong)
                    performSearch();
                };

                // 3. Multi Input Modal Logic (Generic)
                let multiInputModalInstance;
                let currentMultiInputTargetId = null; 

                window.openMultiInput = function(targetId, title, isNumeric = false) {
                    currentMultiInputTargetId = targetId;
                    const cleanTitle = title || 'List Parameter';
                    
                    document.getElementById('multiInputTitle').innerText = 'Input ' + cleanTitle;
                    document.getElementById('multiInputTextarea').value = ''; 
                    
                    // Load existing value if any
                    const currentVal = document.getElementById(targetId)?.value || '';
                    if(currentVal) {
                        document.getElementById('multiInputTextarea').value = currentVal.split(',').join('\n');
                    }

                    if (!multiInputModalInstance) {
                        multiInputModalInstance = new bootstrap.Modal(document.getElementById('multiInputModal'));
                    }
                    multiInputModalInstance.show();
                };

                window.applyMultiInput = function() {
                    if (!currentMultiInputTargetId) return;

                    const rawText = document.getElementById('multiInputTextarea').value;
                    // Split by newline or comma, trim, filter empty
                    const items = rawText.split(/[\n,]+/).map(s => s.trim()).filter(s => s !== '');
                    
                    const targetInput = document.getElementById(currentMultiInputTargetId);
                    if (targetInput) {
                        targetInput.value = items.join(','); // Join with comma
                        
                        // Trigger search automatically? Or just let user press search? 
                        // Let's mimic create-wi: trigger event or just fill
                        // create-wi triggers search on keyup. Here we have a dedicated search function usually?
                        // Dashboard usually relies on 'Enter' in main search. 
                        // Let's trigger search immediately for better UX
                        performSearch();
                    }
                    
                    multiInputModalInstance.hide();
                };

                // 4. Integrasi dengan Perform Search
                // Kita perlu modifikasi/override behavior search bawaan atau tambahkan params saat submit
                
                function performSearch() {
                    // This function is now DEPRECATED/REMOVED in favor of the main AJAX performSearch above.
                    // We will redirect calls to the main performSearch.
                    
                    const mainSearchInput = document.querySelector('input[name="search_total_pro"]');
                    const searchTerm = mainSearchInput ? mainSearchInput.value : '';
                    if (typeof window.performSearch === 'function') {
                         window.performSearch('total_pro', searchTerm);
                    }
                }

                // Attach 'Enter' key to Advanced Inputs
                const advIds = ['advAufnr', 'advMatnr', 'advMaktx', 'advKdauf', 'advKdpos'];
                advIds.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) {
                        el.addEventListener('keyup', function(e) {
                            if (e.key === 'Enter') {
                                performSearch();
                            }
                        });
                    }
                });

                // Attach to Main Search Input to include Advanced Params
                const mainSearchInput = document.querySelector('input[name="search_total_pro"]');
                if(mainSearchInput) {
                    // Prevent default form submit to handle params manually?
                    // Or inject hidden inputs? Injecting hidden inputs is easier for standard form submit.
                    // But performSearch with URL manipulation is cleaner given the dynamic nature.
                    mainSearchInput.closest('form').addEventListener('submit', function(e) {
                         e.preventDefault();
                         performSearch();
                    });
                }
                
                // Reset Button Logic
                const btnReset = document.getElementById('btnResetFilter');
                if(btnReset) {
                    btnReset.addEventListener('click', function() {
                        // Clear all inputs
                        if(mainSearchInput) mainSearchInput.value = '';
                        advIds.forEach(id => {
                            const el = document.getElementById(id);
                            if(el) el.value = '';
                        });
                        
                        // Use AJAX Search to Clear
                        if (typeof window.performSearch === 'function') {
                           window.performSearch('total_pro', '');
                        }
                    });
                }
                
                // Initialize Values from URL (on Load)
                const urlParams = new URL(window.location.href).searchParams;
                advIds.forEach(id => {
                    const param = id.replace(/([A-Z])/g, '_$1').toLowerCase();
                    const val = urlParams.get(param);
                    const el = document.getElementById(id);
                    if(val && el) {
                        el.value = val;
                        // Show collapsible if any advanced param is present
                        const box = document.getElementById('advancedSearchCollapse');
                        const icon = document.getElementById('advSearchIcon');
                        if (box) { 
                            box.classList.remove('d-none');
                             if(icon) icon.className = "fas fa-chevron-up text-xs";
                        }
                    }
                });


                // ===================================================================
                // LOGIKA MULTI MATERIAL FILTER (OLD) - Kept but integrated
                // ===================================================================
                const btnApplyMatnr = document.getElementById('applyMatnrFilter');
                const btnClearMatnr = document.getElementById('clearMatnrFilter');
                const textareaMatnr = document.getElementById('multiMatnrInput');
                const matnrCountInfo = document.getElementById('matnrCountInfo');
                const matnrBadge = document.getElementById('matnrBadge');
                const hiddenInputMatnr = document.getElementById('multiMatnrHiddenInput');
                const modalElementMatnr = document.getElementById('multiMatnrModal');
                let matnrModal;

                if (modalElementMatnr) {
                    matnrModal = new bootstrap.Modal(modalElementMatnr);
                }

                function updateBadge(count) {
                    if (matnrBadge) {
                        matnrBadge.innerText = count;
                        if (count > 0) {
                            matnrBadge.classList.remove('d-none');
                        } else {
                            matnrBadge.classList.add('d-none');
                        }
                    }
                }

                // Initialize state from existing hidden input (if any, e.g. after reload)
                if (hiddenInputMatnr && hiddenInputMatnr.value) {
                    const existingCodes = hiddenInputMatnr.value.split(',').filter(s => s.trim());
                    updateBadge(existingCodes.length);
                    if (textareaMatnr) {
                        textareaMatnr.value = existingCodes.join('\n');
                        if (matnrCountInfo) matnrCountInfo.innerText = `${existingCodes.length} kode terdeteksi`;
                    }
                }

                if (textareaMatnr) {
                    textareaMatnr.addEventListener('input', function() {
                        const lines = this.value.split('\n').filter(line => line.trim() !== '');
                        if (matnrCountInfo) matnrCountInfo.innerText = `${lines.length} kode terdeteksi`;
                    });
                }

                if (btnClearMatnr) {
                    btnClearMatnr.addEventListener('click', function() {
                        if (textareaMatnr) {
                            textareaMatnr.value = '';
                            if (matnrCountInfo) matnrCountInfo.innerText = '0 kode terdeteksi';
                        }
                    });
                }

                if (btnApplyMatnr) {
                    btnApplyMatnr.addEventListener('click', function() {
                        const rawText = textareaMatnr.value;
                        // Split by newline or comma, trim, filter empty
                        const codes = rawText.split(/[\n,]+/).map(s => s.trim()).filter(s => s !== '');
                        
                        if (hiddenInputMatnr) {
                            hiddenInputMatnr.value = codes.join(','); // Join with comma for backend
                        }

                        updateBadge(codes.length);

                        // Close modal
                        if (matnrModal) {
                            matnrModal.hide();
                        } else {
                            // Fallback if modal instance not stored
                            const modalInstance = bootstrap.Modal.getInstance(modalElementMatnr);
                            if(modalInstance) modalInstance.hide();
                        }



                        // Submit form using AJAX (performSearch)
                        if (typeof window.performSearch === 'function') {
                            const searchInput = document.querySelector('input[name="search_total_pro"]');
                            const searchTerm = searchInput ? searchInput.value : '';
                            window.performSearch('total_pro', searchTerm);
                        } else {
                            // Fallback if performSearch not ready
                            const form = document.querySelector('input[name="search_total_pro"]').closest('form');
                            if (form) form.submit();
                        }
                    });
                }
                
                // [NEW] Tambahkan Logika untuk Tombol RESET Filter di main input group (icon X merah)
                // Tombol ini: <a href="..." class="btn btn-outline-danger">...</a>
                // Kita akan override behavior-nya agar tidak reload page jika memungkinkan
                const btnResetAll = document.getElementById('btnResetFilter');
                if(btnResetAll) {
                    btnResetAll.addEventListener('click', function(e) {
                         e.preventDefault();
                         
                         // Clear Search Input
                         const searchInput = document.querySelector('input[name="search_total_pro"]');
                         if(searchInput) searchInput.value = '';
                         
                         // Clear Multi Matnr Input
                         if(textareaMatnr) textareaMatnr.value = '';
                         if(hiddenInputMatnr) hiddenInputMatnr.value = '';
                         updateBadge(0);
                         if(matnrCountInfo) matnrCountInfo.innerText = '0 kode terdeteksi';
                         
                         // Trigger Search Reset
                         if (typeof window.performSearch === 'function') {
                            window.performSearch('total_pro', '');
                         } else {
                             window.location.href = this.href;
                         }
                    });
                }

                // ===================================================================
                // LOGIKA STICKY SECTION VISIBILITY (Show Section if Params Exist)
                // ===================================================================
                const urlParamsSticky = new URLSearchParams(window.location.search);
                const totalProParams = [
                    'search_total_pro', 'page_total_pro', 
                    'adv_aufnr', 'adv_matnr', 'adv_maktx', 
                     'adv_kdauf', 'adv_kdpos',
                    'multi_matnr'
                ];
                
                // Check if any tracked param has a value
                const hasTotalProParams = totalProParams.some(param => {
                    return urlParamsSticky.has(param) && urlParamsSticky.get(param) && urlParamsSticky.get(param).trim() !== '';
                });

                if (hasTotalProParams) {
                    const mainContent = document.getElementById('mainDashboardContent');
                    const totalProSection = document.getElementById('totalProSection');
                    
                    if (mainContent && totalProSection) {
                        mainContent.style.display = 'none';
                        totalProSection.style.display = 'block';
                        
                        // Check other sections to ensure they are hidden (just in case)
                        ['outstandingSoSection', 'outstandingReservasiSection', 'ongoingProSection'].forEach(id => {
                            const el = document.getElementById(id);
                            if(el) el.style.display = 'none';
                        });
                    }
                }
            });
        </script>
    @endpush

</x-layouts.app>
