
<x-layouts.app>
    <div class="container-fluid px-2">
        {{-- Header Halaman --}}
        <x-notification.notification />
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                    {{-- Info Utama --}}
                    <div>
                        <h1 class="h6 fw-semibold text-dark mb-1">Plant Code: {{ $WERKS }}</h1>
                        <p class="small text-muted mb-0">
                            <span class="fw-medium text-body-secondary">Section:</span> {{ $bagian }} |
                            <span class="fw-medium text-body-secondary">Category:</span> {{ $categories }} | 
                            <span class="fw-medium text-body-secondary">Laravel Code:</span> {{ $plant }}
                        </p>
                    </div>
                    {{-- Tombol Aksi --}}
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <a href="{{ route('manufaktur.detail.data2', $plant) }}" class="btn btn-primary btn-sm nav-loader-link">
                            <i class="fas fa-sync-alt me-1"></i> Sync
                        </a>
                        <button onclick="deleteAllStorage()" class="btn btn-warning btn-sm">
                            Hide All
                        </button>
                        <a href="{{ route('manufaktur.dashboard.show', $plant) }}" class="btn btn-secondary btn-sm nav-loader-link">
                            &larr; Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    
        {{-- Kontainer Utama --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                {{-- Tabel Sales Order --}}
                <div id="outstanding-order-container" class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                            <h3 class="h6 fw-semibold text-dark mb-0">Sales Order</h3>
                            <form method="GET" class="w-100" style="max-width: 320px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Buyer..." class="form-control border-start-0" id="searchInput">
                                </div>
                            </form>
                        </div>
    
                        {{-- Logika Max Height untuk Scroll --}}
                        @php
                            $maxRows = 8;
                            $totalRows = count($tdata);
                            $scrollStyle = ($totalRows > $maxRows) ? 'style="max-height: 400px; overflow-y: auto;"' : '';
                        @endphp
    
                        <div class="table-responsive" {!! $scrollStyle !!}>
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="small">
                                        <th class="text-center" style="width: 5%;">No.</th>
                                        <th>Buyer Name</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="salesOrderTableBody"> 
                                    @forelse($tdata as $item)
                                        @php $key = ($item->KUNNR ?? '') . '-' . ($item->NAME1 ?? ''); @endphp
                                        <tr class="cursor-pointer" 
                                            data-key="{{ $key }}" 
                                            onclick="openSalesItem(this)" 
                                            data-searchable-text="{{ strtolower($item->NAME1 ?? '') }}">
                                            
                                            <td class="text-center text-muted small">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle rounded-circle me-2" style="width: 28px; height: 28px;">
                                                        <span class="fw-bold small text-primary">{{ substr($item->NAME1 ?? 'N/A', 0, 1) }}</span>
                                                    </div>
                                                    <span class="fw-semibold text-dark small">{{ $item->NAME1 ?? '-' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center text-muted">
                                                <i class="fas fa-chevron-right small"></i>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center p-4 text-muted small">No data found.</td>
                                        </tr>
                                    @endforelse
                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="3" class="text-center p-4 text-muted small">
                                            <i class="fas fa-search fs-5 d-block mb-2"></i>
                                            No data matching your search.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
    
                <div id="tdata2-section" class="mt-3 d-none"></div>
    
                {{-- Order Overview Section --}}
                <div id="tdata3-container" class="mt-3 d-none">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                        <h3 class="h6 fw-semibold text-dark mb-0">Order Overview</h3>
                        <div class="btn-group btn-group-sm" role="group" id="status-filter">
                            <button type="button" class="btn btn-outline-secondary active" onclick="filterByStatus(this, 'all')">All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'crtd')">CRTD</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'released')">Released</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'outgoing')">Outgoing</button>
                        </div>
                    </div>
                    
                    <div id="bulk-actions-wrapper" data-refresh-url="{{ route('bulk-refresh.store') }}" 
                        class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-3 d-none p-2 border rounded-3 bg-light">
                        
                        <div id="selection-counter" class="d-flex align-items-center">
                            <span class="fw-semibold text-dark me-2 small">Selected:</span>
                            <span class="count-badge fw-bold badge bg-primary" id="selection-count-badge">0</span>
                        </div>
                        
                        <div id="bulk-controls" class="d-flex align-items-center gap-2 ms-sm-auto">
                            <button class="btn btn-warning btn-sm" id="bulk-schedule-btn" style="display: none;" onclick="openBulkScheduleModal()"><i class="fas fa-calendar-alt"></i></button>
                            <button class="btn btn-info btn-sm" id="bulk-refresh-btn" style="display: none;" onclick="openBulkRefreshModal()"><i class="fa-solid fa-arrows-rotate"></i></button>
                            <button class="btn btn-warning btn-sm" id="bulk-changePv-btn" style="display: none;" onclick="openBulkChangePvModal()"><i class="fa-solid fa-code-compare"></i></button>
                            <button class="btn btn-info btn-sm" id="bulk-readpp-btn" style="display: none;" onclick="openBulkReadPpModal()"><i class="fas fa-book-open"></i></button>
                            <button class="btn btn-danger btn-sm" id="bulk-teco-btn" style="display: none;" onclick="openBulkTecoModal()"><i class="fas fa-trash"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="clearAllSelections()">Clear</button>
                        </div>
                    </div>
    
                    <div class="mb-3">
                        <input type="search" id="proSearchInput" class="form-control form-control-sm" placeholder="Search by PRO, MRP, or Start Date...">
                    </div>
                    
                    <div class="table-responsive border rounded-3">
                        <table id="tdata3-table" class="table table-hover table-bordered align-middle mb-0 small">
                            <thead class="table-primary sticky-header-js">
                                <tr class="text-uppercase align-middle" style="font-size: 0.7rem;">
                                    <th class="text-center p-2"><input type="checkbox" class="form-check-input" id="select-all" onchange="toggleSelectAll()"></th>
                                    {{-- Kolom Desktop --}}
                                    <th class="text-center p-2 d-none d-md-table-cell">No.</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">PRO</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Status</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Action</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">MRP</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Material</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Description</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Qty Order</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Qty GR</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Outs GR</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Start Date</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Finish Date</th>
                                    {{-- Kolom Mobile --}}
                                    <th class="p-2 d-md-none" colspan="2">Production Order Details</th>
                                </tr>
                            </thead>
                            <tbody id="tdata3-body">
                                {{-- Rows will be populated by JavaScript --}}
                                <tr id="tdata3-no-results" style="display: none;">
                                    <td colspan="13" class="text-center p-4 text-muted small"> 
                                        <i class="fas fa-search fs-5 d-block mb-2"></i>
                                        No data matching your search.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="tdata3-pagination" class="mt-3 d-flex justify-content-between align-items-center d-none"></div>
                </div>
    
                <div id="additional-data-container" class="mt-3"></div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Detail Outstanding Order -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header border-bottom p-3">
            <h6 class="modal-title fw-semibold" id="detailModalLabel">Order Detail</h6>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="detailModalBody">
            {{-- Konten detail akan dimasukkan di sini oleh JavaScript --}}
            </div>
            <div class="modal-footer bg-light border-top p-2">
            <button type="button" class="btn btn-primary btn-sm w-100" id="modalViewDetailsBtn">View Order Overview</button>
            </div>
        </div>
        </div>
    </div>
    <div class="modal fade" id="tdata3DetailModal" tabindex="-1" aria-labelledby="tdata3DetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content border-0 shadow-sm">
            <div class="modal-header border-bottom p-3">
              <h6 class="modal-title fw-semibold" id="tdata3DetailModalLabel">PRO Detail</h6>
              <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="tdata3DetailModalBody">
              {{-- Konten detail TData3 akan dimasukkan di sini oleh JavaScript --}}
            </div>
            <div class="modal-footer bg-light border-top p-2" id="tdata3DetailModalFooter">
               {{-- Tombol aksi khusus TData3 akan dimasukkan di sini oleh JavaScript --}}
            </div>
          </div>
        </div>
    </div>

    <div class="modal fade" id="tdata4DetailModal" tabindex="-1" aria-labelledby="tdata4DetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header border-bottom p-3">
                    <h6 class="modal-title fw-semibold" id="tdata4DetailModalLabel">Component Detail</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="tdata4DetailModalBody"></div>
                <div class="modal-footer bg-light border-top p-2" id="tdata4DetailModalFooter"></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="tdata1DetailModal" tabindex="-1" aria-labelledby="tdata1DetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header border-bottom p-3">
                    <h6 class="modal-title fw-semibold" id="tdata1DetailModalLabel">Routing Detail</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="tdata1DetailModalBody"></div>
                <div class="modal-footer bg-light border-top p-2">
                    <button type="button" class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    @include('components.modals.schedule-modal')
    @include('components.modals.resultModal')
    @include('components.modals.refreshModal')
    @include('components.modals.changeWCmodal')
    @include('components.modals.changePVmodal')
    @include('components.modals.add-component-modal')
    @include('components.modals.bulk-schedule-modal')
    @include('components.modals.bulk-readpp-modal')
    @include('components.modals.bulk-refresh-modal')
    @include('components.modals.bulk-teco-modal')
    @include('components.modals.bulk-changepv-modal')
    @include('components.modals.edit-component')
    @include('components.modals.add-component-modal')
    @push('scripts')
    <script src="{{ asset('js/bulk-modal-handle.js') }}"></script>
    <script src="{{ asset('js/readpp.js') }}"></script>
    <script src="{{ asset('js/refresh.js') }}"></script>
    <script src="{{ asset('js/schedule.js') }}"></script>
    <script src="{{ asset('js/teco.js') }}"></script>
    <script src="{{ asset('js/component.js') }}"></script>
    <script src="{{ asset('js/changePv.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- [BARU] Inisialisasi semua Modal Bootstrap ---
        let resultModal, scheduleModal, changeWcModal, changePvModal, bulkScheduleModal, bulkReadPpModal, bulkTecoModal, bulkRefreshModal, bulkChangePvModal, editComponentModal;
        let bulkActionPlantCode = null;
        let bulkActionVerid = null;
        let t3ModalInstance;
        let detailModalInstance;
        let t4ModalInstance;
        let t1ModalInstance;
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('detailModal');
            if (modalEl) {
                detailModalInstance = new bootstrap.Modal(modalEl);
            }

            const t3ModalEl = document.getElementById('tdata3DetailModal');
            if (t3ModalEl) {
                t3ModalInstance = new bootstrap.Modal(t3ModalEl);
            }
            const t4ModalEl = document.getElementById('tdata4DetailModal');
            if (t4ModalEl) t4ModalInstance = new bootstrap.Modal(t4ModalEl);
            const t1ModalEl = document.getElementById('tdata1DetailModal');
            if (t1ModalEl) t1ModalInstance = new bootstrap.Modal(t1ModalEl);
        });
        const addComponentModal = document.getElementById('addComponentModal');
        document.addEventListener('DOMContentLoaded', () => {
            // Sukses dari controller
            @if (session('success'))
                Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: @json(session('success')),
                confirmButtonText: 'OK'
                });
            @endif

            // Error umum (controller pakai withErrors(['msg' => ...]))
            @if ($errors->has('msg'))
                Swal.fire({
                icon: 'error',
                title: 'Gagal',
                html: @json($errors->first('msg')).replace(/\n/g,'<br>'),  // jaga line break
                confirmButtonText: 'OK'
                });
            @endif

            // Error field validasi → tampilkan ringkas
            @if ($errors->any() && !$errors->has('msg'))
                Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                confirmButtonText: 'OK'
                });
            @endif
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi modal setelah DOM siap
            resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
            scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            changeWcModal = new bootstrap.Modal(document.getElementById('changeWcModal'));
            changePvModal = new bootstrap.Modal(document.getElementById('changePvModal'));
            bulkScheduleModal = new bootstrap.Modal(document.getElementById('bulkScheduleModal'));
            bulkReadPpModal = new bootstrap.Modal(document.getElementById('bulkReadPpModal'));
            bulkTecoModal = new bootstrap.Modal(document.getElementById('bulkTecoModal'));
            bulkRefreshModal = new bootstrap.Modal(document.getElementById('bulkRefreshModal'));
            bulkChangePvModal = new bootstrap.Modal(document.getElementById('bulkChangePvModal'));
            editComponentModal = new bootstrap.Modal(document.getElementById('dataEntryModal'));
        });

        // --- Fungsi Helper Notifikasi (SweetAlert - Tidak Berubah) ---
        window.toast = (icon, title, text='') => Swal.fire({ icon, title, text, timer: 2500, timerProgressBar: true, showConfirmButton: false, position: 'top-end', toast: true });
        window.SAP = {user: @json(session('sap_id')),pass: @json(session('password'))};

        // --- Variabel State & Data (Tidak Berubah) ---
        let t2CurrentSelectedRow = null, t2CurrentKey = null;
        let currentSelectedRow = null, selectedPLO = new Set(), selectedPRO = new Set(), mappedPRO = new Map();
        let allRowsData = [], currentFilterName = 'all';
        const allTData2 = @json($allTData2, JSON_HEX_TAG), allTData3 = @json($allTData3, JSON_HEX_TAG);
        const allTData1 = @json($allTData1, JSON_HEX_TAG), allTData4ByAufnr = @json($allTData4ByAufnr, JSON_HEX_TAG);
        const tdata1ByAufnr = (() => {
            const by = {};
            if (allTData1 && typeof allTData1 === 'object') {
                Object.values(allTData1).forEach(arr => {
                    (arr || []).forEach(t1 => {
                        const a = (t1?.AUFNR || '').toString();
                        if (a && !by[a]) by[a] = [];
                        if (a) by[a].push(t1);
                    });
                });
            }
            return by;
        })();
        function togglePaginationDisabled(isDisabled) {
            const paginationContainer = document.getElementById('tdata3-pagination');
            if (!paginationContainer) return;

            if (isDisabled) {
                // pe-none adalah kelas Bootstrap untuk pointer-events: none;
                paginationContainer.classList.add('opacity-50', 'pe-none');
            } else {
                paginationContainer.classList.remove('opacity-50', 'pe-none');
            }
        }
        
        // --- Fungsi Helper (Tidak Berubah) ---
        function padAufnr(v){ const s=String(v||''); return s.length>=12 ? s : s.padStart(12,'0'); }
        function sanitize(str){ const d=document.createElement('div'); d.textContent = String(str||''); return d.innerHTML; }
        function formatDate(dateString) { if (!dateString || dateString === '0000-00-00') return '-'; try { const date = new Date(dateString); return `${String(date.getDate()).padStart(2, '0')}-${String(date.getMonth() + 1).padStart(2, '0')}-${date.getFullYear()}`; } catch (e) { return dateString; } }
        function ltrim(str, char) { if (!str) return ''; return str.replace(new RegExp(`^${char}+`), ''); }
        function formatSapYmd(ymd){ if(!ymd || String(ymd).length !== 8) return '-'; const s = String(ymd); return `${s.slice(6,8)}-${s.slice(4,6)}-${s.slice(0,4)}`; }
        

        // --- Logika Utama (Dengan Perbaikan) ---

        function openSalesItem(tr) {
            const key = tr.dataset.key;
            if (currentSelectedRow === tr) {
                hideAllDetails(); // Ini akan menghapus state
                return;
            }
            
            hideAllDetails(); // Ini juga akan menghapus state lama
            currentSelectedRow = tr;

            // ==> BARU: Simpan state baris yang baru dibuka ke sessionStorage
            sessionStorage.setItem('activeSalesOrderKey', key);
            console.log(`State disimpan: ${key}`);
            
            const t1Container = document.getElementById('outstanding-order-container');
            t1Container.querySelectorAll('tbody tr').forEach(row => {
                if (row !== tr) row.classList.add('d-none');
            });
            const headerForm = t1Container.querySelector('form');
            if (headerForm) headerForm.parentElement.classList.add('d-none');
            const pager = t1Container.querySelector('.pagination');
            if (pager) pager.parentElement.classList.add('d-none');

            renderTData2Table(key);
        }

        // Fungsi `hideAllDetails` sekarang bertanggung jawab untuk menghapus state
        function hideAllDetails() {
            // Lakukan pembersihan state DI AWAL, ini yang paling penting.
            sessionStorage.removeItem('activeSalesOrderKey');
            console.log('State dihapus dari sessionStorage.');

            // Lanjutkan dengan membersihkan UI di dalam blok try-catch
            try {
                const t1Container = document.getElementById('outstanding-order-container');
                document.getElementById('tdata2-section').classList.add('d-none');
                document.getElementById('tdata3-container').classList.add('d-none');
                document.getElementById('additional-data-container').innerHTML = '';
                
                t1Container.querySelectorAll('tbody tr').forEach(row => {
                    row.classList.remove('d-none');
                    row.classList.remove('table-active');
                });

                const headerForm = t1Container.querySelector('form');
                if (headerForm) headerForm.parentElement.classList.remove('d-none');
                const pager = t1Container.querySelector('.pagination');
                if (pager) pager.parentElement.classList.remove('d-none');
                
                if (currentSelectedRow) currentSelectedRow = null;
                allRowsData = [];
                clearAllSelections();
                togglePaginationDisabled(false);
            } catch (error) {
                console.error("Terjadi error saat membersihkan UI, namun state sudah berhasil dihapus.", error);
            }
        }
        
        let isAutoLoadingState = false; 

        function loadPersistedState() {
            // === 1. Ambil semua key dari sessionStorage ===
            const activeSOKey = sessionStorage.getItem('activeSalesOrderKey');
            const activeT2Key = sessionStorage.getItem('activeTdata2Key');
            const activeT3Aufnr = sessionStorage.getItem('activeT3Aufnr');
            const activeT3Type = sessionStorage.getItem('activeT3Type');

            // Jika tidak ada state yang disimpan, keluar
            if (!activeSOKey) {
                console.log('INFO: Tidak ada state yang disimpan. Memuat tampilan default (T1).');
                return; 
            }
            
            console.log('INFO: State ditemukan. Memulai simulasi klik T1 -> T2 -> T3...');
            
            // AKTIFKAN FLAG PROTEKSI
            isAutoLoadingState = true; 

            // === 2. Muat state level 1 (T1 -> T2) ===
            console.log(`[DEBUG] Mencari T1 dengan key: ${activeSOKey}`);
            const activeRowT1 = document.querySelector(`tr[data-key="${activeSOKey}"]`);

            if (activeRowT1) {
                console.log('[DEBUG] SUKSES: Baris T1 ditemukan. Memicu openSalesItem...');
                openSalesItem(activeRowT1); 
            } else {
                console.error(`[DEBUG] GAGAL: Baris T1 untuk key ${activeSOKey} tidak ditemukan di DOM. Membersihkan sesi.`);
                sessionStorage.clear(); 
                isAutoLoadingState = false; // Pastikan flag di-reset
                return;
            }

            if (!activeT2Key) return;

            // === 3. Muat state level 2 (T2 -> T3) ===
            setTimeout(() => {
                console.log(`[DEBUG] Mencari T2 dengan key: ${activeT2Key}`);
                const activeRowT2 = document.querySelector(`#tdata2-body tr[data-key="${activeT2Key}"]`);
                
                if (activeRowT2) {
                    console.log('[DEBUG] SUKSES: Baris T2 ditemukan. Memicu handleClickTData2Row...');
                    handleClickTData2Row(activeT2Key, activeRowT2); 
                } else {
                    console.error(`[DEBUG] GAGAL: Baris T2 untuk key ${activeT2Key} tidak ditemukan.`);
                    isAutoLoadingState = false; // Pastikan flag di-reset
                    return;
                }

                if (!activeT3Aufnr || !activeT3Type) return;

                // === 4. Muat state level 3 (T3 -> T4) ===
                setTimeout(() => {
                    console.log(`[DEBUG] Mencari Baris T3 (PRO: ${activeT3Aufnr}) untuk memicu klik ${activeT3Type}.`);
                    
                    const rowT3 = document.querySelector(`#tdata3-body tr[data-aufnr="${activeT3Aufnr}"]`);

                    if (rowT3) {
                        let activeButtonT3;
                        if(activeT3Type === 'route') {
                            activeButtonT3 = rowT3.querySelector('[route-button]');
                        } else if (activeT3Type === 'component') {
                            activeButtonT3 = rowT3.querySelector('.pro-cell-actions button:last-child');
                        }
                        
                        if (activeButtonT3) {
                            console.log(`[DEBUG] SUKSES: Memicu klik tombol ${activeT3Type}!`);
                            activeButtonT3.click();
                            
                            // --- RESET FLAG SETELAH SEMUA KLIK SELESAI ---
                            console.log('INFO: Simulasi auto-load selesai. Resetting state flag.');
                            isAutoLoadingState = false; 
                        } else {
                            console.error(`[DEBUG] GAGAL: Tombol ${activeT3Type} tidak ditemukan di baris T3.`);
                            isAutoLoadingState = false; // Pastikan flag di-reset
                        }
                    } else {
                        console.error(`[DEBUG] GAGAL: Baris T3 untuk AUFNR ${activeT3Aufnr} tidak ditemukan.`);
                        isAutoLoadingState = false; // Pastikan flag di-reset
                    }

                }, 500); // Jeda diperpanjang sedikit untuk stabilitas

            }, 300); // Jeda diperpanjang sedikit untuk stabilitas
        }

        function deleteAllStorage() {
            hideAllDetails()
            // Perintah ini akan menghapus semua data dari session storage
            sessionStorage.clear();
            // Opsional: Beri tahu pengguna bahwa session telah dihapus
            console.info('Seluruh session storage telah berhasil dihapus!');
        }

        // Panggil fungsi load saat halaman selesai dimuat
        document.addEventListener('DOMContentLoaded', loadPersistedState);
        
        // Pastikan fungsi-fungsi ini bisa diakses secara global oleh `onclick`
        window.openSalesItem = openSalesItem;
        window.hideAllDetails = hideAllDetails; 
        window.deleteAllStorage = deleteAllStorage;

        function renderTData2Table(key) {
            const box = document.getElementById('tdata2-section');
            const rows = allTData2[key] || [];

            const cardWrapper = document.createElement('div');
            cardWrapper.className = 'card shadow-sm border-0';
            
            if (!rows.length){
                cardWrapper.innerHTML = `<div class="card-body text-center p-4 text-muted small">No Outstanding Order data for this item.</div>`;
            } else {
                let scrollStyle = rows.length > 8 ? 'style="max-height: 400px; overflow-y: auto;"' : '';

                let tableHtml = `
                    <div class="card-body p-3">
                        <div id="tdata2-header" class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-3">
                            <h3 id="tdata2-title" class="h6 fw-semibold text-dark mb-0">Outstanding Order</h3>
                            <div id="tdata2-search-wrapper" class="input-group input-group-sm" style="max-width: 320px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="tdata2-search" oninput="filterTData2Table()" placeholder="Search SO, Material..." class="form-control form-control-sm border-start-0">
                            </div>
                        </div>

                        <div class="table-responsive" ${scrollStyle}>
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light sticky-header-js">
                                    <tr class="align-middle" style="font-size: 0.7rem;">
                                        <th class="text-center p-2" style="width: 5%;">No.</th>
                                        <th class="p-2" style="min-width: 120px;">Sales Order</th>
                                        <th class="text-center p-2 d-none d-md-table-cell" style="width: 15%;">Material FG</th>
                                        <th class="p-2 d-none d-md-table-cell">Description</th>
                                        <th class="text-center p-2 d-none d-md-table-cell" style="width: 12%;">PO Date</th> 
                                        <th class="text-center p-2 d-none d-md-table-cell" style="width: 6%;">PRO (CRTD)</th> 
                                        <th class="text-center p-2 d-none d-md-table-cell" style="width: 6%;">PRO (Released)</th>
                                        <th class="p-2" style="width: 3%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tdata2-body">`;
                
                rows.forEach((r, i) => {
                    const soKey = `${r.KDAUF || ''}-${r.KDPOS || ''}`;
                    const t3 = allTData3[soKey] || [];
                    let proCrt = 0, proRel = 0;
                    
                    t3.forEach(d3 => {
                        if (d3.AUFNR){
                            const stats = d3.STATS ? d3.STATS.toUpperCase() : '';
                            if (stats === 'CRTD') proCrt++;
                            else if (stats.includes('REL') || stats === 'PCNF') proRel++;
                        }
                    });

                    const searchableText = [r.KDAUF, ltrim(r.KDPOS, '0'), ltrim(r.MATFG, '0'), r.MAKFG].join(' ').toLowerCase();

                    // [PERBAIKAN] Menambahkan data-buyer-key
                    tableHtml += `
                        <tr class="t2-row cursor-pointer" 
                            data-buyer-key="${key}" 
                            data-key="${soKey}" 
                            data-index="${i}" 
                            data-searchable-text="${sanitize(searchableText)}">
                            <td class="text-center text-muted">${i + 1}</td>
                            <td>
                                <div class="fw-semibold text-dark">${sanitize(r.KDAUF || '-')}</div>
                                <div class="text-muted" style="font-size: 0.9em;">Item: ${ltrim(r.KDPOS, '0')}</div>
                            </td>
                            <td class="text-muted text-center d-none d-md-table-cell">${ltrim(r.MATFG, '0') || '-'}</td>
                            <td class="d-none d-md-table-cell">${sanitize(r.MAKFG || '-')}</td>
                            <td class="text-center d-none d-md-table-cell">${formatDate(r.EDATU)}</td>
                            <td class="text-center fw-medium d-none d-md-table-cell">${proCrt}</td>
                            <td class="text-center fw-medium d-none d-md-table-cell">${proRel}</td>
                            <td class="text-center text-muted"><i class="fas fa-chevron-right small"></i></td>
                        </tr>`;
                });

                tableHtml += `
                    <tr id="tdata2-no-results" style="display: none;">
                        <td colspan="8" class="text-center p-4 text-muted small">
                            <i class="fas fa-search fs-5 d-block mb-2"></i>
                            No data matching your search.
                        </td>
                    </tr>
                </tbody></table></div>
                <p class="mt-3 small text-muted">Click a row to view the Order Overview table.</p>
                </div>`;
                cardWrapper.innerHTML = tableHtml;
            }
            
            box.innerHTML = '';
            box.appendChild(cardWrapper);
            box.classList.remove('d-none');
            
            box.querySelectorAll('.t2-row').forEach(tr => {
                tr.addEventListener('click', () => handleRowClick(tr));
            });
        }

        function handleRowClick(element) {
            if (window.innerWidth < 768) { // Breakpoint 'md' Bootstrap
                const buyerKey = element.dataset.buyerKey;
                const soKey = element.dataset.key;
                const index = parseInt(element.dataset.index, 10);
                showDetailModal(buyerKey, soKey, index, element);
            } else {
                handleClickTData2Row(element.dataset.key, element);
            }
        }

        // [PERBAIKAN] Menggunakan buyerKey untuk mencari data
        function showDetailModal(buyerKey, soKey, index, rowElement) {
            const data = allTData2[buyerKey][index]; // Menggunakan buyerKey yang benar
            if (!data || !detailModalInstance) return;

            const t3 = allTData3[soKey] || [];
            let proCrt = 0, proRel = 0;
            t3.forEach(d3 => {
                if (d3.AUFNR){
                    const stats = d3.STATS ? d3.STATS.toUpperCase() : '';
                    if (stats === 'CRTD') proCrt++;
                    else if (stats.includes('REL') || stats === 'PCNF') proRel++;
                }
            });

            const modalBody = document.getElementById('detailModalBody');
            modalBody.innerHTML = `
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                        <span class="text-muted">Material FG</span>
                        <strong class="text-dark text-end">${ltrim(data.MATFG, '0') || '-'}</strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                        <span class="text-muted">Description</span>
                        <strong class="text-dark text-end">${sanitize(data.MAKFG || '-')}</strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                        <span class="text-muted">PO Date</span>
                        <strong class="text-dark text-end">${formatDate(data.EDATU)}</strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                        <span class="text-muted">PRO (CRTD)</span>
                        <span class="badge bg-secondary rounded-pill">${proCrt}</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                        <span class="text-muted">PRO (Released)</span>
                        <span class="badge bg-success rounded-pill">${proRel}</span>
                    </div>
                </div>
            `;

            const viewDetailsBtn = document.getElementById('modalViewDetailsBtn');
            viewDetailsBtn.onclick = () => {
                detailModalInstance.hide();
                handleClickTData2Row(soKey, rowElement);
            };

            detailModalInstance.show();
        }

        function filterTData2Table() {
            // 1. Ambil elemen yang diperlukan dari dalam tabel yang sudah dirender
            const searchInput = document.getElementById('tdata2-search');
            const tableBody = document.getElementById('tdata2-body');
            
            // Pastikan elemen ada sebelum melanjutkan
            if (!searchInput || !tableBody) return;

            const tableRows = tableBody.querySelectorAll('tr.t2-row');
            const noResultsRow = document.getElementById('tdata2-no-results');
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleRowsCount = 0;

            // 2. Loop melalui setiap baris data
            tableRows.forEach(row => {
                const searchableText = row.dataset.searchableText;

                // 3. Cek kecocokan dan tampilkan/sembunyikan baris
                if (searchableText && searchableText.includes(searchTerm)) {
                    row.style.display = ''; // Tampilkan baris
                    visibleRowsCount++;
                } else {
                    row.style.display = 'none'; // Sembunyikan baris
                }
            });

            // 4. Atur visibilitas pesan "tidak ada hasil"
            if (noResultsRow) {
                if (visibleRowsCount === 0 && tableRows.length > 0) {
                    noResultsRow.style.display = ''; // Tampilkan pesan
                } else {
                    noResultsRow.style.display = 'none'; // Sembunyikan pesan
                }
            }
        }

        function showTData1ByAufnr(aufnr) {
            const container = document.getElementById('additional-data-container');
            const divId = `tdata1-${aufnr}`;
            const existing = document.getElementById(divId);
            if (existing) {
                existing.remove();
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('d-none'));
                togglePaginationDisabled(false);
                sessionStorage.removeItem('activeT3Aufnr');
                sessionStorage.removeItem('activeT3Type');
                return;
            }
            const data = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            if (!Array.isArray(data) || data.length === 0) {
                toast('info', 'Routing Empty', 'No routing data found.');
                return;
            }
            sessionStorage.setItem('activeT3Aufnr', aufnr);
            sessionStorage.setItem('activeT3Type', 'route');
            togglePaginationDisabled(true);
            document.querySelectorAll('#tdata3-body tr').forEach(row => {
                if (!row.dataset.rowData || !JSON.parse(row.dataset.rowData).AUFNR.includes(aufnr)) {
                    row.classList.add('d-none');
                } else {
                    row.classList.remove('d-none');
                }
            });
            const rowsHtml = data.map((t1, i) => {
                let hasilPerHari = '-'; 
                const kapazStr = String(t1.KAPAZ || '0').replace(/,/g, '.');
                const vgw01Str = String(t1.VGW01 || '0').replace(/\./g, '').replace(/,/g, '.');
                const kapazNum = parseFloat(kapazStr) || 0;
                const vgw01Num = parseFloat(vgw01Str) || 0;
                if (kapazNum > 0 && vgw01Num > 0) {
                    let result = (t1.VGE01 === 'S') ? (kapazNum * 3600) / vgw01Num : (kapazNum * 60) / vgw01Num;
                    hasilPerHari = Math.floor(result);
                }
                const rowData = encodeURIComponent(JSON.stringify(t1));
                return `
                    <tr class="t1-row cursor-pointer" data-row-data="${rowData}" onclick="handleT1RowClick(this)">
                        <td class="text-center d-none d-md-table-cell">${i + 1}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.VORNR || '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.STEUS || '-'}</td>
                        <td class="d-none d-md-table-cell">${t1.KTEXT || '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.ARBPL || '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.KAPAZ || '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${hasilPerHari}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.PV1 || '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.PV2 || '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${t1.PV3 || '-'}</td>
                        
                        <td class="d-md-none" colspan="10" style="padding: 4px; background-color: #f8f9fa;">
                            <div class="bg-white border rounded-3 shadow-sm p-3">
                                <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                    <div>
                                        <div class="fw-bold text-dark">${t1.KTEXT || 'No Description'}</div>
                                        <div class="text-muted small">Activity: ${t1.VORNR || '-'} | Work Ctr: ${t1.ARBPL || '-'}</div>
                                    </div>
                                    <i class="fas fa-chevron-right text-primary"></i>
                                </div>
                                <div class="d-grid gap-2" style="grid-template-columns: 1fr 1fr;">
                                    <div>
                                        <div class="small text-muted">Time (H)</div>
                                        <div class="fw-semibold">${t1.KAPAZ || '-'}</div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Item/Day</div>
                                        <div class="fw-semibold">${hasilPerHari}</div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Ctrl Key</div>
                                        <div class="fw-semibold">${t1.STEUS || '-'}</div>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-top">
                                    <div class="small text-muted mb-1">Production Version:</div>
                                    <div class="d-grid gap-1" style="grid-template-columns: 1fr 1fr 1fr;">
                                        <div class="bg-light p-2 rounded-2 text-center"><div class="text-muted small">PV 1</div><div class="fw-medium">${t1.PV1 || '-'}</div></div>
                                        <div class="bg-light p-2 rounded-2 text-center"><div class="text-muted small">PV 2</div><div class="fw-medium">${t1.PV2 || '-'}</div></div>
                                        <div class="bg-light p-2 rounded-2 text-center"><div class="text-muted small">PV 3</div><div class="fw-medium">${t1.PV3 || '-'}</div></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
            const scrollClass = data.length > 2 ? 'table-responsive-custom-scroll' : '';
            const block = document.createElement('div');
            block.id = divId;
            block.className = 'mt-4 card shadow-sm border-0';
            block.innerHTML = `
                <div class="card-body p-0">
                    <div class="p-3 bg-white border-bottom">
                        <h6 class="fw-semibold mb-0 text-dark">Routing for PRO ${aufnr}</h6>
                    </div>
                    <div class="table-responsive ${scrollClass}">
                        <table class="table table-hover table-sm mb-0 small">
                            <thead class="table-light sticky-header-custom">
                                <tr>
                                    <th class="text-center p-2 d-none d-md-table-cell" style="width: 5%;">No.</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Activity</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Ctrl Key</th>
                                    <th class="p-2 d-none d-md-table-cell">Description</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Work Ctr</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Time (H)</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Item/Day</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">PV 1</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">PV 2</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">PV 3</th>
                                    <th class="p-2 d-md-none" colspan="10">Routing Details</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>
                </div>`;
            container.innerHTML = '';
            container.appendChild(block);
        }

        function handleT1RowClick(element) {
            if (window.innerWidth < 768) {
                try {
                    const data = JSON.parse(decodeURIComponent(element.dataset.rowData));
                    showT1DetailModal(data);
                } catch (e) {
                    console.error("Gagal memproses data baris untuk modal routing:", e);
                }
            }
        }

        function showT1DetailModal(data) {
            if (!data || !t1ModalInstance) return;

            const modalLabel = document.getElementById('tdata1DetailModalLabel');
            const modalBody = document.getElementById('tdata1DetailModalBody');

            if (!modalLabel || !modalBody) {
                console.error("Elemen untuk modal TData1 tidak ditemukan.");
                return;
            }

            modalLabel.textContent = `Routing: ${data.KTEXT || 'Detail'}`;

            let hasilPerHari = '-'; 
            const kapazStr = String(data.KAPAZ || '0').replace(/,/g, '.');
            const vgw01Str = String(data.VGW01 || '0').replace(/\./g, '').replace(/,/g, '.');
            const kapazNum = parseFloat(kapazStr) || 0;
            const vgw01Num = parseFloat(vgw01Str) || 0;
            if (kapazNum > 0 && vgw01Num > 0) {
                let result = (data.VGE01 === 'S') ? (kapazNum * 3600) / vgw01Num : (kapazNum * 60) / vgw01Num;
                hasilPerHari = Math.floor(result);
            }

            modalBody.innerHTML = `
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Activity</span><strong>${data.VORNR || '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Control Key</span><strong>${data.STEUS || '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Work Center</span><strong>${data.ARBPL || '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Time Capacity (H)</span><strong>${data.KAPAZ || '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Item/Day</span><strong>${hasilPerHari}</strong></div>
                </div>
            `;
            
            t1ModalInstance.show();
        }
        
        function showTData4ByAufnr(aufnr) {
            const container = document.getElementById('additional-data-container');
            const blockId = `tdata4-${aufnr}`;
            const existing = document.getElementById(blockId);
            if (existing) {
                existing.remove();
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('d-none'));
                togglePaginationDisabled(false);
                sessionStorage.removeItem('activeT3Aufnr');
                sessionStorage.removeItem('activeT3Type');
                return;
            }
            sessionStorage.setItem('activeT3Aufnr', aufnr);
            sessionStorage.setItem('activeT3Type', 'component');
            togglePaginationDisabled(true);
            const data = (allTData4ByAufnr && allTData4ByAufnr[aufnr]) ? allTData4ByAufnr[aufnr] : [];
            const plantCode = data.length > 0 && data[0].WERKSX ? data[0].WERKSX : '{{ $kode ?? $plant }}';
            document.querySelectorAll('#tdata3-body tr').forEach(row => {
                if (!row.dataset.rowData || !JSON.parse(row.dataset.rowData).AUFNR.includes(aufnr)) {
                    row.classList.add('d-none');
                } else {
                    row.classList.remove('d-none');
                }
            });
            const routingData = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            const firstRouting = routingData.length > 0 ? routingData[0] : {};
            const vornr = firstRouting.VORNR || '0010';
            const arbpl = firstRouting.ARBPL || '';
            const pwwrk = firstRouting.PWWRK || '{{ $plant }}';
            const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');
            const rowsHtml = data.map((c, i) => {
                const rowData = encodeURIComponent(JSON.stringify(c));
                return `
                    <tr class="t4-row cursor-pointer" data-row-data="${rowData}" onclick="handleT4RowClick(this)">
                        <td class="text-center" onclick="event.stopPropagation()">
                            <input type="checkbox" class="component-select-${aufnr} form-check-input" data-aufnr="${aufnr}" data-rspos="${c.RSPOS || i}" data-material="${ltrim0(c.MATNR)}" onchange="handleComponentSelect('${aufnr}')">
                        </td>
                        <td class="text-center d-none d-md-table-cell">${i + 1}</td>
                        <td class="text-center d-none d-md-table-cell">${c.RSNUM ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${c.RSPOS ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${ltrim0(c.MATNR)}</td>
                        <td class="d-none d-md-table-cell">${sanitize(c.MAKTX)}</td>
                        <td class="text-center d-none d-md-table-cell" onclick="event.stopPropagation()">
                            <button type="button" class="btn btn-warning btn-sm edit-component-btn"
                                    data-aufnr="${c.AUFNR ?? ''}" data-rspos="${c.RSPOS ?? ''}" data-matnr="${c.MATNR ?? ''}"
                                    data-bdmng="${c.BDMNG ?? ''}" data-lgort="${c.LGORT ?? ''}" data-sobkz="${c.SOBKZ ?? ''}"
                                    data-plant="${plantCode}"
                                    onclick="handleEditClick(this)"><i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                        <td class="text-center d-none d-md-table-cell">${c.BDMNG ?? c.MENGE ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${c.KALAB ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${c.OUTSREQ ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${c.VMENG ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${c.LGORT ?? '-'}</td>
                        <td class="text-center d-none d-md-table-cell">${sanitize(c.MEINS === 'ST' ? 'PC' : (c.MEINS || '-'))}</td>
                        <td class="text-center d-none d-md-table-cell">${c.LTEXT ?? '-'}</td>
                        
                        <td class="d-md-none" colspan="13" style="padding: 4px; background-color: #f8f9fa;">
                            <div class="bg-white border rounded-3 shadow-sm p-3">
                                <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                    <div>
                                        <div class="fw-bold text-dark">${c.RSNUM ?? '-'} / ${c.RSPOS ?? '-'}</div>
                                        <div class="text-muted small">${sanitize(c.MAKTX)}</div>
                                    </div>
                                    <i class="fas fa-chevron-right text-primary"></i>
                                </div>
                                <div class="d-grid gap-2" style="grid-template-columns: 1fr 1fr;">
                                    <div>
                                        <div class="small text-muted">Req. Qty</div>
                                        <div class="fw-semibold">${c.BDMNG ?? c.MENGE ?? '-'} <span class="text-muted small">${sanitize(c.MEINS === 'ST' ? 'PC' : (c.MEINS || '-'))}</span></div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Stock</div>
                                        <div class="fw-semibold text-success">${c.KALAB ?? '-'}</div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Outs. Req</div>
                                        <div class="fw-semibold text-danger">${c.OUTSREQ ?? '-'}</div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Committed</div>
                                        <div class="fw-semibold">${c.VMENG ?? '-'}</div>
                                    </div>
                                    <div>
                                        <div class="small text-muted">S.Loc</div>
                                        <div class="fw-semibold">${c.LGORT ?? '-'}</div>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-top">
                                    <div class="small text-muted">Spec. Procurement</div>
                                    <div class="fw-semibold small">${c.LTEXT ?? '-'}</div>
                                </div>
                                <div class="mt-2 text-end">
                                    <button type="button" class="btn btn-warning btn-sm edit-component-btn py-1 px-2"
                                            data-aufnr="${c.AUFNR ?? ''}" data-rspos="${c.RSPOS ?? ''}" data-matnr="${c.MATNR ?? ''}"
                                            data-bdmng="${c.BDMNG ?? ''}" data-lgort="${c.LGORT ?? ''}" data-sobkz="${c.SOBKZ ?? ''}"
                                            data-plant="${plantCode}"
                                            onclick="event.stopPropagation(); handleEditClick(this);">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
            const scrollClass = data.length > 2 ? 'table-responsive-custom-scroll' : '';
            const block = document.createElement('div');
            block.id = blockId;
            block.className = 'mt-4 card shadow-sm border-0';
            block.innerHTML = `
                <div class="card-body p-0">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center p-3 bg-white border-bottom gap-2">
                        <div class="fw-semibold text-dark">Component List for PRO ${aufnr}</div>
                        <div class="d-flex gap-2 ms-sm-auto" id="component-actions-${aufnr}">
                            <div id="bulk-delete-controls-${aufnr}" class="d-none">
                                <div class="d-flex gap-2">
                                    <button type="button" id="bulk-delete-btn-${aufnr}" class="btn btn-danger btn-sm" onclick="bulkDeleteComponents('${aufnr}','${plantCode}')"><i class="fas fa-trash-alt me-1"></i> Delete (0)</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearComponentSelections('${aufnr}')">Clear</button>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm btn-add-component" data-bs-toggle="modal" data-bs-target="#addComponentModal" data-aufnr="${aufnr}" data-vornr="${vornr}" data-arbpl="${arbpl}" data-pwwrk="${pwwrk}"><i class="fas fa-plus me-1"></i> Add</button>
                        </div>
                    </div>
                    <div class="table-responsive ${scrollClass}">
                        <table class="table table-hover table-sm mb-0 small">
                            <thead class="table-light sticky-header-custom">
                                <tr>
                                    <th class="text-center p-2" style="width: 5%;"><input type="checkbox" id="select-all-components-${aufnr}" class="form-check-input" onchange="toggleSelectAllComponents('${aufnr}')"></th>
                                    <th class="text-center p-2 d-none d-md-table-cell" style="width: 5%;">No.</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Reservation No.</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Item</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Material</th>
                                    <th class="p-2 d-none d-md-table-cell">Description</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Action</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Req. Qty</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Stock</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Outs. Req</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Committed</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">S.Loc</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">UOM</th>
                                    <th class="text-center p-2 d-none d-md-table-cell">Spec. Proc.</th>
                                    <th class="p-2 d-md-none" colspan="13">Component Details</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml.length > 0 ? rowsHtml : `<tr><td colspan="15" class="text-center p-4 text-muted">No components found. Click 'Add' to add new one.</td></tr>`}</tbody>
                        </table>
                    </div>
                </div>`;
            container.innerHTML = '';
            container.appendChild(block);
        }

        function handleT4RowClick(element) {
            if (window.innerWidth < 768) {
                try {
                    const data = JSON.parse(decodeURIComponent(element.dataset.rowData));
                    const plantCode = '{{ $kode ?? $plant }}';
                    showT4DetailModal(data, plantCode);
                } catch (e) {
                    console.error("Gagal memproses data baris untuk modal komponen:", e, element.dataset.rowData);
                }
            }
        }

        function showT4DetailModal(data, plantCode) {
            if (!data || !t4ModalInstance) return;

            const modalLabel = document.getElementById('tdata4DetailModalLabel');
            const modalBody = document.getElementById('tdata4DetailModalBody');
            const modalFooter = document.getElementById('tdata4DetailModalFooter');

            if (!modalLabel || !modalBody || !modalFooter) {
                console.error("Elemen untuk modal TData4 tidak ditemukan.");
                return;
            }

            modalLabel.textContent = `Component: ${ltrim(data.MATNR, '0')}`;
            modalBody.innerHTML = `
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Reservation</span><strong>${data.RSNUM ?? '-'} / ${data.RSPOS ?? '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Req. Qty</span><strong>${data.BDMNG ?? data.MENGE ?? '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Stock</span><strong>${data.KALAB ?? '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Outs. Req</span><strong>${data.OUTSREQ ?? '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Committed</span><strong>${data.VMENG ?? '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Storage Loc.</span><strong>${data.LGORT ?? '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">UOM</span><strong>${sanitize(data.MEINS === 'ST' ? 'PC' : (data.MEINS || '-'))}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Spec. Procurement</span><strong>${data.LTEXT ?? '-'}</strong></div>
                </div>
            `;
            
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-warning btn-sm w-100 edit-component-btn"
                        data-aufnr="${data.AUFNR ?? ''}" data-rspos="${data.RSPOS ?? ''}" data-matnr="${data.MATNR ?? ''}"
                        data-bdmng="${data.BDMNG ?? ''}" data-lgort="${data.LGORT ?? ''}" data-sobkz="${data.SOBKZ ?? ''}"
                        data-plant="${plantCode}"
                        onclick="handleEditClick(this); t4ModalInstance.hide();">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Component
                </button>
            `;

            t4ModalInstance.show();
        }
        // ---------------------------------------------------------------
        // PENGELOLA MODAL ADD COMPONENT (BUKA & TUTUP)
        // ---------------------------------------------------------------

        if (addComponentModal) {
            // Listener ini akan berjalan SETIAP KALI modal akan ditampilkan
            addComponentModal.addEventListener('show.bs.modal', function (event) {
                // event.relatedTarget adalah tombol yang memicu modal
                const button = event.relatedTarget;

                // Ambil semua data dari atribut data-* tombol
                const aufnr = button.dataset.aufnr;
                const vornr = button.dataset.vornr;
                const arbpl = button.dataset.arbpl;
                const pwwrk = button.dataset.pwwrk;

                // Temukan input di dalam modal dan isi nilainya
                // Pastikan ID input di modal Anda sudah benar
                addComponentModal.querySelector('#addComponentAufnr').value = aufnr || '';
                addComponentModal.querySelector('#addComponentVornr').value = vornr || '';
                addComponentModal.querySelector('#addComponentPlant').value = pwwrk || ''; // 'plant' di modal diisi oleh 'pwwrk'
                
                // Jika Anda punya display field (bukan input)
                const displayField = addComponentModal.querySelector('#displayAufnr');
                if (displayField) {
                    displayField.textContent = aufnr || ''; // Gunakan textContent untuk elemen non-input
                }
            });
        }

        const materialInput = document.getElementById('materialInput');
        // Pastikan elemennya ada untuk menghindari error
        if (materialInput) {
            // 2. Tambahkan event listener 'blur'. 
            //    Kode ini akan berjalan saat pengguna mengklik di luar input field.
            materialInput.addEventListener('blur', function() {
                
                // Ambil nilai input saat ini dan hapus spasi di awal/akhir
                const currentValue = this.value.trim();

                // 3. Buat regular expression untuk mengecek apakah SEMUA karakter adalah angka
                const isOnlyNumbers = /^\d+$/.test(currentValue);

                // 4. Jika semua karakter adalah angka dan input tidak kosong
                if (isOnlyNumbers && currentValue.length > 0) {
                    
                    // Tambahkan angka '0' di depan hingga total panjangnya 18 karakter
                    this.value = currentValue.padStart(18, '0');
                }
                
                // Jika input berisi huruf atau karakter lain, tidak ada yang terjadi.
            });
        }


        // ---------------------------------------------------------------
        // PENGELOLA SUBMIT FORM VIA AJAX
        // ---------------------------------------------------------------
        const submitBtn = document.getElementById('add-component-submit-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.innerHTML;
                const form = document.getElementById('add-component-form');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                // Validasi sederhana
                if (!data.iv_matnr || !data.iv_bdmng || !data.iv_meins || !data.iv_lgort) {
                    // Ganti 'showSwal' dengan fungsi notifikasi Anda
                    return showSwal('Harap isi semua field yang wajib diisi (*).', 'error');
                }

                button.disabled = true;
                button.innerHTML = 'Menyimpan...'; // Tampilan loading sederhana

                fetch("{{ route('component.add') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json().then(resData => ({ status: response.status, body: resData })))
                .then(({ status, body }) => {
                    if (status >= 400) { throw new Error(body.message || 'Gagal menambahkan komponen.'); }
                    showSwal(body.message, 'success'); // Tampilkan notifikasi
                    setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    showSwal(error.message, 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }
        
        function handleClickTData2Row(key, tr) {
            const t3Container = document.getElementById('tdata3-container');
            const allT2Rows = document.querySelectorAll('#tdata2-section .t2-row');
        
            const searchContainer = document.getElementById('tdata2-search-wrapper'); 

            // KASUS 1: Klik baris yang sama untuk MENUTUP detail (collapse)
            if (t2CurrentKey === key && t2CurrentSelectedRow === tr && !t3Container.classList.contains('d-none')) {
                
                // [PERBAIKAN] Tampilkan kembali searchbar
                if (searchContainer) {
                    searchContainer.style.display = ''; 
                }

                // [PERUBAHAN] Tampilkan kembali SEMUA baris di TData2
                allT2Rows.forEach(row => row.style.display = '');

                // Logika penutupan
                t2CurrentSelectedRow.classList.remove('table-active');
                t3Container.classList.add('d-none');
                document.getElementById('additional-data-container').innerHTML = '';
                sessionStorage.removeItem('activeTdata2Key');
                
                // Reset state
                t2CurrentKey = null;
                t2CurrentSelectedRow = null;
                return;
            }

            // KASUS 2: Klik baris baru atau membuka detail

            // [PERBAIKAN] Sembunyikan searchbar
            if (searchContainer) {
                searchContainer.style.display = 'none'; 
            }
            
            // Logika untuk menyembunyikan baris lain (sudah ada)
            allT2Rows.forEach(row => {
                row.style.display = (row === tr) ? '' : 'none';
            });

            // Logika pembukaan detail
            // Hapus active dari baris sebelumnya jika ada
            if (t2CurrentSelectedRow) {
                t2CurrentSelectedRow.classList.remove('table-active');
            }
            
            tr.classList.add('table-active');
            t2CurrentSelectedRow = tr;
            t2CurrentKey = key;
            sessionStorage.setItem('activeTdata2Key', key);
            
            // Panggil fungsi untuk memuat dan menampilkan data TData3
            showTData3ForKey(key);
        }

        function showTData3ForKey(key) {
            const t3Container = document.getElementById('tdata3-container');
            const rows = allTData3[key] || [];
            allRowsData = rows;

            if (rows.length) {
                // [PERBAIKAN] Cari tombol 'All' terlebih dahulu, baru panggil fungsinya
                const allButton = document.querySelector('#status-filter button'); // Tombol pertama di grup adalah 'All'
                filterByStatus(allButton, 'all');
                
                t3Container.classList.remove('d-none');
            } else {
                const tbody = document.getElementById('tdata3-body');
                tbody.innerHTML = `<tr><td colspan="13" class="text-center p-4 text-muted">Tidak ada order overview (T_DATA3) untuk item ini.</td></tr>`;
                document.getElementById('tdata3-pagination').innerHTML = '';
                t3Container.classList.remove('d-none');
            }
        }
        
        function hideAllDetails() {
            const t1Container = document.getElementById('outstanding-order-container');
            document.getElementById('tdata2-section').classList.add('d-none');
            document.getElementById('tdata3-container').classList.add('d-none');
            document.getElementById('additional-data-container').innerHTML = '';
            
            t1Container.querySelectorAll('tbody tr').forEach(row => {
                row.classList.remove('d-none');
                row.classList.remove('table-active'); // Gunakan .table-active
            });

            // PERBAIKAN: Cek dulu apakah elemennya ada sebelum dimanipulasi
            const headerForm = t1Container.querySelector('form');
            if (headerForm) {
                headerForm.parentElement.classList.remove('d-none');
            }

            const pager = t1Container.querySelector('.pagination');
            if (pager) {
                pager.parentElement.classList.remove('d-none');
            }
            
            if (currentSelectedRow) currentSelectedRow = null;
            allRowsData = [];
            clearAllSelections();
            togglePaginationDisabled(false);
        }

        function filterByStatus(button, status) {
            document.querySelectorAll('#status-filter button').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            currentFilterName = status;

            let filteredData = allRowsData;
            
            // Logika Filtering Data
            if (status === 'plo') {
                filteredData = allRowsData.filter(d3 => d3.PLNUM && !d3.AUFNR);
            } else if (status === 'crtd') {
                filteredData = allRowsData.filter(d3 => d3.AUFNR && d3.STATS === 'CRTD');
            } else if (status === 'released') {
                // Filter 'released' (sama seperti perbaikan sebelumnya)
                // Mencakup semua status yang mengandung 'REL'
                filteredData = allRowsData.filter(d3 => d3.AUFNR && d3.STATS.includes('REL'));
            } else if (status === 'outgoing') {
                // Tentukan tanggal hari ini
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const todayString = `${year}-${month}-${day}`;
                
                // PERBAIKAN DI SINI:
                // Filter 'outgoing' sekarang mencari AUFNR, GSTRP hari ini, dan STATS yang mengandung 'REL'.
                filteredData = allRowsData.filter(d3 => 
                    d3.AUFNR && 
                    d3.GSTRP === todayString && 
                    d3.STATS.includes('REL') // Perubahan: menggunakan .includes('REL')
                );
            }

            renderT3Page(filteredData);
        }
        
        function renderT3Page(filteredData) {
            const tbody = document.getElementById('tdata3-body');
            const tableWrapper = document.querySelector('#tdata3-container .table-responsive'); 
            
            tbody.innerHTML = '';

            const MAX_ROWS = 8;
            const MAX_HEIGHT_PX = '400px'; 
            const NO_RESULTS_COLSPAN = 13;

            if (tableWrapper) {
                tableWrapper.style.maxHeight = '';
                tableWrapper.style.overflowY = '';
            }

            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${NO_RESULTS_COLSPAN}" class="text-center p-4 text-muted">No data for this filter.</td></tr>`;
            } else {
                filteredData.forEach((d3, i) => {
                    const row = createTableRow(d3, i + 1);
                    tbody.appendChild(row);
                });

                if (filteredData.length > MAX_ROWS && tableWrapper) {
                    tableWrapper.style.maxHeight = MAX_HEIGHT_PX;
                    tableWrapper.style.overflowY = 'auto';
                }
            }

            const pg = document.getElementById('tdata3-pagination');
            if (pg) pg.innerHTML = '';
            
            clearAllSelections(); 
        }

        const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');
        
        function createTableRow(d3, index) {
            const row = document.createElement('tr');
            row.dataset.rowData = JSON.stringify(d3);
            row.className = 'cursor-pointer';
            row.setAttribute('onclick', 'handleT3RowClick(this)');

            const canSelect = d3.PLNUM || d3.AUFNR;
            const psmng = parseFloat(d3.PSMNG) || 0;
            const wemng = parseFloat(d3.WEMNG) || 0;
            const remainingQty = psmng - wemng;

            let statusBadgeClass = 'bg-secondary';
            if (d3.STATS === 'CRTD') statusBadgeClass = 'bg-secondary';
            else if (d3.STATS && d3.STATS.includes('TECO')) statusBadgeClass = 'bg-danger';
            else if (d3.STATS && d3.STATS.includes('REL')) statusBadgeClass = 'bg-success';

            row.innerHTML = `
                <td class="text-center" onclick="event.stopPropagation()">
                    <input type="checkbox" class="form-check-input bulk-select"
                        ${canSelect ? '' : 'disabled'}
                        data-type="${d3.PLNUM && !d3.AUFNR ? 'PLO' : 'PRO'}"
                        data-id="${d3.PLNUM && !d3.AUFNR ? d3.PLNUM : d3.AUFNR}"
                        data-auart="${d3.AUART || ''}"
                        onchange="handleBulkSelect(this)">
                </td>
                
                {{-- Tampilan Desktop --}}
                <td class="text-center d-none d-md-table-cell">${index}</td>
                <td class="text-center d-none d-md-table-cell">
                    <div class="pro-cell-inner d-flex align-items-center justify-content-between">
                        <span class="fw-medium">${d3.AUFNR || '-'}</span>
                        ${d3.AUFNR ? `
                        <div class="pro-cell-actions d-flex align-items-center gap-2">
                            <button class="btn btn-info btn-sm py-0 px-1" onclick="event.stopPropagation(); showTData1ByAufnr('${d3.AUFNR}');">Route</button>
                            <button class="btn btn-primary btn-sm py-0 px-1" onclick="event.stopPropagation(); showTData4ByAufnr('${d3.AUFNR}');">Comp</button>
                        </div>
                        ` : ''}
                    </div>
                </td>
                <td class="text-center d-none d-md-table-cell"><span class="badge ${statusBadgeClass}">${d3.STATS || '-'}</span></td>
                <td class="text-center d-none d-md-table-cell" onclick="event.stopPropagation();">
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        ${d3.AUFNR ? `<button type="button" title="Reschedule" class="btn btn-warning btn-sm" onclick="openSchedule('${encodeURIComponent(padAufnr(d3.AUFNR))}', '${formatDate(d3.SSAVD)}')"><i class="fas fa-clock-rotate-left"></i></button>` : ''}
                        ${d3.AUFNR ? `<button type="button" title="Refresh PRO" class="btn btn-info btn-sm" onclick="openRefresh('${d3.AUFNR}', '${d3.WERKSX}')"><i class="fa-solid fa-arrows-rotate"></i></button>` : ''}
                        ${d3.AUFNR ? `<button type="button" title="Change PV" class="btn btn-warning btn-sm" onclick="openChangePvModal('${d3.AUFNR}', '${d3.VERID}', '${d3.WERKSX}')"><i class="fa-solid fa-code-compare"></i></button>` : ''}
                        ${d3.AUFNR ? `<button type="button" title="Read PP" class="btn btn-info btn-sm" onclick="openReadPP('${encodeURIComponent(padAufnr(d3.AUFNR))}')"><i class="fas fa-book-open"></i></button>` : ''}
                        ${d3.AUFNR ? `<button type="button" title="TECO" class="btn btn-danger btn-sm" onclick="openTeco('${encodeURIComponent(padAufnr(d3.AUFNR))}')"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </td>
                <td class="text-center d-none d-md-table-cell">${d3.DISPO || '-'}</td>
                <td class="text-center d-none d-md-table-cell">${d3.MATNR ? ltrim(d3.MATNR, '0') : '-'}</td>
                <td class="text-center d-none d-md-table-cell">${sanitize(d3.MAKTX) || '-'}</td>
                <td class="text-center d-none d-md-table-cell">${d3.PSMNG || '-'}</td>
                <td class="text-center d-none d-md-table-cell">${d3.WEMNG}</td>
                <td class="text-center d-none d-md-table-cell">${remainingQty}</td>
                <td class="text-center d-none d-md-table-cell">${formatDate(d3.GSTRP)}</td>
                <td class="text-center d-none d-md-table-cell">${formatDate(d3.GLTRP)}</td>

                {{-- Tampilan Mobile --}}
                <td class="d-md-none" colspan="2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold text-dark">${d3.AUFNR || '-'} <span class="badge ${statusBadgeClass} ms-1">${d3.STATS || '-'}</span></div>
                            <div class="text-muted" style="font-size: 0.9em;">${sanitize(d3.MAKTX) || '-'}</div>
                            <div class="text-muted" style="font-size: 0.9em;">Qty: ${psmng} | Outs: ${remainingQty}</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted mt-1"></i>
                    </div>
                </td>
            `;
            return row;
        }

        function handleT3RowClick(element) {
            if (window.innerWidth < 768) {
                showT3DetailModal(element);
            }
        }

        // [PERBAIKAN] Fungsi showT3DetailModal kini menggunakan modal dan tombolnya sendiri
        function showT3DetailModal(element) {
            const data = JSON.parse(element.dataset.rowData);
            // Gunakan instance modal T3 yang baru
            if (!data || !t3ModalInstance) return;

            // Dapatkan elemen dari modal T3 yang baru
            const modalLabel = document.getElementById('tdata3DetailModalLabel');
            const modalBody = document.getElementById('tdata3DetailModalBody');
            const modalFooter = document.getElementById('tdata3DetailModalFooter');
            
            if (!modalLabel || !modalBody || !modalFooter) {
                console.error("Elemen untuk modal TData3 tidak ditemukan di DOM.");
                return;
            }
            
            modalLabel.textContent = `PRO Detail: ${data.AUFNR || data.PLNUM}`;

            const psmng = parseFloat(data.PSMNG) || 0;
            const wemng = parseFloat(data.WEMNG) || 0;
            const remainingQty = psmng - wemng;
            
            // Isi body modal dengan detail seperti sebelumnya, namun juga menyertakan tombol aksi inline
            const actionButtonsHtml = data.AUFNR ? `
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3 pt-3 border-top">
                    <button type="button" title="Reschedule" class="btn btn-warning btn-sm" onclick="openSchedule('${encodeURIComponent(padAufnr(data.AUFNR))}', '${formatDate(data.SSAVD)}')"><i class="fas fa-clock-rotate-left me-1"></i> Reschedule</button>
                    <button type="button" title="Refresh PRO" class="btn btn-info btn-sm" onclick="openRefresh('${data.AUFNR}', '${data.WERKSX}')"><i class="fa-solid fa-arrows-rotate me-1"></i> Refresh</button>
                    <button type="button" title="Change PV" class="btn btn-warning btn-sm" onclick="openChangePvModal('${data.AUFNR}', '${data.VERID}', '${data.WERKSX}')"><i class="fa-solid fa-code-compare me-1"></i> Change PV</button>
                    <button type="button" title="Read PP" class="btn btn-info btn-sm" onclick="openReadPP('${encodeURIComponent(padAufnr(data.AUFNR))}')"><i class="fas fa-book-open me-1"></i> Read PP</button>
                    <button type="button" title="TECO" class="btn btn-danger btn-sm" onclick="openTeco('${encodeURIComponent(padAufnr(data.AUFNR))}')"><i class="fas fa-trash me-1"></i> TECO</button>
                </div>
            ` : '';

            modalBody.innerHTML = `
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">MRP</span><strong class="text-dark text-end">${data.DISPO || '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Material</span><strong class="text-dark text-end">${data.MATNR ? ltrim(data.MATNR, '0') : '-'}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Qty Order</span><strong class="text-dark text-end">${psmng}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Qty GR</span><strong class="text-dark text-end">${wemng}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Outs GR</span><strong class="text-dark text-end">${remainingQty}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Start Date</span><strong class="text-dark text-end">${formatDate(data.GSTRP)}</strong></div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-start"><span class="text-muted">Finish Date</span><strong class="text-dark text-end">${formatDate(data.GLTRP)}</strong></div>
                </div>
                ${actionButtonsHtml}
            `;
            
            // [PERBAIKAN] Buat tombol Comp dan Route di footer
            let footerHtml = '';
            if (data.AUFNR) {
                footerHtml = `
                    <div class="w-100 d-flex flex-column flex-sm-row gap-2">
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" onclick="showTData4ByAufnr('${data.AUFNR}'); t3ModalInstance.hide();">
                            <i class="fas fa-cogs me-1"></i> View Comp
                        </button>
                        <button type="button" class="btn btn-info btn-sm flex-grow-1 text-white" onclick="showTData1ByAufnr('${data.AUFNR}'); t3ModalInstance.hide();">
                            <i class="fas fa-route me-1"></i> View Route
                        </button>
                    </div>`;
            } else {
                footerHtml = `<button type="button" class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">Close</button>`;
            }
            modalFooter.innerHTML = footerHtml;

            // Tampilkan modal T3 yang baru
            t3ModalInstance.show();
        }
        // =================================================================
        // 3. SEMUA FUNGSI AKSI (MODALS, BULK, API)
        // =================================================================

        document.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('confirmScheduleBtn');
            const form = document.getElementById('scheduleForm');

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    // Ambil referensi tombol dan simpan teks aslinya
                    const button = this;
                    const originalButtonText = button.innerHTML;
                    const cancelButton = button.previousElementSibling; // Tombol "Batal"

                    // Tampilkan loading & sembunyikan tombol Batal
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
                    cancelButton.style.display = 'none';

                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    fetch("{{ route('reschedule.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json().then(resData => ({ status: response.status, body: resData })))
                    .then(({ status, body }) => {
                        if (status >= 400) { throw new Error(body.message); }
                        
                        // Tutup modal dan tampilkan notifikasi sukses
                        const modalElement = document.getElementById('scheduleModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) { modalInstance.hide(); }

                        showSwal(body.message, 'success');
                        // Lakukan reload tabel jika perlu
                        // if (typeof dataTable !== 'undefined') dataTable.ajax.reload();
                    })
                    .catch(error => {
                        showSwal(error.message, 'error');
                    })
                    .finally(() => {
                        // Kembalikan tombol ke keadaan semula
                        button.disabled = false;
                        button.innerHTML = originalButtonText;
                        cancelButton.style.display = 'inline-block';
                    });
                });
            }
        });
        
        function openSchedule(aufnrEnc, scheduleDate) {
            console.log(aufnrEnc, scheduleDate);
            const aufnr = decodeURIComponent(aufnrEnc);
            document.getElementById('scheduleAufnr').value = aufnr;
            document.getElementById('scheduleDate').value = scheduleDate;
            document.getElementById('scheduleTime').value = '00.00.00';
            scheduleModal.show();
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const changeWcModal = document.getElementById('changeWcModal');
            
            // 1. Mengisi data saat modal akan dibuka
            changeWcModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const aufnr = button.dataset.aufnr;
                const vornr = button.dataset.vornr;
                const pwwrk = button.dataset.pwwrk;
                const arbplAsal = button.dataset.arbpl; // Work Center Asal

                // Isi field di dalam modal
                document.getElementById('changeWcAufnr').value = aufnr;
                document.getElementById('changeWcVornr').value = vornr;
                document.getElementById('changeWcPwwrk').value = pwwrk;
                document.getElementById('changeWcAsal').value = arbplAsal;
                document.getElementById('changeWcTujuan').value = ''; // Reset dropdown
            });

            // 2. Menangani klik tombol "Save" via AJAX
            const confirmBtn = document.getElementById('confirmChangeWcBtn');
            confirmBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.innerHTML;
                const form = document.getElementById('changeWcForm');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

                // console.log("Data yang akan dikirim ke Flask:", data); 

                fetch("{{ route('change-wc-pro') }}", { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json().then(resData => ({ status: response.status, body: resData })))
                .then(({ status, body }) => {
                    if (status >= 400) { throw new Error(body.message); }
                    
                    // 1. Tutup modal terlebih dahulu
                    const modalElement = document.getElementById('changeWcModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    // 2. Tampilkan notifikasi sukses
                    showSwal(body.message, 'success');

                    // 3. Tunggu 1.5 detik, LALU reload halaman
                    setTimeout(() => {
                        location.reload();
                    }, 1500); // 1500 milidetik = 1.5 detik
                })
                .catch(error => {
                    showSwal(error.message, 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });

        function openChangePvModal(aufnr, currentPV = '', plantVal = null) {
            const defaultPlant = @json($plant);

            // isi hidden form
            document.getElementById('changePvAufnr').value = (aufnr || '').trim();
            document.getElementById('changePvWerks').value = (plantVal || defaultPlant || '').trim();

            // set select (opsional)
            const sel = document.getElementById('changePvInput');
            sel.value = (currentPV || '').trim();               // kalau '0001' dsb
            document.getElementById('changePvCurrent').textContent =
            currentPV ? `Current PV: ${currentPV}` : '';

            // tampilkan modal
            (bootstrap.Modal.getOrCreateInstance(document.getElementById('changePvModal'))).show();
        }

        function handleBulkSelect(checkbox) {
            const row = checkbox.closest('tr');
            if (!row || !row.dataset.rowData) return;

            const rowData = JSON.parse(row.dataset.rowData);
            const id = checkbox.dataset.id;

            // [BARU] Ekstrak plant code dari rowData. Pastikan propertinya benar ('WERKSX').
            const plantCode = rowData.WERKSX || null;

            // [PENCEGAHAN] Hentikan jika ID atau Plant Code tidak ada di data.
            if (!id || !plantCode) {
                console.error("Gagal mendapatkan ID atau Plant Code dari data baris.", rowData);
                toast('error', 'Data Tidak Lengkap', 'Tidak dapat memproses item tanpa ID atau Plant Code.');
                checkbox.checked = false; // Batalkan centang
                return;
            }

            if (checkbox.checked) {
                // [LOGIKA UTAMA] Jika ini adalah item PERTAMA yang dipilih
                if (selectedPRO.size === 0) {
                    // Tetapkan plant code untuk sesi bulk transaction ini.
                    bulkActionPlantCode = plantCode;
                    console.log(`Plant Code untuk sesi bulk diatur ke: ${bulkActionPlantCode}`);
                }
                // [VALIDASI] Jika item berikutnya dipilih, cek apakah plant code-nya sama.
                else if (plantCode !== bulkActionPlantCode) {
                    // Jika berbeda, tolak pemilihan.
                    toast('error', 'Gagal Memilih', 'Hanya bisa memilih PRO dari Plant yang sama dalam satu transaksi bulk.');
                    checkbox.checked = false; // Batalkan centang yang baru saja dilakukan pengguna
                    return; // Hentikan eksekusi fungsi
                }

                // Jika lolos validasi (atau ini item pertama), tambahkan ke daftar.
                selectedPRO.add(id);
                mappedPRO.set(id, rowData);

            } else { // Jika checkbox sedang di-uncheck
                selectedPRO.delete(id);
                mappedPRO.delete(id);

                // [RESET] Jika tidak ada lagi item yang terpilih, reset plant code sesi bulk.
                if (selectedPRO.size === 0) {
                    bulkActionPlantCode = null;
                    console.log('Plant Code sesi bulk telah di-reset.');
                }
            }

            // Panggil updateBulkControls untuk memperbarui tampilan tombol dan counter
            updateBulkControls();
        }
        function toggleSelectAll() { 
            const selectAll = document.getElementById('select-all').checked;
            document.querySelectorAll('.bulk-select').forEach(cb => {
                cb.checked = selectAll;
                handleBulkSelect(cb);
            });
        }
        function clearAllSelections() {
            selectedPLO.clear();
            selectedPRO.clear();
            document.querySelectorAll('.bulk-select, #select-all').forEach(cb => cb.checked = false);
            
            bulkActionPlantCode = null; // <-- TAMBAHKAN BARIS INI
            
            updateBulkControls();
        }
        function handleComponentSelect(aufnr){
            const checkboxes = document.querySelectorAll(`.component-select-${aufnr}`);
            const selected = [...checkboxes].filter(cb => cb.checked);
            const controls = document.getElementById(`bulk-delete-controls-${aufnr}`);
            const btn = document.getElementById(`bulk-delete-btn-${aufnr}`);
            if (selected.length > 0){
                controls.classList.remove('d-none');
                btn.textContent = `Delete Selected (${selected.length})`;
            } else {
                controls.classList.add('d-none');
            }
        }
        function toggleSelectAllComponents(aufnr){
            const master = document.getElementById(`select-all-components-${aufnr}`);
            const checkboxes = document.querySelectorAll(`.component-select-${aufnr}`);
            checkboxes.forEach(cb => cb.checked = master.checked);
            handleComponentSelect(aufnr);
        }
        function clearComponentSelections(aufnr){
            const master = document.getElementById(`select-all-components-${aufnr}`);
            if (master) master.checked = false;
            document.querySelectorAll(`.component-select-${aufnr}`).forEach(cb => cb.checked = false);
            handleComponentSelect(aufnr);
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            // Pastikan tombol "Simpan" di modal Change PV Anda memiliki id="changePvSubmitBtn"
            const confirmChangePvBtn = document.getElementById('changePvSubmitBtn');

            if (confirmChangePvBtn) {
                confirmChangePvBtn.addEventListener('click', function() {
                    const button = this;
                    const originalText = button.innerHTML;
                    
                    // Pastikan form di modal Anda memiliki id="changePvForm"
                    const form = document.getElementById('changePvForm');
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    // 1. Validasi Sederhana
                    if (!data.AUFNR) {
                        return showSwal('AUFNR tidak ditemukan di form.', 'error');
                    }
                    if (!data.PROD_VERSION) {
                        return showSwal('Isi Production Version (PV) dahulu.', 'error');
                    }
                    if (!data.plant) {
                        return showSwal('Plant (WERKS) tidak ditemukan di form.', 'error');
                    }

                    // Tampilkan status loading
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                    
                    // Kirim request ke endpoint Laravel
                    fetch("{{ route('change-pv') }}", { 
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        // Menggunakan pola yang sama untuk menangani respons
                        return response.json().then(resData => ({ status: response.status, body: resData }));
                    })
                    .then(({ status, body }) => {
                        if (status >= 400) { 
                            // Jika ada error dari server, lempar pesan errornya
                            throw new Error(body.error || body.message || 'Terjadi kesalahan di server.'); 
                        }
                        
                        // Logika sukses dibuat sama persis dengan change WC
                        const modalElement = document.getElementById('changePvModal'); // Pastikan ID modal Anda benar
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        showSwal(body.message, 'success');

                        // Tunggu sejenak, lalu reload halaman
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    })
                    .catch(error => {
                        // Menampilkan error menggunakan SweetAlert
                        showSwal(error.message, 'error');
                    })
                    .finally(() => {
                        // Mengembalikan tombol ke keadaan semula
                        button.disabled = false;
                        button.innerHTML = originalText;
                    });
                });
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
        const addComponentModal = document.getElementById('addComponentModal');
        
        // 1. Logika untuk mengisi data ke modal saat akan dibuka
        if (addComponentModal) {
            addComponentModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const aufnr = button.dataset.aufnr;
                const vornr = button.dataset.vornr;
                const pwwrk = button.dataset.pwwrk;
                
                // Isi input di dalam modal
                addComponentModal.querySelector('#addComponentAufnr').value = aufnr;
                addComponentModal.querySelector('#addComponentVornr').value = vornr;
                addComponentModal.querySelector('#addComponentPlant').value = pwwrk;
                addComponentModal.querySelector('#displayAufnr').value = aufnr; // Mengisi input display juga
            });
        }

        // 2. Logika untuk menangani klik tombol "Simpan" via AJAX
        const confirmBtn = document.getElementById('confirmAddComponentBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.innerHTML;
                const form = document.getElementById('addComponentForm');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                // Validasi sederhana di sisi klien
                if (!data.iv_matnr || !data.iv_bdmng || !data.iv_meins || !data.iv_lgort || !data.iv_bdmng) {
                    return showSwal('Form belum diisi dengan lengkap', 'error');
                }

                // Tampilkan status loading
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

                // Kirim request ke endpoint Laravel
                fetch("{{ route('component.add') }}", { // Pastikan route ini ada
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json().then(resData => ({ status: response.status, body: resData })))
                .then(({ status, body }) => {
                    if (status >= 400) {
                        throw new Error(body.message || 'Gagal menambahkan komponen.');
                    }
                    
                    const modalInstance = bootstrap.Modal.getInstance(addComponentModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    
                    showSwal(body.message, 'success');

                    // Tunggu notifikasi, lalu reload halaman/tabel
                    setTimeout(() => {
                        // Ganti dengan dataTable.ajax.reload() jika Anda ingin refresh tabel saja
                        location.reload(); 
                    }, 1500);
                })
                .catch(error => {
                    showSwal(error.message, 'error');
                })
                .finally(() => {
                    // Kembalikan tombol ke keadaan semula
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }
    });

    function updateComponentActionButtons(aufnr) {
        // 1. Dapatkan semua elemen yang dibutuhkan
        const checkedBoxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
        const controlsDiv = document.getElementById(`bulk-delete-controls-${aufnr}`);
        const deleteBtn = document.getElementById(`bulk-delete-btn-${aufnr}`);
        const addBtn = controlsDiv.nextElementSibling; // Tombol Add Component

        if (!controlsDiv || !deleteBtn || !addBtn) return;

        // 2. Cek jumlah checkbox yang tercentang
        if (checkedBoxes.length > 0) {
            controlsDiv.style.display = 'flex'; // Tampilkan tombol delete/clear
            deleteBtn.innerHTML = `<i class="fas fa-trash-alt me-1"></i> Delete Selected (${checkedBoxes.length})`;
            addBtn.classList.remove('ms-auto'); // Hapus margin auto agar tombol add tidak aneh posisinya
        } else {
            controlsDiv.style.display = 'none'; // Sembunyikan tombol delete/clear
            addBtn.classList.add('ms-auto'); // Kembalikan margin auto agar tombol add tetap di kanan
        }
    }

    async function bulkDeleteComponents(aufnr, kode) {
        const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);

        const payload = {
            aufnr: aufnr,
            components: Array.from(selectedCheckboxes).map(cb => {
                return { rspos: cb.dataset.rspos };
            }),
            plant: kode
        };

        if (payload.components.length === 0) {
            Swal.fire('Tidak Ada yang Dipilih', 'Silakan pilih komponen yang ingin dihapus terlebih dahulu.', 'info');
            return;
        }

        // Membuat daftar RSPOS di dalam sebuah kotak yang bisa di-scroll
        const rsposListHtml =
            `<div style="max-height: 150px; overflow-y: auto; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 10px;">
                <ul style="text-align: left; margin: 0; padding: 0; list-style-position: inside;">` +
                payload.components.map(comp => `<li>RSPOS: <strong>${comp.rspos}</strong></li>`).join('') +
                `</ul>
            </div>`;

        const result = await Swal.fire({
            title: 'Konfirmasi Hapus',
            icon: 'warning',
            html:
                `Anda yakin ingin menghapus <strong>${payload.components.length} komponen</strong> berikut?` +
                rsposListHtml,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menghapus...',
                text: 'Harap tunggu, sedang memproses permintaan.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('/component/delete-bulk', { // Pastikan URL ini benar
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const responseData = await response.json();

                if (response.ok) {
                    await Swal.fire({
                        title: 'Berhasil, Refresh PRO Agar Data yang tampil Update',
                        text: responseData.message,
                        icon: 'success'
                    });
                    location.reload();
                } else {
                    let errorText = responseData.message;
                    if (responseData.errors && responseData.errors.length > 0) {
                        errorText += '<br><br><strong>Detail:</strong><br>' + responseData.errors.join('<br>');
                    }
                    Swal.fire({
                        title: 'Gagal!',
                        html: errorText,
                        icon: 'error'
                    });
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                Swal.fire('Error Jaringan', 'Tidak dapat terhubung ke server. Silakan coba lagi.', 'error');
            }
        }
    }

    function openTeco(aufnr) {
        Swal.fire({
            title: 'Konfirmasi TECO',
            text: `Anda yakin ingin melakukan TECO untuk order ${aufnr}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tampilkan loading spinner
                Swal.fire({
                    title: 'Memproses TECO...',
                    text: 'Mohon tunggu, sedang menghubungi SAP.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Kirim request ke Controller Laravel
                fetch("{{ route('order.teco') }}", { // Gunakan route name agar lebih aman
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ aufnr: aufnr })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                        }).then(() => { // <-- TAMBAHKAN .then() DI SINI
                            // Periksa apakah backend mengirim sinyal 'refresh'
                            if (data.action === 'refresh') {
                                location.reload(); // Muat ulang halaman
                            }
                        });;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: data.message,
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Terjadi kesalahan saat mengirim permintaan!',
                    });
                });
            }
        });
    }

    function openReadPP(aufnr) {
        Swal.fire({
            title: 'Konfirmasi Read PP',
            text: `Anda yakin ingin melakukan Read PP (Re-explode BOM) untuk order ${aufnr}? Proses ini akan memperbarui komponen di production order.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tampilkan loading spinner
                Swal.fire({
                    title: 'Memproses Read PP...',
                    text: 'Mohon tunggu, sedang menghubungi SAP.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Kirim request ke Controller Laravel
                fetch("{{ route('order.readpp') }}", { // Menggunakan route name baru
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ aufnr: aufnr })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                        });
                        // Opsional: Muat ulang data tabel jika perlu untuk melihat perubahan
                        // location.reload(); 
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            // Menampilkan pesan error yang lebih detail dari SAP jika ada
                            html: data.message + (data.errors ? `<br><br><strong>Detail:</strong><br><pre style="text-align:center; font-size: 0.8em;">${data.errors.join('<br>')}</pre>` : ''),
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Terjadi kesalahan saat mengirim permintaan!',
                    });
                });
            }
        });
        }

        document.addEventListener('DOMContentLoaded', function () {

        // 1. Dapatkan elemen yang dibutuhkan
        const searchInput = document.getElementById('proSearchInput');
        const tableBody = document.getElementById('tdata3-body');
        const noResultsRow = document.getElementById('tdata3-no-results');

        // Pastikan elemennya ada
        if (searchInput && tableBody && noResultsRow) {
            
            // Gunakan event 'input' agar lebih responsif daripada 'keyup'
            searchInput.addEventListener('input', function() {
                
                const filterText = searchInput.value.toUpperCase().trim();
                const tableRows = tableBody.getElementsByTagName('tr');
                let visibleRowsCount = 0;

                // Loop melalui setiap baris (kecuali baris "no-results")
                for (let i = 0; i < tableRows.length; i++) {
                    const row = tableRows[i];
                    
                    // Lewati baris "no-results" dalam loop
                    if (row.id === 'tdata3-no-results') continue;

                    const cells = row.getElementsByTagName('td');
                    let rowText = '';

                    // ✨ PERUBAHAN UTAMA: Gabungkan teks dari semua sel (td) dalam satu baris
                    for (let j = 0; j < cells.length; j++) {
                        rowText += (cells[j].textContent || cells[j].innerText) + ' ';
                    }
                    
                    // Cek apakah gabungan teks baris mengandung teks pencarian
                    if (rowText.toUpperCase().indexOf(filterText) > -1) {
                        row.style.display = ""; // Tampilkan baris
                        visibleRowsCount++;
                    } else {
                        row.style.display = "none"; // Sembunyikan baris
                    }
                }

                // ✨ BARU: Tampilkan atau sembunyikan pesan "tidak ada hasil"
                if (visibleRowsCount === 0) {
                    noResultsRow.style.display = ''; // Tampilkan pesan jika tidak ada baris yang terlihat
                } else {
                    noResultsRow.style.display = 'none'; // Sembunyikan pesan jika ada hasil
                }
            });
        }
        });

        function handleEditClick(buttonElement) {
            // 'buttonElement' adalah tombol yang baru saja Anda klik

            // Ambil data dari atribut data-* menggunakan 'dataset'
            const aufnr = buttonElement.dataset.aufnr;
            const rspos = buttonElement.dataset.rspos;
            const matnr = buttonElement.dataset.matnr;
            const bdmng = buttonElement.dataset.bdmng;
            const lgort = buttonElement.dataset.lgort;
            const sobkz = buttonElement.dataset.sobkz;
            const plant = buttonElement.dataset.plant;

            // Sekarang Anda bisa menggunakan semua variabel ini
            console.log('✅ Tombol Edit Diklik via onclick!');
            console.log('AUFNR:', aufnr);
            console.log('RSPOS:', rspos);
            console.log('MATNR:', matnr);
            console.log('PLANT:', plant);

            // ✨ CONTOH: Langsung panggil logika untuk menampilkan modal di sini
            // (Ini adalah kode dari pembahasan modal kita sebelumnya)
            
            // Isi nilai-nilai ke dalam field form di modal
            document.getElementById('formPro').value = aufnr;
            document.getElementById('formRspos').value = rspos;
            document.getElementById('formMatnr').value = matnr;
            document.getElementById('formBdmng').value = bdmng;
            document.getElementById('formLgort').value = lgort;
            document.getElementById('formSobkz').value = sobkz;
            document.getElementById('formPlant').value = plant;

            // Tampilkan modal
            const dataModal = new bootstrap.Modal(document.getElementById('dataEntryModal'));
            dataModal.show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // 1. Ambil elemen input pencarian dan elemen lainnya yang dibutuhkan
            const searchInput = document.getElementById('searchInput');
            const tableBody = document.getElementById('salesOrderTableBody');
            const tableRows = tableBody.querySelectorAll('tr:not(#noResultsRow)'); // Ambil semua baris data
            const noResultsRow = document.getElementById('noResultsRow');

            // Pastikan semua elemen ada sebelum menjalankan script
            if (searchInput && tableBody && noResultsRow) {
                
                // 2. Tambahkan event listener 'input' pada kotak pencarian
                searchInput.addEventListener('input', function() {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    let visibleRowsCount = 0;

                    // 3. Loop melalui setiap baris data di tabel
                    tableRows.forEach(row => {
                        const searchableText = row.dataset.searchableText;

                        // 4. Cek apakah teks di baris mengandung kata kunci pencarian
                        if (searchableText && searchableText.includes(searchTerm)) {
                            row.style.display = ''; // Tampilkan baris jika cocok
                            visibleRowsCount++;
                        } else {
                            row.style.display = 'none'; // Sembunyikan baris jika tidak cocok
                        }
                    });

                    // 5. Tampilkan atau sembunyikan pesan "tidak ada hasil"
                    if (visibleRowsCount === 0 && tableRows.length > 0) {
                        noResultsRow.style.display = ''; // Tampilkan pesan
                    } else {
                        noResultsRow.style.display = 'none'; // Sembunyikan pesan
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const visibleDateInput = document.getElementById('visibleDate');
            const scheduleDateInput = document.getElementById('scheduleDate');
            const dateError = document.getElementById('dateError');

            flatpickr("#visibleDate", {
                // Opsi ini akan membuat input tersembunyi dan mengisinya dengan format backend
                "altInput": true,
                "altFormat": "d/m/Y", // Format yang dilihat pengguna (dd/mm/yyyy)
                "dateFormat": "Y-m-d", // Format yang dikirim ke backend (YYYY-MM-DD)
                
                // Langsung nonaktifkan tanggal lampau di kalender
                "minDate": "today",

                // Fungsi yang berjalan setiap kali tanggal berubah
                "onChange": function(selectedDates, dateStr, instance) {
                    // Cek manual jika pengguna mengetik tanggal lampau
                    const today = new Date();
                    today.setHours(0, 0, 0, 0); // Atur jam ke awal hari untuk perbandingan akurat

                    if (selectedDates[0] < today) {
                        // Jika tanggal lampau, tambahkan border merah dan tampilkan pesan
                        visibleDateInput.classList.add('is-invalid');
                        dateError.style.display = 'block';
                    } else {
                        // Jika valid, hapus border merah dan sembunyikan pesan
                        visibleDateInput.classList.remove('is-invalid');
                        dateError.style.display = 'none';
                    }
                }
            });
        });

        let bulkDatePicker;

        document.addEventListener('DOMContentLoaded', function() {
            const visibleDateInput = document.getElementById('bulkVisibleDate');
            const hiddenDateInput = document.getElementById('bulkScheduleDate');
            const errorDiv = document.getElementById('bulkDateError');
            const submitButton = document.getElementById('confirmBulkScheduleBtn');

            if (visibleDateInput && hiddenDateInput && errorDiv && submitButton) {
                const bulkElement = document.getElementById('bulkVisibleDate');
                if (bulkElement) {
                    // Isi variabel global yang sudah kita deklarasikan di atas
                    bulkDatePicker = flatpickr(bulkElement, {
                        "altInput": true,
                        "altFormat": "d/m/Y",
                        "dateFormat": "Y-m-d",
                        "minDate": "today",
                        "onChange": function(selectedDates, dateStr, instance) {
                            const today = new Date();
                            today.setHours(0, 0, 0, 0);

                            if (selectedDates.length === 0 || selectedDates[0] < today) {
                                // Jika tanggal KOSONG atau LAMPAU
                                visibleDateInput.classList.add('is-invalid');
                                errorDiv.style.display = 'block';
                                submitButton.disabled = true;
                            } else {
                                // Jika tanggal VALID
                                visibleDateInput.classList.remove('is-invalid');
                                errorDiv.style.display = 'none';
                                submitButton.disabled = false;
                            }
                        }
                    });
                } else {
                    console.warn("Elemen #bulkVisibleDate tidak ditemukan saat halaman dimuat.");
                }
            }
        });

        (function() {
            const modalEl = document.getElementById('dataEntryModal');
            if (!modalEl) return;

            const form = modalEl.querySelector('#entryForm');
            const saveButton = modalEl.querySelector('#saveButton');
            const defaultText = saveButton.querySelector('.default-text');
            const loadingText = saveButton.querySelector('.loading-text');
            const sobkzSelect = modalEl.querySelector('#formSobkz');
            const sobkzToggle = modalEl.querySelector('#sobkzToggle');

            let initialSobkzValue = '';

            sobkzToggle.addEventListener('change', function() {
                sobkzSelect.value = this.checked ? '1' : '0';
            });

            modalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                form.querySelector('#formPro').value = button.getAttribute('data-aufnr') || '';
                form.querySelector('#formRspos').value = button.getAttribute('data-rspos') || '';
                form.querySelector('#formPlant').value = button.getAttribute('data-plant') || '';
                
                form.querySelector('#formMatnr').value = '';
                form.querySelector('#formBdmng').value = '';
                form.querySelector('#formLgort').value = '';
                
                initialSobkzValue = button.getAttribute('data-sobkz') === '1' ? '1' : '0';
                sobkzSelect.value = ""; 
                sobkzToggle.checked = initialSobkzValue === '1';
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                
                saveButton.disabled = true;
                defaultText.classList.add('d-none');
                loadingText.classList.remove('d-none');

                const formData = new FormData(form);
                const payload = {};
                
                payload.aufnr = formData.get('aufnr');
                payload.rspos = formData.get('rspos');
                payload.plant = formData.get('plant');

                if (formData.get('matnr')) payload.matnr = formData.get('matnr');
                if (formData.get('bdmng')) payload.bdmng = formData.get('bdmng');
                if (formData.get('lgort')) payload.lgort = formData.get('lgort');

                if (sobkzSelect.value !== '') {
                    payload.sobkz = sobkzSelect.value;
                }

                try {
                    const response = await fetch("{{ route('components.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Terjadi kesalahan.');

                    // --- [DIUBAH] Mengganti alert dengan SweetAlert untuk notifikasi sukses ---
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message,
                        confirmButtonText: 'OK'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            location.reload(); // Muat ulang halaman setelah user menekan OK
                        }
                    });
                    
                } catch (error) {
                    console.error('Submit error:', error);
                    
                    // --- [DIUBAH] Mengganti alert dengan SweetAlert untuk notifikasi gagal ---
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Gagal menyimpan data: ' + error.message,
                        confirmButtonText: 'Tutup'
                    });

                } finally {
                    saveButton.disabled = false;
                    defaultText.classList.remove('d-none');
                    loadingText.classList.add('d-none');
                    
                    // Modal ditutup hanya jika sukses, agar user bisa lihat error jika gagal
                    if (event.submitter.dataset.success) {
                        bootstrap.Modal.getInstance(modalEl).hide();
                    }
                }
            });
        })();

        async function bulkDeleteComponents(aufnr, kode) {
            const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);

            const payload = {
                aufnr: aufnr,
                components: Array.from(selectedCheckboxes).map(cb => {
                    return { rspos: cb.dataset.rspos };
                }),
                plant: kode
            };

            if (payload.components.length === 0) {
                Swal.fire('Tidak Ada yang Dipilih', 'Silakan pilih komponen yang ingin dihapus terlebih dahulu.', 'info');
                return;
            }

            // Membuat daftar RSPOS di dalam sebuah kotak yang bisa di-scroll
            const rsposListHtml =
                `<div style="max-height: 150px; overflow-y: auto; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <ul style="text-align: left; margin: 0; padding: 0; list-style-position: inside;">` +
                    payload.components.map(comp => `<li>RSPOS: <strong>${comp.rspos}</strong></li>`).join('') +
                    `</ul>
                </div>`;

            const result = await Swal.fire({
                title: 'Konfirmasi Hapus',
                icon: 'warning',
                html:
                    `Anda yakin ingin menghapus <strong>${payload.components.length} komponen</strong> berikut?` +
                    rsposListHtml,
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Harap tunggu, sedang memproses permintaan.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                try {
                    const response = await fetch('/component/delete-bulk', { // Pastikan URL ini benar
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const responseData = await response.json();

                    if (response.ok) {
                        await Swal.fire({
                            title: 'Berhasil, Refresh PRO Agar Data yang tampil Update',
                            text: responseData.message,
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        let errorText = responseData.message;
                        if (responseData.errors && responseData.errors.length > 0) {
                            errorText += '<br><br><strong>Detail:</strong><br>' + responseData.errors.join('<br>');
                        }
                        Swal.fire({
                            title: 'Gagal!',
                            html: errorText,
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    Swal.fire('Error Jaringan', 'Tidak dapat terhubung ke server. Silakan coba lagi.', 'error');
                }
            }
        }

    </script>
@endpush
</x-layouts.app>