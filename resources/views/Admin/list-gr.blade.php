<x-layouts.app title="List GR - PT. Kayu Mebel Indonesia">
    @push('styles')
    <style>
        /* === Kontainer & Header (Tidak Berubah) === */
        #calendar {
            border: none; background-color: #fff; border-radius: 1rem; padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.07), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        .fc-header-toolbar { margin-bottom: 2rem !important; }
        .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 600; color: #333; }
        .fc .fc-button { background: transparent !important; border: none !important; box-shadow: none !important; color: #888; }
        .fc .fc-button-primary { color: #fff !important; background-color: #6c757d !important; }
        
        /* === Grid & Teks Tanggal (Tidak Berubah) === */
        .fc .fc-view, .fc .fc-scrollgrid { border: none !important; }
        .fc-daygrid-day { border: none !important; }
        .fc .fc-daygrid-day-number { font-size: 0.875rem; color: #555; padding: 0.5rem; }
    
        /* ✨ [PERBAIKAN] Aturan CSS untuk hari Minggu dibuat lebih kuat */
        #calendar .fc-day-sun > .fc-daygrid-day-frame {
            background-color: rgba(220, 53, 69, 0.07) !important; /* Latar belakang merah sangat lembut */
            border-radius: 0.5rem;
        }
        #calendar .fc-day-sun a.fc-daygrid-day-number {
            color: #b02a37 !important; /* Teks angka merah tua */
            font-weight: 600;
        }
    
        /* Lingkaran untuk Hari Ini (Tidak Berubah) */
        .fc .fc-day-today > .fc-daygrid-day-frame { background-color: transparent; }
        .fc .fc-day-today .fc-daygrid-day-number {
            background-color: #0d6efd; color: #fff; width: 28px; height: 28px;
            border-radius: 50%; display: flex; justify-content: center;
            align-items: center; padding: 0; margin: 0.25rem auto 0;
        }
        
        /* Event Titik (Tidak Berubah) */
        .fc-daygrid-day-events { display: flex; justify-content: center; padding-bottom: 5px; }
        .fc-event-dot-custom { width: 6px; height: 6px; background-color: #0d6efd; border-radius: 50%; }
        .fc-day-sun .fc-event-dot-custom { background-color: #b02a37; }
        .fc-day-today .fc-event-dot-custom { background-color: #0d6efd; }
    
        /* Penyesuaian Ukuran untuk Mobile (Tidak Berubah) */
        @media (max-width: 576px) {
            #calendar { padding: 0.75rem; font-size: 0.7rem; }
            .fc .fc-toolbar-title { font-size: 1rem; }
            .fc .fc-daygrid-day-number { padding: 2px; font-size: 0.65rem; }
            .fc .fc-day-today .fc-daygrid-day-number { width: 20px; height: 20px; margin-top: 2px; }
            .fc-event-dot-custom { width: 4px; height: 4px; }
            .fc-col-header-cell-cushion { font-size: 0.7rem; }
        }
        .popover {
            max-width: 300px; /* Atur lebar maksimal popover */
        }

        .popover-header {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .popover-body {
            font-size: 0.85rem;
            padding: 0.75rem;
        }
        /* [BARU] Styling untuk kartu event GR */
        .fc-event-card-gr {
            background-color: #e7f5ff; /* Warna biru muda */
            border: 1px solid #b3d9ff; /* Border biru yang lebih gelap */
            color: #0056b3; /* Warna teks biru tua */
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.7rem;
            line-height: 1.4;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
        }

        /* [BARU] Aturan warna event untuk hari Minggu */
        .fc-day-sun .fc-event-card-gr {
            background-color: #fbeaea;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* [BARU] Penyesuaian padding untuk angka tanggal */
        .fc .fc-daygrid-day-number {
            font-size: 0.875rem;
            color: #555;
            padding: 4px; /* Memberi jarak dari tepian */
            float: right; /* Memastikan posisi tetap di kanan */
        }
        /* [BARU] CSS untuk Tampilan Kartu di Modal Mobile */
        .modal-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            margin: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .modal-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }

        .modal-card-body {
            padding: 0.75rem;
            font-size: 0.9rem;
        }

        .modal-card-body p {
            font-size: 0.95rem;
            font-weight: 500;
            color: #343a40;
        }

        .modal-card-grid {
            display: grid;
            grid-template-columns: 100px 1fr; /* Kolom label dan kolom nilai */
            gap: 0.5rem;
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
                                Report GR dari : <span class="text-success fw-bold">{{ $nama_bagian }} - {{ $sub_kategori }} - {{ $kategori }}</span>
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
                    <table class="table table-hover table-striped mb-0" id="main-detail-table">
                        <thead class="table-light sticky-top">
                            <tr class="align-middle">
                                <th class="text-center small text-uppercase" data-sort-key="AUFNR" style="cursor: pointer;">PRO</th>
                                <th class="small text-uppercase d-none d-md-table-cell">Material Description</th>
                                <th class="text-center small text-uppercase d-none d-md-table-cell" data-sort-key="KDAUF" style="cursor: pointer;">Sales Order</th>
                                <th class="text-center small text-uppercase d-none d-md-table-cell" data-sort-key="KDPOS" style="cursor: pointer;">SO Item</th>
                                <th class="text-center small text-uppercase" data-sort-key="PSMNG" style="cursor: pointer;">Quantity PRO</th>
                                <th class="text-center small text-uppercase" data-sort-key="MENGE" style="cursor: pointer;">Quantity GR</th>
                                <th class="text-center small text-uppercase d-none d-md-table-cell" data-sort-key="BUDAT_MKPF" style="cursor: pointer;">Tgl. Posting</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dataGr as $item)
                                <tr class="detail-row"
                                    data-material="{{ $item->MAKTX ?? '-' }}"
                                    data-so="{{ $item->KDAUF ?? '-' }}"
                                    data-so-item="{{ $item->KDPOS ?? '-' }}"
                                    data-posting-date="{{ \Carbon\Carbon::parse($item->BUDAT_MKPF)->format('d M Y') }}"
                                    style="cursor: pointer;"
                                >
                                    <td class="text-center align-middle small fw-medium">{{ $item->AUFNR ?? '-' }}</td>
                                    <td class="align-middle small d-none d-md-table-cell">{{ $item->MAKTX ?? '-' }}</td>
                                    <td class="text-center align-middle d-none d-md-table-cell"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">{{ $item->KDAUF ?? '-' }}</span></td>
                                    <td class="text-center align-middle d-none d-md-table-cell"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">{{ $item->KDPOS ?? '-' }}</span></td>
                                    <td class="text-center align-middle small">{{ number_format($item->PSMNG ?? 0) }}</td>
                                    <td class="text-center align-middle small fw-bold text-success">{{ number_format($item->MENGE ?? 0) }}</td>
                                    <td class="text-center align-middle d-none d-md-table-cell"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">{{ \Carbon\Carbon::parse($item->BUDAT_MKPF)->format('d M Y') }}</span></td>
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
        
        <!-- [BARU] MODAL UNTUK POPUP DETAIL ITEM DI MOBILE -->
        <div class="modal fade" id="itemDetailModal" tabindex="-1" aria-labelledby="itemDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h1 class="modal-title fs-5" id="itemDetailModalLabel">Detail Item</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div id="itemDetailContent">
                            <!-- Konten akan diisi oleh JavaScript -->
                        </div>
                    </div>
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
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center small text-uppercase" data-sort-key="AUFNR" style="cursor: pointer;">PRO</th>
                                    <th class="small text-uppercase">Material Description</th>
                                    <th class="text-center small text-uppercase" data-sort-key="KDAUF" style="cursor: pointer;">Sales Order</th>
                                    <th class="text-center small text-uppercase" data-sort-key="KDPOS" style="cursor: pointer;">SO Item</th>
                                    <th class="text-center small text-uppercase" data-sort-key="PSMNG" style="cursor: pointer;">Quantity PRO</th>
                                    <th class="text-center small text-uppercase" data-sort-key="MENGE" style="cursor: pointer;">Quantity GR</th>
                                    <th class="text-center small text-uppercase" data-sort-key="BUDAT_MKPF" style="cursor: pointer;">Posting Date</th>
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

    <script>
        window.processedCalendarData = @json($processedData ?? []);
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeGoodReceiptCalendar();
        });
    </script>
</x-layouts.app>