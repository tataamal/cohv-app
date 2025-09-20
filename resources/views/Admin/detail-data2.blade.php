
<x-layouts.app>
    <div class="container-fluid">
        {{-- Header Halaman --}}
        <x-notification.notification />
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h1 class="h5 fw-semibold text-dark mb-1">Kode Plant: {{ $plant }}</h1>
                        <p class="small text-muted mb-0">
                            <span class="fw-medium text-body-secondary">Nama Bagian:</span> {{ $bagian }} |
                            <span class="fw-medium text-body-secondary">Kategori:</span> {{ $categories }}
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <a href="{{ route('detail.data2', $plant) }}" class="btn btn-primary btn-sm nav-loader-link">
                            <i class="fas fa-sync-alt me-1"></i> Sync
                        </a>
                        <button onclick="hideAllDetails()" class="btn btn-warning btn-sm">Hide All</button>
                        <a href="{{ route('dashboard.show', $plant) }}" class="btn btn-secondary btn-sm nav-loader-link">
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
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Buyer..."
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
                                <tbody>
                                @forelse($tdata as $item)
                                    @php $key = ($item->KUNNR ?? '') . '-' . ($item->NAME1 ?? ''); @endphp
                                    <tr class="cursor-pointer" data-key="{{ $key }}" onclick="openSalesItem(this)">
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
                        </div>
                    </div>
                    
                    <div id="bulk-actions-wrapper" data-refresh-url="{{ route('bulk-refresh.store') }}" class="d-flex align-items-center gap-3 mt-4 mb-3 d-none">
                        <div id="selection-counter">
                            <span>PRO Terpilih:</span>
                            <span class="count-badge" id="selection-count-badge">0</span>
                        </div>
                        <div id="bulk-controls" class="d-flex align-items-center gap-2">
                            <button class="btn btn-success btn-sm" id="bulk-schedule-btn" style="display: none;" onclick="openBulkScheduleModal()">
                                <i class="fas fa-calendar-alt me-1"></i> Selected Schedule
                            </button>
                            <button class="btn btn-primary btn-sm" id="bulk-readpp-btn" style="display: none;" onclick="openBulkReadPpModal()">
                                <i class="fas fa-book-open me-1"></i> Selected Read PP
                            </button>
                            <button class="btn btn-danger btn-sm" id="bulk-teco-btn" style="display: none;" onclick="openBulkTecoModal()">
                                <i class="fas fa-circle-check me-1"></i> Selected TECO
                            </button>
                            <button class="btn btn-info btn-sm" id="bulk-refresh-btn" style="display: none;" onclick="openBulkRefreshModal()">
                                <i class="fas fa-sync-alt me-1"></i> Selected Refresh
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="clearAllSelections()">Clear All</button>
                        </div>
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
                            <tbody id="tdata3-body"></tbody>
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

    @include('components.modals.add-component-modal')
    @push('scripts')
    <script src="{{ asset('js/bulk-modal-handle.js') }}"></script>
    <script src="{{ asset('js/readpp.js') }}"></script>
    <script src="{{ asset('js/refresh.js') }}"></script>
    <script src="{{ asset('js/schedule.js') }}"></script>
    <script src="{{ asset('js/teco.js') }}"></script>
    <script src="{{ asset('js/component.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- [BARU] Inisialisasi semua Modal Bootstrap ---
        let resultModal, scheduleModal, changeWcModal, changePvModal, bulkScheduleModal, bulkReadPpModal, bulkTecoModal, bulkRefreshModal;
        let bulkActionPlantCode = null;
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
        });

        // --- Fungsi Helper Notifikasi (SweetAlert - Tidak Berubah) ---
        window.toast = (icon, title, text='') => Swal.fire({ icon, title, text, timer: 2500, timerProgressBar: true, showConfirmButton: false, position: 'top-end', toast: true });
        window.SAP = {user: @json(session('sap_id')),pass: @json(session('password'))};

        // --- Variabel State & Data (Tidak Berubah) ---
        let t2CurrentSelectedRow = null, t2CurrentKey = null;
        let currentSelectedRow = null, selectedPLO = new Set(), selectedPRO = new Set();
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
                hideAllDetails();
                return;
            }
            
            hideAllDetails();
            currentSelectedRow = tr;
            
            const t1Container = document.getElementById('outstanding-order-container');

            // Sembunyikan semua baris lain di tabel
            t1Container.querySelectorAll('tbody tr').forEach(row => {
                if (row !== tr) {
                    row.classList.add('d-none');
                }
            });

            // [PERBAIKAN] Tambahkan pengecekan sebelum menyembunyikan elemen
            const headerForm = t1Container.querySelector('form');
            if (headerForm) {
                headerForm.parentElement.classList.add('d-none');
            }

            const pager = t1Container.querySelector('.pagination');
            if (pager) {
                pager.parentElement.classList.add('d-none');
            }

            renderTData2Table(key);
        }

        function renderTData2Table(key) {
            const box = document.getElementById('tdata2-section');
            const rows = allTData2[key] || []; // Menggunakan allT2Data sesuai variabel di kode sebelumnya

            const cardWrapper = document.createElement('div');
            cardWrapper.className = 'card shadow-sm border-0';
            
            if (!rows.length){
                cardWrapper.innerHTML = `<div class="card-body text-center p-5 text-muted">Tidak ada data Outstanding Order untuk item ini.</div>`;
            } else {
                let scrollStyle = '';
                if (rows.length > 8) {
                    scrollStyle = 'style="max-height: 300px; overflow-y: auto;"';
                }

                let tableHtml = `
                    <style>
                        .sticky-header th {
                            position: -webkit-sticky;
                            position: sticky;
                            top: 0;
                            z-index: 1;
                            background-color: #fff; 
                            box-shadow: inset 0 -2px 0 #dee2e6; 
                        }
                    </style>
                    <div class="card-body">
                        <div id="tdata2-header" class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-3">
                            <h3 id="tdata2-title" class="h5 fw-semibold text-dark mb-0">Outstanding Order</h3>
                            <div id="tdata2-search-wrapper" class="input-group" style="max-width: 320px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="tdata2-search" oninput="filterTData2Table()" placeholder="Cari di tabel ini..." class="form-control border-start-0">
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
                                <tbody id="tdata2-tbody">`;
                
                rows.forEach((r, i) => {
                    const soKey = `${r.KDAUF || ''}-${r.KDPOS || ''}`;
                    const t3 = allTData3[soKey] || allTData3[key] || [];
                    let ploCount = 0, proCrt = 0, proRel = 0;
                    t3.forEach(d3 => {
                        if (d3.PLNUM && !d3.AUFNR) ploCount++;
                        if (d3.AUFNR){
                            if (d3.STATS === 'CRTD') proCrt++;
                            else if (['PCNF','REL','CNF REL'].includes(d3.STATS)) proRel++;
                        }
                    });
                    tableHtml += `
                        <tr class="t2-row cursor-pointer" data-key="${soKey}" data-index="${i}">
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
                
                tableHtml += `</tbody></table></div>
                    <p class="mt-3 small text-muted">Klik salah satu baris untuk melihat ORDER OVERVIEW TABLE.</p>
                    </div>`;
                cardWrapper.innerHTML = tableHtml;
            }
            
            box.innerHTML = '';
            box.appendChild(cardWrapper);
            box.classList.remove('d-none');
            // Pastikan event listener memanggil fungsi yang benar
            box.querySelectorAll('.t2-row').forEach(tr => {
                tr.addEventListener('click', () => handleClickTData2Row(tr.dataset.key, tr));
            });
        }

        function handleClickTData2Row(key, clickedTrElement) {
            // Ambil semua baris di tbody tdata2
            const allT2Rows = document.querySelectorAll('#tdata2-tbody .t2-row');
            
            // Sembunyikan semua baris lain dan hapus status 'aktif'
            allT2Rows.forEach(row => {
                if (row !== clickedTrElement) {
                    row.style.display = 'none'; // Sembunyikan baris
                }
                row.classList.remove('table-active'); // Hapus highlight dari baris manapun
                row.classList.remove('cursor-pointer'); // Hapus cursor pointer karena interaksi dinonaktifkan sementara
            });

            // Pastikan baris yang diklik terlihat dan beri highlight
            clickedTrElement.style.display = 'table-row';
            clickedTrElement.classList.add('table-active');

            // --- Ubah UI di header tdata2 (judul, search bar, dan tombol kembali) ---
            const header = document.getElementById('tdata2-header');
            const title = document.getElementById('tdata2-title');
            const searchWrapper = document.getElementById('tdata2-search-wrapper');

            if (header && title && searchWrapper) {
                // Sembunyikan search bar karena tidak relevan untuk satu baris
                searchWrapper.style.display = 'none';

                // Hanya tambahkan tombol 'kembali' jika belum ada
                if (!document.getElementById('tdata2-back-btn')) {
                    const backButton = document.createElement('button');
                    backButton.id = 'tdata2-back-btn';
                    backButton.className = 'btn btn-sm btn-outline-secondary ms-auto';
                    backButton.innerHTML = `<i class="fas fa-arrow-left me-2"></i> Tampilkan Semua Order`;
                    
                    // Logika untuk mengembalikan tampilan seperti semula
                    backButton.onclick = () => {
                        allT2Rows.forEach(row => {
                            row.style.display = 'table-row'; // Tampilkan lagi semua baris
                            row.classList.remove('table-active');
                            row.classList.add('cursor-pointer'); // Kembalikan interaktivitas
                        });

                        // Sembunyikan section tdata3
                        const tdata3Box = document.getElementById('tdata3-section'); // Asumsi ID tdata3
                        if (tdata3Box) {
                            tdata3Box.innerHTML = '';
                            tdata3Box.classList.add('d-none');
                        }

                        // Kembalikan header seperti semula
                        title.textContent = 'Outstanding Order';
                        searchWrapper.style.display = 'flex';
                        backButton.remove(); // Hapus tombol 'kembali'
                    };

                    title.textContent = 'Order Terpilih'; // Ganti judul
                    header.appendChild(backButton);
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
                return;
            }
            const data = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            if (!Array.isArray(data) || data.length === 0) {
                toast('info','Routing kosong','Tidak ada data routing ditemukan.');
                return;
            }
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
                    <td class="text-center">
                        <div class="d-flex gap-2 justify-content-center">
                            <button class="btn btn-danger btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#changeWcModal"
                                data-aufnr="${t1.AUFNR}"
                                data-vornr="${t1.VORNR}"
                                data-pwwrk="${t1.PWWRK}"
                                data-arbpl="${t1.ARBPL || ''}">
                                Edit WC
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="openChangePvModal('${t1.AUFNR}', '${t1.VERID || ''}', '${t1.WERKS || ''}')">Change PV</button>
                        </div>
                    </td>
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
                                <th scope="col" class="text-center">Action</th>
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
            if (existing) {
                existing.remove();
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('d-none'));
                togglePaginationDisabled(false);
                return;
            }
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
                    <td class="px-4 py-3 text-center">${sanitize(c.MAKTX)}</td>
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
            block.innerHTML = block.innerHTML = `
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
                                <th>Number Reservasi</th>
                                <th>Item Reservasi</th>
                                <th>Material</th>
                                <th>Description</th>
                                <th class="text-center">Req. Qty</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Outs. Req</th>
                                <th>S.Log</th>
                                <th class="text-center">UOM</th>
                                <th>Spec. Procurement</th>
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

            // Jika klik baris yang sama dan t3 sedang terbuka -> collapse (tutup)
            if (t2CurrentKey === key && t2CurrentSelectedRow === tr && !t3Container.classList.contains('d-none')) {
                // hilangkan highlight baris
                t2CurrentSelectedRow.classList.remove('table-active');

                // sembunyikan tdata3 & bersihkan tambahan
                t3Container.classList.add('d-none');
                document.getElementById('additional-data-container').innerHTML = '';

                // reset state pagination/selection
                allRowsData = [];
                t3CurrentPage = 1;
                clearAllSelections();
                togglePaginationDisabled(false);

                // reset pointer baris t2
                t2CurrentKey = null;
                t2CurrentSelectedRow = null;
                return;
            }

            // Kalau klik baris berbeda / saat tertutup -> buka & highlight
            document.getElementById('tdata2-section')
                .querySelectorAll('.t2-row')
                .forEach(r => r.classList.remove('table-active'));

            tr.classList.add('table-active');
            t2CurrentSelectedRow = tr;
            t2CurrentKey = key;

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
        
        function createTableRow(d3, index) {
            const row = document.createElement('tr');

            const canSelectForPLO = d3.PLNUM && !d3.AUFNR;
            const canSelectForPRO = d3.AUFNR && d3.STATS === 'CRTD';
            const canSelect = canSelectForPLO || canSelectForPRO;

            let statusBadgeClass = 'badge-status-other';
            if (d3.STATS === 'CRTD') statusBadgeClass = 'badge-status-crtd';
            if (['PCNF','REL','CNF REL'].includes(d3.STATS)) statusBadgeClass = 'badge-status-rel';

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

                <!-- PRO: satu baris horizontal; nomor kiri, tombol kanan; bloknya terpusat -->
                <td class="text-center">
                <div class="pro-cell-inner d-flex align-items-center justify-content-between">
                    <span class="fw-medium">${d3.AUFNR || '-'}</span>
                    ${d3.AUFNR ? `
                    <div class="pro-cell-actions d-flex align-items-center gap-2">
                        <button class="btn btn-info btn-sm py-0 px-1" onclick="showTData1ByAufnr('${d3.AUFNR}')">Route</button>
                        <button class="btn btn-primary btn-sm py-0 px-1" onclick="showTData4ByAufnr('${d3.AUFNR}')">Comp</button>
                    </div>
                    ` : ''}
                </div>
                </td>

                <td class="text-center">
                <span class="badge ${statusBadgeClass}">${d3.STATS || '-'}</span>
                </td>

                <!-- Action: beri jarak antar tombol -->
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

            // Ambil semua data dari baris yang dipilih
            const rowData = JSON.parse(row.dataset.rowData);
            const plantCode = rowData.WERKSX; // Ambil kode plant dari data baris

            // <-- TAMBAHKAN DEBUG DI SINI
            console.log('Kode Plant (WERKSX) dari baris yang dipilih:', plantCode);
            // ------------------------------------

            const type = checkbox.dataset.type;
            const id = checkbox.dataset.id;
            const auart = checkbox.dataset.auart;

            if (checkbox.checked) {
                // Jika ini adalah item PERTAMA yang dipilih, simpan kode plant-nya
                if (selectedPLO.size === 0 && selectedPRO.size === 0) {
                    bulkActionPlantCode = plantCode;
                    // Anda bisa tambahkan log di sini juga untuk memastikan kapan kode disimpan
                    console.log(` -> Kode Plant '${plantCode}' disimpan untuk aksi bulk.`);
                }
                
                if (type === 'PLO') {
                    const ploDataString = JSON.stringify({ plnum: id, auart: auart });
                    selectedPLO.add(ploDataString);
                } else {
                    selectedPRO.add(id);
                }
            } else {
                // Logika untuk uncheck
                if (type === 'PLO') {
                    const ploDataString = JSON.stringify({ plnum: id, auart: auart });
                    selectedPLO.delete(ploDataString);
                } else {
                    selectedPRO.delete(id);
                }

                // Jika SEMUA item sudah tidak dipilih, reset kode plant
                if (selectedPLO.size === 0 && selectedPRO.size === 0) {
                    bulkActionPlantCode = null;
                    // Log untuk memastikan kode di-reset
                    console.log(' -> Semua pilihan dibatalkan, kode plant di-reset.');
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
    </script>
@endpush
</x-layouts.app>