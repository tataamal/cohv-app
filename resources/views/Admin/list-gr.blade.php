<x-layouts.app title="List GR - PT. Kayu Mebel Indonesia">
    @push('styles')
    <style>
        /* Gaya tambahan untuk memastikan sticky header di tabel scroll tidak tembus pandang */
        .table-responsive thead.sticky-top th {
            background-color: var(--bs-table-bg);
        }
    </style>
    @endpush
    <div class="mb-4">
        <div class="card shadow-sm p-3">
            <div class="d-lg-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                    @isset($kode)
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3" style="width: 6px; height: 24px; background-color: #28a745; border-radius: 3px;"></div>
                            <h1 class="h5 mb-0">
                                Report untuk Kode: <span class="text-success fw-bold">{{ $kode }}</span>
                            </h1>
                        </div>
                        <p class="text-muted ms-4 ps-1">List Good Receipt (GR)</p>
                    @else
                        <div class="d-flex align-items-center mb-2">
                             <div class="me-3" style="width: 6px; height: 24px; background-color: #6f42c1; border-radius: 3px;"></div>
                            <h1 class="h5 mb-0">Good Receipt Report</h1>
                        </div>
                        <p class="text-muted ms-4 ps-1">Laporan Penerimaan Material</p>
                    @endisset

                    @isset($error)
                        <div class="alert alert-danger mt-3 ms-4 ps-1" role="alert">
                            {{ $error }}
                        </div>
                    @endisset
                </div>
                <div class="mt-3 mt-lg-0">
                    <div class="d-inline-flex align-items-center px-3 py-2 bg-light border rounded-3">
                        <i class="bi bi-calendar3 me-2 text-muted"></i>
                        <span class="fw-medium text-secondary">{{ now()->format('l, d F Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="h6 card-title mb-1">Kalender Good Receipt</h3>
                        <p class="card-subtitle text-muted small">Klik pada tanggal untuk melihat detail lengkap</p>
                    </div>
                     <div class="d-flex align-items-center justify-content-center text-primary-emphasis bg-primary-subtle border border-primary-subtle" style="width: 32px; height: 32px; border-radius: 0.5rem;">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                 <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="h6 card-title mb-1">Detail Semua Data</h3>
                        <p class="card-subtitle text-muted small">Rincian lengkap semua item material yang diterima</p>
                    </div>
                     <div class="d-flex align-items-center justify-content-center text-info-emphasis bg-info-subtle border border-info-subtle" style="width: 32px; height: 32px; border-radius: 0.5rem;">
                        <i class="bi bi-list-ul"></i>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center small text-uppercase">PRO</th>
                                <th class="text-center small text-uppercase">Material Description</th>
                                <th class="text-center small text-uppercase">Sales Order</th>
                                <th class="text-center small text-uppercase">SO Item</th>
                                <th class="text-center small text-uppercase">Quantity PRO</th>
                                <th class="text-center small text-uppercase">Quantity GR</th>
                                <th class="text-center small text-uppercase">Tgl. Posting</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dataGr as $item)
                                <tr>
                                    <td class="text-center align-middle small fw-medium">{{ $item->AUFNR ?? '-' }}</td>
                                    <td class="align-middle small">{{ $item->MAKTX ?? '-' }}</td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">{{ $item->KDAUF ?? '-' }}</span>
                                    </td>
                                    <td class="text-center align-middle">
                                         <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">{{ $item->KDPOS ?? '-' }}</span>
                                    </td>
                                    <td class="text-center align-middle small">{{ number_format($item->PSMNG ?? 0) }}</td>
                                    <td class="text-center align-middle small fw-bold text-success">{{ number_format($item->MENGE ?? 0) }}</td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">
                                            {{ \Carbon\Carbon::parse($item->BUDAT_MKPF)->format('d M Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center p-5">
                                        <i class="bi bi-inbox fs-2 text-muted"></i>
                                        <h5 class="mt-2">Tidak Ada Data</h5>
                                        <p class="text-muted">Tidak ada data yang cocok untuk ditampilkan saat ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                     <h1 class="modal-title fs-5" id="modalTitle">Detail Tanggal:</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                         <table class="table table-striped mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center small text-uppercase">PRO</th>
                                    <th class="text-center small text-uppercase">Material Description</th>
                                    <th class="text-center small text-uppercase">Sales Order</th>
                                    <th class="text-center small text-uppercase">SO Item</th>
                                    <th class="text-center small text-uppercase">Quantity PRO</th>
                                    <th class="text-center small text-uppercase">Quantity GR</th>
                                    <th class="text-center small text-uppercase">Posting Date</th>
                                </tr>
                            </thead>
                            <tbody id="modal-table-body">
                                </tbody>
                        </table>
                    </div>
                </div>
                 <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Script Vanilla JS kita untuk fungsionalitas kalender dan modal --}}
    <script>
        // Pastikan script berjalan setelah semua elemen halaman dimuat
         window.processedCalendarData = @json($processedData ?? []);
    </script>
    @endpush
</x-layouts.app>