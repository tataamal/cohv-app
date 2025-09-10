<x-layouts.app>
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
                <div id="outstanding-order-container">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-3 gap-3">
                        <h3 class="h5 fw-semibold text-dark mb-0">Sales Order Table</h3>
                        <form method="GET" class="w-100" style="max-width: 320px;">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="form-control border-start-0" id="searchInput">
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-primary">
                                <tr class="small text-uppercase">
                                    <th class="text-center" style="width: 5%;">No.</th>
                                    <th>Buyer Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tdata as $index => $item)
                                    @php $key = ($item->KDAUF ?? '') . '-' . ($item->KDPOS ?? ''); @endphp
                                    <tr class="cursor-pointer" data-key="{{ $key }}" onclick="openSalesItem(this)">
                                        <td class="text-center small">{{ $tdata->firstItem() + $index }}</td>
                                        <td class="fw-medium text-dark">{{ $item->NAME1 ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center p-5 text-muted">Tidak ada data ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $tdata->appends(['search' => $search])->links() }}
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
                    <div id="tdata3-pagination" class="mt-3 d-flex justify-content-between align-items-center"></div>
                </div>

                <div id="additional-data-container" class="mt-4"></div>
            </div>
        </div>
    </div>

    @include('Admin.add-component-modal')

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

    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('reschedule.store') }}" method="POST" id="scheduleForm">
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
                <form action="{{ route('change-wc') }}" method="POST">
                    @csrf
                    <input type="hidden" id="changeWcAufnr" name="aufnr">
                    <input type="hidden" id="changeWcVornr" name="vornr">
                    <input type="hidden" id="changeWcSequ" name="sequ" value="0">
                    <input type="hidden" id="changeWcPlant" name="plant"> 
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
                <div class="modal-header">
                    <h5 class="modal-title" id="changePvModalLabel">Change Production Version (PV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="changePvAufnr">
                    <input type="hidden" id="changePvWerks">
                    <label for="changePvInput" class="form-label">Production Version (PV)</label>
                    <select id="changePvInput" class="form-select">
                        <option value="">-- Pilih Production Version --</option>
                        <option value="0001">PV 0001</option>
                        <option value="0002">PV 0002</option>
                        <option value="0003">PV 0003</option>
                    </select>
                    <div id="changePvCurrent" class="form-text"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="changePvSubmitBtn" class="btn btn-primary" onclick="submitChangePv()">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let resultModal, scheduleModal, changeWcModal, changePvModal;
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi modal setelah DOM siap
            resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
            scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            changeWcModal = new bootstrap.Modal(document.getElementById('changeWcModal'));
            changePvModal = new bootstrap.Modal(document.getElementById('changePvModal'));
        });
        // Toast singkat di pojok
        window.toast = (icon, title, text='') => Swal.fire({
        icon, title, text, timer: 2500, timerProgressBar: true,
        showConfirmButton: false, position: 'top-end', toast: true
        });

        // Dialog konfirmasi standar
        window.confirmSwal = (opts={}) => Swal.fire({
        icon: opts.icon || 'question',
        title: opts.title || 'Konfirmasi',
        text: opts.text || '',
        showCancelButton: true,
        confirmButtonText: opts.confirmText || 'Ya',
        cancelButtonText: opts.cancelText || 'Batal'
        });

        // Notif hasil sukses/gagal (modal biasa)
        window.resultSwal = async ({success=true, title, html, text}) => {
        await Swal.fire({
            icon: success ? 'success' : 'error',
            title: title || (success ? 'Berhasil' : 'Gagal'),
            html, text,
            confirmButtonText: 'OK'
        });

        // Variabel global untuk menyimpan state
        let currentSelectedRow = null, currentActiveKey = null, selectedPLO = new Set(), selectedPRO = new Set();
        let allRowsData = [], refreshPlant = null, refreshOrders = [], currentSOKey = null, currentFilterName = 'all';
        let currentT2Selection = null, t3CurrentPage = 1;
        const t3ItemsPerPage = 10;
        const allTData2 = @json($allTData2, JSON_HEX_TAG), allTData3 = @json($allTData3, JSON_HEX_TAG);
        const allTData1 = @json($allTData1, JSON_HEX_TAG), allTData4ByAufnr = @json($allTData4ByAufnr, JSON_HEX_TAG);
        const RELEASE_ORDER_URL = @json(route('release.order.direct', ['aufnr' => '__AUFNR__']));
        const tdata1ByAufnr = (() => {
            const by = {};
            if (allTData1 && typeof allTData1 === 'object') {
                Object.values(allTData1).forEach(arr => {
                    (arr || []).forEach(t1 => {
                        const a = (t1?.AUFNR || '').toString();
                        if (!a) return;
                        if (!by[a]) by[a] = [];
                        by[a].push(t1);
                    });
                });
            }
            return by;
        })();
        const allTData4ByPlnum = @json($allTData4ByPlnum, JSON_HEX_TAG);
        function padAufnr(v){ const s=String(v||''); return s.length>=12 ? s : s.padStart(12,'0'); }
        function sanitize(str){ const d=document.createElement('div'); d.textContent = String(str||''); return d.innerHTML; }
        function openSalesItem(tr) {
            const key = tr.dataset.key; // "KDAUF-KDPOS"
            if (currentSelectedRow === tr) { hideAllDetails();return ;}
            hideAllDetails();
            t3Container.classList.add('hidden');
            currentSelectedRow = tr;
            document.querySelectorAll('#outstanding-order-container tbody tr').forEach(row => { if (row !== tr) row.classList.add('d-none'); });
            document.querySelector('#outstanding-order-container form').parentElement.classList.add('d-none');
            document.querySelector('#outstanding-order-container .pagination').parentElement.classList.add('d-none');
            renderTData2Table(key);
        }
        function formatDate(dateString) {
            if (!dateString || dateString === '0000-00-00') return '-';
            try {
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}-${month}-${year}`;
            } catch (e) { return dateString; }
        }

        function ltrim(str, char) {
            if (!str) return '';
            const regex = new RegExp(`^${char}+`);
            return str.replace(regex, '');
        }

        function formatSapYmd(ymd){
            if(!ymd || String(ymd).length !== 8) return '-';
            const s = String(ymd);
            return `${s.slice(6,8)}-${s.slice(4,6)}-${s.slice(0,4)}`;
        }

        function renderT3PaginationControls(totalItems) {
            const paginationContainer = document.getElementById('tdata3-pagination');
            paginationContainer.innerHTML = ''; // Kosongkan kontainer

            if (totalItems <= t3ItemsPerPage) {
                if (totalItems > 0) {
                    paginationContainer.innerHTML = `<span class="text-sm text-gray-500">Showing 1 to ${totalItems} of ${totalItems} results</span>`;
                }
                return;
            }

            const totalPages = Math.ceil(totalItems / t3ItemsPerPage);
            const currentPage = t3CurrentPage;

            const startItem = (currentPage - 1) * t3ItemsPerPage + 1;
            const endItem = Math.min(startItem + t3ItemsPerPage - 1, totalItems);
            let infoHtml = `<span class="text-sm text-gray-500">Showing ${startItem} to ${endItem} of ${totalItems} results</span>`;

            let buttonsHtml = '';
            const pagePadding = 2;
            let pagesToShow = new Set();

            pagesToShow.add(1);
            pagesToShow.add(totalPages);

            for (let i = -pagePadding; i <= pagePadding; i++) {
                const page = currentPage + i;
                if (page > 0 && page <= totalPages) {
                    pagesToShow.add(page);
                }
            }

            const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b);
            let lastPage = 0;

            sortedPages.forEach(page => {
                if (lastPage > 0 && page - lastPage > 1) {
                    buttonsHtml += `<span class="px-3 py-1 text-sm text-gray-500">...</span>`;
                }
                
                const isActive = page === currentPage;
                buttonsHtml += `<button class="px-3 py-1 text-sm rounded-md ${isActive ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'}" onclick="changeT3Page(${page})">${page}</button>`;
                
                lastPage = page;
            });

            paginationContainer.innerHTML = `
                <div class="flex-grow text-left">
                    ${infoHtml}
                </div>
                <div class="flex items-center gap-1">
                    <button class="px-3 py-1 text-sm rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" 
                            onclick="changeT3Page(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                        &laquo; Prev
                    </button>
                    ${buttonsHtml}
                    <button class="px-3 py-1 text-sm rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" 
                            onclick="changeT3Page(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                        Next &raquo;
                    </button>
                </div>
            `;
        }

        function togglePaginationDisabled(isDisabled) {
            const paginationContainer = document.getElementById('tdata3-pagination');
            if (!paginationContainer) return;

            if (isDisabled) {
                paginationContainer.classList.add('opacity-50', 'pointer-events-none');
            } else {
                paginationContainer.classList.remove('opacity-50', 'pointer-events-none');
            }
        }

        function changeT3Page(page) {
            t3CurrentPage = page;
            filterByStatus(currentFilterName); 
        }

        function renderT3Page(filteredData) {
            const tbody = document.getElementById('tdata3-body');
            tbody.innerHTML = '';

            const startIndex = (t3CurrentPage - 1) * t3ItemsPerPage;
            const endIndex = startIndex + t3ItemsPerPage;
            const paginatedItems = filteredData.slice(startIndex, endIndex);

            if (paginatedItems.length === 0) {
                 tbody.innerHTML = `<tr><td colspan="14" class="text-center p-4">Tidak ada data untuk halaman ini.</td></tr>`;
            } else {
                paginatedItems.forEach((d3, index) => {
                    const row = createTableRow(d3, startIndex + index + 1);
                    tbody.appendChild(row);
                });
            }

            renderT3PaginationControls(filteredData.length);
            clearAllSelections();
        }

        function showResultModal(data) {
          const isBatch = Array.isArray(data?.results);
          if (isBatch) {
              const plant = data.results[0]?.plant || data.results[0]?.PLANT || @json($plant);
              const orders = data.results.flatMap(r => (r.production_orders || [])).filter(Boolean);
              refreshPlant  = plant;
              refreshOrders = Array.from(new Set(orders.map(padAufnr)));
          } else {
              const plant = data.plant || data.PLANT || @json($plant);
              const orders = (data.production_orders && data.production_orders.length)
                ? data.production_orders
                : (data.order_number ? [data.order_number] : []);
              refreshPlant  = plant;
              refreshOrders = Array.from(new Set(orders.map(padAufnr)));
          }

          const modalEl   = document.getElementById('resultModal');
          const overlayEl = document.getElementById('resultOverlay');
          const panelEl   = document.getElementById('resultPanel');

          const singleView = document.getElementById('singleView');
          const batchView  = document.getElementById('batchView');

          if (isBatch) {
              singleView.classList.add('hidden');
              batchView.classList.remove('hidden');
              const tbody = document.getElementById('batchTbody');
              tbody.innerHTML = '';
              data.results.forEach((r, i) => {
                const pros = (r.production_orders || []).filter(Boolean);
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
                  <td class="px-4 py-2">${i+1}</td>
                  <td class="px-4 py-2 font-mono">${sanitize(r.planned_order || r.PLANNED_ORDER || '-')}</td>
                  <td class="px-4 py-2">${sanitize(r.plant || r.PLANT || '-')}</td>
                  <td class="px-4 py-2">
                    ${pros.length ? pros.map(p=>`<span class="inline-flex rounded bg-gray-100 px-2 py-0.5 font-mono text-xs">${sanitize(padAufnr(p))}</span>`).join(' ') : '-'}
                  </td>
                `;
                tbody.appendChild(tr);
              });
          } else {
              singleView.classList.remove('hidden');
              batchView.classList.add('hidden');
              document.getElementById('plantValue').textContent = data.plant || data.PLANT || '-';
              const poList = document.getElementById('poList');
              const pros = (data.production_orders || []).filter(Boolean);
              poList.innerHTML = pros.length
                ? pros.map(p=>`<span class="inline-flex rounded bg-gray-100 px-2 py-0.5 font-mono text-xs">${sanitize(padAufnr(p))}</span>`).join('')
                : '<span class="text-gray-500">-</span>';
          }

          modalEl.classList.remove('hidden');
          requestAnimationFrame(() => {
              overlayEl.classList.remove('opacity-0');
              panelEl.classList.remove('opacity-0','scale-95');
              document.getElementById('resultOk')?.addEventListener('click', () => location.reload(), { once:true });
              document.getElementById('resultClose')?.addEventListener('click', () => closeResultModal(), { once:true });
          });
        }

        function openResultModal(){
          const modalEl   = document.getElementById('resultModal');
          const overlayEl = document.getElementById('resultOverlay');
          const panelEl   = document.getElementById('resultPanel');
          modalEl.classList.remove('hidden');
          requestAnimationFrame(() => {
            overlayEl.classList.remove('opacity-0');
            panelEl.classList.remove('opacity-0','scale-95');
          });
        }
        function closeResultModal(){
          const modalEl   = document.getElementById('resultModal');
          const overlayEl = document.getElementById('resultOverlay');
          const panelEl   = document.getElementById('resultPanel');
          overlayEl.classList.add('opacity-0');
          panelEl.classList.add('opacity-0','scale-95');
          setTimeout(() => modalEl.classList.add('hidden'), 150);
        }

        function toggleViews(isBatch){
          document.getElementById('singleView').classList.toggle('hidden', !!isBatch);
          document.getElementById('batchView').classList.toggle('hidden', !isBatch);
        }
        
        function hideAllDetails() {
            const t2Container = document.getElementById('outstanding-order-container');

            document.getElementById('tdata3-container').classList.add('hidden');

            const box = document.getElementById('tdata2-section');
            if (box) { box.innerHTML = ''; box.classList.add('hidden'); }

            document.getElementById('additional-data-container').innerHTML = '';
            
            // Hapus inline style min-width dari tabel tdata3 untuk reset
            const targetTable = document.getElementById('tdata3-table');
            if (targetTable) {
                targetTable.style.minWidth = ''; 
            }

            const allMainRows = document.querySelectorAll('#outstanding-order-container tbody tr');
            allMainRows.forEach(row => row.classList.remove('hidden'));
            const headerRow = t2Container.querySelector('.flex.justify-between.items-center.mb-4');
            if (headerRow) headerRow.classList.remove('hidden');
            const pager = t2Container.querySelector('.mt-6');
            if (pager) pager.classList.remove('hidden');

            if (currentSelectedRow) currentSelectedRow.classList.remove('bg-blue-100');
            currentSelectedRow = null;
            currentActiveKey = null;
            currentSOKey = null;
            allRowsData = [];
            clearAllSelections();

            togglePaginationDisabled(false);
        }

        function filterByStatus(buttonOrStatus) {
            let status, button;
            
            if (typeof buttonOrStatus === 'string') {
                status = buttonOrStatus;
                button = document.getElementById(`filter-${status}`);
            } else {
                status = arguments[1];
                button = buttonOrStatus;
            }

            const filterContainer = document.getElementById('status-filter');
            if (filterContainer && button) {
                filterContainer.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                    btn.classList.add('text-gray-600');
                });
                button.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
                button.classList.remove('text-gray-600');
            }
            
            if (currentFilterName !== status) {
                t3CurrentPage = 1; 
                currentFilterName = status;
            }

            let filteredData = allRowsData;
            if (status === 'plo') {
                filteredData = allRowsData.filter(d3 => d3.PLNUM && !d3.AUFNR);
            } else if (status === 'crtd') {
                filteredData = allRowsData.filter(d3 => d3.AUFNR && d3.STATS === 'CRTD');
            } else if (status === 'released') {
                filteredData = allRowsData.filter(d3 => d3.AUFNR && ['PCNF', 'REL', 'CNF REL'].includes(d3.STATS));
            }
            
            renderT3Page(filteredData);
        }

        function changeT3Page(page) {
            t3CurrentPage = page;
            filterByStatus(currentFilterName); 
        }

        function escapeJsonString(str) {
            if (!str) return '';
            return str.toString()
                .replace(/\\/g, '\\\\')  
                .replace(/"/g, '\\"')   
                .replace(/\n/g, '\\n')  
                .replace(/\r/g, '\\r')  
                .replace(/\t/g, '\\t');  
        }

        function createTableRow(d3, index) {
            const row = document.createElement('tr');
            row.className = 'border-t';
            
            const canSelectForPLO = d3.PLNUM && !d3.AUFNR;
            const canSelectForPRO = d3.AUFNR && d3.STATS === 'CRTD';
            const canSelect = canSelectForPLO || canSelectForPRO;

            let statusDisplay = d3.STATS || '-';
            let statusClass = 'bg-gray-200 text-gray-800';
            if (d3.STATS === 'CRTD') statusClass = 'bg-orange-100 text-orange-800';
            if (['PCNF', 'REL', 'CNF REL'].includes(d3.STATS)) statusClass = 'bg-green-100 text-green-800';

            row.innerHTML = `
                <td class="px-2 py-1 border text-center">
                    ${canSelect ? `<input type="checkbox" class="bulk-select" data-type="${canSelectForPLO ? 'PLO' : 'PRO'}" data-id="${canSelectForPLO ? d3.PLNUM : d3.AUFNR}" data-auart="${d3.AUART || ''}" onchange="handleBulkSelect(this)">` : ''}
                </td>
                <td class="px-2 py-1 border text-center">${index}</td>
                <td class="px-2 py-1 border text-center">
                    ${d3.AUFNR || '-'}
                    ${d3.AUFNR ? `
                        <button class="bg-indigo-600 text-white px-2 py-1 rounded text-xs hover:bg-indigo-700" onclick="showTData1ByAufnr('${d3.AUFNR}')">Route</button>
                        <button class="bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700" onclick="showTData4ByAufnr('${d3.AUFNR}')">Comp</button>
                        ` : ''}
                </td>
                <td class="px-2 py-1 border text-center">
                    <span class="px-2 py-1 rounded-full text-xs font-medium ${statusClass}">${statusDisplay}</span>
                </td>
                <td class="px-2 py-1 border text-center">
                <div class="flex items-center gap-2">
                    ${d3.AUFNR ? `
                    <button type="button" title="Reschedule"
                            class="p-2 leading-none rounded-md text-slate-600 bg-transparent border border-slate-300 
                                hover:bg-amber-500 hover:text-white hover:border-amber-500 transition-colors"
                            onclick="openSchedule('${encodeURIComponent(padAufnr(d3.AUFNR))}')">
                        <i class="fa-solid fa-clock-rotate-left fa-fw"></i>
                    </button>` : ''}

                    ${d3.AUFNR ? `
                    <button type="button" title="Read PP"
                            class="p-2 leading-none rounded-md text-slate-600 bg-transparent border border-slate-300 
                                hover:bg-sky-500 hover:text-white hover:border-sky-500 transition-colors"
                            onclick="openReadPP('${encodeURIComponent(padAufnr(d3.AUFNR))}')">
                        <i class="fa-solid fa-book-open fa-fw"></i>
                    </button>` : ''}

                    ${(d3.AUFNR && d3.STATS === 'REL') ? `
                    <button type="button" title="TECO"
                            class="p-2 leading-none rounded-md text-slate-600 bg-transparent border border-slate-300 
                                hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-colors"
                            onclick="openTeco('${encodeURIComponent(padAufnr(d3.AUFNR))}')">
                        <i class="fa-solid fa-circle-check fa-fw"></i>
                    </button>` : ''}

                </div>
                </td>
                <td class="px-2 py-1 border text-center">${d3.DISPO || '-'}</td>
                <td class="px-2 py-1 border text-center">${d3.MATNR ? ltrim(d3.MATNR, '0') : '-'}</td>
                <td class="px-2 py-1 border text-center">${escapeJsonString(d3.MAKTX) || '-'}</td>
                <td class="px-2 py-1 border text-center">${d3.PSMNG || '-'}</td>
                <td class="px-2 py-1 border text-center">${d3.WEMNG || '-'}</td>
                <td class="px-2 py-1 border text-center">${d3.MENG2 || '-'}</td>
                <td class="px-2 py-1 border text-center">${formatDate(d3.SSAVD)}</td>
                <td class="px-2 py-1 border text-center">${formatDate(d3.SSSLD)}</td>
            `;

            row.dataset.rowData = JSON.stringify(d3);
            return row;
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('convert-btn')) {
                    const row = e.target.closest('tr');
                    const rowIndex = parseInt(e.target.dataset.rowIndex);
                    const rowData = JSON.parse(row.dataset.rowData);
                    console.log('Convert clicked for row:', rowIndex, rowData);
                    convertPlannedOrderFixed(rowData);
                }
            });
        });
        
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

        function showTData1ByAufnr(aufnr) {
            const container = document.getElementById('additional-data-container');
            const divId = `tdata1-${aufnr}`;
            const existing = document.getElementById(divId);
            if (existing) {
                existing.remove();
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('hidden'));
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
                if (!row.textContent.includes(aufnr)) row.classList.add('hidden');
                else row.classList.remove('hidden');
            });
            const rowsHtml = data.map((t1, i) => `
                <tr class="bg-white">
                    <td class="px-4 py-3 text-center">${i + 1}</td>
                    <td class="px-4 py-3 text-center">${t1.VORNR || '-'}</td>
                    <td class="px-4 py-3 text-center">${t1.STEUS || '-'}</td>
                    <td class="px-4 py-3 text-center">${t1.KTEXT || '-'}</td>
                    <td class="px-4 py-3 text-center">${t1.ARBPL || '-'}</td>
                    <td class="px-4 py-3 text-center">${t1.PV1 || '-'}</td>
                    <td class="px-4 py-3 text-center">${t1.PV2 || '-'}</td>
                    <td class="px-4 py-3 text-center">${t1.PV3 || '-'}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-2 justify-center">
                            <button class="px-2 py-1 text-xs  ont-semibold text-indigo-700 bg-indigo-100 rounded-md hover:bg-indigo-200" onclick="openChangeWcModal('${t1.AUFNR}', '${t1.VORNR}', '${t1.ARBPL || ''}')">Edit WC</button>
                            <button class="px-2 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-md hover:bg-amber-200" onclick="openChangePvModal('${t1.AUFNR}', '${t1.VERID || ''}', '${t1.WERKS || ''}')">Change PV</button>
                        </div>
                    </td>
                </tr>
            `).join('');
            const block = document.createElement('div');
            block.id = divId;
            block.className = 'mt-4';
            block.innerHTML = `
                <h4 class="text-md font-semibold text-gray-800 mb-2">Routing Overview</h4>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="table-auto w-full text-sm">
                        <thead class="bg-purple-50 text-purple-800 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">No.</th>
                                <th class="px-4 py-3">Activity</th>
                                <th class="px-4 py-3">Control Key</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Work Center</th>
                                <th class="px-4 py-3">PV 1</th>
                                <th class="px-4 py-3">PV 2</th>
                                <th class="px-4 py-3">PV 3</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">${rowsHtml}</tbody>
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
                document.querySelectorAll('#tdata3-body tr').forEach(row => row.classList.remove('hidden'));
                togglePaginationDisabled(false);
                return;
            }
            togglePaginationDisabled(true);
            const data = (allTData4ByAufnr && allTData4ByAufnr[aufnr]) ? allTData4ByAufnr[aufnr] : [];
            document.querySelectorAll('#tdata3-body tr').forEach(row => {
                if (!row.textContent.includes(aufnr)) row.classList.add('hidden');
                else row.classList.remove('hidden');
            });
            const ltrim0 = (s) => String(s ?? '').replace(/^0+/, '');
            const routingData = (tdata1ByAufnr && tdata1ByAufnr[aufnr]) ? tdata1ByAufnr[aufnr] : [];
            const vornr = (routingData.length > 0 && routingData[0].VORNR) ? routingData[0].VORNR : '0010';
            const plant = '{{ $plant }}';
            const rowsHtml = data.map((c, i) => `
                <tr class="bg-white">
                    <td class="px-4 py-3 text-center"><input type="checkbox" class="component-select-${aufnr} rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500" data-aufnr="${aufnr}" data-rspos="${c.RSPOS || i}" data-material="${ltrim0(c.MATNR)}" onchange="handleComponentSelect('${aufnr}')"></td>
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
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-md font-semibold text-gray-800">Component List (T_DATA4) — AUFNR: ${sanitize(aufnr)}</h4>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2 hidden" id="bulk-delete-controls-${aufnr}">
                            <button type="button" id="bulk-delete-btn-${aufnr}" class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition" onclick="bulkDeleteComponents('${aufnr}')">Delete Selected (0)</button>
                            <button type="button" class="inline-flex items-center px-3 py-1.5 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition" onclick="clearComponentSelections('${aufnr}')">Clear All</button>
                        </div>
                        <button type="button" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition" onclick="openModalAddComponent('${aufnr}', '${vornr}', '${plant}')">Add Component</button>
                    </div>
                </div>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="table-auto w-full text-sm">
                        <thead class="bg-purple-50 text-purple-800 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3"><input type="checkbox" id="select-all-components-${aufnr}" onchange="toggleSelectAllComponents('${aufnr}')"></th>
                                <th class="px-4 py-3">No.</th>
                                <th class="px-4 py-3">Material</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Req. Qty</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Spec. Procurement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">${rowsHtml.length > 0 ? rowsHtml : `<tr><td colspan="7" class="text-center p-12 text-gray-500">Belum ada komponen. Klik 'Add Component' untuk menambahkan.</td></tr>`}</tbody>
                    </table>
                </div>`;
            container.innerHTML = '';
            container.appendChild(block);
        }

        function handleComponentSelect(aufnr){
            const checkboxes = document.querySelectorAll(`.component-select-${aufnr}`);
            const selected = [...checkboxes].filter(cb => cb.checked);
            const controls = document.getElementById(`bulk-delete-controls-${aufnr}`);
            const btn = document.getElementById(`bulk-delete-btn-${aufnr}`);
            if (selected.length > 0){
                controls.classList.remove('hidden');
                btn.textContent = `Delete Selected (${selected.length})`;
            } else {
                controls.classList.add('hidden');
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
        function bulkDeleteComponents(aufnr){
            const selected = [...document.querySelectorAll(`.component-select-${aufnr}`)].filter(cb => cb.checked);
            if (selected.length === 0) return;
            const payload = selected.map(cb => ({
                rspos: cb.dataset.rspos,
                material: cb.dataset.material,
            }));
            console.log('Bulk delete ->', aufnr, payload);
            // TODO: panggil API delete Anda di sini
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
            bulkControls.classList.toggle('hidden', !hasPLO && !hasPRO);
            convertBtn.classList.toggle('hidden', !hasPLO);
            releaseBtn.classList.toggle('hidden', !hasPRO);
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

        function bulkReleaseOrders() {
            if (selectedPRO.size === 0) return alert('No PRO selected.');
            if (confirm(`Are you sure you want to release ${selectedPRO.size} production orders?`)) {
                alert('Fungsi bulk release belum diimplementasikan di backend.');
            }
        }

                function formatDate(dateString) {


        /**
         * Fungsi baru untuk memfilter baris tabel T_DATA2 berdasarkan input pencarian.
         */
        function filterTData2Table() {
            const input = document.getElementById('tdata2-search');
            const filterText = input.value.toLowerCase();
            const tableBody = document.getElementById('tdata2-tbody');
            
            // ✅ PERBAIKAN: Hanya menargetkan baris data aktual untuk difilter
            const dataRows = Array.from(tableBody.querySelectorAll('tr.t2-row'));
            let visibleRows = 0;

            // Hapus pesan "tidak ada hasil" dari pencarian sebelumnya
            const existingNoResultsRow = tableBody.querySelector('.no-results-row');
            if (existingNoResultsRow) {
                existingNoResultsRow.remove();
            }

            // Loop melalui setiap baris data
            dataRows.forEach(row => {
                const rowText = row.textContent || row.innerText;
                if (rowText.toLowerCase().includes(filterText)) {
                    row.style.display = ""; // Tampilkan baris jika cocok
                    visibleRows++;
                } else {
                    row.style.display = "none"; // Sembunyikan baris jika tidak cocok
                }
            });

            // Jika tidak ada baris yang cocok, tambahkan pesan ke dalam tabel
            if (visibleRows === 0 && dataRows.length > 0) {
                const noResultsRow = tableBody.insertRow(); // Membuat <tr> baru
                noResultsRow.classList.add('no-results-row');
                const cell = noResultsRow.insertCell(); // Membuat <td> baru
                cell.colSpan = 9; // Menggabungkan semua 9 kolom
                cell.textContent = 'Mohon maaf data yang Anda cari tidak ada';
                cell.className = 'px-4 py-12 text-center text-gray-500';
            }
        }
        
        function handleClickTData2Row(key, tr) {
            const idx = Number(tr.dataset.index);
            const t3Container = document.getElementById('tdata3-container');

            if (currentT2Selection &&
                currentT2Selection.key === key &&
                currentT2Selection.index === idx) {

                const isVisible = !t3Container.classList.contains('hidden');
                if (isVisible) {
                    t3Container.classList.add('hidden');       // sembunyikan T_DATA3
                    tr.classList.remove('bg-blue-100');         // lepas highlight baris T_DATA2
                    currentT2Selection = null;                  // reset pilihan
                } else {
                    showTData3ForKey(key);
                    tr.classList.add('bg-blue-100');
                    currentT2Selection = { key, index: idx };
                }
                return;
            }

            const box = document.getElementById('tdata2-section');
            box.querySelectorAll('.t2-row').forEach(r => r.classList.remove('bg-blue-100'));
            tr.classList.add('bg-blue-100');
            currentT2Selection = { key, index: idx };

            showTData3ForKey(key);
        }

        function showTData3ForKey(key){
            const t3Container = document.getElementById('tdata3-container');
            const rows = allTData3[key] || [];
            allRowsData = rows;

            t3CurrentPage = 1; // Selalu reset ke halaman 1 saat data baru dipilih

            if (rows.length) {
                // ... baris selanjutnya tetap sama
                filterByStatus('all'); 
                t3Container.classList.remove('hidden');
            } else {
                const tbody = document.getElementById('tdata3-body');
                tbody.innerHTML = `
                <tr><td colspan="14" class="px-3 py-2 text-center text-gray-500 border">
                    Tidak ada order overview (T_DATA3) untuk item ini.
                </td></tr>`;
                // Kosongkan pagination jika tidak ada data
                document.getElementById('tdata3-pagination').innerHTML = ''; 
                t3Container.classList.remove('hidden');
            }

            if (rows.length) {
                currentFilterName = 'all';
                filterByStatus('all');
                t3Container.classList.remove('hidden');
            } else {
                const tbody = document.getElementById('tdata3-body');
                tbody.innerHTML = `
                <tr><td colspan="14" class="px-3 py-2 text-center text-gray-500 border">
                    Tidak ada order overview (T_DATA3) untuk item ini.
                </td></tr>`;
                t3Container.classList.remove('hidden');
            }
        }
        
        function openSchedule(aufnrEnc) {
            const aufnr = decodeURIComponent(aufnrEnc);
            document.getElementById('scheduleAufnr').value = aufnr;
            document.getElementById('scheduleDate').value = '';
            document.getElementById('scheduleTime').value = '00.00.00';

            const m = document.getElementById('scheduleModal');
            const o = document.getElementById('scheduleOverlay');
            const p = document.getElementById('schedulePanel');
            m.classList.remove('hidden');
            requestAnimationFrame(() => {
                o.classList.remove('opacity-0');
                p.classList.remove('opacity-0','scale-95');
            });
        }

        function closeScheduleModal() {
            const m = document.getElementById('scheduleModal');
            const o = document.getElementById('scheduleOverlay');
            const p = document.getElementById('schedulePanel');
            o.classList.add('opacity-0');
            p.classList.add('opacity-0','scale-95');
            setTimeout(() => m.classList.add('hidden'), 150);
        } 

        function openChangeWcModal(aufnr, vornr, currentWC = '', plant = '') {
            document.getElementById('changeWcAufnr').value = aufnr || '';
            document.getElementById('changeWcVornr').value = vornr || '';
            document.getElementById('changeWcInput').value = '';
            document.getElementById('changeWcInput').placeholder = `${currentWC}`;
            document.getElementById('changeWcCurrent').textContent =
                currentWC ? `Current WC: ${currentWC}` : '';

            // 👇 plant tambahan
            document.getElementById('changeWcPlant').value = plant || '';

            const m = document.getElementById('changeWcModal');
            const o = document.getElementById('changeWcOverlay');
            const p = document.getElementById('changeWcPanel');
            m.classList.remove('hidden');
            requestAnimationFrame(() => {
                o.classList.remove('opacity-0');
                p.classList.remove('opacity-0','scale-95');
            });
        }

        function closeChangeWcModal() {
            const m = document.getElementById('changeWcModal');
            const o = document.getElementById('changeWcOverlay');
            const p = document.getElementById('changeWcPanel');
            o.classList.add('opacity-0');
            p.classList.add('opacity-0','scale-95');
            setTimeout(() => m.classList.add('hidden'), 150);
        }

        // Tutup saat klik overlay
        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'changeWcOverlay') closeChangeWcModal();
        });

        // Tutup saat tekan ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeChangeWcModal();
        });

        if (typeof window.openModalEditWC !== 'function') {
            window.openModalEditWC = (aufnr, vornr, currentWC='') => openChangeWcModal(aufnr, vornr, currentWC);
        }

        function openChangePvModal(aufnr, currentPV = '', plantVal = null) {
            const defaultPlant = @json($plant);
            document.getElementById('changePvAufnr').value = aufnr || '';
            document.getElementById('changePvWerks').value = (plantVal || defaultPlant || '').trim();

            document.getElementById('changePvInput').value = '';
            document.getElementById('changePvInput').placeholder = `${currentPV}`;
            document.getElementById('changePvCurrent').textContent =
                currentPV ? `Current PV: ${currentPV}` : '';

            const m = document.getElementById('changePvModal');
            const o = document.getElementById('changePvOverlay');
            const p = document.getElementById('changePvPanel');
            m.classList.remove('hidden');
            requestAnimationFrame(() => {
                o.classList.remove('opacity-0');
                p.classList.remove('opacity-0', 'scale-95');
            });
        }

        function closeChangePvModal() {
            const m = document.getElementById('changePvModal');
            const o = document.getElementById('changePvOverlay');
            const p = document.getElementById('changePvPanel');
            o.classList.add('opacity-0');
            p.classList.add('opacity-0', 'scale-95');
            setTimeout(() => m.classList.add('hidden'), 150);
        }

        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'changePvOverlay') closeChangePvModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeChangePvModal();
        });

        if (typeof window.openModalChangePV !== 'function') {
            window.openModalChangePV = (aufnr, currentPV='') => openChangePvModal(aufnr, currentPV);
        }

        function openScheduleModal(aufnr) {
            const modal  = document.getElementById('scheduleModal');
            const panel  = document.getElementById('schedulePanel');
            const overlay= document.getElementById('scheduleOverlay');

            document.getElementById('scheduleAufnr').value = aufnr;

            const now = new Date();
            const yyyy = now.getFullYear();
            const mm   = String(now.getMonth()+1).padStart(2,'0');
            const dd   = String(now.getDate()).padStart(2,'0');
            const HH   = String(now.getHours()).padStart(2,'0');
            const MM   = String(now.getMinutes()).padStart(2,'0');
            const SS   = String(now.getSeconds()).padStart(2,'0');

            document.getElementById('scheduleDate').value = `${yyyy}-${mm}-${dd}`;
            document.getElementById('scheduleTime').value = `${HH}.${MM}.${SS}`;

            modal.classList.remove('hidden');
            requestAnimationFrame(() => {
                overlay.classList.remove('opacity-0');
                panel.classList.remove('opacity-0','scale-95');
            });
        }

        // Auto-format jam input
        (function() {
            const el = document.getElementById('scheduleTime');
            if (!el) return;

            el.addEventListener('input', (e) => {
                let v = e.target.value.replace(/[^\d]/g,'');
                if (v.length > 6) v = v.slice(0,6);
                if (v.length >= 5)       e.target.value = v.replace(/(\d{2})(\d{2})(\d{1,2})/, '$1.$2.$3');
                else if (v.length >= 3)  e.target.value = v.replace(/(\d{2})(\d{1,2})/, '$1.$2');
                else                     e.target.value = v;
            });

            const form = document.getElementById('scheduleForm');
            form.addEventListener('submit', () => {
                el.value = el.value.trim();
            });
        })();

        function startGlobalLoading() {
            if (window.loading?.show) return window.loading.show();
            if (window.showLoading)   return window.showLoading();
            if (window.toggleLoading) return window.toggleLoading(true);

            const el = document.getElementById('global-loading');
            if (el) el.classList.remove('hidden', 'opacity-0');
        }

        function stopGlobalLoading() {
            if (window.loading?.hide) return window.loading.hide();
            if (window.hideLoading)   return window.hideLoading();
            if (window.toggleLoading) return window.toggleLoading(false);

            const el = document.getElementById('global-loading');
            if (el) el.classList.add('hidden', 'opacity-0');
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

        function getPlantFromCurrentData(){
            // Sesuaikan dengan sumber data Anda
            // contoh: return window.currentPlant || document.getElementById('plantHidden').value;
            return (window.currentPlant || '');
        }

        // --- LOGIKA MODAL ADD COMPONENT ---
        function openModalAddComponent(aufnr, vornr) {
            console.log("Membuka modal dengan data:", { aufnr, vornr }); // Untuk debugging

            // Set values di form (hidden inputs)
            document.getElementById('add-component-aufnr').value = aufnr;
            document.getElementById('add-component-vornr').value = vornr;
            
            // Set nilai yang akan ditampilkan (readonly inputs)
            document.getElementById('display-aufnr').value = aufnr;
            document.getElementById('display-vornr').value = vornr;
            
            // Reset input fields dari sesi sebelumnya
            document.getElementById('add-component-matnr').value = '';
            document.getElementById('add-component-bdmng').value = '';
            document.getElementById('add-component-meins').value = '';
            document.getElementById('add-component-lgort').value = '';
            document.getElementById('add-component-plant-select').value = ''; // Reset pilihan plant
            
            // Tampilkan modal
            const modal = document.getElementById('modal-add-component');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModalAddComponent() {
            const modal = document.getElementById('modal-add-component');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Tambahkan event listener saat DOM siap
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('add-component-form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('add-component-submit-btn');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Menambahkan...';
                
                const formData = new FormData(form);

                const formDataObj = Object.fromEntries(formData.entries());
                console.log('Data yang akan dikirim ke Controller:', formDataObj);

                const aufnr = formData.get('iv_aufnr'); // Ambil aufnr untuk update tabel
                
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        toast('success', data.message || 'Komponen berhasil ditambahkan!');
                        closeModalAddComponent();
                        
                        // Panggil fungsi untuk me-render ulang tabel T_DATA4
                        if (data.components) {
                            updateComponentTable(aufnr, data.components);
                        }
                        
                    } else {
                        throw new Error(data.message || 'Gagal menambahkan komponen.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultSwal({ success: false, title: 'Operasi Gagal', text: error.message });
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    stopGlobalLoading();
                });
            });
        });

        // Fungsi baru untuk me-render ulang tabel komponen (T_DATA4)
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

        async function bulkDeleteComponents(aufnr) {
            const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
            
            if (selectedCheckboxes.length === 0) {
                toast('info', 'Tidak ada komponen yang dipilih.');
                return;
            }

            const { isConfirmed } = await confirmSwal({
                title: `Hapus ${selectedCheckboxes.length} Komponen?`,
                text: 'Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                confirmText: 'Ya, Hapus'
            });

            if (!isConfirmed) {
                return;
            }

            const componentsToDelete = Array.from(selectedCheckboxes).map(cb => ({
                rspos: cb.dataset.rspos,
                material: cb.dataset.material
            }));

            const payload = {
                aufnr: aufnr,
                components: componentsToDelete
            };

            startGlobalLoading(); // Spinner dimulai di sini

            try {
                const response = await fetch('{{ route('component.delete.bulk') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Terjadi kesalahan pada server.');
                }

                // PERBAIKAN: Hentikan loading SEBELUM menampilkan notifikasi
                stopGlobalLoading();

                let resultHtml = `<p>${result.message}</p>`;
                if (result.errors && result.errors.length > 0) {
                    resultHtml += '<ul class="text-left mt-2 pl-5 list-disc">';
                    result.errors.forEach(err => {
                        resultHtml += `<li>${err}</li>`;
                    });
                    resultHtml += '</ul>';
                }

                await resultSwal({
                    success: result.success,
                    title: result.success ? 'Berhasil' : 'Selesai dengan Kegagalan',
                    html: resultHtml
                });
                
                location.reload();

            } catch (error) {
                // PERBAIKAN: Hentikan loading SEBELUM menampilkan notifikasi error
                stopGlobalLoading(); 

                console.error('Error saat menghapus komponen:', error);
                await resultSwal({
                    success: false,
                    title: 'Operasi Gagal',
                    text: error.message
                });
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

        document.addEventListener('DOMContentLoaded', () => {
            @if ($errors->has('msg'))
                // Tampilkan error utama
                window.resultSwal({
                    success: false,
                    title: 'Gagal Menjadwalkan',
                    html: `{!! nl2br(e($errors->first('msg'))) !!}`
                });
            @elseif ($errors->any())
                // Fallback kalau ada error lainnya
                window.resultSwal({
                    success: false,
                    title: 'Gagal',
                    html: `{!! nl2br(e(implode("\n", $errors->all()))) !!}`
                });
            @endif

            @if (session('success'))
                window.resultSwal({
                    success: true,
                    title: 'Berhasil',
                    text: @json(session('success'))
                });
            @endif

            @if (session('sap_raw'))
                // Opsional: log detail response SAP di console untuk debugging
                console.log('SAP RAW:', @json(session('sap_raw')));
                // Atau tampilkan ringkasannya:
                // const ret = (@json(session('sap_raw')) || {}).sap_return || [];
                // window.toast('info', 'SAP Return', `${ret.length} baris`);
            @endif
        });
    
    </script>
    @endpush
</x-layouts.app>