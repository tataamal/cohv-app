<x-layouts.landing title="COHV - PT Kayu Mebel Indonesia">

    @php
        $user = Auth::user() ?? (object) ['name' => 'User', 'role' => 'Guest'];
    @endphp

    @push('styles')
    <style>
        #cogi-dashboard-container {
            position: relative;
        }

        .cogi-view {
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
            will-change: opacity, transform;
        }

        .cogi-view.hidden {
            opacity: 0;
            transform: scale(0.98);
            pointer-events: none;
            position: absolute;
            width: 100%;
        }
        
        /* Efek Piala Emas Bercahaya */
        @keyframes glow {
            0%, 100% { filter: drop-shadow(0 0 3px rgba(255, 193, 7, 0.7)); }
            50% { filter: drop-shadow(0 0 8px rgba(255, 193, 7, 1)); }
        }

        #cogiBarChart {
            cursor: pointer;
        }

        .summary-panel {
            background-color: #f8f9fa;
            border-radius: 0.75rem;
        }
        .summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.3s ease; 
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-item-label {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #495057;
        }
        .summary-item-label i {
            width: 20px;
            margin-right: 0.75rem;
            color: #6c757d; 
        }
        .summary-item-value {
            font-weight: 600;
            font-size: 0.95rem;
            color: #212529;
            text-align: right;
        }
        .summary-item.highest {
            background-color: #f8d7da; 
        }
        .summary-item.highest .summary-item-label,
        .summary-item.highest .summary-item-value {
             font-weight: 700; 
             color: #58151c; 
        }
         .summary-item.highest .summary-item-label i.fa-trophy {
            color: #ffc107; /* Pastikan ikon piala tetap emas */
         }
        
        /* Penyesuaian layout tabel */
        .table-cogi-detail {
            table-layout: fixed; 
            width: 100%;
        }

        .table-cogi-detail th, 
        .table-cogi-detail td {
            white-space: nowrap; 
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
    @endpush

    <div class="d-flex flex-column min-vh-100">
        {{-- Hero Section --}}
        <div class="hero-section">
            <header class="w-100 mx-auto" style="max-width: 1140px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; padding: 0.375rem;">
                            <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="img-fluid">
                        </div>
                        <h1 class="h6 fw-semibold text-white d-none d-sm-block mb-0">PT. Kayu Mebel Indonesia</h1>
                    </div>
                    
                    <div class="dropdown">
                        <button id="user-menu-button" class="btn btn-link text-white text-decoration-none d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="fw-semibold text-white d-none d-sm-block me-2">{{ $user->name }}</span>
                            <i class="fa-solid fa-user text-white"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <div class="w-50 mx-auto text-center py-2" style="max-width: 1140px;">
                <h1 class="display-5 fw-bold">Selamat Datang!</h1>
                <p class="fs-5 text-white-75 mt-2" style="min-height: 28px;">
                    <span id="typing-effect">Bagian mana yang ingin anda kerjakan ?</span>
                    <span id="typing-cursor" class="d-inline-block bg-white" style="width: 2px; height: 1.5rem; animation: pulse 1s infinite; margin-bottom: -4px;"></span>
                </p>
            </div>
        </div>
        
        {{-- Konten Utama --}}
        <main class="w-100 mx-auto p-3 p-md-4 flex-grow-1 main-content" style="max-width: 1140px;">

            <!-- Container untuk Dashboard & Tabel -->
            <div id="cogi-dashboard-container" class="mb-4">

                <div id="cogi-dashboard-view" class="cogi-view">
                    <div class="row g-4 mb-4">
                        <div class="col-lg-12">
                            <div class="card rounded-4 shadow-sm border-0 h-100">
                                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap">
                                    <h2 class="h6 fw-bold mb-0 pt-2">Ringkasan COGI per Devisi</h2>
                                    <button id="sync-cogi-btn" class="btn btn-primary btn-sm my-2">
                                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                                        <i class="fa-solid fa-sync-alt me-1 icon-sync"></i>
                                        <span class="text-sync">Sinkronisasi Data</span>
                                    </button>
                                </div>
                                <div class="card-body" style="position: relative;">
                                    
                                    <div id="chart-loader" class="text-center py-5" style="min-height: 300px;">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted small">Memuat data chart...</p>
                                    </div>
                                    
                                    <div id="chart-grid-container" class="row g-4 d-none">
                                        <div class="col-sm-6">
                                            <h3 class="h6 fw-bold text-center mb-2" style="font-size: 0.9rem;">Plant 1000 (Sby)</h3>
                                            <canvas id="cogiDonutChart1000" style="max-height: 250px; cursor: pointer;"></canvas>
                                        </div>
                                        <div class="col-sm-6">
                                            <h3 class="h6 fw-bold text-center mb-2" style="font-size: 0.9rem;">Plant 1001 (Sby)</h3>
                                            <canvas id="cogiDonutChart1001" style="max-height: 250px; cursor: pointer;"></canvas>
                                        </div>
                                        <div class="col-sm-6 mt-4">
                                            <h3 class="h6 fw-bold text-center mb-2" style="font-size: 0.9rem;">Plant 2000 (Sby)</h3>
                                            <canvas id="cogiDonutChart2000" style="max-height: 250px; cursor: pointer;"></canvas>
                                        </div>
                                        <div class="col-sm-6 mt-4">
                                            <h3 class="h6 fw-bold text-center mb-2" style="font-size: 0.9rem;">Plant 3000 (Smg)</h3>
                                            <canvas id="cogiDonutChart3000" style="max-height: 250px; cursor: pointer;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        
                        <div class="col-lg-6 mb-4">
                            <div class="card rounded-4 shadow-sm border-0 h-100">
                                <div class="card-header bg-white border-0 pt-3 pb-0">
                                    <h2 class="h6 fw-bold mb-4 pt-2">Ranking Plant</h2>
                                </div>
                                <div class="card-body p-0">
                                    <div id="summary-loader" class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="summary-rank-list"> 
                                        </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card rounded-4 shadow-sm border-0 h-100">
                                <div class="card-header bg-white border-0 pt-3 pb-0">
                                    <h2 class="h6 fw-bold mb-4 pt-2">COGI per Tipe Material (TYPMAT)</h2>
                                </div>
                                <div class="card-body p-0">
                                    <div id="typmat-loader" class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="typmat-summary-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="cogi-table-view" class="cogi-view hidden">
                    <div class="card rounded-4 shadow-sm border-0">
                        <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center mb-2">
                                <button id="back-to-dashboard-btn" class="btn btn-outline-secondary btn-sm me-3">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                                </button>
                                <h2 id="table-title" class="h6 fw-bold mb-0">Detail COGI Plant...</h2>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <select id="division-filter" class="form-select form-select-sm me-2" style="min-width: 150px; max-width: 200px; display: none;">
                                    <option value="">Semua Devisi</option>
                                </select>
                                <input type="text" id="table-search" class="form-control form-control-sm" placeholder="Cari di hasil ini..." style="max-width: 250px;">
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="table-loader" class="text-center py-5 d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Memuat data tabel...</p>
                            </div>

                            <div id="table-container" class="table-responsive" style="max-height: 60vh;">
                                <table class="table table-striped table-hover table-sm table-cogi-detail">
                                    <thead class="table-light" style="position: sticky; top: 0;">
                                        <tr>
                                            <th style="width: 5%;">No</th>
                                            <th style="width: 12%;">PRO</th>
                                            <th style="width: 15%;">Reservasi Number</th>
                                            <th style="width: 15%;">Material Number</th>
                                            <th style="width: 28%;">Description</th>
                                            <th style="width: 8%;">Devisi</th>
                                            <th style="width: 17%;">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cogi-detail-table-body"></tbody>
                                </table>
                            </div>

                            <div id="table-no-data" class="text-center py-5 d-none">
                                <p class="text-muted small">Tidak ada data untuk ditampilkan.</p>
                            </div>
                        </div>
                    </div>
                </div> 

            </div>

            <div class="row g-4"> 
                @php
                    $colorClasses = [
                        ['bg' => 'bg-primary-subtle', 'text' => 'text-primary-emphasis'],
                        ['bg' => 'bg-success-subtle', 'text' => 'text-success-emphasis'],
                        ['bg' => 'bg-info-subtle', 'text' => 'text-info-emphasis'],
                    ];
                @endphp
    
                @forelse ($plants as $plant)
                    @php
                        $colors = $colorClasses[$loop->index % count($colorClasses)];
                    @endphp
                    
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 d-flex">
                        <a href="{{ route('manufaktur.dashboard.show', [$plant->kode]) }}"
                            onclick="event.preventDefault(); appLoader.show(); setTimeout(() => { window.location.href = this.href }, 150)"
                            class="card w-100 text-decoration-none text-center p-3 rounded-4 shadow-sm plant-card">
                            <div class="card-body">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-3 {{ $colors['bg'] }}" style="width: 56px; height: 56px;">
                                    <svg class="{{ $colors['text'] }}" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                <h3 class="card-title h6 fw-bold text-dark">{{ $plant->nama_bagian }}</h3>
                                <p class="card-text text-muted small">Kategori: {{ $plant->kategori }}</p>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5 bg-white rounded-4 border">
                            <h3 class="h5 fw-semibold text-body-secondary">Tidak Ada Plant</h3>
                            <p class="text-muted mt-2">Tidak ada plant yang terhubung dengan akun Anda saat ini.</p>
                        </div>
                    </div>
                @endforelse 
            </div>
        </main>
    </div>


    @push('scripts')
        {{-- Library Chart.js --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const SAP_USERNAME = "{{ Auth::user()->sap_username ?? '' }}";
            const SAP_PASSWORD = "{{ Auth::user()->sap_password ?? '' }}";
                
                let currentPlantData = [];
                let cogiCharts = {}; // Objek untuk menampung 4 chart
                const plantCodes = ['1000', '1001', '2000', '3000'];
                const plantNames = {
                    '1000': 'Plant 1000 (Sby)',
                    '1001': 'Plant 1001 (Sby)',
                    '2000': 'Plant 2000 (Sby)',
                    '3000': 'Plant 3000 (Smg)',
                };

                // --- Elemen DOM ---
                const cogiDashboardView = document.getElementById('cogi-dashboard-view');
                const cogiTableView = document.getElementById('cogi-table-view');
                const backToDashboardBtn = document.getElementById('back-to-dashboard-btn');
                const divisionFilter = document.getElementById('division-filter');
                const tableSearch = document.getElementById('table-search'); 
                const tableTitle = document.getElementById('table-title');
                const tableLoader = document.getElementById('table-loader');
                const tableContainer = document.getElementById('table-container');
                const tableBody = document.getElementById('cogi-detail-table-body');
                const tableNoData = document.getElementById('table-no-data');
                const chartGridContainer = document.getElementById('chart-grid-container');
                const chartLoader = document.getElementById('chart-loader');
                const summaryLoader = document.getElementById('summary-loader');
                const summaryRankList = document.getElementById('summary-rank-list'); 
                const typmatLoader = document.getElementById('typmat-loader');
                const typmatList = document.getElementById('typmat-summary-list');
                const syncBtn = document.getElementById('sync-cogi-btn');
                const syncBtnSpinner = syncBtn.querySelector('.spinner-border');
                const syncBtnIcon = syncBtn.querySelector('.icon-sync');
                const syncBtnText = syncBtn.querySelector('.text-sync');
                

                // --- Helper Functions ---
                const numberFormatter = (value) => {
                    const num = parseFloat(value);
                    if (isNaN(num)) return '0';
                    return new Intl.NumberFormat('id-ID').format(num);
                };

                const dateFormatter = (dateString) => {
                    if (!dateString) return '-';
                    try {
                        const date = new Date(dateString);
                        const options = { day: 'numeric', month: 'long', year: 'numeric' };
                        return date.toLocaleDateString('en-GB', options); 
                    } catch (e) {
                        return dateString;
                    }
                };
                
                // --- Fungsi Navigasi View ---
                function showDashboardView() {
                    cogiTableView.classList.add('hidden');
                    cogiDashboardView.classList.remove('hidden');
                }

                function showTableView() {
                    cogiDashboardView.classList.add('hidden');
                    cogiTableView.classList.remove('hidden');
                }

                // --- Fungsi Tabel & Filter ---
                function populateTable(data) {
                    tableBody.innerHTML = ''; 
                    if (data.length === 0) {
                        tableContainer.classList.add('d-none');
                        tableNoData.classList.remove('d-none');
                        return;
                    }
                    tableContainer.classList.remove('d-none');
                    tableNoData.classList.add('d-none');
                    
                    let rowNumber = 1;
                    let rowsHtml = ''; 
                    
                    data.forEach(item => {
                        rowsHtml += `
                            <tr>
                                <td>${rowNumber++}</td>
                                <td>${item.AUFNR || '-'}</td>
                                <td>${item.RSNUM || '-'}</td>
                                <td>${item.MATNRH || '-'}</td>
                                <td>${item.MAKTXH || '-'}</td>
                                <td>${item.DEVISI || '-'}</td> 
                                <td>${dateFormatter(item.BUDAT)}</td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = rowsHtml;
                }
                
                function filterTable() {
                    const selectedDivision = divisionFilter.value;
                    const searchTerm = tableSearch.value.toLowerCase();
                    
                    const filteredData = currentPlantData.filter(item => {
                        
                        let itemDevisi = (item.DEVISI || "").trim();
                        if (itemDevisi === "") {
                            itemDevisi = "Others DEVISI";
                        }
                        const divisionMatch = (selectedDivision === "") || (itemDevisi.trim() === selectedDivision.trim());
                        // ==========================================================

                        const searchMatch = (searchTerm === "") ||
                            (item.AUFNR && item.AUFNR.toLowerCase().includes(searchTerm)) ||
                            (item.RSNUM && item.RSNUM.toLowerCase().includes(searchTerm)) ||
                            (item.MATNRH && item.MATNRH.toLowerCase().includes(searchTerm)) ||
                            (item.MAKTXH && item.MAKTXH.toLowerCase().includes(searchTerm)) ||
                            (item.DEVISI && item.DEVISI.toLowerCase().includes(searchTerm)) || 
                            (dateFormatter(item.BUDAT).includes(searchTerm));
                        
                        return divisionMatch && searchMatch;
                    });
                    
                    populateTable(filteredData);
                }

                function populateDivisionFilter(divisionOptions) {
                    const oldValue = divisionFilter.value;
                    let optionsHtml = '<option value="">Semua Devisi</option>';
                    
                    if (divisionOptions) {
                        divisionOptions.forEach(devisi => {
                            if (devisi) { 
                                optionsHtml += `<option value="${devisi}">${devisi}</option>`;
                            }
                        });
                    }
                    divisionFilter.innerHTML = optionsHtml;
                    divisionFilter.value = oldValue;
                }

                // --- Fungsi Fetch Data Detail ---
                async function fetchCogiDetails(plantCode, plantName, divisionName = "") {
                    showTableView();
                    tableLoader.classList.remove('d-none');
                    tableContainer.classList.add('d-none');
                    tableNoData.classList.add('d-none');
                    tableTitle.textContent = `Detail COGI ${plantName}`;
                    divisionFilter.value = divisionName;
                    tableSearch.value = "";

                    const SAP_USERNAME = "{{ session('username') ?? '' }}";
                    const SAP_PASSWORD = "{{ session('password') ?? '' }}";

                    let url = '{{ route("api.cogi.details", ["plantCode" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', plantCode);
                    try {
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        if (!response.ok) throw new Error('Status: ' + response.status);
                        
                        const json = await response.json();
                        currentPlantData = json.data || json; 

                        filterTable();
                        
                    } catch (error) {
                        console.error('Error fetching COGI details:', error);
                        tableNoData.innerHTML = `<p class="text-danger fw-semibold">Gagal mengambil detail data COGI. ${error.message}</p>`;
                        tableNoData.classList.remove('d-none');
                    } finally {
                        tableLoader.classList.add('d-none');
                    }
                }
                function populateSummaryRanking(rankingData) {
                    summaryRankList.innerHTML = ''; 
                    if (!rankingData || rankingData.length === 0) {
                        summaryRankList.innerHTML = '<p class="text-muted small p-3">Data ranking tidak tersedia.</p>';
                        return;
                    }

                    rankingData.forEach((plant, index) => {
                        const isHighest = index === 0;
                        const iconClass = isHighest ? 'fa-solid fa-trophy' : 'fa-solid fa-industry';
                        const iconColor = isHighest ? '#ffc107' : '#6c757d';
                        const itemClass = isHighest ? 'summary-item highest' : 'summary-item';
                        
                        const itemHTML = `
                            <div class="${itemClass}" data-plant-code="${plant.plant_code}">
                                <span class="summary-item-label">
                                    <i class="${iconClass}" style="color: ${iconColor};"></i> ${plant.plant_name}
                                </span>
                                <span class="summary-item-value">${numberFormatter(plant.value)}</span>
                            </div>
                        `;
                        summaryRankList.innerHTML += itemHTML;
                    });
                }
                function populateTypmatSummary(typmatData) {
                    typmatList.innerHTML = ''; // Kosongkan
                    if (!typmatData) {
                        typmatList.innerHTML = '<p class="text-muted small p-3">Data TYPMAT tidak tersedia.</p>';
                        return;
                    }

                    let html = '<div class="row g-0 px-3">'; // Gunakan row untuk 2 kolom
                    
                    // Loop per plant
                    plantCodes.forEach((plantCode, index) => {
                        const plantData = typmatData[plantCode] || [];
                        const plantName = plantNames[plantCode];
                        
                        // Tambah pemisah per 2 kolom
                        if (index === 2) {
                            html += '</div><hr class="my-2"><div class="row g-0 px-3">';
                        }

                        html += `<div class="col-md-6 ${index % 2 === 0 ? 'pe-2' : 'ps-2'}">`;
                        html += `<h4 class="h6 fw-bold mb-2 mt-2" style="font-size: 0.9rem;">${plantName}</h4>`;
                        
                        if (plantData.length === 0) {
                            html += '<p class="text-muted small">Tidak ada data.</p>';
                        } else {
                            plantData.forEach(item => {
                                // Kita gunakan style .summary-item yang sudah ada
                                html += `
                                    <div class="summary-item" style="padding: 0.5rem 0.2rem;">
                                        <span class="summary-item-label" style="font-size: 0.85rem;">
                                            <i class="fa-solid fa-tag" style="width: 16px; margin-right: 8px;"></i> ${item.name}
                                        </span>
                                        <span class="summary-item-value" style="font-size: 0.9rem;">${numberFormatter(item.value)}</span>
                                    </div>
                                `;
                            });
                        }
                        html += `</div>`; // end col-md-6
                    });

                    html += '</div>'; // end row
                    typmatList.innerHTML = html;
                }
            
                function initializeDonutCharts(chartData) {
                    Object.values(cogiCharts).forEach(chart => chart.destroy());
                    cogiCharts = {};

                    const chartOptions = {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'right', 
                                labels: { 
                                    boxWidth: 10, 
                                    padding: 10, 
                                    font: { size: 10 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        return ` ${label}: ${numberFormatter(value)}`;
                                    }
                                }
                            }
                        },
                        onClick: (event, elements) => {
                            if (elements.length > 0) {
                                const chartInstance = event.chart;
                                const plantCode = chartInstance.canvas.id.replace('cogiDonutChart', '');
                                
                                const index = elements[0].index;
                                const divisionName = chartInstance.data.labels[index];
                                const plantName = plantNames[plantCode];
                                fetchCogiDetails(plantCode, plantName, divisionName);
                            }
                        }
                    };

                    // Loop untuk membuat 4 chart
                    plantCodes.forEach(plantCode => {
                        const ctx = document.getElementById(`cogiDonutChart${plantCode}`).getContext('2d');
                        const plantData = chartData[plantCode] || { labels: [], values: [] };

                        cogiCharts[plantCode] = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: plantData.labels,
                                datasets: [{
                                    data: plantData.values,
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.7)',
                                        'rgba(54, 162, 235, 0.7)',
                                        'rgba(255, 206, 86, 0.7)',
                                        'rgba(75, 192, 192, 0.7)',
                                        'rgba(153, 102, 255, 0.7)',
                                        'rgba(255, 159, 64, 0.7)',
                                        'rgba(201, 203, 207, 0.7)',
                                        'rgba(50, 205, 50, 0.7)',
                                        'rgba(210, 105, 30, 0.7)',
                                        'rgba(0, 128, 128, 0.7)'
                                    ],
                                    borderColor: '#ffffff',
                                    borderWidth: 2,
                                }]
                            },
                            options: chartOptions
                        });
                    });
                }

                // --- Fungsi Loader & Error ---
                function showLoaders() {
                    chartLoader.style.display = 'block';
                    chartGridContainer.classList.add('d-none');
                    summaryLoader.style.display = 'block';
                    summaryRankList.innerHTML = '';
                    typmatLoader.style.display = 'block';
                    typmatList.innerHTML = '';
                }

                function hideLoaders() {
                    chartLoader.style.display = 'none';
                    chartGridContainer.classList.remove('d-none');
                    summaryLoader.style.display = 'none';
                    typmatLoader.style.display = 'none';
                }
                
                function showErrors(message) {
                    const errorMsg = `<p class="text-danger fw-semibold small p-3">${message}</p>`;
                    chartLoader.innerHTML = errorMsg;
                    chartLoader.style.display = 'block';
                    summaryLoader.style.display = 'none';
                    summaryRankList.innerHTML = errorMsg; 
                    typmatLoader.style.display = 'none';
                    typmatList.innerHTML = errorMsg;
                }
                
                // --- Fungsi Fetch Dashboard Utama ---
                async function fetchCogiData() {
                    showLoaders();
                    
                    try {
                        const response = await fetch('{{ route("api.cogi.dashboard") }}', {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        
                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Status: ' + response.status);
                        }

                        const data = await response.json(); 
                        
                        initializeDonutCharts(data.chart_data);
                        populateDivisionFilter(data.division_filter_options);
                        populateSummaryRanking(data.summary_ranking); 
                        populateTypmatSummary(data.typmat_summary);
                        
                        hideLoaders();

                    } catch (error) {
                        console.error('Error fetching COGI data:', error);
                        showErrors('Gagal memuat data dashboard. ' + error.message);
                    }
                }

                // --- Fungsi Sync ---
                async function syncCogiData() {
                    const syncUrl = '{{ route("api.cogi.sync") }}';
                    
                    Swal.fire({
                        title: 'Sinkronisasi...',
                        text: 'Sedang mengambil data dari SAP dan menyimpan ke DB. Mohon tunggu.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading(); 
                        }
                    });

                    try {
                        const response = await fetch(syncUrl, {
                            method: 'POST',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.message || `Status: ${response.status}`);
                        }
                        await fetchCogiData();
                        showDashboardView();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: result.message || 'Sinkronisasi berhasil!'
                        });

                    } catch (error) {
                        console.error('Error syncing COGI data:', error);
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Sinkronisasi Gagal',
                            text: error.message || 'Gagal terhubung ke server. Silakan coba lagi.'
                        });
                    } 
                }

                // --- Event Listeners ---
                backToDashboardBtn.addEventListener('click', showDashboardView);
                divisionFilter.addEventListener('change', filterTable);
                tableSearch.addEventListener('input', filterTable);
                syncBtn.addEventListener('click', syncCogiData);
                
                // --- Panggilan Awal ---
                fetchCogiData();

            }); // <-- AKHIR DARI DOMCONTENTLOADED
            
            // <-- Fungsi 'populateTypmatSummary' yang salah tadinya di sini
            
        </script>
    @endpush
</x-layouts.landing>

