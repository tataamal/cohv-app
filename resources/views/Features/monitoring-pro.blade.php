<x-layouts.app>
    {{-- Atur judul halaman jika layout Anda mendukungnya --}}
    @section('title', 'Monitoring PRO - ' . $activeKode)

    <div class="container-fluid py-4">
        {{-- BAGIAN HEADER HALAMAN --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="page-title">
                        Monitoring PRO - Plant {{ $activeKode }}
                    </h4>
                    <p class="page-subtitle text-muted">
                        Welcome, here is the COHV data information and visualization.
                    </p>
                </div>
                <div class="col-auto">
                    <span class="page-date text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ now()->format('l, d F Y') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- BARIS KARTU STATISTIK --}}
        <div class="row">
            {{-- Kartu 1: On Scheduling --}}
            <div class="col-xl-3 col-md-6 mb-4">
                {{-- [DIPERBAIKI] Bungkus kartu dengan tag <a> dengan class dan data-filter --}}
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

            {{-- Kartu 2: Overdue --}}
            <div class="col-xl-3 col-md-6 mb-4">
                {{-- [DIPERBAIKI] Bungkus kartu dengan tag <a> dengan class dan data-filter --}}
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
            
            {{-- Kartu 3: Outgoing --}}
            <div class="col-xl-3 col-md-6 mb-4">
                {{-- [DIPERBAIKI] Bungkus kartu dengan tag <a> dengan class dan data-filter --}}
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

            {{-- Kartu 4: Created (CRTD) --}}
            <div class="col-xl-3 col-md-6 mb-4">
                {{-- [DIPERBAIKI] Bungkus kartu dengan tag <a> dengan class dan data-filter --}}
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Production Order List</h5>
                {{-- [BONUS] Tombol untuk menampilkan semua data kembali --}}
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

    {{-- Jangan lupa push script Anda --}}
    @push('scripts')
        {{-- File _monitoring-pro.js akan menangani klik di sini --}}
    @endpush
</x-layouts.app>