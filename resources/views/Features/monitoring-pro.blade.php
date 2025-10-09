<x-layouts.app>
    {{-- Atur judul halaman jika layout Anda mendukungnya --}}
    @section('title', 'Monitoring PRO - ' . $activeKode)

    <div class="container-fluid py-4">
        {{-- BAGIAN HEADER HALAMAN --}}
        {{-- [PERBAIKAN] Mengubah struktur flex menjadi block di mobile agar rapi --}}
        <div class="page-header mb-4 d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-3">
            <div class="text-center text-sm-start">
                <h4 class="page-title">
                    Monitoring PRO - Plant {{ $activeKode }}
                </h4>
                <p class="page-subtitle text-muted mb-0">
                    Welcome, here is the COHV data information and visualization.
                </p>
            </div>
            <div class="text-center text-sm-end">
                <span class="page-date text-muted">
                    <i class="fas fa-calendar-alt me-1"></i>
                    {{ now()->format('l, d F Y') }}
                </span>
            </div>
        </div>

        {{-- BARIS KARTU STATISTIK --}}
        <div class="row">
            {{-- [PERBAIKAN] Menambahkan 'col-12' agar selalu 1 kolom di layar terkecil --}}
            <div class="col-12 col-md-6 col-xl-3 mb-4">
                <a href="#" class="stat-card-link" data-filter="on-schedule">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon bg-success-soft">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <button class="stat-card-info" title="Info">
                                <i class="fas fa-info-circle"></i>
                            </button>
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
                            <div class="stat-card-icon bg-danger-soft">
                                <i class="fas fa-clock-rotate-left"></i>
                            </div>
                            <button class="stat-card-info" title="Info">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                        <div class="stat-card-body">
                            <p class="stat-card-title">Overdue PRO</p>
                            <h3 class="stat-card-value">{{ number_format($overdueProCount, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-12 col-md-6 col-xl-3 mb-4">
                <a href="#" class="stat-card-link" data-filter="outgoing">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon bg-info-soft">
                                <i class="fas fa-arrow-right-from-bracket"></i>
                            </div>
                            <button class="stat-card-info" title="Info">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                        <div class="stat-card-body">
                            <p class="stat-card-title">Outgoing PRO</p>
                            <h3 class="stat-card-value">{{ number_format($outgoingProCount, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-3 mb-4">
                <a href="#" class="stat-card-link" data-filter="created">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon bg-secondary-soft">
                                <i class="fas fa-file-circle-plus"></i>
                            </div>
                            <button class="stat-card-info" title="Info">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                        <div class="stat-card-body">
                            <p class="stat-card-title">Created (CRTD)</p>
                            <h3 class="stat-card-value">{{ number_format($createdProCount, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Tabel Data PRO --}}
        <div class="card">
            {{-- [PERBAIKAN] Header dibuat bertumpuk di mobile menggunakan flex-column --}}
            <div class="card-header d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2">
                <h5 class="card-title mb-0">Production Order List</h5>
                <a href="#" class="btn btn-sm btn-outline-secondary stat-card-link" data-filter="all">
                    Show All
                </a>
            </div>
            <div class="card-body">
                <div id="pro-table-container" data-kode="{{ $activeKode }}">
                    @include('Features.partials.pro-table', ['pros' => $pros])
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- File _monitoring-pro.js akan menangani klik di sini --}}
    @endpush
</x-layouts.app>