<x-layouts.app>
    @section('title', 'Monitoring PRO - ' . $activeKode)
    @push('styles')
    {{-- [FINAL] Menggunakan Flexbox untuk layout halaman --}}
    <style>
        /* Mengubah container-fluid menjadi flex-container vertikal */
        .page-container-flex {
            display: flex;
            flex-direction: column;
            /* Sesuaikan 100px dengan tinggi navbar + padding Anda jika perlu */
            height: calc(100vh - 100px); 
        }

        /* Konten di atas daftar kartu (header + stats) tidak akan bisa di-scroll */
        .page-content-fixed {
            flex-shrink: 0;
        }

        /* Kontainer daftar kartu yang bisa di-scroll */
        .page-content-scrollable {
            flex-grow: 1; /* Mengisi sisa ruang yang tersedia */
            overflow-y: auto; /* Scrollbar hanya muncul di sini */
            min-height: 200px; /* Tinggi minimum untuk estetika */
        }
        
        /* [PERBAIKAN] Membuat header daftar PRO menjadi sticky */
        #pro-list-card .card-header {
            position: -webkit-sticky; /* Untuk browser Safari */
            position: sticky;
            top: 0; /* Menempel di bagian paling atas dari scroll container */
            z-index: 10; /* Memastikan header selalu di atas konten kartu */
            background-color: #fff; /* Memberi background solid agar konten tidak tembus */
            border-bottom: 1px solid #dee2e6; /* Garis pemisah */
        }

        /* Styling untuk Kartu PRO */
        .pro-card-container {
            padding: 0.75rem !important;
            background-color: #f8f9fa;
        }
        .pro-card {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            border-left-width: 5px !important;
            border-left-style: solid;
        }
        .border-start-danger { border-left-color: #dc3545 !important; }
        .border-start-success { border-left-color: #198754 !important; }
        .border-start-warning { border-left-color: #ffc107 !important; }
        .border-start-secondary { border-left-color: #6c757d !important; }
        .border-start-primary { border-left-color: #0d6efd !important; }
        .border-start-dark { border-left-color: #212529 !important; }
        .pro-card-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .pro-card-buyer-so, .pro-card-details { font-size: 0.9rem; }
        .pro-card-pro-number { font-weight: 700; color: #212529; font-size: 1.25rem; }
        .pro-card-material { font-size: 0.9rem; }
        .loading-spinner-container { display: flex; align-items: center; justify-content: center; height: 100%; min-height: 200px; }
        .stat-card.active { border: 2px solid #0d6efd; box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25); }
    </style>
    @endpush

    <div class="container-fluid py-4 page-container-flex">

        <div class="page-content-fixed">
            {{-- BAGIAN HEADER HALAMAN --}}
            <div class="page-header mb-4 d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-3">
                <div class="text-center text-sm-start">
                    <h4 class="page-title">Monitoring PRO -{{ $nama_bagian }} - {{ $sub_kategori }}</h4>
                    <p class="page-subtitle text-muted mb-0">Welcome, here is the COHV data in {{ $kategori }}</p>
                </div>
                <div class="text-center text-sm-end">
                    <span class="page-date text-muted"><i class="fas fa-calendar-alt me-1"></i> {{ now()->format('l, d F Y') }}</span>
                </div>
            </div>

            {{-- BARIS KARTU STATISTIK (VERSI LENGKAP) --}}
            <div class="row">
                <div class="col-12 col-md-6 col-xl-3 mb-4">
                    <a href="#" class="stat-card-link" data-filter="on-schedule">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon bg-success-soft"><i class="fas fa-calendar-check"></i></div>
                                <button class="stat-card-info" title="Info"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="stat-card-body">
                                <p class="stat-card-title">On Scheduling</p>
                                <h3 class="stat-card-value">{{ number_format($onScheduleProCount, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-xl-3 mb-4">
                    <a href="#" class="stat-card-link" data-filter="overdue">
                        <div class="stat-card card-bg-danger-soft">
                            <div class="stat-card-header">
                                <div class="stat-card-icon bg-danger-soft"><i class="fas fa-clock-rotate-left"></i></div>
                                <button class="stat-card-info" title="Info"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="stat-card-body">
                                <p class="stat-card-title">Overdue PRO</p>
                                <h3 class="stat-card-value">{{ number_format($overdueProCount, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-xl-3 mb-4">
                    <a href="#" class="stat-card-link" data-filter="On Process">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon bg-info-soft"><i class="fas fa-arrow-right-from-bracket"></i></div>
                                <button class="stat-card-info" title="Info"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="stat-card-body">
                                <p class="stat-card-title">On Proccess PRO</p>
                                <h3 class="stat-card-value">{{ number_format($outgoingProCount, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-xl-3 mb-4">
                    <a href="#" class="stat-card-link" data-filter="created">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-icon bg-secondary-soft"><i class="fas fa-file-circle-plus"></i></div>
                                <button class="stat-card-info" title="Info"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="stat-card-body">
                                <p class="stat-card-title">Created (CRTD)</p>
                                <h3 class="stat-card-value">{{ number_format($createdProCount, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- AREA SCROLLABLE UNTUK DAFTAR KARTU --}}
        <div class="page-content-scrollable">
            <div id="pro-list-card" style="display: none;">
                <div class="card shadow-sm">
                     <div class="card-header d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2">
                        <h5 class="card-title mb-0" id="pro-list-title">Production Order List</h5>
                        <a href="#" class="btn btn-sm btn-outline-secondary stat-card-link" data-filter="all">Show All</a>
                    </div>
                    <div class="card-body pro-card-container" id="pro-card-list-body">
                        {{-- Konten diisi oleh JavaScript --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const statCardLinks = document.querySelectorAll('.stat-card-link');
                const proListCard = document.getElementById('pro-list-card');
                const proListBody = document.getElementById('pro-card-list-body');
                const proListTitle = document.getElementById('pro-list-title');
                const activeKode = "{{ $activeKode }}";

                const loadingSpinner = `
                    <div class="loading-spinner-container">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                // [PERBAIKAN] Menggunakan URL hardcoded seperti pada JS Anda, ini lebih aman
                const filterUrlTemplate = `/monitoring-pro/${activeKode}/filter`;

                async function fetchAndDisplayPros(filter) {
                    proListCard.style.display = 'block';
                    proListBody.innerHTML = loadingSpinner;
                    
                    const titleText = filter.charAt(0).toUpperCase() + filter.slice(1).replace('-', ' ');
                    proListTitle.textContent = `Production Order List (${titleText})`;
                    if (filter === 'all') {
                        proListTitle.textContent = 'All Production Orders';
                    }

                    try {
                        // [PERBAIKAN] Menggunakan parameter 'status' sesuai dengan controller
                        const response = await fetch(`${filterUrlTemplate}?status=${filter}`);
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok. Status: ' + response.status);
                        }
                        const html = await response.text();
                        proListBody.innerHTML = html;
                    } catch (error) {
                        console.error('Fetch error:', error);
                        proListBody.innerHTML = `
                            <div class="text-center p-5">
                                <i class="bi bi-x-circle fs-2 text-danger"></i>
                                <h5 class="mt-2">Gagal Memuat Data</h5>
                                <p class="text-muted">Terjadi kesalahan saat mengambil data dari server.</p>
                            </div>
                        `;
                    }
                }

                statCardLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        document.querySelectorAll('.stat-card.active').forEach(c => c.classList.remove('active'));
                        
                        const parentCard = this.querySelector('.stat-card');
                        if(parentCard) {
                            parentCard.classList.add('active');
                        }

                        const filter = this.dataset.filter;
                        fetchAndDisplayPros(filter);
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>

