<x-layouts.app title="List GR - PT. Kayu Mebel Indonesia">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="plant-code" content="{{ $kode }}">
    @push('styles')
    <style>
        /* === Kontainer & Header === */
        #calendar {
            border: none; background-color: #fff; border-radius: 1rem; padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.07), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        .fc-header-toolbar { margin-bottom: 2rem !important; }
        .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 600; color: #333; }
        .fc .fc-button { background: transparent !important; border: none !important; box-shadow: none !important; color: #888; }
        .fc .fc-button-primary { color: #fff !important; background-color: #6c757d !important; }
        
        /* === Grid & Teks Tanggal === */
        .fc .fc-view, .fc .fc-scrollgrid { border: none !important; }
        .fc-daygrid-day { border: none !important; }
        .fc .fc-daygrid-day-number { font-size: 0.875rem; color: #555; padding: 4px; float: right; }
    
        /* Styling Hari Minggu */
        #calendar .fc-day-sun > .fc-daygrid-day-frame {
            background-color: rgba(220, 53, 69, 0.07) !important;
            border-radius: 0.5rem;
        }
        #calendar .fc-day-sun a.fc-daygrid-day-number {
            color: #b02a37 !important;
            font-weight: 600;
        }
    
        /* Lingkaran Hari Ini */
        .fc .fc-day-today > .fc-daygrid-day-frame { background-color: transparent; }
        .fc .fc-day-today .fc-daygrid-day-number {
            background-color: #0d6efd; color: #fff; width: 28px; height: 28px;
            border-radius: 50%; display: flex; justify-content: center;
            align-items: center; padding: 0; margin: 0.25rem auto 0;
        }
        
        /* Event Styling */
        .fc-daygrid-day-events { display: flex; justify-content: center; padding-bottom: 5px; }
        
        /* Popover Styling */
        .popover { max-width: 320px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); border: none; }
        .popover-header { font-weight: 600; font-size: 0.95rem; background-color: #f8f9fa; border-bottom: 1px solid #eee; }
        .popover-body { font-size: 0.85rem; padding: 1rem; color: #444; }

        /* Kartu Event di Kalender */
        .fc-event-card-gr {
            background-color: #e7f5ff;
            border: 1px solid #b3d9ff;
            color: #0056b3;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 0.75rem;
            line-height: 1.3;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
            transition: transform 0.1s;
            cursor: pointer;
        }
        .fc-event-card-gr:hover { transform: scale(1.02); }

        /* Event Minggu */
        .fc-day-sun .fc-event-card-gr {
            background-color: #fbeaea;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* Modal Card Mobile */
        .modal-card {
            background-color: #f8f9fa; border: 1px solid #dee2e6;
            border-radius: 0.5rem; margin: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .modal-card-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.5rem 0.75rem; background-color: #fff;
            border-bottom: 1px solid #dee2e6; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem;
        }
        .modal-card-body { padding: 0.75rem; font-size: 0.9rem; }
        .modal-card-grid { display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem; }

        /* Filter Card Styles */
        .filter-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .filter-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07);
        }
        
        .filter-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        
        .btn-print {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            height: 38px; /* Sama dengan tinggi input/select */
            white-space: nowrap;
            align-self: flex-end;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(25, 135, 84, 0.2);
        }
        .btn-print:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .btn-print {
                width: 100%;
                margin-top: 1rem;
                justify-content: center;
            }
            .filter-form-grid .col-md-2 {
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            #calendar { padding: 0.75rem; font-size: 0.7rem; }
            .fc .fc-toolbar-title { font-size: 1rem; }
            .fc-event-card-gr { font-size: 0.65rem; padding: 1px 2px; }
        }

        .transition-hover { transition: all 0.2s ease; }
        .transition-hover:hover { background-color: #fee2e2 !important; transform: translateY(-1px); }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15) !important; border-color: #86b7fe; }
        .flatpickr-input { background-color: #f8f9fa !important; }

        .transition-hover:hover { background-color: #e9ecef !important; color: #dc3545 !important; }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
            background-color: #fff;
        }
        .form-control, .form-select, .btn-primary {
            min-height: 42px; 
        }

        .flatpickr-input { background-color: #fff !important; }
    </style>
    @endpush

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 position-relative overflow-visible" style="z-index: 1;">
                <!-- Aksen Garis Atas -->
                <div class="position-absolute top-0 start-0 w-100 bg-primary rounded-top-4" style="height: 4px;"></div>
                
                <div class="card-body p-4">
                    
                    <!-- Header Filter & Reset -->
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">
                                <i class="bi bi-sliders me-2 text-primary"></i>Filter Data
                            </h5>
                            <p class="text-muted small mb-0">Sesuaikan parameter di bawah untuk menyaring laporan.</p>
                        </div>
                        
                        <!-- Tombol Reset (Desain Minimalis) -->
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 transition-hover border-0 bg-light text-muted" 
                                id="btn-reset-filter" onclick="resetFilters()" style="font-size: 0.8rem;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                        </button>
                    </div>
    
                    <!-- Form Grid -->
                    <div class="row g-3">
                        
                        <!-- 1. Range Tanggal -->
                        <div class="col-lg-4 col-md-6">
                            <label for="filter-date" class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                Range Tanggal
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted ps-3 rounded-start-3 border-secondary-subtle">
                                    <i class="bi bi-calendar4-range"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 border-secondary-subtle ps-2 rounded-end-3 py-2 shadow-none" 
                                       id="filter-date" placeholder="Pilih Tanggal...">
                            </div>
                        </div>
    
                        <!-- 2. MRP (Dispo) -->
                        <div class="col-lg-3 col-md-6">
                            <label for="filter-mrp" class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                MRP (Dispo)
                            </label>
                            <select class="form-select border-secondary-subtle py-2 rounded-3 shadow-none" id="filter-mrp">
                                <option value="">Semua MRP</option>
                                @foreach($dataGr->unique('DISPO')->sortBy('DISPO') as $opt)
                                    <option value="{{ $opt->DISPO }}">{{ $opt->DISPO }}</option>
                                @endforeach
                            </select>
                        </div>
    
                        <!-- 3. Workcenter -->
                        <div class="col-lg-3 col-md-6">
                            <label for="filter-wc" class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                Workcenter
                            </label>
                            <select class="form-select border-secondary-subtle py-2 rounded-3 shadow-none" id="filter-wc">
                                <option value="">Semua Workcenter</option>
                                @foreach($dataGr->unique('ARBPL')->sortBy('ARBPL') as $opt)
                                    <option value="{{ $opt->ARBPL }}">{{ $opt->ARBPL }}</option>
                                @endforeach
                            </select>
                        </div>
    
                        <!-- 5. Tombol Cetak (Alignment Fix) -->
                        <div class="col-lg-2 col-md-12">
                            <!-- Label Kosong untuk Menjaga Kesejajaran Vertical -->
                            <label class="form-label d-none d-lg-block mb-1">&nbsp;</label>
                            
                            <div class="position-relative">
                                <button type="button" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm border-0 d-flex align-items-center justify-content-center gap-2" 
                                        id="btn-print" onclick="handlePrint()" disabled 
                                        style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); transition: all 0.2s;">
                                    <i class="bi bi-printer-fill"></i> 
                                    <span>Cetak</span>
                                </button>
    
                                <!-- Badge Counter (Posisi diperbaiki agar tidak terpotong) -->
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm border border-2 border-white" 
                                      id="print-count-badge" style="display: none; transform: translate(-50%, -50%) !important;">
                                    0
                                </span>
                            </div>
                        </div>
    
                    </div>
                    
                    <!-- Footer Info -->
                    <div class="d-flex justify-content-end mt-3 pt-2 border-top border-light-subtle">
                        <div class="small text-muted d-flex align-items-center bg-light px-3 py-1 rounded-pill">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span>Data siap dicetak: </span>
                            <span class="fw-bold text-dark ms-1" id="print-count-text">0</span> 
                            <span class="ms-1"> baris</span>
                        </div>
                    </div>
    
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Section -->
    <div class="row">
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
    </div>

    <!-- Modal Detail Tanggal (GR) -->
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
                                    <th class="text-center small text-uppercase" data-sort-key="KDAUF" style="cursor: pointer;">SO Item</th>
                                    <th class="text-center small text-uppercase" data-sort-key="PSMNG" style="cursor: pointer;">Qty PRO</th>
                                    <th class="text-center small text-uppercase" data-sort-key="MENGE" style="cursor: pointer;">Qty GR</th>
                                    <th class="text-center small text-uppercase">Value (Est)</th>
                                    <th class="small text-uppercase">Workcenter</th>
                                    <th class="small text-uppercase">MRP</th>
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

    <!-- Modal Detail Set (NEW) -->
    <div class="modal fade" id="setDetailModal" tabindex="-1" aria-labelledby="setModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle text-primary-emphasis">
                    <h1 class="modal-title fs-5 fw-bold" id="setModalTitle">
                        <i class="bi bi-collection me-2"></i>Detail Set
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 bg-light">
                    <!-- Container untuk daftar Set -->
                    <div id="set-modal-content" class="d-flex flex-column gap-3">
                        <!-- Items rendered via JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Injection untuk JS --}}
    <script>
        window.processedCalendarData = @json($processedData ?? []);
        window.allGrData = @json($dataGr ?? []);
    </script>
    
    <script>
        
    </script>
</x-layouts.app>