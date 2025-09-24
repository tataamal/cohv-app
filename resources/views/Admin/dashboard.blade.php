<x-layouts.app title="Dashboard Plant">

    @push('styles')
    <style>
        /* CSS untuk membuat tabel bisa di-scroll dengan header yang sticky */
        .table-container-scroll {
            max-height: 500px; /* Anda bisa sesuaikan tinggi maksimal tabel di sini */
            overflow-y: auto;
        }
        .table-container-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            /* Pastikan warna background sama dengan thead agar tidak transparan saat scroll */
            background-color: #f8f9fa !important; 
        }
        .chart-container {
            position: relative;
            /* Ukuran untuk Desktop & Tablet */
            height: 32rem;
            /* width: 100%; */
            /* max-height: 400px; */
        }

        /* Aturan khusus untuk layar kecil (mobile) */
        @media (max-width: 767px) {
            .chart-container {
                /* Beri tinggi yang cukup agar chart tidak hilang saat render */
                height: 400px; 
                
                /* Batasi tinggi maksimal agar tidak terlalu besar */
                max-height: 500px; 
            }
        }
    </style>
    @endpush

    <!-- Header Section -->
    <div class="mb-5">
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

    <!-- Stats Cards Row (GRID DIUBAH MENJADI ROW/COL) -->
    <div class="row g-4 mb-5">
        
        <!-- Card 1: Outstanding Order -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                            <svg class="text-info-emphasis" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-muted mb-0">Outstanding Sales Order</p>
                    </div>
                    <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $TData2 }}">0</p>
                </div>
            </div>
        </div>

        <!-- Card 2: Total PRO -->
        <div class="col-md-6 col-lg-4">
             <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                            <svg class="text-primary" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <p class="text-muted mb-0">Total PRO</p>
                    </div>
                    <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $TData3 }}">0</p>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Outstanding Reservasi -->
        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle rounded-3 me-3" style="width: 40px; height: 40px;">
                            <svg class="text-warning-emphasis" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-muted mb-0">Outstanding Reservasi</p>
                    </div>
                    <p class="stat-value h2 fw-bold text-dark mt-3" data-target="{{ $outstandingReservasi }}">0</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4"> <div class="col-12"> <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h3 class="h5 fw-semibold text-dark">Data Kapasitas Workcenter</h3>
                    <p class="small text-muted mb-4">Perbandingan Display Jumlah PRO dan Kapasitas di setiap Workcenter</p>
                    <div class="chart-container item d-flex justify-content-center align-items-center">
                        <canvas id="myBarChart" 
                                data-labels="{{ json_encode($labels) }}"
                                data-datasets="{{ json_encode($datasets) }}"
                                data-urls="{{ json_encode($targetUrls) }}"></canvas>
                            </div>
                            <div class="mt-3 text-center">
                                <p class="small text-muted mb-2">Atau klik link Workcenter di bawah ini:</p>
                                @foreach($labels as $index => $label)
                                    <a href="{{ $targetUrls[$index] ?? '#' }}" class="btn btn-sm btn-outline-primary m-1">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mx-3 mb-4">

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h3 class="h5 fw-semibold text-dark">Status PRO</h3>
                    <p class="small text-muted mb-4">Perbandingan status pada field PRO.</p>
                    <div style="height: 24rem;" class="d-flex align-items-center justify-content-center">
                        <canvas class="chart-canvas" 
                                data-type="pie"
                                data-labels="{{ json_encode($doughnutChartLabels) }}"
                                data-datasets="{{ json_encode($doughnutChartDatasets) }}"></canvas>
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

    <!-- Tabel Section -->
    <div class="row g-4 mx-4 ">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-3 gap-3">
                    <div>
                        <h3 class="h5 fw-semibold text-dark">List Data Reservasi</h3>
                        <!-- PERBAIKAN: Deskripsi diubah agar lebih umum -->
                        <p class="small text-muted mb-0">Detail item material yang telah direservasi.</p>
                    </div>
                    <form method="GET" action="{{ url()->current() }}" class="w-100" style="max-width: 320px;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                            <input type="text" name="search_reservasi" value="{{ request('search_reservasi') }}" placeholder="Cari Reservasi..." class="form-control border-start-0">
                        </div>
                    </form>
                </div>

                <div class="table-responsive table-container-scroll">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th class="text-center">No.</th>
                                <th class="text-center">No. Reservasi</th>
                                <th class="text-center">Kode Material</th>
                                <th class="text-center">Deskripsi Material</th>
                                <th class="text-center">Req. Qty</th>
                                <th class="text-end">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($TData4 as $item)
                                <tr>
                                    <td class="text-center small">{{ $loop->iteration }}</td>
                                    <td class="text-center small">{{ $item->RSNUM ?? '-' }}</td>
                                    <td class="text-center small">
                                        {{ $item->MATNR ? ( ($t = ltrim((string)$item->MATNR, '0')) === '' ? '0' : $t ) : '-' }}
                                    </td>
                                    <td class="text-center small">{{ $item->MAKTX ?? '-' }}</td>
                                    <td class="text-center small fw-medium">{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end small fw-medium text-primary">
                                        {{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center p-5 text-muted">
                                        Tidak ada data reservasi ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartCanvas = document.getElementById('myBarChart');
            if (!chartCanvas) return;

            // Ambil data dari atribut data-*
            const chartLabels = JSON.parse(chartCanvas.dataset.labels);
            const chartDatasets = JSON.parse(chartCanvas.dataset.datasets);
            const targetUrls = JSON.parse(chartCanvas.dataset.urls); // ✨ FIX: Ambil dari chartCanvas.dataset

            // ✨ REVISI: Deteksi ukuran layar
            const isMobile = window.innerWidth < 768;

            const barChartCtx = chartCanvas.getContext('2d');
            new Chart(barChartCtx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: chartDatasets
                },
                options: {
                    // ✨ REVISI: Opsi utama untuk mengubah orientasi chart
                    indexAxis: isMobile ? 'y' : 'x', // Ubah sumbu kategori ke Y jika mobile
                    responsive: true,
                    maintainAspectRatio: !isMobile, // Jangan jaga rasio di mobile agar tinggi bisa menyesuaikan

                    scales: {
                        // Konfigurasi sumbu disesuaikan berdasarkan orientasi
                        x: { 
                            beginAtZero: true,
                            title: { display: true, text: isMobile ? 'Jumlah PRO atau Capacity' : 'Workcenter yang ada di Plant ini' }
                        },
                        y: { 
                            title: { display: true, text: isMobile ? 'Workcenter yang ada di Plant ini' : 'Jumlah PRO atau Capacity' }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    const dataIndex = context[0].dataIndex;
                                    // Asumsikan 'descriptions' ada di dalam dataset
                                    const description = context[0].dataset.descriptions ? context[0].dataset.descriptions[dataIndex] : '';
                                    return description || '';
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    // ✨ REVISI: Ambil nilai dari sumbu yang benar (x untuk horizontal, y untuk vertikal)
                                    const value = isMobile ? context.parsed.x : context.parsed.y;
                                    label += new Intl.NumberFormat('id-ID').format(value);
                                    const satuan = context.dataset.satuan || '';
                                    if (satuan) {
                                        label += ' ' + satuan;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const dataIndex = elements[0].index;
                            const url = targetUrls[dataIndex];
                            if (url) {
                                window.location.href = url;
                            }
                        }
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    }
                }
            });

            const lollipopCanvas = document.getElementById('lollipopChart');

            if (lollipopCanvas) {
                // Ambil data dari Blade
                const labels = @json($lolipopChartLabels ?? []);
                const datasets = @json($lolipopChartDatasets ?? []);
                
                const ctx = lollipopCanvas.getContext('2d');
                
                new Chart(ctx, {
                    type: 'bar', // Tipe dasar chart adalah 'bar'
                    data: {
                        labels: labels,
                        datasets: datasets // Masukkan dua dataset kita di sini
                    },
                    options: {
                        // Kunci untuk membuatnya horizontal
                        indexAxis: 'y', 
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total Kapasitas'
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            // Sembunyikan legenda, karena sudah jelas dari judul
                            legend: {
                                display: false
                            },
                            // Atur tooltip agar tidak menampilkan keduanya (gagang & titik)
                            tooltip: {
                                // Hanya tampilkan tooltip untuk dataset titik (dataset ke-2, index 1)
                                filter: function (tooltipItem) {
                                    return tooltipItem.datasetIndex === 1;
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.app>