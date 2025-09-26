
<x-layouts.app>
    <div class="container-fluid">
        {{-- Header Halaman --}}
        <x-notification.notification />
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h1 class="h5 fw-semibold text-dark mb-1">Kode Plant: {{ $WERKS }}</h1>
                        <p class="small text-muted mb-0">
                            <span class="fw-medium text-body-secondary">Nama Bagian : </span> {{ $bagian }} |
                            <span class="fw-medium text-body-secondary">Kategori : </span> {{ $categories }} | 
                            <span class="fw-medium text-body-secondary">Kode Laravel : </span> {{ $plant }}
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <a href="{{ route('manufaktur.detail.data2', $plant) }}" class="btn btn-primary btn-sm nav-loader-link">
                            <i class="fas fa-sync-alt me-1"></i> Sync
                        </a>
                        <button onclick="deleteAllStorage()" class="btn btn-warning btn-sm">
                            Hide All
                        </button>
                        <a href="{{ route('manufaktur.dashboard.show', $plant) }}" class="btn btn-secondary btn-sm nav-loader-link">
                            &larr; Back To Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kontainer Utama --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                {{-- Container untuk Tabel Utama dan Paginasi --}}
                <div id="outstanding-order-container" class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-3">
                            <h3 class="h5 fw-semibold text-dark mb-0">Sales Order Table</h3>
                            <form method="GET" class="w-100" style="max-width: 320px;">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Mau Cari Buyer Siapa ?"
                                        class="form-control border-start-0" id="searchInput">
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive scrollable">
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
                                        
                                        {{-- ✨ PERUBAHAN: Tambahkan atribut data-searchable-text --}}
                                        <tr class="cursor-pointer" 
                                            data-key="{{ $key }}" 
                                            onclick="openSalesItem(this)" 
                                            data-searchable-text="{{ strtolower($item->NAME1 ?? '') }}">
                                            
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle rounded-circle me-3" style="width: 32px; height: 32px;">
                                                        <span class="fw-bold text-primary">{{ substr($item->NAME1 ?? 'N/A', 0, 1) }}</span>
                                                    </div>
                                                    <span class="fw-semibold text-dark">{{ $item->NAME1 ?? '-' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center text-muted">
                                                <i class="fas fa-chevron-right"></i>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center p-5 text-muted">Tidak ada data ditemukan.</td>
                                        </tr>
                                    @endforelse

                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="3" class="text-center p-5 text-muted">
                                            <i class="fas fa-search fs-4 d-block mb-2"></i>
                                            Tidak ada data yang cocok dengan pencarian Anda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="tdata2-section" class="mt-4 d-none"></div>

                <div id="tdata3-container" class="mt-4 d-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
                        <h3 class="h5 fw-semibold text-dark">Order Overview</h3>
                        <div class="btn-group btn-group-sm" role="group" id="status-filter">
                            <button type="button" class="btn btn-outline-secondary active" onclick="filterByStatus(this, 'all')">All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'crtd')">PRO (CRTD)</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'released')">PRO (Released)</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'outgoing')">PRO (Outgoing)</button>
                        </div>
                    </div>
                    
                    <div id="bulk-actions-wrapper" data-refresh-url="{{ route('bulk-refresh.store') }}" class="d-flex align-items-center gap-3 mt-4 mb-3 d-none">
                        <div id="selection-counter">
                            <span>PRO Terpilih:</span>
                            <span class="count-badge" id="selection-count-badge">0</span>
                        </div>
                        <div id="bulk-controls" class="d-flex align-items-center gap-2">
                            <button class="btn btn-warning btn-sm" id="bulk-schedule-btn" style="display: none;" onclick="openBulkScheduleModal()">
                                <i class="fas fa-calendar-alt me-1"></i> Bulk Schedule
                            </button>
                            <button class="btn btn-readpp btn-sm" id="bulk-readpp-btn" style="display: none;" onclick="openBulkReadPpModal()">
                                <i class="fas fa-book-open me-1"></i> Buklk Read PP
                            </button>
                            <button class="btn btn-teco btn-sm" id="bulk-teco-btn" style="display: none;" onclick="openBulkTecoModal()">
                                <i class="fas fa-circle-check me-1"></i> Bulk TECO
                            </button>
                            <button class="btn btn-primary btn-sm" id="bulk-refresh-btn" style="display: none;" onclick="openBulkRefreshModal()">
                                <i class="fas fa-sync-alt me-1"></i> Bulk Refresh
                            </button>
                            <button class="btn btn-warning btn-sm" id="bulk-changePv-btn" style="display: none;" onclick="openBulkChangePvModal()">
                                <i class="fa-solid fa-code-compare"></i> Bulk Change PV
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="clearAllSelections()">Clear All</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="search" id="proSearchInput" class="form-control" placeholder="Silahkan cari data menggunakan PRO, MRP, atau Start Date nya...">
                    </div>
                    
                    <div class="table-responsive border rounded-3">
                        <table id="tdata3-table" class="table table-hover table-bordered align-middle mb-0 small">
                            <thead class="table-primary">
                                <tr class="text-uppercase" style="font-size: 0.75rem;">
                                    <th class="text-center"><input type="checkbox" class="form-check-input" id="select-all" onchange="toggleSelectAll()"></th>
                                    <th class="text-center">No.</th>
                                    <th class="text-center">PRO</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                    <th class="text-center">MRP</th>
                                    <th class="text-center">Material</th>
                                    <th class="text-center">Description</th>
                                    <th class="text-center">Qty Order</th>
                                    <th class="text-center">Qty GR</th>
                                    <th class="text-center">Outs GR</th>
                                    <th class="text-center">Start Date</th>
                                    <th class="text-center">Finish Date</th>
                                </tr>
                            </thead>
                            <tbody id="tdata3-body">
                                
                                <tr id="tdata3-no-results" style="display: none;">
                                    <td colspan="13" class="text-center p-5 text-muted"> 
                                        <i class="fas fa-search fs-4 d-block mb-2"></i>
                                        Tidak ada data yang cocok dengan pencarian Anda.
                                    </td>
                                </tr>
                                </tbody>
                        </table>
                    </div>
                    <div id="tdata3-pagination" class="mt-3 d-flex justify-content-between align-items-center d-none"></div>
                </div>

                <div id="additional-data-container" class="mt-4"></div>
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
        
        function loadPersistedState() {

            const navEntries = performance.getEntriesByType("navigation");
            if (navEntries.length > 0 && navEntries[0].type !== 'reload') {
                sessionStorage.clear();
                console.log('Navigasi baru terdeteksi. Session storage dibersihkan.');
                return; 
            } else {
                console.log('Refresh halaman terdeteksi. State dipertahankan.');
            }
            // === 1. Ambil semua key dari sessionStorage ===
            const activeSOKey = sessionStorage.getItem('activeSalesOrderKey');
            const activeT2Key = sessionStorage.getItem('activeTdata2Key');
            const activeT3Aufnr = sessionStorage.getItem('activeT3Aufnr');
            const activeT3Type = sessionStorage.getItem('activeT3Type');

            if (!activeSOKey) return;

            // === 2. Muat state level 1 (T1 -> T2) ===
            console.log(`[DEBUG] Mencari T1 dengan key: ${activeSOKey}`);
            const activeRowT1 = document.querySelector(`tr[data-key="${activeSOKey}"]`);

            if (activeRowT1) {
                openSalesItem(activeRowT1);
            } else {
                console.error(`[DEBUG] GAGAL: Baris T1 untuk key ${activeSOKey} tidak ditemukan.`);
                sessionStorage.clear();
                return;
            }

            if (!activeT2Key) return;

            // === 3. Muat state level 2 (T2 -> T3) ===
            setTimeout(() => {
                console.log(`[DEBUG] Mencari T2 dengan key: ${activeT2Key}`);
                const activeRowT2 = document.querySelector(`#tdata2-body tr[data-key="${activeT2Key}"]`);
                
                // ----> TAMBAHKAN LOG INI <----
                if (activeRowT2) {
                    console.log('[DEBUG] SUKSES: Baris T2 ditemukan!', activeRowT2);
                    handleClickTData2Row(activeT2Key, activeRowT2);
                } else {
                    console.error(`[DEBUG] GAGAL: Baris T2 untuk key ${activeT2Key} tidak ditemukan di dalam #tdata2-body.`);
                    return;
                }

                if (!activeT3Aufnr || !activeT3Type) return;

                // === 4. Muat state level 3 (T3 -> T4) ===
                setTimeout(() => {
                    const buttonSelector = `#tdata3-body tr[data-aufnr="${activeT3Aufnr}"] .${activeT3Type}-button`;
                    console.log(`[DEBUG] Mencari Tombol T3 dengan selector: ${buttonSelector}`);
                    const activeButtonT3 = document.querySelector(buttonSelector);

                    // ----> TAMBAHKAN LOG INI <----
                    if (activeButtonT3) {
                        console.log('[DEBUG] SUKSES: Tombol T3 ditemukan!', activeButtonT3);
                        activeButtonT3.click();
                    } else {
                        console.error(`[DEBUG] GAGAL: Tombol T3 tidak ditemukan.`);
                    }
                }, 300); // Naikkan sedikit jeda untuk amannya

            }, 300); // Naikkan sedikit jeda untuk amannya
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
                cardWrapper.innerHTML = `<div class="card-body text-center p-5 text-muted">Tidak ada data Outstanding Order untuk item ini.</div>`;
            } else {
                let scrollStyle = '';
                if (rows.length > 8) {
                    scrollStyle = 'style="max-height: 400px; overflow-y: auto;"'; // Sedikit lebih tinggi
                }

                let tableHtml = `
                    <style>
                        .sticky-header th {
                            position: -webkit-sticky;
                            position: sticky;
                            top: 0;
                            z-index: 1;
                            background-color: #f8f9fa; /* Warna latar yang lebih solid */
                            box-shadow: inset 0 -2px 0 #dee2e6;
                        }
                    </style>
                    <div class="card-body">
                        <div id="tdata2-header" class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-3">
                            <h3 id="tdata2-title" class="h5 fw-semibold text-dark mb-0">Outstanding Order</h3>
                            <div id="tdata2-search-wrapper" class="input-group" style="max-width: 320px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="tdata2-search" oninput="filterTData2Table()" placeholder="Cari SO, Material..." class="form-control border-start-0">
                            </div>
                        </div>

                        <div class="table-responsive" ${scrollStyle}>
                            <table class="table table-hover align-middle mb-0">
                                <thead class="sticky-header">
                                    <tr class="small">
                                        <th class="text-center">No.</th>
                                        <th>Order</th><th>Item</th><th>Material FG</th>
                                        <th>Description</th><th>PO Date</th><th>Total PLO</th>
                                        <th>PRO (CRTD)</th><th>PRO (Released)</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tdata2-body">`;
                
                rows.forEach((r, i) => {
                    const soKey = `${r.KDAUF || ''}-${r.KDPOS || ''}`;
                    const t3 = allTData3[soKey] || []; // Penyesuaian logika pengambilan data
                    let ploCount = 0, proCrt = 0, proRel = 0;
                    t3.forEach(d3 => {
                        if (d3.PLNUM && !d3.AUFNR) ploCount++;
                        if (d3.AUFNR){
                            if (d3.STATS === 'CRTD') proCrt++;
                            else if (['PCNF','REL','CNF REL'].includes(d3.STATS)) proRel++;
                        }
                    });

                    const searchableText = [
                        r.KDAUF,
                        ltrim(r.KDPOS, '0'),
                        ltrim(r.MATFG, '0'),
                        r.MAKFG
                    ].join(' ').toLowerCase();

                    tableHtml += `
                        <tr class="t2-row cursor-pointer" data-key="${soKey}" data-index="${i}" data-searchable-text="${sanitize(searchableText)}">
                            <td class="text-center text-muted small">${i + 1}</td>
                            <td class="fw-semibold text-dark">${sanitize(r.KDAUF || '-')}</td>
                            <td class="text-muted">${ltrim(r.KDPOS, '0')}</td>
                            <td class="text-muted">${ltrim(r.MATFG, '0') || '-'}</td>
                            <td>${sanitize(r.MAKFG || '-')}</td>
                            <td>${formatSapYmd(r.EDATU)}</td>
                            <td class="text-center fw-medium">${ploCount}</td>
                            <td class="text-center fw-medium">${proCrt}</td>
                            <td class="text-center fw-medium">${proRel}</td>
                            <td class="text-center text-muted"><i class="fas fa-chevron-right"></i></td>
                        </tr>`;
                });

                tableHtml += `
                    <tr id="tdata2-no-results" style="display: none;">
                        <td colspan="10" class="text-center p-5 text-muted">
                            <i class="fas fa-search fs-4 d-block mb-2"></i>
                            Tidak ada data yang cocok dengan pencarian Anda.
                        </td>
                    </tr>
                </tbody></table></div>
                        <p class="mt-3 small text-muted">Klik salah satu baris untuk melihat ORDER OVERVIEW TABLE.</p>
                        </div>`;
                cardWrapper.innerHTML = tableHtml;
            }
            
            box.innerHTML = '';
            box.appendChild(cardWrapper);
            box.classList.remove('d-none');
            
            box.querySelectorAll('.t2-row').forEach(tr => {
                tr.addEventListener('click', () => handleClickTData2Row(tr.dataset.key, tr));
            });
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

            // BLOK LOGIKA UNTUK MENUTUP (COLLAPSE)
            if (existing) {
                existing.remove();
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('d-none'));
                togglePaginationDisabled(false);

                // >> BARU: Hapus state dari session saat detail ditutup
                sessionStorage.removeItem('activeT3Aufnr');
                sessionStorage.removeItem('activeT3Type');
                console.log(`State T3 untuk AUFNR ${aufnr} dihapus.`);
                
                return;
            }

            // BLOK LOGIKA UNTUK MEMBUKA (EXPAND)
            const data = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            if (!Array.isArray(data) || data.length === 0) {
                toast('info', 'Routing kosong', 'Tidak ada data routing ditemukan.');
                return;
            }
            
            // >> BARU: Simpan state ke session saat detail dibuka
            // Karena fungsi ini spesifik untuk "Routing", kita asumsikan tipenya 'route'
            sessionStorage.setItem('activeT3Aufnr', aufnr);
            sessionStorage.setItem('activeT3Type', 'route');
            console.log(`State T3 disimpan: AUFNR=${aufnr}, Tipe=route`);

            togglePaginationDisabled(true);
            document.querySelectorAll('#tdata3-body tr').forEach(row => {
                if (!row.textContent.includes(aufnr)) row.classList.add('d-none');
                else row.classList.remove('d-none');
            });

            const rowsHtml = data.map((t1, i) => `
                <tr class="bg-white">
                    <td class="text-center fs-6">${i + 1}</td>
                    <td class="text-center">${t1.VORNR || '-'}</td>
                    <td class="text-center">${t1.STEUS || '-'}</td>
                    <td class="text-center">${t1.KTEXT || '-'}</td>
                    <td class="text-center">${t1.ARBPL || '-'}</td>
                    <td class="text-center">${t1.PV1 || '-'}</td>
                    <td class="text-center">${t1.PV2 || '-'}</td>
                    <td class="text-center">${t1.PV3 || '-'}</td>
                </tr>
            `).join('');

            const block = document.createElement('div');
            block.id = divId;
            block.className = 'mt-4';
            block.innerHTML = `
                <h4 class="mb-2">Routing Overview</h4>
                <div class="table-responsive border rounded-lg">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="bg-purple-light text-purple-dark">
                            <tr>
                                <th scope="col" class="text-center fs-6">No.</th>
                                <th scope="col" class="text-center">Activity</th>
                                <th scope="col" class="text-center">Control Key</th>
                                <th scope="col" class="text-center">Description</th>
                                <th scope="col" class="text-center">Work Center</th>
                                <th scope="col" class="text-center">PV 1</th>
                                <th scope="col" class="text-center">PV 2</th>
                                <th scope="col" class="text-center">PV 3</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>`;
            
            container.innerHTML = '';
            container.appendChild(block);
        }
        
        function showTData4ByAufnr(aufnr) {
            const container = document.getElementById('additional-data-container');
            const blockId = `tdata4-${aufnr}`;
            const existing = document.getElementById(blockId);

            // BLOK LOGIKA UNTUK MENUTUP (COLLAPSE)
            if (existing) {
                existing.remove();
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('d-none'));
                togglePaginationDisabled(false);

                // >> BARU: Hapus state dari session saat detail ditutup
                sessionStorage.removeItem('activeT3Aufnr');
                sessionStorage.removeItem('activeT3Type');
                console.log(`State T3 untuk komponen AUFNR ${aufnr} dihapus.`);
                
                return;
            }

            // BLOK LOGIKA UNTUK MEMBUKA (EXPAND)
            // >> BARU: Simpan state ke session saat detail dibuka
            // Karena fungsi ini untuk menampilkan komponen, kita set tipenya 'component'
            sessionStorage.setItem('activeT3Aufnr', aufnr);
            sessionStorage.setItem('activeT3Type', 'component');
            console.log(`State T3 disimpan: AUFNR=${aufnr}, Tipe=component`);

            togglePaginationDisabled(true);
            const data = (allTData4ByAufnr && allTData4ByAufnr[aufnr]) ? allTData4ByAufnr[aufnr] : [];
            const plantCode = data.length > 0 && data[0].WERKSX ? data[0].WERKSX : '{{ $kode ?? $plant }}';
            document.querySelectorAll('#tdata3-body tr').forEach(row => {
                if (!row.textContent.includes(aufnr)) row.classList.add('d-none');
                else row.classList.remove('d-none');
            });
            
            // 1. Ambil data routing dan objek pertamanya dengan aman
            const routingData = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            const firstRouting = routingData.length > 0 ? routingData[0] : {};

            // 2. Siapkan variabel dari objek firstRouting
            const vornr = firstRouting.VORNR || '0010';
            const arbpl = firstRouting.ARBPL || ''; // Ambil ARBPL dari objek
            const pwwrk = firstRouting.PWWRK || '{{ $plant }}'; // Ambil WERKS dari objek, fallback ke plant

            const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');

            const rowsHtml = data.map((c, i) => `
                <tr class="bg-white">
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="component-select-${aufnr} form-check-input" data-aufnr="${aufnr}" data-rspos="${c.RSPOS || i}" data-material="${ltrim0(c.MATNR)}" onchange="handleComponentSelect('${aufnr}')">
                    </td>
                    <td class="px-4 py-3 text-center">${i + 1}</td>
                    <td class="px-4 py-3 text-center">${c.RSNUM ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${c.RSPOS ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${ltrim0(c.MATNR)}</td>
                    <td class="px-18 py-3 text-center">${sanitize(c.MAKTX)}</td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" class="btn btn-warning font-bold btn-sm edit-component-btn"
                                data-aufnr="${c.AUFNR ?? ''}"
                                data-rspos="${c.RSPOS ?? ''}"
                                data-matnr="${c.MATNR ?? ''}"
                                data-bdmng="${c.BDMNG ?? ''}"
                                data-lgort="${c.LGORT ?? ''}"
                                data-sobkz="${c.SOBKZ ?? ''}"
                                data-plant="${plantCode}"
                                onclick="handleEditClick(this)"> <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </td>
                    <td class="px-4 py-3 text-center">${c.BDMNG ?? c.MENGE ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${c.KALAB ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${c.OUTSREQ ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${c.LGORT ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${sanitize(c.MEINS === 'ST' ? 'PC' : (c.MEINS || '-'))}</td>
                    <td class="px-4 py-3 text-center">${c.LTEXT ?? '-'}</td>
                </tr>
            `).join('');

            const block = document.createElement('div');

            block.id = blockId;
            block.className = 'mt-4';
            block.innerHTML = `
            <div class="component-table-wrapper">
            
                <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                    <div class="d-flex gap-2" id="bulk-delete-controls-${aufnr}" style="display: none;">
                        <button type="button" id="bulk-delete-btn-${aufnr}" class="btn btn-danger btn-sm" onclick="bulkDeleteComponents('${aufnr}','${plantCode}')">
                            <i class="fas fa-trash-alt me-1"></i> Delete Selected (0)
                        </button>
                        
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearComponentSelections('${aufnr}')">
                            <i class="fas fa-times me-1"></i> Clear All
                        </button>
                    </div>

                    <button class="btn btn-primary btn-sm btn-add-component ms-auto" 
                            data-bs-toggle="modal" 
                            data-bs-target="#addComponentModal"
                            data-aufnr="${aufnr}"
                            data-vornr="${vornr}"
                            data-arbpl="${arbpl}"
                            data-pwwrk="${pwwrk}">
                        <i class="fas fa-plus me-1"></i> Add Component
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 5%;">
                                    <input type="checkbox" id="select-all-components-${aufnr}" class="form-check-input" onchange="toggleSelectAllComponents('${aufnr}')">
                                </th>
                                <th class="text-center" style="width: 5%;">No.</th>
                                <th class="text-center">Number Reservasi</th>
                                <th class="text-center">Item Reservasi</th>
                                <th class="text-center">Material</th>
                                <th class="text-center">Description</th>
                                <th class="text-center" >Action</th>
                                <th class="text-center">Req. Qty</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Outs. Req</th>
                                <th class="text-center">S.Log</th>
                                <th class="text-center">UOM</th>
                                <th class="text-center">Spec. Procurement</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml.length > 0 ? rowsHtml : `<tr><td colspan="12" class="text-center p-5 text-muted">Belum ada komponen. Klik 'Add Component' untuk menambahkan.</td></tr>`}
                        </tbody>
                    </table>
                </div>
            </div>`;
            container.innerHTML = '';
            container.appendChild(block);
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
            // Ambil semua baris dari TData2 untuk dimanipulasi
            const allT2Rows = document.querySelectorAll('#tdata2-section .t2-row'); 

            // KASUS 1: Klik baris yang sama untuk MENUTUP detail (collapse)
            if (t2CurrentKey === key && t2CurrentSelectedRow === tr && !t3Container.classList.contains('d-none')) {
                
                // [PERUBAHAN] Tampilkan kembali SEMUA baris di TData2
                allT2Rows.forEach(row => row.style.display = ''); // Menghapus style 'display: none'

                // Logika lama Anda untuk menutup detail (sudah benar)
                t2CurrentSelectedRow.classList.remove('table-active');
                t3Container.classList.add('d-none');
                document.getElementById('additional-data-container').innerHTML = '';
                sessionStorage.removeItem('activeTdata2Key');
                
                // Reset state
                t2CurrentKey = null;
                t2CurrentSelectedRow = null;
                // ... reset lainnya seperti pagination jika ada ...
                return;
            }
            allT2Rows.forEach(row => {
                row.style.display = (row === tr) ? '' : 'none'; // Tampil jika baris ini adalah yg diklik, selain itu sembunyikan
            });

            // Logika lama Anda untuk membuka detail (sudah benar)
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
            if (status === 'plo') filteredData = allRowsData.filter(d3 => d3.PLNUM && !d3.AUFNR);
            else if (status === 'crtd') filteredData = allRowsData.filter(d3 => d3.AUFNR && d3.STATS === 'CRTD');
            else if (status === 'released') filteredData = allRowsData.filter(d3 => d3.AUFNR && ['PCNF','REL','CNF REL'].includes(d3.STATS));
            else if (status === 'outgoing') {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const todayString = `${year}-${month}-${day}`;
                filteredData = allRowsData.filter(d3 => d3.AUFNR && d3.GSTRP === todayString);
            }

            renderT3Page(filteredData);
        }
        
        function renderT3Page(filteredData) {
            const tbody = document.getElementById('tdata3-body');
            tbody.innerHTML = '';

            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="13" class="text-center p-4 text-muted">Tidak ada data untuk filter ini.</td></tr>`;
            } else {
                filteredData.forEach((d3, i) => {
                const row = createTableRow(d3, i + 1); // urutan 1..N
                tbody.appendChild(row);
                });
            }

            // kosongkan area pagination (atau bisa disembunyikan via CSS)
            const pg = document.getElementById('tdata3-pagination');
            if (pg) pg.innerHTML = '';
            clearAllSelections();
        }

        const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');
        
        function createTableRow(d3, index) {
            const row = document.createElement('tr');

            row.dataset.aufnr = ltrim0(d3.AUFNR || '');

            const canSelectForPLO = d3.PLNUM && !d3.AUFNR;
            const canSelectForPRO = !!d3.AUFNR;
            const canSelect = canSelectForPLO || canSelectForPRO;

            let statusBadgeClass = 'badge-status-other';
            if (d3.STATS === 'CRTD') statusBadgeClass = 'badge-status-crtd';
            if (['PCNF','REL','CNF REL'].includes(d3.STATS)) statusBadgeClass = 'badge-status-rel';

            // Tombol Route dan Comp di samping AUFNR sudah ada di sini dan dipertahankan.
            row.innerHTML = `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input bulk-select"
                        ${canSelect ? '' : 'disabled'}
                        data-type="${canSelectForPLO ? 'PLO' : 'PRO'}"
                        data-id="${canSelectForPLO ? d3.PLNUM : d3.AUFNR}"
                        data-auart="${d3.AUART || ''}"
                        onchange="handleBulkSelect(this)">
                </td>
                <td class="text-center">${index}</td>
                <td class="text-center">
                    <div class="pro-cell-inner d-flex align-items-center justify-content-between">
                        <span class="fw-medium">${d3.AUFNR || '-'}</span>
                        ${d3.AUFNR ? `
                        <div class="pro-cell-actions d-flex align-items-center gap-2">
                            <button class="btn btn-info btn-sm py-0 px-1" route-button onclick="showTData1ByAufnr('${d3.AUFNR}')">Route</button>
                            <button class="btn btn-primary btn-sm py-0 px-1" onclick="showTData4ByAufnr('${d3.AUFNR}')">Comp</button>
                        </div>
                        ` : ''}
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge ${statusBadgeClass}">${d3.STATS || '-'}</span>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        ${d3.AUFNR ? `
                        <button type="button" title="Reschedule" class="btn btn-schedule btn-sm"
                            onclick="openSchedule(
                                '${encodeURIComponent(padAufnr(d3.AUFNR))}',
                                '${formatDate(d3.SSAVD)}'
                            )">
                            <i class="fas fa-clock-rotate-left"></i>
                        </button>` : ''}
                        ${d3.AUFNR ? `
                        <button type="button" title="Read PP" class="btn btn-readpp btn-sm"
                            onclick="openReadPP('${encodeURIComponent(padAufnr(d3.AUFNR))}')">
                            <i class="fas fa-book-open"></i>
                        </button>` : ''}
                        ${d3.AUFNR ? `
                        <button type="button" title="TECO" class="btn btn-teco btn-sm"
                            onclick="openTeco('${encodeURIComponent(padAufnr(d3.AUFNR))}')">
                            <i class="fas fa-circle-check"></i>
                        </button>` : ''}
                        ${d3.AUFNR ? `
                        <button type="button" title="Refresh PRO" class="btn btn-primary btn-sm"
                            onclick="openRefresh('${d3.AUFNR}', '${d3.WERKSX}')">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>` : ''}
                        ${d3.AUFNR ? `
                        <button type="button" title="Change PV" class="btn btn-warning btn-sm"
                            onclick="openChangePvModal('${d3.AUFNR}', '${d3.VERID}', '${d3.WERKSX}')">
                            <i class="fa-solid fa-code-compare"></i>
                        </button>` : ''}
                    </div>
                </td>
                <td class="text-center">${d3.DISPO || '-'}</td>
                <td class="text-center">${d3.MATNR ? ltrim(d3.MATNR, '0') : '-'}</td>
                <td class="text-center">${sanitize(d3.MAKTX) || '-'}</td>
                <td class="text-center">${d3.PSMNG || '-'}</td>
                <td class="text-center">${d3.WEMNG || '-'}</td>
                <td class="text-center">${d3.MENG2 || '-'}</td>
                <td class="text-center">${formatDate(d3.GSTRP)}</td>
                <td class="text-center">${formatDate(d3.GLTRP)}</td>
            `;

            row.dataset.rowData = JSON.stringify(d3);
            return row;
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
            const id = rowData.AUFNR; 
            if (!id) return; 

            if (checkbox.checked) {
                if (mappedPRO.size > 0 && rowData.WERKSX !== bulkActionPlantCode) {
                    Swal.fire(
                        'Aksi Diblokir',
                        'Anda hanya dapat memilih PRO dari plant yang sama (' + bulkActionPlantCode + ').',
                        'warning'
                    );
                    checkbox.checked = false; // Batalkan centang secara otomatis
                    return; // Hentikan fungsi
                }

                selectedPRO.add(id);
                mappedPRO.set(id, rowData);

                // Atur plant code hanya pada pilihan pertama
                if (mappedPRO.size === 1) { 
                    bulkActionPlantCode = rowData.WERKSX;
                }

            } else { // Jika tidak dicentang
                selectedPRO.delete(id);
                mappedPRO.delete(id);

                // Reset plant code jika tidak ada lagi yang dipilih
                if (mappedPRO.size === 0) {
                    bulkActionPlantCode = null;
                }
            }
            
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

    </script>
@endpush
</x-layouts.app>