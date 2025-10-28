<x-layouts.landing title="COHV - PT Kayu Mebel Indonesia">

    @php
        $user = Auth::user() ?? (object) ['name' => 'User', 'role' => 'Guest'];
    @endphp

    {{-- CSS untuk transisi fade yang halus & UI Modern --}}
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

        #trophy-container {
            position: absolute; 
            top: 5px; 
            left: 0; 
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            opacity: 0; 
            transform: translateX(-50%) translateY(-20px);
            z-index: 10;
            filter: drop-shadow(2px 2px 3px rgba(0,0,0,0.3)); /* Shadow */
        }
        
        #trophy-container.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            animation: glow 2s infinite ease-in-out; /* Animasi glow */
        }

        #cogiBarChart {
            cursor: pointer;
        }

        /* Style untuk panel ringkasan */
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
            text-align: right; /* [BARU] Rata kanan untuk nilai */
        }

        /* Style untuk highlight baris tertinggi */
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
        /* [DIHAPUS] .summary-item-value.highlight tidak diperlukan lagi */
        
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
            
            <div class="w-100 mx-auto text-center py-5" style="max-width: 1140px;">
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

                <!-- [VIEW 1] Dashboard Utama (Chart + Summary) -->
                <div id="cogi-dashboard-view" class="cogi-view">
                    <div class="row g-4">
                        
                        <!-- Kolom Chart -->
                        <div class="col-lg-8">
                            <div class="card rounded-4 shadow-sm border-0 h-100">
                                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap">
                                    <h2 class="h6 fw-bold mb-0 pt-2">Ringkasan Total COGI (Qty)</h2>
                                    <button id="sync-cogi-btn" class="btn btn-primary btn-sm my-2">
                                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                                        <i class="fa-solid fa-sync-alt me-1 icon-sync"></i>
                                        <span class="text-sync">Sinkronisasi Data</span>
                                    </button>
                                </div>
                                <div class="card-body" style="position: relative;">
                                    <div id="trophy-container">
                                        <i class="fa-solid fa-trophy fs-4" style="color: #ffc107;"></i> <!-- Emas -->
                                    </div>

                                    <div id="chart-loader" class="text-center py-5" style="min-height: 300px;">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted small">Memuat data chart...</p>
                                    </div>
                                    
                                    <canvas id="cogiBarChart" style="display: none; min-height: 300px; max-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Ringkasan Data -->
                        <div class="col-lg-4">
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
                                    <!-- [PERUBAHAN] Container dinamis untuk ranking -->
                                    <div id="summary-rank-list"> 
                                        <!-- Ranking akan dibuat oleh JS di sini -->
                                    </div>
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
                                <select id="dispo-filter" class="form-select form-select-sm me-2" style="min-width: 150px; max-width: 200px;">
                                    <option value="">Semua Dispo</option>
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
                                <!-- Menambahkan class .table-cogi-detail -->
                                <table class="table table-striped table-hover table-sm table-cogi-detail">
                                    <thead class="table-light" style="position: sticky; top: 0;">
                                        <tr>
                                            <!-- Menambahkan style width ke TH -->
                                            <th style="width: 5%;">No</th>
                                            <th style="width: 12%;">PRO</th>
                                            <th style="width: 15%;">Reservasi Number</th>
                                            <th style="width: 15%;">Material Number</th>
                                            <th style="width: 28%;">Description</th>
                                            <th style="width: 8%;">MRP</th>
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
                </div> <!-- [AKHIR] #cogi-table-view -->

            </div>
            <!-- [AKHIR] #cogi-dashboard-container -->


            {{-- Bagian Plant Cards --}}
            <!-- Menghapus 'justify-content-center' -->
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    {{-- Import helper getRelativePosition dari Chart.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/4.4.1/helpers.min.js" xintegrity="sha512-JG3S/E6MNtPqodZUk1M/N4Q5Yj3Q5n/yQbzM09FxL2zfl9TtT8xF7RbfL+E7aP/htw50aD36HHY3h88P7FpwEg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> 

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            let currentPlantData = [];
            let cogiChart;
            const plantCodes = ['1000', '1001', '2000', '3000'];
            const plantNames = {
                '1000': 'Plant 1000 (Sby)',
                '1001': 'Plant 1001 (Sby)',
                '2000': 'Plant 2000 (Sby)',
                '3000': 'Plant 3000 (Smg)',
            };
            const defaultColors = {
                bg: 'rgba(54, 162, 235, 0.2)', // Biru muda transparan
                border: 'rgba(54, 162, 235, 1)' // Biru solid
            };

            // --- Elemen DOM ---
            const cogiDashboardView = document.getElementById('cogi-dashboard-view');
            const cogiTableView = document.getElementById('cogi-table-view');
            const backToDashboardBtn = document.getElementById('back-to-dashboard-btn');
            const dispoFilter = document.getElementById('dispo-filter');
            const tableSearch = document.getElementById('table-search'); 
            const tableTitle = document.getElementById('table-title');
            const tableLoader = document.getElementById('table-loader');
            const tableContainer = document.getElementById('table-container');
            const tableBody = document.getElementById('cogi-detail-table-body');
            const tableNoData = document.getElementById('table-no-data');
            
            const trophyContainer = document.getElementById('trophy-container');
            const ctx = document.getElementById('cogiBarChart').getContext('2d');
            const chartCanvas = document.getElementById('cogiBarChart');
            const chartLoader = document.getElementById('chart-loader');
            const summaryLoader = document.getElementById('summary-loader');
            const summaryRankList = document.getElementById('summary-rank-list'); 
            const syncBtn = document.getElementById('sync-cogi-btn');
            const syncBtnSpinner = syncBtn.querySelector('.spinner-border');
            const syncBtnIcon = syncBtn.querySelector('.icon-sync');
            const syncBtnText = syncBtn.querySelector('.text-sync');

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
            
            function showDashboardView() {
                cogiTableView.classList.add('hidden');
                cogiDashboardView.classList.remove('hidden');
                 if (cogiChart && cogiChart.data.datasets[0].data.length > 0) {
                     const highestIndex = cogiChart.data.datasets[0].data.indexOf(Math.max(...cogiChart.data.datasets[0].data));
                     showTrophy(highestIndex);
                 }
            }

            function showTableView() {
                cogiDashboardView.classList.add('hidden');
                cogiTableView.classList.remove('hidden');
                trophyContainer.classList.remove('show');
            }

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
                data.forEach(item => {
                    const row = `
                        <tr>
                            <td>${rowNumber++}</td>
                            <td>${item.AUFNR || '-'}</td>
                            <td>${item.RSNUM || '-'}</td>
                            <td>${item.MATNRH || '-'}</td>
                            <td>${item.MAKTXH || '-'}</td>
                            <td>${item.DISPOH || '-'}</td>
                            <td>${dateFormatter(item.BUDAT)}</td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            }
            
            function filterTable() {
                const selectedDispo = dispoFilter.value;
                const searchTerm = tableSearch.value.toLowerCase();
                const filteredData = currentPlantData.filter(item => {
                    const dispoMatch = (selectedDispo === "") || (item.DISPO === selectedDispo);
                    const searchMatch = (searchTerm === "") ||
                        (item.AUFNR && item.AUFNR.toLowerCase().includes(searchTerm)) ||
                        (item.RSNUM && item.RSNUM.toLowerCase().includes(searchTerm)) ||
                        (item.MATNRH && item.MATNRH.toLowerCase().includes(searchTerm)) ||
                        (item.MAKTXH && item.MAKTXH.toLowerCase().includes(searchTerm)) ||
                        (item.DISPOH && item.DISPOH.toLowerCase().includes(searchTerm)) ||
                        (dateFormatter(item.BUDAT).includes(searchTerm));
                    return dispoMatch && searchMatch;
                });
                populateTable(filteredData);
            }

            function populateDispoFilter(dispoOptions) {
                const oldValue = dispoFilter.value;
                dispoFilter.innerHTML = '<option value="">Semua MRP</option>';
                dispoOptions.forEach(dispo => {
                    if(dispo) {
                        dispoFilter.innerHTML += `<option value="${dispo}">${dispo}</option>`;
                    }
                });
                dispoFilter.value = oldValue;
            }

            // --- Fungsi untuk mengambil detail COGI ---
            async function fetchCogiDetails(plantCode, plantName) {
                showTableView();
                tableLoader.classList.remove('d-none');
                tableContainer.classList.add('d-none');
                tableNoData.classList.add('d-none');
                tableTitle.textContent = `Detail COGI ${plantName}`;
                dispoFilter.value = "";
                tableSearch.value = "";

                let url = '{{ route("api.cogi.details", ["plantCode" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', plantCode);
                try {
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (!response.ok) throw new Error('Status: ' + response.status);
                    const json = await response.json();
                    currentPlantData = json.data;
                    populateDispoFilter(json.dispo_options);
                    populateTable(currentPlantData);
                } catch (error) {
                    console.error('Error fetching COGI details:', error);
                    tableNoData.innerHTML = `<p class="text-danger fw-semibold">Gagal mengambil detail data COGI. ${error.message}</p>`;
                    tableNoData.classList.remove('d-none');
                } finally {
                    tableLoader.classList.add('d-none');
                }
            }
            
            // --- Fungsi untuk menampilkan piala ---
            function showTrophy(index) {
                if (!cogiChart || cogiChart.getDatasetMeta(0).data.length === 0) return;
                try {
                    const bar = cogiChart.getDatasetMeta(0).data[index];
                    if (bar) {
                        trophyContainer.style.left = `${bar.x}px`;
                        trophyContainer.classList.add('show');
                    } else {
                         trophyContainer.classList.remove('show'); // Sembunyikan jika bar tidak ada
                    }
                } catch (e) {
                    console.error("Gagal menempatkan piala:", e);
                    trophyContainer.classList.remove('show');
                }
            }
            
            // --- [BARU] Fungsi untuk membuat ulang panel ranking ---
            function populateSummaryRanking(rankingData) {
                 summaryRankList.innerHTML = ''; // Kosongkan dulu
                 if (!rankingData || rankingData.length === 0) {
                     summaryRankList.innerHTML = '<p class="text-muted small p-3">Data ranking tidak tersedia.</p>';
                     return;
                 }

                 rankingData.forEach((plant, index) => {
                     const isHighest = index === 0;
                     const iconClass = isHighest ? 'fa-solid fa-trophy' : 'fa-solid fa-industry';
                     const iconColor = isHighest ? '#ffc107' : '#6c757d'; // Emas untuk piala, abu-abu untuk lainnya
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


            // --- Inisialisasi Chart ---
            try {
                // Warna gradien merah untuk highlight
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(220, 53, 69, 0.6)'); 
                gradient.addColorStop(1, 'rgba(170, 40, 50, 0.8)'); 

                const highlightColors = {
                    bg: gradient,
                    border: 'rgba(220, 53, 69, 1)'
                };

                cogiChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: plantCodes.map(code => plantNames[code]), // Gunakan nama plant dari mapping
                        datasets: [{
                            label: 'Total COGI (Qty)',
                            data: [0, 0, 0, 0],
                            backgroundColor: defaultColors.bg,
                            borderColor: defaultColors.border,
                            borderWidth: 2, 
                            borderRadius: 6,
                            hoverBackgroundColor: 'rgba(54, 162, 235, 0.4)',
                            hoverBorderColor: 'rgba(54, 162, 235, 1)',
                        }]
                    },
                    options: { 
                        responsive: true,
                        maintainAspectRatio: false,
                        onClick: (event) => {
                            if (typeof Chart.helpers === 'undefined') {
                                console.error('Chart.js helpers library not loaded!');
                                return;
                            }
                            const position = Chart.helpers.getRelativePosition(event, cogiChart);
                            const index = cogiChart.scales.x.getValueForPixel(position.x);
                            
                            if (index !== undefined && index >= 0 && index < plantCodes.length) {
                                const plantCode = plantCodes[index]; // Dapatkan kode plant dari index
                                const plantName = plantNames[plantCode]; // Dapatkan nama plant
                                fetchCogiDetails(plantCode, plantName);
                            }
                        },
                        onResize: (chart) => {
                             if (chart.data.datasets[0].data.length > 0) {
                                const highestIndex = chart.data.datasets[0].data.indexOf(Math.max(...chart.data.datasets[0].data));
                                showTrophy(highestIndex);
                            } else {
                                trophyContainer.classList.remove('show');
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { display: true, drawBorder: false } },
                            x: { grid: { display: false } }
                        },
                        plugins: {
                            tooltip: {
                                enabled: true,
                                backgroundColor: '#212529',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 12 },
                                displayColors: false,
                                callbacks: {
                                    label: (context) => `Total: ${numberFormatter(context.parsed.y)}`
                                }
                            },
                            legend: { display: false }
                        }
                    }
                });

                // Fungsi untuk update warna chart
                cogiChart.updateColors = (highestIndex) => {
                    const newBgs = [];
                    const newBorders = [];
                     // Pastikan data ada sebelum loop
                     if (!cogiChart.data.datasets[0].data) return; 

                    cogiChart.data.datasets[0].data.forEach((_, index) => {
                        if (index === highestIndex) {
                            newBgs.push(highlightColors.bg);
                            newBorders.push(highlightColors.border);
                        } else {
                            newBgs.push(defaultColors.bg);
                            newBorders.push(defaultColors.border);
                        }
                    });
                    cogiChart.data.datasets[0].backgroundColor = newBgs;
                    cogiChart.data.datasets[0].borderColor = newBorders;
                };

            } catch (e) {
                console.error("Gagal menginisialisasi chart:", e);
                chartLoader.innerHTML = '<p class="text-danger">Gagal memuat chart library.</p>';
            }

            // --- Fungsi Loader ---
            function showLoaders() {
                chartLoader.style.display = 'block';
                chartCanvas.style.display = 'none';
                trophyContainer.classList.remove('show');
                summaryLoader.style.display = 'block';
                summaryRankList.innerHTML = ''; // Kosongkan ranking saat loading
            }

            function hideLoaders() {
                chartLoader.style.display = 'none';
                chartCanvas.style.display = 'block';
                summaryLoader.style.display = 'none';
            }
            
            function showErrors(message) {
                const errorMsg = `<p class="text-danger fw-semibold small p-3">${message}</p>`;
                chartLoader.innerHTML = errorMsg;
                summaryLoader.parentElement.innerHTML = errorMsg; // Tampilkan error di tempat loader summary
            }

            // --- Fungsi Fetch Dashboard ---
            async function fetchCogiData() {
                showLoaders();
                
                try {
                    const response = await fetch('{{ route("api.cogi.dashboard") }}', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    
                    if (!response.ok) throw new Error('Status: ' + response.status);

                    const data = await response.json(); 

                    if (cogiChart) {
                        cogiChart.data.datasets[0].data = data.chart_data;
                        cogiChart.updateColors(data.highest_index); 
                        cogiChart.update();

                        setTimeout(() => {
                             // Pastikan view dashboard masih aktif sebelum menampilkan piala
                            if (!cogiDashboardView.classList.contains('hidden')) {
                                showTrophy(data.highest_index);
                            }
                        }, 300); 
                    }

                    // [PERUBAHAN] Panggil fungsi populate ranking
                    populateSummaryRanking(data.summary_ranking); 
                    
                    hideLoaders();

                } catch (error) {
                    console.error('Error fetching COGI data:', error);
                    showErrors('Gagal memuat data dashboard. ' + error.message);
                }
            }

            // --- Fungsi Sync ---
            async function syncCogiData() {
                syncBtn.disabled = true;
                syncBtnSpinner.classList.remove('d-none');
                syncBtnIcon.classList.add('d-none');
                syncBtnText.textContent = 'Sinkronisasi...';

                try {
                    const response = await fetch('{{ route("api.cogi.sync") }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Status: ' + response.status);
                    }

                    const result = await response.json();
                    alert(result.message || 'Sinkronisasi berhasil!');
                    await fetchCogiData();
                    showDashboardView(); 

                } catch (error) {
                    console.error('Error syncing COGI data:', error);
                    alert(error.message || 'Sinkronisasi gagal. Silakan coba lagi.');
                } finally {
                    syncBtn.disabled = false;
                    syncBtnSpinner.classList.add('d-none');
                    syncBtnIcon.classList.remove('d-none');
                    syncBtnText.textContent = 'Sinkronisasi Data';
                }
            }

            // --- Event Listeners ---
            backToDashboardBtn.addEventListener('click', showDashboardView);
            dispoFilter.addEventListener('change', filterTable);
            tableSearch.addEventListener('input', filterTable);
            syncBtn.addEventListener('click', syncCogiData);
            
            // Panggil data dashboard saat halaman dimuat
            fetchCogiData();
        });
    </script>
    @endpush
</x-layouts.landing>

