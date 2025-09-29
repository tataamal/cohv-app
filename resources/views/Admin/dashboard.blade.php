<x-layouts.app title="Dashboard Plant">

    @push('styles')
    <style>
        /* CSS Kustom untuk Tampilan Profesional */
        body {
            background-color: var(--bs-body-tertiary);
        }

        /* Transisi halus untuk elemen interaktif */
        .card, .btn {
            transition: all 0.25s ease-in-out;
        }

        /* Efek hover untuk kartu yang dapat diklik */
        .card-interactive:hover {
            transform: translateY(-5px);
            box-shadow: var(--bs-box-shadow-lg) !important;
            border-color: var(--bs-primary) !important;
        }

        /* Styling untuk tabel dengan header sticky */
        .table-container-scroll {
            max-height: 350px; /* Tinggi maksimal tabel disesuaikan */
            overflow-y: auto;
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: var(--bs-border-radius);
        }
        .table-container-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--bs-tertiary-bg); /* Menggunakan variabel bootstrap untuk tema */
        }

        /* Wrapper untuk konsistensi ukuran chart */
        .chart-wrapper {
            position: relative;
            height: 400px;
            max-height: 50vh;
        }

        @media (min-width: 992px) {
            .chart-wrapper {
                height: 450px;
            }
        }
        
        /* Styling untuk tombol info */
        .info-button {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 5;
        }
    </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between">
                <div>
                    <h1 class="h3 fw-bold text-dark">Dashboard Plant - {{ $nama_bagian }}</h1>
                    <p class="mt-1 text-muted">Selamat datang, berikut adalah informasi dan visualisasi data COHV</p>
                </div>
                <div class="mt-3 mt-sm-0 small text-muted">
                    <i class="fas fa-calendar-alt me-2"></i>{{ now()->format('l, d F Y') }}
                </div>
            </div>
        </div>

        <div id="mainDashboardContent">
            <div id="cardStatsSection" class="row g-4 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="top"
                            data-bs-content="Menampilkan jumlah Sales Order (SO) pada bagian ini">
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
                    <div class="card border-0 shadow-sm h-100 position-relative">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="top"
                            data-bs-content="Jumlah total Production Order (PRO) pada bagian ini.">
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
                            <h3 class="h5 fw-semibold text-dark">Data Kapasitas Workcenter</h3>
                            <p class="small text-muted mb-4">Perbandingan Jumlah PRO dan Kapasitas di setiap Workcenter.</p>
                            <div class="chart-wrapper">
                                <canvas id="myBarChart" data-labels="{{ json_encode($labels ?? []) }}" data-datasets="{{ json_encode($datasets ?? []) }}" data-urls="{{ json_encode($targetUrls ?? []) }}"></canvas>
                            </div>
                            <div class="mt-4 text-center">
                                <p class="small text-muted mb-2">Navigasi cepat ke Workcenter:</p>
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
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100 position-relative">
                         <button class="btn btn-sm btn-outline-secondary rounded-circle info-button"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="left"
                            data-bs-content="Distribusi persentase dari semua status Production Order yang ada.">
                            <i class="fas fa-info"></i>
                        </button>
                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h5 fw-semibold text-dark">Status PRO</h3>
                            <p class="small text-muted mb-4">Distribusi status pada Production Order.</p>
                            <div class="chart-wrapper flex-grow-1">
                                <canvas id="pieChart" data-labels="{{ json_encode($doughnutChartLabels ?? []) }}" data-datasets="{{ json_encode($doughnutChartDatasets ?? []) }}"></canvas>
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
                            <h3 class="h5 fw-semibold text-dark">Peringkat 5 Workcenter</h3>
                            <p class="small text-muted mb-4">Berdasarkan total kapasitas tertinggi.</p>
                            <div class="chart-wrapper flex-grow-1">
                                <canvas id="lollipopChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="outstandingReservasiSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">Daftar Item Outstanding Reservasi</h3>
                            <p class="small text-muted mb-0">Material yang dibutuhkan belum terpenuhi oleh stok.</p>
                        </div>
                        <button id="backToDashboardBtn" class="btn btn-outline-secondary flex-shrink-0">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </button>
                    </div>
                    <div class="mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInput" placeholder="Cari Reservasi..." class="form-control border-start-0">
                        </div>
                    </div>
                    <div class="table-container-scroll">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="text-center">No.</th>
                                    <th class="text-center">No. Reservasi</th>
                                    <th class="text-center">Kode Material</th>
                                    <th class="text-center">Deskripsi Material</th>
                                    <th class="text-center">Req. Qty</th>
                                    <th class="text-center">Stock</th>
                                </tr>
                            </thead>
                            <tbody id="reservasiTableBody">
                                @forelse($TData4 as $item)
                                    <tr data-searchable-text="{{ strtolower(($item->RSNUM ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td cclass="text-center">{{ $item->RSNUM ?? '-' }}</td>
                                        <td class="text-center">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') ?: '0' : '-' }}</td>
                                        <td class="text-center">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}</td>
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

        <div id="ongoingProSection" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <div>
                            <h3 class="h5 fw-semibold text-dark mb-1">Daftar Ongoing PRO</h3>
                            <p class="small text-muted mb-0">Daftar Production Order yang sedang berjalan.</p>
                        </div>
                        <button id="backToDashboardBtnPro" class="btn btn-outline-secondary flex-shrink-0">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </button>
                    </div>
                    <div class="mb-3" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" id="realtimeSearchInputPro" placeholder="Cari SO, PRO, Material..." class="form-control border-start-0">
                        </div>
                    </div>
                    <div class="table-container-scroll">
                        <table class="table table-hover table-striped table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase align-middle">
                                    <th class="text-center">No.</th>
                                    <th class="text-center">SO</th>
                                    <th class="text-center">SO. Item</th>
                                    <th class="text-center">PRO</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Kode Material</th>
                                    <th class="text-center">Deskripsi</th>
                                    <th class="text-center">Plant</th>
                                    <th class="text-center">MRP</th>
                                    <th class="text-center">Qty. ORDER</th>
                                    <th class="text-center">Qty. GR</th>
                                    <th class="text-center">Outs. GR</th>
                                    <th class="text-center">Start Date</th>
                                    <th class="text-center">End Date</th>
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
                                        <td class="text-center">{{ $item->KDAUF ?? '-' }}</td>
                                        <td class="text-center">{{ $item->KDPOS ?? '-' }}</td>
                                        <td class="text-center">{{ $item->AUFNR ?? '-' }}</td>
                                        <td class="text-center"><span class="badge rounded-pill {{ $badgeClass }}">{{ $status ?: '-' }}</span></td>
                                        <td class="text-center">{{ $item->MATNR ? ltrim((string)$item->MATNR, '0') : '-' }}</td>
                                        <td class="text-center"">{{ $item->MAKTX ?? '-' }}</td>
                                        <td class="text-center">{{ $item->PWWRK ?? '-' }}</td>
                                        <td class="text-center">{{ $item->DISPO ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}</td>
                                        <td class="text-center">{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}</td>
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

        // Mengaktifkan semua popover di halaman
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

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
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, title: { display: !isMobile, text: 'Workcenter' }},
                        y: { title: { display: true, text: 'Jumlah' }}
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    const value = context.parsed.y;
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
                type: 'pie',
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
        const mainDashboardContent = document.getElementById('mainDashboardContent');
        const outstandingReservasiSection = document.getElementById('outstandingReservasiSection');
        const ongoingProSection = document.getElementById('ongoingProSection');

        const cardOutstandingReservasi = document.getElementById('card-outstanding-reservasi');
        const cardOutgoingPro = document.getElementById('card-outgoing-pro');

        const backToDashboardBtns = [
            document.getElementById('backToDashboardBtn'),
            document.getElementById('backToDashboardBtnPro')
        ].filter(Boolean);

        function showSection(sectionToShow) {
            mainDashboardContent.style.display = 'none';
            outstandingReservasiSection.style.display = 'none';
            ongoingProSection.style.display = 'none';

            if (sectionToShow) {
                sectionToShow.style.display = 'block';
            }
        }

        if (cardOutstandingReservasi) {
            cardOutstandingReservasi.addEventListener('click', () => showSection(outstandingReservasiSection));
        }

        if (cardOutgoingPro) {
            cardOutgoingPro.addEventListener('click', () => showSection(ongoingProSection));
        }

        backToDashboardBtns.forEach(btn => {
            btn.addEventListener('click', () => showSection(mainDashboardContent));
        });

        // =======================================================
        // PART 3: REALTIME SEARCH LOGIC
        // =======================================================
        function setupRealtimeSearch(inputId, tableBodyId, noResultsRowId) {
            const searchInput = document.getElementById(inputId);
            const tableBody = document.getElementById(tableBodyId);
            const noResultsRow = document.getElementById(noResultsRowId);
            if (!searchInput || !tableBody || !noResultsRow) return;

            const tableRows = tableBody.querySelectorAll('tr:not(#' + noResultsRowId + ')');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
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