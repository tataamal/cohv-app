
<x-layouts.app>
    @push('styles')
    <style>
    /* Scroll vertikal bila tinggi konten melebihi batas */
    #outstanding-order-container .table-responsive {
        max-height: 420px;   /* atur sesuai kebutuhan */
        overflow-y: auto;
    }

    /* Bantu sticky header */
    #outstanding-order-container .table-responsive table {
        border-collapse: separate;
        border-spacing: 0;
    }

    /* Header nempel saat discroll */
    #outstanding-order-container thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-body-bg, #fff);
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.075);
    }

    /* Jika card pakai efek glass */
    .card-glass #outstanding-order-container thead th {
        background: rgba(255, 255, 255, 0.75);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }
    /* Scroll dan sticky header untuk tdata3 */
    #tdata3-container .table-responsive {
        max-height: 420px;      /* biar kalau data banyak, scroll vertikal */
        overflow-y: auto;
    }
    #tdata3-container table {
        border-collapse: separate;
        border-spacing: 0;
    }
    #tdata3-container thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc; /* soft background header */
        color: #374151;      /* teks gelap */
        font-weight: 600;
    }

    /* Hover row lebih soft */
    #tdata3-container tbody tr:hover {
        background-color: rgba(0,0,0,.03);
    }

    /* Soft border */
    #tdata3-container table td,
    #tdata3-container table th {
        border-color: #e5e7eb !important;
    }

    /* Modern badge warna status */
    .badge-status-crtd {
        background: #facc15; /* kuning lembut */
        color: #000;
    }
    .badge-status-rel {
        background: #4ade80; /* hijau lembut */
        color: #064e3b;
    }
    .badge-status-other {
        background: #9ca3af; /* abu lembut */
        color: #111827;
    }

    /* Button warna custom */
    .btn-schedule {
        background-color: #facc15; /* kuning */
        color: #111827;
        border: none;
    }
    .btn-schedule:hover { background-color: #eab308; color:#fff; }

    .btn-readpp {
        background-color: #4ade80; /* hijau */
        color: #064e3b;
        border: none;
    }
    .btn-readpp:hover { background-color: #22c55e; color:#fff; }

    .btn-teco {
        background-color: #f87171; /* merah soft */
        color: #fff;
        border: none;
    }
    .btn-teco:hover { background-color: #dc2626; }
    .pro-cell-inner {
        width: min(360px, 100%);
        margin: 0 auto;                 /* center di dalam cell */
    }
    .pro-cell-actions .btn {
        padding: .2rem .4rem;           /* tombol kecil rapi */
    }
    </style>
    @endpush
    <div class="container-fluid">
        {{-- Header Halaman --}}
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
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'plo')">PLO</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'crtd')">PRO (CRTD)</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterByStatus(this, 'released')">PRO (Released)</button>
                        </div>
                    </div>
                    
                    <div id="bulk-controls" class="d-flex align-items-center gap-2 mb-3 d-none">
                        <button id="bulk-convert-btn" class="btn btn-warning btn-sm d-none" onclick="bulkConvertPlannedOrders()">Convert Selected PLO</button>
                        <button id="bulk-release-btn" class="btn btn-success btn-sm d-none" onclick="bulkReleaseOrders()">Release Selected PRO</button>
                        <button class="btn btn-secondary btn-sm" onclick="clearAllSelections()">Clear All</button>
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
    <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultModalLabel">Production Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="singleView">
                        <div class="mb-3">
                            <small class="text-muted">Plant</small>
                            <p id="plantValue" class="fw-medium text-dark">-</p>
                        </div>
                        <div>
                            <small class="text-muted">Production Order</small>
                            <div id="poList" class="mt-1 d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                    <div id="batchView" class="d-none">
                        <p class="small text-muted mb-2">Converted Orders</p>
                        <div class="table-responsive border rounded-3" style="max-height: 400px;">
                            <table class="table table-sm">
                                <thead class="table-light"><tr><th>#</th><th>Planned Order</th><th>Plant</th><th>Production Order</th></tr></thead>
                                <tbody id="batchTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="resultOk" class="btn btn-primary">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel aria-hidden="true"">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route(name: 'reschedule.store') }}" method="POST" id="scheduleForm">
                    @csrf
                    <input type="hidden" name="aufnr" id="scheduleAufnr">
                    <div class="modal-header">
                        <h5 class="modal-title" id="scheduleModalLabel">Schedule Production Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="scheduleDate" class="form-label">Tanggal</label>
                            <input type="date" name="date" id="scheduleDate" required class="form-control">
                        </div>
                        <div>
                            <label for="scheduleTime" class="form-label">Jam (HH.MM.SS)</label>
                            <input type="text" name="time" id="scheduleTime" placeholder="00.00.00" required pattern="^\d{2}[\.:]\d{2}[\.:]\d{2}$" class="form-control">
                            <div class="form-text">Format 24 jam, contoh: 13.30.00</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changeWcModal" tabindex="-1" aria-labelledby="changeWcModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('change-wc') }}" method="POST" >
                    @csrf
                    <input type="hidden" id="changeWcAufnr" name="aufnr">
                    <input type="hidden" id="changeWcVornr" name="vornr">
                    <input type="hidden" id="changeWcSequ" name="sequ" value="0">
                    <input type="hidden" id="changeWcPlant" name="plant" value="{{ $plant }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeWcModalLabel">Change Work Center</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="changeWcInput" class="form-label">Work Center</label>
                        <input type="text" id="changeWcInput" name="work_center" placeholder="Masukkan Work Center baru" class="form-control" required>
                        <div id="changeWcCurrent" class="form-text"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changePvModal" tabindex="-1" aria-labelledby="changePvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <form action="{{ route('change-pv') }}" method="POST" id="changePvForm">
                @csrf
                <div class="modal-header">
                <h5 class="modal-title" id="changePvModalLabel">Change Production Version (PV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                {{-- Hidden yang akan diisi saat modal dibuka --}}
                <input type="hidden" id="changePvAufnr" name="AUFNR">
                <input type="hidden" id="changePvWerks" name="plant">

                <label for="changePvInput" class="form-label">Production Version (PV)</label>
                <select id="changePvInput" name="PROD_VERSION" class="form-select" required>
                    <option value="">-- Pilih Production Version --</option>
                    <option value="0001">PV 0001</option>
                    <option value="0002">PV 0002</option>
                    <option value="0003">PV 0003</option>
                </select>
                <div id="changePvCurrent" class="form-text"></div>
                </div>

                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" id="changePvSubmitBtn" class="btn btn-primary">Simpan</button>
                </div>
            </form>
            </div>
        </div>
        </div>
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- [BARU] Inisialisasi semua Modal Bootstrap ---
        let resultModal, scheduleModal, changeWcModal, changePvModal;
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
        });

        // --- Fungsi Helper Notifikasi (SweetAlert - Tidak Berubah) ---
        window.toast = (icon, title, text='') => Swal.fire({ icon, title, text, timer: 2500, timerProgressBar: true, showConfirmButton: false, position: 'top-end', toast: true });
        window.confirmSwal = (opts={}) => Swal.fire({ icon: opts.icon || 'question', title: opts.title || 'Konfirmasi', text: opts.text || '', showCancelButton: true, confirmButtonText: opts.confirmText || 'Ya', cancelButtonText: opts.cancelText || 'Batal' });
        window.resultSwal = async ({success=true, title, html, text}) => { await Swal.fire({ icon: success ? 'success' : 'error', title: title || (success ? 'Berhasil' : 'Gagal'), html, text, confirmButtonText: 'OK' }); };
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

            // Panggil fungsi untuk merender tdata3 (saya asumsikan Anda sudah punya fungsi ini)
            // contoh: renderTData3Table(key);
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
                            <button class="btn btn-danger btn-sm" onclick="openChangeWcModal('${t1.AUFNR}', '${t1.VORNR}', '${t1.ARBPL || ''}')">Edit WC</button>
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
            document.querySelectorAll('#tdata3-body tr').forEach(row => {
                if (!row.textContent.includes(aufnr)) row.classList.add('d-none');
                else row.classList.remove('d-none');
            });
            const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');
            const routingData = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            const vornr = (routingData.length > 0 && routingData[0].VORNR) ? routingData[0].VORNR : '0010';
            const plant = '{{ $plant }}';
            const rowsHtml = data.map((c, i) => `
                <tr class="bg-white">
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="component-select-${aufnr} form-check-input" data-aufnr="${aufnr}" data-rspos="${c.RSPOS || i}" data-material="${ltrim0(c.MATNR)}" onchange="handleComponentSelect('${aufnr}')">
                    </td>
                    <td class="px-4 py-3 text-center">${i + 1}</td>
                    <td class="px-4 py-3 text-center">${ltrim0(c.MATNR)}</td>
                    <td class="px-4 py-3 text-center">${sanitize(c.MAKTX)}</td>
                    <td class="px-4 py-3 text-center">${c.BDMNG ?? c.MENGE ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${c.ENMNG ?? '-'}</td>
                    <td class="px-4 py-3 text-center">${sanitize(c.MEINS || '-')}</td>
                </tr>
            `).join('');
            const block = document.createElement('div');
            block.id = blockId;
            block.className = 'mt-4';
            block.innerHTML = `
                <div class="d-flex justify-content-end align-items-center mb-2">
                    <div class="d-flex gap-2" id="bulk-delete-controls-${aufnr}" style="display: none;">
                        <button type="button" id="bulk-delete-btn-${aufnr}" class="btn btn-danger btn-sm" onclick="bulkDeleteComponents('${aufnr}')">Delete Selected (0)</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearComponentSelections('${aufnr}')">Clear All</button>
                    </div>
                    <button type="button" class="btn btn-success btn-sm ms-2" onclick="openModalAddComponent('${aufnr}', '${vornr}', '${plant}')">Add Component</button>
                </div>
                <div class="table-responsive border rounded-lg">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="bg-purple-light text-purple-dark">
                            <tr>
                                <th scope="col" class="text-center">
                                    <input type="checkbox" id="select-all-components-${aufnr}" class="form-check-input" onchange="toggleSelectAllComponents('${aufnr}')">
                                </th>
                                <th scope="col" class="text-center">No.</th>
                                <th scope="col" class="text-center">Material</th>
                                <th scope="col" class="text-center">Description</th>
                                <th scope="col" class="text-center">Req. Qty</th>
                                <th scope="col" class="text-center">Stock</th>
                                <th scope="col" class="text-center">Spec. Procurement</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml.length > 0 ? rowsHtml : `<tr><td colspan="7" class="text-center p-5 text-muted">Belum ada komponen. Klik 'Add Component' untuk menambahkan.</td></tr>`}</tbody>
                    </table>
                </div>`;
            container.innerHTML = '';
            container.appendChild(block);

            const bulkDeleteControls = document.getElementById(`bulk-delete-controls-${aufnr}`);
            if (bulkDeleteControls) {
                bulkDeleteControls.style.display = 'none';
            }
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

        function renderT3PaginationControls(totalItems) {
            const paginationContainer = document.getElementById('tdata3-pagination');
            if (totalItems <= t3ItemsPerPage) {
                paginationContainer.innerHTML = totalItems > 0 ? `<span class="small text-muted">Showing 1 to ${totalItems} of ${totalItems} results</span>` : ''; return;
            }
            const totalPages = Math.ceil(totalItems / t3ItemsPerPage);
            const startItem = (t3CurrentPage - 1) * t3ItemsPerPage + 1;
            const endItem = Math.min(startItem + t3ItemsPerPage - 1, totalItems);
            let infoHtml = `<span class="small text-muted">Showing ${startItem} to ${endItem} of ${totalItems} results</span>`;
            let buttonsHtml = '';
            let pagesToShow = new Set([1, totalPages, t3CurrentPage, t3CurrentPage - 1, t3CurrentPage + 1]);
            const sortedPages = Array.from(pagesToShow).filter(p => p > 0 && p <= totalPages).sort((a,b) => a - b);
            let lastPage = 0;
            sortedPages.forEach(page => {
                if (lastPage > 0 && page - lastPage > 1) { buttonsHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`; }
                const isActive = page === t3CurrentPage;
                buttonsHtml += `<li class="page-item ${isActive ? 'active' : ''}"><button type="button" class="page-link" onclick="changeT3Page(${page})">${page}</button></li>`;
                lastPage = page;
            });
            paginationContainer.innerHTML = `
                <div>${infoHtml}</div>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item ${t3CurrentPage === 1 ? 'disabled' : ''}"><button type="button" class="page-link" onclick="changeT3Page(${t3CurrentPage - 1})">Prev</button></li>
                    ${buttonsHtml}
                    <li class="page-item ${t3CurrentPage === totalPages ? 'disabled' : ''}"><button type="button" class="page-link" onclick="changeT3Page(${t3CurrentPage + 1})">Next</button></li>
                </ul>`;
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
                        onclick="openSchedule('${encodeURIComponent(padAufnr(d3.AUFNR))}')">
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
                </div>
                </td>

                <td class="text-center">${d3.DISPO || '-'}</td>
                <td class="text-center">${d3.MATNR ? ltrim(d3.MATNR, '0') : '-'}</td>
                <td class="text-center">${sanitize(d3.MAKTX) || '-'}</td>
                <td class="text-center">${d3.PSMNG || '-'}</td>
                <td class="text-center">${d3.WEMNG || '-'}</td>
                <td class="text-center">${d3.MENG2 || '-'}</td>
                <td class="text-center">${formatDate(d3.SSAVD)}</td>
                <td class="text-center">${formatDate(d3.SSSLD)}</td>
            `;
            row.dataset.rowData = JSON.stringify(d3);
            return row;
            }
        // =================================================================
        // 3. SEMUA FUNGSI AKSI (MODALS, BULK, API)
        // =================================================================

        function scheduleBtnLoading(form){
            const btn = form.querySelector('#scheduleSubmitBtn');
            if(!btn) return true;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
            return true; // lanjut submit normal
        }
        
        function openSchedule(aufnrEnc) {
            const aufnr = decodeURIComponent(aufnrEnc);
            document.getElementById('scheduleAufnr').value = aufnr;
            document.getElementById('scheduleDate').value = new Date().toISOString().slice(0,10);
            document.getElementById('scheduleTime').value = '00.00.00';
            scheduleModal.show();
        }
        function openChangeWcModal(aufnr, vornr, currentWC = '') {
            document.getElementById('changeWcAufnr').value = aufnr || '';
            document.getElementById('changeWcVornr').value = vornr || '';
            document.getElementById('changeWcInput').value = '';
            document.getElementById('changeWcPlant').value = "{{ $plant }}";
            document.getElementById('changeWcInput').placeholder = `${currentWC}`;
            document.getElementById('changeWcCurrent').textContent = currentWC ? `Current WC: ${currentWC}` : '';
            changeWcModal.show();
        }
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
        function openModalAddComponent(aufnr, vornr) {
            document.getElementById('add-component-aufnr').value = aufnr;
            document.getElementById('add-component-vornr').value = vornr;
            document.getElementById('display-aufnr').value = aufnr;
            document.getElementById('display-vornr').value = vornr;
            document.getElementById('add-component-form').reset();
            if(addComponentModal) addComponentModal.show();
        }

        function handleBulkSelect(checkbox) { 
            const type = checkbox.dataset.type;
            const id = checkbox.dataset.id;
            const auart = checkbox.dataset.auart;

            if (type === 'PLO') {
                const ploDataString = JSON.stringify({ plnum: id, auart: auart });
                if (checkbox.checked) {
                    selectedPLO.add(ploDataString);
                } else {
                    selectedPLO.delete(ploDataString);
                }
            } else {
                if (checkbox.checked) {
                    selectedPRO.add(id);
                } else {
                    selectedPRO.delete(id);
                }
            }
            updateBulkControls();
        }
        function updateBulkControls() { 
            const bulkControls = document.getElementById('bulk-controls');
            const convertBtn = document.getElementById('bulk-convert-btn');
            const releaseBtn = document.getElementById('bulk-release-btn');
            const hasPLO = selectedPLO.size > 0;
            const hasPRO = selectedPRO.size > 0;
            bulkControls.classList.toggle('d-none', !hasPLO && !hasPRO);
            convertBtn.classList.toggle('d-none', !hasPLO);
            releaseBtn.classList.toggle('d-none', !hasPRO);
            if(hasPLO) convertBtn.textContent = `Convert Selected PLO (${selectedPLO.size})`;
            if(hasPRO) releaseBtn.textContent = `Release Selected PRO (${selectedPRO.size})`;
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
        async function convertPlannedOrderFixed(d3) {
            console.log('Convert button clicked', d3);
            const plnum = d3.PLNUM; 
            const auart = d3.AUART;
            const plant = @json($plant);

            if (!plnum || !auart) {
            console.error('Missing data:', { plnum, auart });
            return toast('error', 'Data tidak lengkap', 'PLNUM atau AUART tidak ditemukan.');
            }

            const { isConfirmed } = await confirmSwal({
            title: 'Konversi Planned Order?',
            text: `Konversi ${plnum} (Tipe: ${auart})`
            });
            if (!isConfirmed) return;

            const loader = document.getElementById('global-loading');
            if (loader) loader.style.display = 'flex';

            const url = '/create_prod_order';
            const requestData = { PLANNED_ORDER: plnum, AUART: auart, PLANT: plant };

            try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const ct = response.headers.get('content-type') || '';
            const raw = await response.text();
            if (!ct.includes('application/json')) {
                throw new Error(`Non-JSON response (status ${response.status}): ${raw.slice(0,120)}...`);
            }
            const data = JSON.parse(raw);
            if (!response.ok) {
                const msg = data?.error || data?.message || response.statusText;
                throw new Error(msg);
            }

            // Batch
            if (Array.isArray(data.results)) {
                showResultModal({
                results: data.results.map(r => ({
                    planned_order: r.planned_order || r.PLANNED_ORDER || plnum,
                    plant:         r.plant || r.PLANT || plant,
                    production_orders: (r.production_orders || []).map(padAufnr)
                }))
                });
                return;
            }

            // Single
            const orders = (data.production_orders && data.production_orders.length)
                ? data.production_orders.map(padAufnr)
                : (data.order_number ? [padAufnr(data.order_number)] : []);
            const modalData = {
                planned_order: data.planned_order || plnum,
                plant: data.plant || plant,
                production_orders: orders
            };
            showResultModal(modalData);

            } catch (error) {
            console.error('Convert error:', error);
            await resultSwal({ success: false, title: 'Konversi gagal', text: error.message || String(error) });
            } finally {
            if (loader) loader.style.display = 'none';
            }
        }
        async function bulkConvertPlannedOrders() {
            if (selectedPLO.size === 0) return toast('info','Tidak ada PLO terpilih');

            const { isConfirmed } = await confirmSwal({
                title: 'Konversi massal?',
                text: `Akan mengkonversi ${selectedPLO.size} Planned Order`
            });
            if (!isConfirmed) return;

            const loader = document.getElementById('global-loading');
            if (loader) loader.style.display = 'flex';

            const ploArray = Array.from(selectedPLO).map(itemStr => JSON.parse(itemStr));

            try {
                const results = await Promise.all(ploArray.map(async item => {
                  const url = '/create_prod_order';
                  const requestData = { PLANNED_ORDER: item.plnum, AUART: item.auart };

                  const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                      'Accept': 'application/json',
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                  });

                  const json = await res.json().catch(() => ({}));
                  if (!res.ok) throw new Error(json?.error || json?.message || res.statusText);
                  return json;
                }));

                const successCount = results.filter(r => r.success).length;
                await resultSwal({
                  success: true,
                  title: 'Konversi massal selesai',
                  text: `${successCount} dari ${ploArray.length} Planned Order berhasil.`
                });
                location.reload();
            } catch (error) {
                await resultSwal({
                  success: false,
                  title: 'Konversi massal gagal',
                  text: 'Terjadi kesalahan. Beberapa/semua order mungkin gagal.'
                });
            } finally {
                if (loader) loader.style.display = 'none';
            }
        }
        function bulkReleaseOrders() { alert('Fungsi bulk release belum diimplementasikan.'); }
        async function bulkDeleteComponents(aufnr) {
            const selected = [...document.querySelectorAll(`.component-select-${aufnr}`)].filter(cb => cb.checked);
            if (selected.length === 0) return;
            const payload = selected.map(cb => ({
                rspos: cb.dataset.rspos,
                material: cb.dataset.material,
            }));
            console.log('Bulk delete ->', aufnr, payload);
            // TODO: panggil API delete Anda di sini
        }
        async function submitChangePv() {
            const btn    = document.getElementById('changePvSubmitBtn');
            const aufnr  = (document.getElementById('changePvAufnr').value || '').trim();
            let   pv     = (document.getElementById('changePvInput').value || '').trim();
            const werks  = (document.getElementById('changePvWerks').value || '').trim();

            if (!aufnr) return (window.notify?.('AUFNR tidak ditemukan.', 'error') || alert('AUFNR tidak ditemukan.'));
            if (!pv)    return (window.notify?.('Isi Production Version (PV) dahulu.', 'error') || alert('Isi PV dahulu.'));
            if (!werks) return (window.notify?.('Plant (WERKS) tidak ditemukan.', 'error') || alert('Plant (WERKS) tidak ditemukan.'));

            const verid = pv.replace(/\s+/g, '').padStart(4, '0');

            const oldLabel = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';
            startGlobalLoading();

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const url  = "{{ route('change-pv') }}";

                const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ AUFNR: aufnr, PROD_VERSION: verid, plant: werks })
                });

                const raw = await res.text();
                let data = {};
                try { data = raw ? JSON.parse(raw) : {}; }
                catch { throw new Error(`Non-JSON response: ${raw.slice(0, 200)}...`); }

                if (!res.ok) {
                const msg = data?.error || data?.message || `HTTP ${res.status}`;
                throw new Error(msg);
                }

                // ✅ Matikan loader begitu respon sukses diterima
                stopGlobalLoading();

                if (window.refreshOrderDetail) await window.refreshOrderDetail(aufnr);
                closeChangePvModal?.();

                await resultSwal({
                success: true,
                title: 'Production Version diperbarui',
                html: `
                    <div style="text-align:left">
                    <div><b>Order</b>: ${aufnr}</div>
                    <div><b>Plant</b>: ${werks}</div>
                    <div><b>PV Baru</b>: ${verid}</div>
                    </div>
                `
                });

                // boleh tampilkan loader lagi saat reload (beforeunload handler akan show), itu normal
                location.reload();

            } catch (err) {
                // ✅ Pastikan loader juga mati saat error
                stopGlobalLoading();

                await resultSwal({
                success: false,
                title: 'Gagal mengubah PV',
                text: err?.message || String(err)
                });
            } finally {
                btn.disabled = false;
                btn.textContent = oldLabel;
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
        function updateComponentTable(aufnr, newComponentsData) {
            const container = document.getElementById(`tdata4-${aufnr}`);
            if (!container) return; // Jika tabel T_DATA4 tidak sedang ditampilkan, tidak melakukan apa-apa
            const tbody = container.querySelector('tbody');
            if (!tbody) return;
            // Simpan data baru ke variabel global agar konsisten
            allTData4ByAufnr[aufnr] = newComponentsData;
            const esc = (v) => { /* ... fungsi esc Anda ... */ };
            const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');
            const rowsHtml = newComponentsData.map((c, i) => `
                <tr>
                    <td class="border px-2 py-1 text-center">
                        <input type="checkbox"
                            class="component-select-${aufnr}"
                            data-aufnr="${aufnr}"
                            data-rspos="${c.RSPOS || i}"
                            data-material="${ltrim0(c.MATNR)}"
                            onchange="handleComponentSelect('${aufnr}')">
                    </td>
                    <td class="border px-2 py-1 text-center">${i + 1}</td>
                    <td class="border px-2 py-1">${ltrim0(c.MATNR)}</td>
                    <td class="border px-2 py-1">${esc(c.MAKTX)}</td>
                    <td class="border px-2 py-1 text-center">${c.BDMNG ?? c.MENGE ?? '-'}</td>
                    <td class="border px-2 py-1 text-center">${c.ENMNG ?? '-'}</td>
                    <td class="border px-2 py-1 text-center">${esc(c.MEINS || '-')}</td>
                </tr>
            `).join('');
            tbody.innerHTML = rowsHtml.length > 0 ? rowsHtml : `<tr><td colspan="7" class="text-center p-4 text-gray-500">Belum ada komponen.</td></tr>`;
            
            // Reset seleksi checkbox
            clearComponentSelections(aufnr);
        }
        
        async function handleAddComponentSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menambahkan...`;
            
            const formData = new FormData(form);
            const aufnr = formData.get('iv_aufnr');
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Gagal menambahkan komponen.');
                
                toast('success', data.message || 'Komponen berhasil ditambahkan!');
                if (addComponentModal) addComponentModal.hide();
                if (data.components) {
                    updateComponentTable(aufnr, data.components);
                }
            } catch (error) {
                console.error('Error:', error);
                resultSwal({ success: false, title: 'Operasi Gagal', text: error.message });
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
        
    </script>
@endpush
</x-layouts.app>