<x-layouts.app>
    <div class="max-w-screen-2xl mx-auto">
        {{-- Header Halaman (Tetap Rapi) --}}
        <div class="bg-white p-6 rounded-xl border border-gray-200 mb-8">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Kode Plant: {{ $plant }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="font-medium text-gray-600">Nama Bagian:</span> {{ $bagian }} |
                        <span class="font-medium text-gray-600">Kategori:</span> {{ $categories }}
                    </p>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <a href="{{ route('detail.data2', $plant) }}"
                       @click.prevent="isLoading = true; setTimeout(() => { window.location.href = $el.href }, 150)"
                       class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4 20h5v-5M20 4h-5v5"></path></svg>
                        Sync
                    </a>
                    <a href="#" onclick="hideAllDetails(); return false;" class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition">
                        Hide All
                    </a>
                    <a href="{{ route('dashboard.show', $plant) }}"
                       @click.prevent="isLoading = true; setTimeout(() => { window.location.href = $el.href }, 150)"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                            &larr; Back To Dashboard
                    </a>
                </div>
            </div>
        </div>
        <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200 w-full min-w-0">
            <div class="p-6 text-gray-900">
                {{-- Container untuk Tabel Utama dan Paginasi --}}
                <div id="outstanding-order-container">
                    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                        <h3 class="text-lg font-semibold text-gray-800">SALES ORDER TABLE</h3>
                        <form method="GET" class="flex items-center w-full sm:w-auto sm:max-w-xs">
                            <div class="relative flex-grow">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" id="searchInput">
                                <div class="absolute top-0 left-0 inline-flex items-center p-2.5">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full table-auto text-sm text-left whitespace-nowrap">
                            <thead class="bg-purple-50 text-purple-800 font-semibold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-center w-16">No.</th>
                                    <th class="px-4 py-3">Buyer Name</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($tdata as $index => $item)
                                    @php
                                    $key = ($item->KDAUF ?? '') . '-' . ($item->KDPOS ?? '');
                                    @endphp
                                    <tr class="hover:bg-purple-50 cursor-pointer" data-key="{{ $key }}" onclick="openSalesItem(this)">
                                        <td class="px-4 py-3 text-center">{{ $tdata->firstItem() + $index }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $item->NAME1 ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-12 text-center text-gray-500">Tidak ada data ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        {{ $tdata->appends(['search' => $search])->links() }}
                    </div>
                </div>

                <div id="tdata2-section" class="mt-8 hidden"></div>

                <div id="tdata3-container" class="mt-8 hidden">
                    <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
                        <h3 class="text-lg font-semibold text-gray-800">Order Overview</h3>
                        <div class="flex items-center gap-2">
                            <div class="flex bg-gray-100 rounded-lg p-1 space-x-1" id="status-filter">
                                <button id="filter-all" class="px-3 py-1.5 rounded-md text-sm font-medium bg-white text-gray-900 shadow-sm" onclick="filterByStatus(this, 'all')">All</button>
                                <button id="filter-plo" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-600" onclick="filterByStatus(this, 'plo')">PLO</button>
                                <button id="filter-crtd" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-600" onclick="filterByStatus(this, 'crtd')">PRO (CRTD)</button>
                                <button id="filter-released" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-600" onclick="filterByStatus(this, 'released')">PRO (Released)</button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="bulk-controls" class="flex items-center gap-2 mb-4 hidden">
                        <button id="bulk-convert-btn" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition hidden" onclick="bulkConvertPlannedOrders()">Convert Selected PLO</button>
                        <button id="bulk-release-btn" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition hidden" onclick="bulkReleaseOrders()">Release Selected PRO</button>
                        <button class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition" onclick="clearAllSelections()">Clear All</button>
                    </div>
                    
                    {{-- Struktur ini sudah benar, tidak perlu diubah --}}
                    <div class="overflow-x-auto border rounded-lg">
                        <table id="tdata3-table" class="min-w-full table-auto text-sm text-left whitespace-nowrap">
                            <thead class="bg-purple-50 text-purple-800 font-semibold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-center"><input type="checkbox" id="select-all" onchange="toggleSelectAll()"></th>
                                    <th class="px-4 py-3 text-center">No.</th>
                                    <th class="px-4 py-3 text-center">PRO</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                    <th class="px-4 py-3 text-center">MRP</th>
                                    <th class="px-4 py-3 text-center">Material</th>
                                    <th class="px-4 py-3 text-center">Description</th>
                                    <th class="px-4 py-3 text-center">Qty Order</th>
                                    <th class="px-4 py-3 text-center">Qty GR</th>
                                    <th class="px-4 py-3 text-center">Outs GR</th>
                                    <th class="px-4 py-3 text-center">Start Date</th>
                                    <th class="px-4 py-3 text-center">Finish Date</th>
                                </tr>
                            </thead>
                            <tbody id="tdata3-body" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                    
                    <div id="tdata3-pagination" class="mt-4 flex justify-center items-center gap-2"></div>
                </div>

                <div id="additional-data-container" class="mt-4"></div>
            </div>
        </div>
    </div>
    @include('Admin.add-component-modal')
    {{-- ========= RESULT MODAL (Tailwind) ========= --}}
    <div id="resultModal" class="fixed inset-0 hidden z-50">
        <div id="resultOverlay" class="absolute inset-0 bg-black/40 opacity-0 transition-opacity"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div id="resultPanel"
                class="w-full max-w-xl origin-center transform rounded-2xl bg-white shadow-2xl opacity-0 scale-95 transition-all">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h3 class="text-lg font-semibold">Production Order</h3>
                <button id="resultClose" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">✕</button>
            </div>

            <div id="singleView" class="px-6 py-5 space-y-4">
                <div>
                <div class="text-sm text-gray-500">Plant</div>
                <div id="plantValue" class="mt-1 text-base font-medium text-gray-900">-</div>
                </div>
                <div>
                <div class="text-sm text-gray-500">Production Order</div>
                <div id="poList" class="mt-1 flex flex-wrap gap-2">
                    </div>
                </div>
            </div>

            <div id="batchView" class="hidden px-6 py-5">
                <div class="text-sm text-gray-500 mb-2">Converted Orders</div>
                <div class="overflow-auto rounded-xl border max-h-80">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Planned Order</th>
                        <th class="px-4 py-2">Plant</th>
                        <th class="px-4 py-2">Production Order</th>
                    </tr>
                    </thead>
                    <tbody id="batchTbody" class="divide-y"></tbody>
                </table>
                </div>
            </div>
            <div class="border-t px-6 py-4 flex justify-end">
                <button id="resultOk" class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">OK</button>
            </div>
            </div>
        </div>
    </div>

    <div id="scheduleModal" class="fixed inset-0 z-50 hidden">
        <div id="scheduleOverlay" class="fixed inset-0 bg-black/30 backdrop-blur-sm opacity-0 transition-opacity"></div>

        <div id="scheduleContainer" class="absolute inset-0 flex items-center justify-center p-4">
            <div id="schedulePanel"
                class="w-full max-w-md origin-center transform rounded-2xl bg-white shadow-2xl opacity-0 scale-95 transition-all">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h3 class="text-lg font-semibold">Schedule Production Order</h3>
                <button type="button" onclick="closeScheduleModal()"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">✕</button>
            </div>

            <form action="{{ route('reschedule.store') }}" method="POST" class="px-6 py-5 space-y-4" id="scheduleForm">
                @csrf
                <input type="hidden" name="aufnr" id="scheduleAufnr">

                <div>
                <label class="text-sm text-gray-600">Tanggal</label>
                <input type="date" name="date" id="scheduleDate" required
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                <label class="text-sm text-gray-600">Jam (HH.MM.SS)</label>
                <input type="text" name="time" id="scheduleTime" placeholder="00.00.00" required
                        pattern="^\d{2}[\.:]\d{2}[\.:]\d{2}$" inputmode="numeric" autocomplete="off"
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Format 24 jam, contoh: 13.30.00</p>
                </div>

                <div class="border-t px-0 pt-4 flex justify-end gap-2">
                <button type="button" onclick="closeScheduleModal()"
                        class="rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-100">Batal</button>
                <button type="submit"
                        class="rounded-lg bg-green-600 px-4 py-2 text-white hover:bg-green-700">Simpan</button>
                </div>
            </form>
            </div>
        </div>
        </div>

    <div id="changeWcModal" class="fixed inset-0 z-50 hidden">
        <div id="changeWcOverlay" class="fixed inset-0 bg-black/30 backdrop-blur-sm opacity-0 transition-opacity"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div id="changeWcPanel"
                class="w-full max-w-md origin-center transform rounded-2xl bg-white shadow-2xl opacity-0 scale-95 transition-all">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h3 class="text-lg font-semibold">Change Work Center</h3>
                <button type="button" onclick="closeChangeWcModal()" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">✕</button>
            </div>

            <form action="{{ route('change-wc') }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" id="changeWcAufnr" name="aufnr">
                <input type="hidden" id="changeWcVornr" name="vornr">
                <input type="hidden" id="changeWcSequ"  name="sequ" value="0"><div>
                <label for="changeWcInput" class="text-sm text-gray-600">Work Center</label>
                <input type="text" id="changeWcInput" name="work_center" placeholder="Masukkan Work Center baru"
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500" required>
                <p id="changeWcCurrent" class="text-xs text-gray-500 mt-1"></p>
                </div>

                <div class="border-t pt-4 flex justify-end gap-2">
                <button type="button" onclick="closeChangeWcModal()" class="rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-100">Tutup</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Save</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    <div id="changePvModal" class="fixed inset-0 z-50 hidden">
    <div id="changePvOverlay" class="fixed inset-0 bg-black/30 backdrop-blur-sm opacity-0 transition-opacity"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div id="changePvPanel"
                class="w-full max-w-md origin-center transform rounded-2xl bg-white shadow-2xl opacity-0 scale-95 transition-all">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h3 class="text-lg font-semibold">Change Production Version (PV)</h3>
                <button type="button" onclick="closeChangePvModal()"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">✕</button>
            </div>

            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="changePvAufnr">
                <input type="hidden" id="changePvWerks">

                <div>
                    <label for="changePvInput" class="text-sm text-gray-600">Production Version (PV)</label>
                    
                    <select id="changePvInput"
                            class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                        <option value="">-- Pilih Production Version --</option>
                        <option value="0001">PV 0001</option>
                        <option value="0002">PV 0002</option>
                        <option value="0003">PV 0003</option>
                    </select>
                    
                    <p id="changePvCurrent" class="text-xs text-gray-500 mt-1"></p>
                </div>
            </div>

            <div class="border-t px-6 py-4 flex justify-end gap-2">
                <button type="button" onclick="closeChangePvModal()"
                        class="rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-100">Tutup</button>
                <button type="button" id="changePvSubmitBtn"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                        onclick="submitChangePv()">Simpan</button>
            </div>
        </div>
    </div>
    </div>
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function () {
      const overlay = document.getElementById('global-loading');
      if (!overlay) return;

      const show = () => overlay.classList.remove('hidden');
      const hide = () => overlay.classList.add('hidden');

      // A) Klik pada elemen dengan [data-loading]
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-loading]');
        if (!trigger) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (e.button !== 0) return;
        if (trigger.getAttribute('target') === '_blank') return;

        if (trigger.tagName === 'BUTTON') {
          trigger.disabled = true;
          trigger.classList.add('opacity-70', 'pointer-events-none');
        }
        show();
      }, { passive: true });

      // B) Submit form apa pun
      document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form.matches('form')) return;

        const btn = form.querySelector('button[type="submit"][data-loading]');
        if (btn) {
          btn.disabled = true;
          btn.classList.add('opacity-70', 'pointer-events-none');
        }
        show();
      }, true);

      // C) Pindah halaman (redirect server-side)
      window.addEventListener('beforeunload', show);

      // Hooks optional untuk AJAX manual:
      window.addEventListener('loading:show', show);
      window.addEventListener('loading:hide', hide);
    })();
    </script>

    <script>
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
      };
    </script>
    
    <script>
        // Variabel global untuk menyimpan state
        let currentSelectedRow = null;
        let currentActiveKey = null; // Bisa AUFNR atau PLNUM
        let selectedPLO = new Set();
        let selectedPRO = new Set();
        let allRowsData = [];
        let refreshPlant = null;
        let refreshOrders = []; // array AUFNR (12 digit)
        let currentSOKey = null;
        let currentFilterName = 'all';
        let currentT2Selection = null;
        let t3CurrentPage = 1;
        const t3ItemsPerPage = 10;

        function padAufnr(v){ const s=String(v||''); return s.length>=12 ? s : s.padStart(12,'0'); }
        const RELEASE_ORDER_URL = @json(route('release.order.direct', ['aufnr' => '__AUFNR__']));

        const allTData2 = @json($allTData2, JSON_HEX_TAG);
        const allTData3 = @json($allTData3, JSON_HEX_TAG);
        const allTData1 = @json($allTData1, JSON_HEX_TAG);
        const allTData4ByAufnr = @json($allTData4ByAufnr, JSON_HEX_TAG);
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

        function sanitize(str){ const d=document.createElement('div'); d.textContent = String(str||''); return d.innerHTML; }

        function openSalesItem(tr) {
            const key = tr.dataset.key; // "KDAUF-KDPOS"
            const t3Container = document.getElementById('tdata3-container');
            const t2Container = document.getElementById('outstanding-order-container');

            // klik baris yang sama -> tutup semua detail
            if (currentSelectedRow === tr) {
                hideAllDetails();
                return;
            }

            // reset + sembunyikan T_DATA3 dulu
            hideAllDetails();
            t3Container.classList.add('hidden');

            // ✅ PERBAIKAN 2: BLOK JAVASCRIPT UNTUK MENGATUR MIN-WIDTH DIHAPUS TOTAL
            /*
            const referenceTable = document.querySelector('#outstanding-order-container table');
            const targetTable = document.getElementById('tdata3-table');

            if (referenceTable && targetTable) {
                const referenceWidth = referenceTable.scrollWidth;
                targetTable.style.minWidth = `${referenceWidth}px`;
            }
            */

            currentSelectedRow = tr;

            // tampilkan hanya baris yang diklik, sembunyikan yang lain + header + pager
            document.querySelectorAll('#outstanding-order-container tbody tr')
                .forEach(row => { if (row !== tr) row.classList.add('hidden'); });
            const headerRow = t2Container.querySelector('.flex.justify-between.items-center.mb-4');
            if (headerRow) headerRow.classList.add('hidden');
            const pager = t2Container.querySelector('.mt-6');
            if (pager) pager.classList.add('hidden');

            // render T_DATA2 dulu; T_DATA3 menunggu klik baris T_DATA2
            renderTData2Table(key);
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
                            <button class="px-2 py-1 text-xs font-semibold text-indigo-700 bg-indigo-100 rounded-md hover:bg-indigo-200" onclick="openChangeWcModal('${t1.AUFNR}', '${t1.VORNR}', '${t1.ARBPL || ''}')">Edit WC</button>
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

        function renderTData2Table(key){
            const box = document.getElementById('tdata2-section');
            box.innerHTML = '';
            box.classList.add('hidden');

            const rows = allTData2[key] || [];
            if (!rows.length){
                box.innerHTML = `
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Outstanding Order</h4>
                <div class="bg-gray-50 rounded-xl border p-12 text-center">
                    <p class="text-gray-500 text-sm">Tidak ada data Outstanding Order untuk item ini.</p>
                </div>`;
                box.classList.remove('hidden');
                return;
            }

            // ✅ PERBAIKAN: Menambahkan header flexbox dengan input pencarian
            let html = `
                <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                    <h4 class="text-lg font-semibold text-gray-800">Outstanding Order</h4>
                    <div class="relative w-full sm:w-auto sm:max-w-xs">
                        <input 
                            type="text" 
                            id="tdata2-search" 
                            oninput="filterTData2Table()" 
                            placeholder="Cari di tabel ini..." 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                        <div class="absolute top-0 left-0 inline-flex items-center p-2.5 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full table-auto text-sm text-left whitespace-nowrap">
                    <thead class="bg-purple-50 text-purple-800 font-semibold uppercase text-xs">
                        <tr>
                        <th class="px-4 py-3 text-center">No.</th>
                        <th class="px-4 py-3 text-center">Order</th>
                        <th class="px-4 py-3 text-center">Item</th>
                        <th class="px-4 py-3 text-center">Material FG</th>
                        <th class="px-4 py-3 text-center">Description Material</th>
                        <th class="px-4 py-3 text-center">PO Date</th>
                        <th class="px-4 py-3 text-center">Total PLO</th>
                        <th class="px-4 py-3 text-center">PRO (CRTD)</th>
                        <th class="px-4 py-3 text-center">PRO (Released)</th>
                        </tr>
                    </thead>
                    {{-- ✅ PERBAIKAN: Menambahkan ID unik ke tbody untuk filtering --}}
                    <tbody id="tdata2-tbody" class="divide-y divide-gray-200 bg-white">`;

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
                html += `
                    <tr class="t2-row hover:bg-purple-50 cursor-pointer" data-key="${soKey}" data-index="${i}">
                        <td class="px-4 py-3 text-center">${i + 1}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">${sanitize(r.KDAUF || '-')}</td>
                        <td class="px-4 py-3">${(r.KDPOS || '').toString().replace(/^0+/, '')}</td>
                        <td class="px-4 py-3">${(r.MATFG || '').toString().replace(/^0+/, '') || '-'}</td>
                        <td class="px-4 py-3">${sanitize(r.MAKFG || '-')}</td>
                        <td class="px-4 py-3">${formatSapYmd(r.EDATU)}</td>
                        <td class="px-4 py-3 text-center">${ploCount}</td>
                        <td class="px-4 py-3 text-center">${proCrt}</td>
                        <td class="px-4 py-3 text-center">${proRel}</td>
                    </tr>`;
            });
            html += `</tbody></table></div><p class="mt-3 text-xs text-gray-500">Klik salah satu baris untuk melihat ORDER OVERVIEW TABLE.</p>`;
            box.innerHTML = html;
            box.classList.remove('hidden');
            box.querySelectorAll('.t2-row').forEach(tr => {
                tr.addEventListener('click', () => handleClickTData2Row(tr.dataset.key, tr));
            });
        }

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

        function openChangeWcModal(aufnr, vornr, currentWC = '') {
            document.getElementById('changeWcAufnr').value = aufnr || '';
            document.getElementById('changeWcVornr').value = vornr || '';
            document.getElementById('changeWcInput').value = '';
            document.getElementById('changeWcInput').placeholder = `${currentWC}`;
            document.getElementById('changeWcCurrent').textContent =
                currentWC ? `Current WC: ${currentWC}` : '';

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