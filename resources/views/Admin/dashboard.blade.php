<x-layouts.app title="Dashboard Plant">

    @push('styles')
    <style>
        /* CSS for making tables scrollable with a sticky header */
        .table-container-scroll {
            max-height: 300px; /* You can adjust the max table height here */
            overflow-y: auto;
        }
        .table-container-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            /* Ensure the background color matches the thead to avoid transparency on scroll */
            background-color: #f8f9fa !important; 
        }
        .chart-container {
            position: relative;
            /* Size for Desktop & Tablet */
            height: 28rem;
        }

        /* Specific rules for small screens (mobile) */
        @media (max-width: 767px) {
            .chart-container {
                /* Provide enough height so the chart doesn't collapse on render */
                height: 400px; 
                /* Limit max height to avoid it being too large */
                max-height: 500px; 
            }
        }
    </style>
    @endpush

    <!-- Header Section -->
    <div class="mb-2">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between">
            <div>
                <h1 class="h3 fw-bold text-dark">Dashboard Plant - {{ $nama_bagian }}</h1>
                <p class="mt-2 text-muted">Berikut adalah report dari data COHV</p>
            </div>
            <div class="mt-3 mt-sm-0 small text-muted">
                {{ now()->format('l, d F Y') }}
            </div>
        </div>
    </div>

    <!-- ======================================================= -->
    <!--          MAIN DASHBOARD SECTIONS COLLECTION             -->
    <!-- ======================================================= -->

    <!-- Stats Cards Row (Wrapper fixed) -->
    <div id="cardStatsSection">
        <div class="row g-4 mb-3 mx-1">
            <div class="col-12 col-md-6 col-lg">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                                <svg class="text-info-emphasis" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-muted mb-0">Outstanding SO</p>
                        </div>
                        <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $TData2 ?? 0 }}">0</p>
                    </div>
                </div>
            </div>
        
            <div class="col-12 col-md-6 col-lg">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                                <svg class="text-primary" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            </div>
                            <p class="text-muted mb-0">Total PRO</p>
                        </div>
                        <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $TData3 ?? 0 }}">0</p>
                    </div>
                </div>
            </div>
        
            <div class="col-12 col-md-6 col-lg">
                <div id="card-outstanding-reservasi" class="card border-0 shadow-sm h-100" style="cursor: pointer;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                                <svg class="text-danger-emphasis" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-muted mb-0">Outstanding Reservasi</p>
                        </div>
                        <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $outstandingReservasi ?? 0 }}">0</p>
                    </div>
                </div>
            </div>
        
            <div class="col-12 col-md-6 col-lg">
                <div id="card-outgoing-pro" class="card border-0 shadow-sm h-100" style="cursor: pointer;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                                 <svg class="text-secondary-emphasis" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-muted mb-0">Outgoing PRO</p>
                        </div>
                        <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $ongoingPRO ?? 0 }}">0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="barChartSection">
        <div class="row g-4 mx-2 mb-2"> 
            <div class="col-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-semibold text-dark">Data Kapasitas Workcenter</h3>
                        <p class="small text-muted mb-4">Perbandingan Display Jumlah PRO dan Kapasitas di setiap Workcenter</p>
                        <div class="chart-container item d-flex justify-content-center align-items-center">
                            <canvas id="myBarChart" data-labels="{{ json_encode($labels ?? []) }}" data-datasets="{{ json_encode($datasets ?? []) }}" data-urls="{{ json_encode($targetUrls ?? []) }}"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="small text-muted mb-2">Atau klik link Workcenter di bawah ini:</p>
                            @if (!empty($labels))
                                @foreach($labels as $index => $label)
                                    <a href="{{ $targetUrls[$index] ?? '#' }}" class="btn btn-sm btn-outline-primary m-1">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pieChartSection">
        <div class="row g-4 mx-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-semibold text-dark">Status PRO</h3>
                        <p class="small text-muted mb-4">Perbandingan status pada field PRO.</p>
                        <div style="height: 24rem;" class="d-flex align-items-center justify-content-center">
                            <canvas id="pieChart" data-labels="{{ json_encode($doughnutChartLabels ?? []) }}" data-datasets="{{ json_encode($doughnutChartDatasets ?? []) }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 fw-semibold text-dark">Peringkat 5 Workcenter</h3>
                        <p class="small text-muted mb-4">Berdasarkan total kapasitas tertinggi.</p>
                        <div style="height: 24rem;">
                            <canvas id="lollipopChart"></canvas> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================= -->
    <!--         HIDDEN DETAIL SECTIONS COLLECTION               -->
    <!-- ======================================================= -->

    <!-- Outstanding Reservation Table -->
    <div id="outstandingReservasiSection" class="row g-4 mx-4" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">Daftar Item Outstanding Reservasi</h3>
                            <p class="small text-muted mb-0">Material yang dibutuhkan belum terpenuhi oleh stok.</p>
                        </div>
                        <button id="backToDashboardBtn" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </button>
                    </div>
                    <div class="w-100 mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInput" placeholder="Cari Reservasi..." class="form-control border-start-0">
                        </div>
                    </div>
                    <div class="table-responsive table-container-scroll">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="text-center">No.</th>
                                    <th class="text-center">No. Reservasi</th>
                                    <th class="text-center">Kode Material</th>
                                    <th>Deskripsi Material</th>
                                    <th class="text-center">Req. Qty</th>
                                    <th class="text-end">Stock</th>
                                </tr>
                            </thead>
                            <tbody id="reservasiTableBody">
                                @forelse($TData4 as $item)
                                    <tr data-searchable-text="{{ strtolower(($item->RSNUM ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">
                                        <td class="text-center small">{{ $loop->iteration }}</td>
                                        <td class="text-center small">{{ $item->RSNUM ?? '-' }}</td>
                                        <td class="text-center small">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') ?: '0' : '-' }}</td>
                                        <td class="small">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="text-center small fw-medium">{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end small fw-medium text-primary">{{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center p-5 text-muted">Tidak ada data reservasi ditemukan.</td></tr>
                                @endforelse
                                <tr id="noResultsRow" style="display: none;"><td colspan="6" class="text-center p-5 text-muted"><i class="fas fa-search fs-4 d-block mb-2"></i>Tidak ada data yang cocok dengan pencarian Anda.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ongoing PRO Table -->
    <div id="ongoingProSection" class="row g-4 mx-4" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">Daftar Ongoing PRO</h3>
                            <p class="small text-muted mb-0">Daftar Production Order yang sedang berjalan.</p>
                        </div>
                        <button id="backToDashboardBtnPro" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </button>
                    </div>
                    <div class="w-100 mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInputPro" placeholder="Cari SO, PRO, Material..." class="form-control border-start-0">
                        </div>
                    </div>
                    <div class="table-responsive table-container-scroll">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="text-center align-middle">No.</th>
                                    <th class="text-center align-middle">SO</th>
                                    <th class="text-center align-middle">SO. Item</th>
                                    <th class="text-center align-middle">PRO</th>
                                    <th class="text-center align-middle">Status</th>
                                    <th class="text-center align-middle">Kode Material</th>
                                    <th class="text-center align-middle">Deskripsi</th>
                                    <th class="text-center align-middle">Plant</th>
                                    <th class="text-center align-middle">MRP</th>\
                                    <th class="text-center align-middle">Qty. ORDER</th>
                                    <th class="text-center align-middle">Qty. GR</th>
                                    <th class="text-center align-middle">Outs. GR</th>
                                    <th class="text-center align-middle">Start Date</th>
                                    <th class="text-center align-middle">End Date</th>
                                </tr>
                            </thead>
                            <tbody id="ongoingProTableBody">
                                @forelse($ongoingProData ?? [] as $item)
                                    @php
                                        $status = strtoupper($item->STATS ?? '');
                                        $badgeClass = 'bg-secondary-subtle text-secondary-emphasis'; // Default
                                        if (in_array($status, ['REL', 'PCNF', 'CNF'])) $badgeClass = 'bg-success-subtle text-success-emphasis';
                                        elseif ($status === 'CRTD') $badgeClass = 'bg-info-subtle text-info-emphasis';
                                        elseif ($status === 'TECO') $badgeClass = 'bg-dark-subtle text-dark-emphasis';
                                    @endphp
                                    <tr data-searchable-text="{{ strtolower(($item->KDAUF ?? '') . ' ' . ($item->AUFNR ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">
                                        <td class="text-center small">{{ $loop->iteration }}</td>
                                        <td class="small text-center">{{ $item->KDAUF ?? '-' }}</td>
                                        <td class="small text-center">{{ $item->KDPOS ?? '-' }}</td>
                                        <td class="small text-center">{{ $item->AUFNR ?? '-' }}</td>
                                        <td class="text-center"><span class="badge rounded-pill {{ $badgeClass }}">{{ $status ?: '-' }}</span></td>
                                        <td class="small text-center">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') : '-' }}</td>
                                        <td class="smal text-center">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="small text-center">{{ $item->PWWRK ?? '-' }}</td>
                                        <td class="small text-center">{{ $item->DISPO ?? '-' }}</td>
                                        <td class="small text-center">{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="small text-center">{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="small text-center">{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}</td>
                                        <td class="small text-center">{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}</td>
                                        <td class="small text-center">{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="14" class="text-center p-5 text-muted">Tidak ada data Ongoing PRO yang ditemukan.</td></tr>
                                @endforelse
                                <tr id="noResultsProRow" style="display: none;"><td colspan="14" class="text-center p-5 text-muted"><i class="fas fa-search fs-4 d-block mb-2"></i>Tidak ada data yang cocok dengan pencarian Anda.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
    
        // =======================================================
        // PART 1: INITIALIZE ALL CHARTS
        // =======================================================
        function initBarChart() {
            const chartCanvas = document.getElementById('myBarChart');
            if (!chartCanvas) return;
            const chartLabels = JSON.parse(chartCanvas.dataset.labels);
            const chartDatasets = JSON.parse(chartCanvas.dataset.datasets);
            const targetUrls = JSON.parse(chartCanvas.dataset.urls);

            function debounce(func, delay = 250) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), delay);
                };
            }

            function getResponsiveChartOptions() {
                const isMobile = window.innerWidth < 768;
                return {
                    indexAxis: isMobile ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: !isMobile,
                    scales: {
                        x: { beginAtZero: true, title: { display: true, text: isMobile ? 'Jumlah' : 'Workcenter' }},
                        y: { title: { display: true, text: isMobile ? 'Workcenter' : 'Jumlah' }}
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    const value = isMobile ? context.parsed.x : context.parsed.y;
                                    label += new Intl.NumberFormat('id-ID').format(value);
                                    return label;
                                }
                            }
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const url = targetUrls[elements[0].index];
                            if (url) window.location.href = url;
                        }
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    }
                };
            }

            const barChart = new Chart(chartCanvas.getContext('2d'), {
                type: 'bar',
                data: { labels: chartLabels, datasets: chartDatasets },
                options: getResponsiveChartOptions()
            });

            window.addEventListener('resize', debounce(() => {
                barChart.options = getResponsiveChartOptions();
                barChart.update();
            }));
        }
    
        function initLollipopChart() {
            const lollipopCanvas = document.getElementById('lollipopChart');
            if (!lollipopCanvas) return;
            const labels = @json($lolipopChartLabels ?? []);
            const datasets = @json($lolipopChartDatasets ?? []);
            new Chart(lollipopCanvas.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, title: { display: true, text: 'Total Kapasitas' }},
                        y: { grid: { display: false }}
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            filter: (tooltipItem) => tooltipItem.datasetIndex === 1
                        }
                    }
                }
            });
        }
    
        function initPieChart() {
            const pieCanvas = document.getElementById('pieChart');
            if (!pieCanvas) return;
            const labels = JSON.parse(pieCanvas.dataset.labels);
            const datasets = JSON.parse(pieCanvas.dataset.datasets);
            new Chart(pieCanvas.getContext('2d'), {
                type: 'pie', // Changed from doughnut for simplicity, can be changed back
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        }
    
        initBarChart();
        initLollipopChart();
        initPieChart();
    
        // =======================================================
        // PART 2: DASHBOARD SHOW/HIDE LOGIC
        // =======================================================
        const cardOutstandingReservasi = document.getElementById('card-outstanding-reservasi');
        const cardOutgoingPro = document.getElementById('card-outgoing-pro');
    
        const backToDashboardBtnReservasi = document.getElementById('backToDashboardBtn');
        const backToDashboardBtnPro = document.getElementById('backToDashboardBtnPro');
    
        const mainDashboardSections = [
            document.getElementById('cardStatsSection'),
            document.getElementById('barChartSection'),
            document.getElementById('pieChartSection')
        ].filter(Boolean);
    
        const allDetailSections = [
            document.getElementById('outstandingReservasiSection'),
            document.getElementById('ongoingProSection')
        ].filter(Boolean);
    
        function toggleView(sectionToShow) {
            mainDashboardSections.forEach(section => section.style.display = 'none');
            allDetailSections.forEach(section => section.style.display = 'none');
    
            if (sectionToShow === 'main') {
                mainDashboardSections.forEach(section => section.style.display = 'block');
            } else if (sectionToShow) {
                sectionToShow.style.display = 'block';
            }
        }
    
        if (cardOutstandingReservasi) {
            cardOutstandingReservasi.addEventListener('click', () => toggleView(document.getElementById('outstandingReservasiSection')));
        }
        if (backToDashboardBtnReservasi) {
            backToDashboardBtnReservasi.addEventListener('click', () => toggleView('main'));
        }
    
        if (cardOutgoingPro) {
            cardOutgoingPro.addEventListener('click', () => toggleView(document.getElementById('ongoingProSection')));
        }
        if (backToDashboardBtnPro) {
            backToDashboardBtnPro.addEventListener('click', () => toggleView('main'));
        }
    
        // =======================================================
        // PART 3: REALTIME SEARCH LOGIC (FOR BOTH TABLES)
        // =======================================================
        function setupRealtimeSearch(inputId, tableBodyId, noResultsRowId) {
            const searchInput = document.getElementById(inputId);
            const tableBody = document.getElementById(tableBodyId);
            const noResultsRow = document.getElementById(noResultsRowId);
    
            if (!searchInput || !tableBody || !noResultsRow) return;
    
            const tableRows = tableBody.querySelectorAll('tr:not(#' + noResultsRowId + ')');
    
            searchInput.addEventListener('input', function() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleRowsCount = 0;
    
                tableRows.forEach(row => {
                    const searchableText = row.dataset.searchableText || '';
                    if (searchableText.includes(searchTerm)) {
                        row.style.display = '';
                        visibleRowsCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
    
                noResultsRow.style.display = visibleRowsCount === 0 ? '' : 'none';
            });
        }
    
        setupRealtimeSearch('realtimeSearchInput', 'reservasiTableBody', 'noResultsRow');
        setupRealtimeSearch('realtimeSearchInputPro', 'ongoingProTableBody', 'noResultsProRow');
    
    });
    </script>
    @endpush

</x-layouts.app>
