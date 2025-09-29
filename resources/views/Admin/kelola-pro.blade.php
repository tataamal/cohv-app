<x-layouts.app title="Kelola Work Center">

    @push('styles')
    <style>
        /* CSS Kustom untuk Tampilan Profesional yang Lebih Lapang */
        body {
            background-color: #f8f9fa;
        }

        .card, .btn, .form-select {
            transition: all 0.25s ease-in-out;
        }
        
        .card {
            border-radius: 0.75rem;
            border: 1px solid var(--bs-border-color-translucent);
        }

        /* Styling untuk tabel dengan header sticky */
        .table-container-scroll {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: var(--bs-card-inner-border-radius);
        }
        .table-container-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--bs-tertiary-bg);
            box-shadow: inset 0 -2px 0 var(--bs-border-color);
        }

        /* Feedback visual untuk baris yang dapat di-drag */
        .pro-row {
            cursor: grab;
        }
        .pro-row:hover {
            background-color: var(--bs-primary-bg-subtle);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .pro-row.table-active {
            background-color: var(--bs-info-bg-subtle) !important;
            border-left: 4px solid var(--bs-info);
        }

        /* Styling untuk Loading Overlay */
        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(8px);
            z-index: 9999;
        }
        .loader { width: 108px; display: flex; justify-content: space-between; }
        .loader::after, .loader::before {
            content: ''; display: inline-block; width: 48px; height: 48px; background-color: #FFF;
            background-image: radial-gradient(circle 14px, #0d161b 100%, transparent 0);
            background-repeat: no-repeat; border-radius: 50%;
            animation: eyeMove 10s infinite, blink 10s infinite;
        }
        @keyframes eyeMove {
            0%, 10% { background-position: 0px 0px } 13%, 40% { background-position: -15px 0px }
            43%, 70% { background-position: 15px 0px } 73%, 90% { background-position: 0px 15px }
            93%, 100% { background-position: 0px 0px }
        }
        @keyframes blink {
            0%, 10%, 12%, 20%, 22%, 40%, 42%, 60%, 62%, 70%, 72%, 90%, 92%, 98%, 100% { height: 48px }
            11%, 21%, 41%, 61%, 71%, 91%, 99% { height: 18px }
        }

        /* Styling untuk notifikasi Toast (untuk error frontend) */
        .toast-notification {
            position: fixed; top: 1.5rem; right: 1.5rem; z-index: 10000; padding: 1rem 1.5rem;
            border-radius: 0.5rem; color: #fff; background-color: var(--bs-danger);
            box-shadow: var(--bs-box-shadow-lg); opacity: 0; transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .toast-notification.show { opacity: 1; transform: translateY(0); }
        
        /* Styling untuk Panel Aksi Bulk */
        #bulk-action-bar {
            background-color: var(--bs-primary-bg-subtle);
            border: 1px solid var(--bs-primary-border-subtle);
        }
        #bulk-drag-handle {
            cursor: grab;
            color: var(--bs-primary);
        }
        #bulk-drag-handle:active {
            cursor: grabbing;
        }
    </style>
    @endpush

    <div class="container-fluid p-3 p-lg-4">
        {{-- Komponen Notifikasi Global untuk menampilkan pesan dari controller --}}
        <x-notification.notification />

        {{-- Loading Overlay --}}
        <div id="loading-overlay" class="loading-overlay d-none d-flex justify-content-center align-items-center flex-column text-center">
            <div class="loader mb-4"></div>
            <h2 class="h4 fw-semibold text-dark">Memproses Pemindahan...</h2>
        </div>
        
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold text-dark">Kelola Work Center</h1>
                <p class="mt-1 text-muted">Visualisasikan dan pindahkan Production Order (PRO) antar Work Center.</p>
            </div>
            <div>
                <a href="{{ route('manufaktur.dashboard.show', ['kode' => $kode]) }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                            <div>
                                <h5 class="card-title fw-bold text-dark mb-0">Peta Kepadatan Work Center</h5>
                                <p class="card-subtitle text-muted small mt-1">Pilih baris PRO untuk filter, atau drag & drop untuk memindahkan.</p>
                            </div>
                            <div class="d-none d-md-flex flex-wrap gap-3 mt-2 mt-sm-0" id="legend-content">
                                <span class="d-flex align-items-center small text-muted"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #0d6efd;"></span>WC Saat Ini</span>
                                <span class="d-flex align-items-center small text-muted"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #198754;"></span>Compatible</span>
                                <span class="d-flex align-items-center small text-muted"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #ffc107;"></span>With Condition</span>
                                <span class="d-flex align-items-center small text-muted"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #6c757d;"></span>Not Compatible</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 position-relative">
                        <button id="legend-popover-btn" class="btn btn-sm btn-outline-secondary rounded-circle position-absolute top-0 end-0 m-3 d-md-none" type="button" data-bs-toggle="popover" data-bs-title="Legenda Chart" data-bs-html="true">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div style="height: 22rem;">
                            <canvas id="wcChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 me-3 fs-6">Daftar PRO di: <span class="text-primary fw-bold">{{ $workCenter }}</span></h5>
                            <button class="btn btn-sm btn-outline-info rounded-circle" data-bs-toggle="modal" data-bs-target="#petunjukModal" title="Lihat Petunjuk" aria-label="Lihat Petunjuk">
                                <i class="fa-solid fa-question"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center w-100 w-md-auto">
                            <select class="form-select form-select-sm me-2" id="wcQuickNavSelect" aria-label="Navigasi cepat Work Center">
                                @foreach($allWcs as $wc_item)
                                    <option value="{{ $wc_item->ARBPL }}" {{ $wc_item->ARBPL == $workCenter ? 'selected' : '' }}>
                                        {{ $wc_item->ARBPL }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary flex-shrink-0" id="wcQuickNavBtn">Pindah</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3">
                            <input type="search" id="proSearchInput" class="form-control" placeholder="🔍 Cari berdasarkan Kode PRO (AUFNR)...">
                        </div>

                        <div id="bulk-action-bar" class="d-none align-items-center justify-content-between p-2 mx-3 mb-3 rounded-3">
                            <div class="d-flex align-items-center">
                                <span id="bulk-drag-handle" class="px-2" draggable="true" title="Drag to move selected items">
                                    <i class="fa-solid fa-grip-vertical fs-5"></i>
                                </span>
                                <span class="fw-semibold text-primary" id="selection-count">0 PROs selected</span>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearBulkSelection()">Clear Selection</button>
                        </div>
                        
                        <div class="table-container-scroll">
                            <table class="table table-hover table-striped mb-0 table-sticky align-middle">
                                <thead>
                                    <tr class="small text-uppercase">
                                        <th class="text-center" style="width: 50px;"><input class="form-check-input" type="checkbox" id="select-all-pro" title="Select all visible"></th>
                                        <th class="text-center" scope="col">No</th>
                                        <th class="text-center" scope="col">Kode PRO</th>
                                        <th class="text-center" scope="col">SO</th>
                                        <th class="text-center" scope="col">SO. Item</th>
                                        <th class="text-center" scope="col">Status</th>
                                        <th class="text-center" scope="col">KD. Workcenter</th>
                                        <th class="text-center" scope="col">Material Description</th>
                                        <th class="text-center" scope="col">Operation Key</th>
                                    </tr>
                                </thead>
                                <tbody id="proTableBody">
                                    @forelse ($pros as $pro)
                                        <tr class="pro-row" draggable="true" 
                                            data-pro-code="{{ $pro->AUFNR }}" 
                                            data-wc-asal="{{ $pro->ARBPL }}"
                                            data-oper="{{ $pro->VORNR }}"
                                            data-pwwrk="{{ $pro->PWWRK }}">
                                            <td class="text-center"><input class="form-check-input pro-select-checkbox" type="checkbox"></td>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="text-center fw-bold">{{ $pro->AUFNR }}</td>
                                            <td class="text-center">{{ $pro->KDAUF }}</td>
                                            <td class="text-center">{{ $pro->KDPOS }}</td>
                                            <td class="text-center">
                                                <span class="badge
                                                    @switch($pro->STATS)
                                                        @case('REL')
                                                            bg-success
                                                            @break
                                                        @case('CRTD')
                                                            bg-primary
                                                            @break
                                                        @default
                                                            bg-secondary
                                                    @endswitch
                                                ">
                                                    {{ $pro->STATS }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ $pro->ARBPL }}</td>
                                            <td class="text-center">{{ $pro->MAKTX }}</td>
                                            <td class="text-center">{{ $pro->STEUS }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">Tidak ada data PRO di Work Center ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UNTUK KONFIRMASI PEMINDAHAN --}}
    <div class="modal fade" id="changeWcModal" tabindex="-1" aria-labelledby="changeWcModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="changeWcForm" method="POST" action="">
                    @csrf
                    {{-- Input untuk single move --}}
                    <input type="hidden" name="aufnr" id="formAufnr">
                    <input type="hidden" name="vornr" id="formVornr">
                    <input type="hidden" name="pwwrk" id="formPwwrk">
                    <input type="hidden" name="plant" id="formPlant">
                    <input type="hidden" name="work_center_tujuan" id="formWcTujuan">
                    
                    {{-- Input untuk bulk move --}}
                    <input type="hidden" name="bulk_pros" id="formBulkPros">

                    <div class="modal-header">
                        <h5 class="modal-title" id="changeWcModalLabel">Konfirmasi Pindah Work Center</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="single-move-content">
                            Anda akan memindahkan PRO <strong id="proCodeWc"></strong> dari WC <strong id="wcAsalWc"></strong> ke WC <strong id="wcTujuanWc"></strong>.
                        </div>
                        <div id="bulk-move-content" class="d-none">
                            Anda akan memindahkan <strong id="bulk-pro-count"></strong> PRO ke WC <strong id="bulk-wcTujuanWc"></strong>.
                            <p class="mt-2">Daftar PRO:</p>
                            <div id="pro-list-modal" class="list-group" style="max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>
                        <p class="mt-3">Apakah Anda yakin ingin melanjutkan?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Ya, Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('components.modals.petunjuk-penggunaan-modal')

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification'; toast.textContent = message; document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }

        const popoverTriggerEl = document.getElementById('legend-popover-btn');
        const legendContentEl = document.getElementById('legend-content');
        if (popoverTriggerEl && legendContentEl) {
            new bootstrap.Popover(popoverTriggerEl, {
                content: `<div class="d-flex flex-column gap-2">${legendContentEl.innerHTML}</div>`,
                html: true, sanitize: false, placement: 'bottom'
            });
        }

        const ctx = document.getElementById('wcChart').getContext('2d');
        const originalChartLabels = @json($chartLabels ?? []);
        const originalChartDataPro = @json($chartProData ?? []);
        const originalChartDataCapacity = @json($chartCapacityData ?? []);
        const compatibilities = @json($compatibilities ?? (object)[]);
        const plantKode = @json($kode ?? '');
        const wcDescriptionMap = @json($wcDescriptionMap);

        const defaultColor = 'rgba(13, 110, 253, 0.7)';
        const compatibleColor = 'rgba(25, 135, 84, 0.7)';
        const conditionalColor = 'rgba(255, 193, 7, 0.7)';
        const proColor = 'rgba(102, 16, 242, 0.7)';
        const capacityColor = 'rgba(253, 126, 20, 0.7)';

        const wcChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: originalChartLabels,
                datasets: [
                    { label: 'Jumlah PRO', data: originalChartDataPro, backgroundColor: proColor, borderWidth: 1, borderColor: 'rgba(255,255,255,0.5)', borderRadius: 4 },
                    { label: 'Jumlah Capacity', data: originalChartDataCapacity, backgroundColor: capacityColor, borderWidth: 1, borderColor: 'rgba(255,255,255,0.5)', borderRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { title: (items) => wcDescriptionMap[items[0].label] || items[0].label, label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')} ${(ctx.dataset.label.includes('PRO')?'PRO':'Jam')}` } } } }
        });

        let activeWcAsal = null;
        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target.matches('.pro-select-checkbox')) { return; }
                const wcAsal = this.dataset.wcAsal;
                const allRows = document.querySelectorAll('.pro-row');
                if (wcAsal === activeWcAsal) {
                    wcChart.data.labels = originalChartLabels;
                    wcChart.data.datasets[0].data = originalChartDataPro;
                    wcChart.data.datasets[1].data = originalChartDataCapacity;
                    wcChart.data.datasets[0].backgroundColor = Array(originalChartLabels.length).fill(proColor);
                    wcChart.data.datasets[1].backgroundColor = Array(originalChartLabels.length).fill(capacityColor);
                    activeWcAsal = null;
                    allRows.forEach(r => r.classList.remove('table-active'));
                } else {
                    activeWcAsal = wcAsal;
                    allRows.forEach(r => r.classList.remove('table-active'));
                    this.classList.add('table-active');
                    const rulesMap = Object.fromEntries((compatibilities[wcAsal] || []).map(rule => [rule.wc_tujuan_code, rule.status]));
                    const filtered = { labels: [], proData: [], capacityData: [], colors: [] };
                    originalChartLabels.forEach((targetWc, index) => {
                        const status = rulesMap[targetWc];
                        if (targetWc === wcAsal || status === 'compatible' || status === 'compatible with condition') {
                            filtered.labels.push(targetWc);
                            filtered.proData.push(originalChartDataPro[index]);
                            filtered.capacityData.push(originalChartDataCapacity[index]);
                            if (targetWc === wcAsal) filtered.colors.push(defaultColor);
                            else if (status === 'compatible') filtered.colors.push(compatibleColor);
                            else if (status === 'compatible with condition') filtered.colors.push(conditionalColor);
                        }
                    });
                    wcChart.data.labels = filtered.labels;
                    wcChart.data.datasets[0].data = filtered.proData;
                    wcChart.data.datasets[1].data = filtered.capacityData;
                    wcChart.data.datasets[0].backgroundColor = filtered.colors;
                    wcChart.data.datasets[1].backgroundColor = filtered.colors;
                }
                wcChart.update();
            });

            row.addEventListener('dragstart', function(e) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    type: 'single', proCode: this.dataset.proCode, wcAsal: this.dataset.wcAsal,
                    oper: this.dataset.oper, pwwrk: this.dataset.pwwrk
                }));
            });
        });

        document.getElementById('bulk-drag-handle').addEventListener('dragstart', function(e) {
            const selectedPros = Array.from(document.querySelectorAll('.pro-select-checkbox:checked')).map(cb => {
                const row = cb.closest('.pro-row');
                return { proCode: row.dataset.proCode, wcAsal: row.dataset.wcAsal, oper: row.dataset.oper, pwwrk: row.dataset.pwwrk };
            });
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'bulk', pros: selectedPros }));
        });

        const chartCanvas = document.getElementById('wcChart');
        chartCanvas.addEventListener('dragover', (e) => e.preventDefault());
        
        // ========================================================================= //
        // === PERBAIKAN BUG DROP AREA DIMULAI DI SINI ===                           //
        // ========================================================================= //
        chartCanvas.addEventListener('drop', function(e) {
            e.preventDefault();
            const droppedData = JSON.parse(e.dataTransfer.getData('text/plain'));
            
            // Logika baru: Dapatkan index kategori pada sumbu X berdasarkan posisi kursor,
            // bukan berdasarkan bar yang ada.
            const canvasPosition = Chart.helpers.getRelativePosition(e, wcChart);
            const dataIndex = wcChart.scales.x.getValueForPixel(canvasPosition.x);
            
            // Pastikan index yang didapat valid dan ada labelnya
            if (dataIndex !== undefined && wcChart.data.labels[dataIndex]) {
                const wcTujuan = wcChart.data.labels[dataIndex];
                const modal = new bootstrap.Modal(document.getElementById('changeWcModal'));
                
                if (droppedData.type === 'single') {
                    handleSingleDrop(droppedData, wcTujuan, modal);
                } else if (droppedData.type === 'bulk') {
                    handleBulkDrop(droppedData, wcTujuan, modal);
                }
            }
        });
        // ========================================================================= //
        // === PERBAIKAN BUG DROP AREA SELESAI ===                                   //
        // ========================================================================= //

        function handleSingleDrop(data, wcTujuan, modal) {
            if (wcTujuan === data.wcAsal) return;
            const status = (compatibilities[data.wcAsal] || []).find(r => r.wc_tujuan_code === wcTujuan)?.status;
            if (status === 'compatible' || status === 'compatible with condition') {
                document.getElementById('single-move-content').classList.remove('d-none');
                document.getElementById('bulk-move-content').classList.add('d-none');
                document.getElementById('proCodeWc').textContent = data.proCode;
                document.getElementById('wcAsalWc').textContent = data.wcAsal;
                document.getElementById('wcTujuanWc').textContent = wcTujuan;
                
                const form = document.getElementById('changeWcForm');
                form.action = `/changeWC`;
                document.getElementById('formAufnr').value = data.proCode;
                document.getElementById('formVornr').value = data.oper;
                document.getElementById('formPwwrk').value = data.pwwrk;
                document.getElementById('formPlant').value = plantKode;
                document.getElementById('formWcTujuan').value = wcTujuan;
                
                document.getElementById('formBulkPros').value = '';
                modal.show();
            } else { showToast(`Pemindahan ke Work Center ${wcTujuan} tidak diizinkan.`); }
        }

        function handleBulkDrop(data, wcTujuan, modal) {
            if (data.pros.length === 0) return;
            let allCompatible = true;
            for (const pro of data.pros) {
                if (wcTujuan === pro.wcAsal) continue;
                const status = (compatibilities[pro.wcAsal] || []).find(r => r.wc_tujuan_code === wcTujuan)?.status;
                if (status !== 'compatible' && status !== 'compatible with condition') {
                    allCompatible = false;
                    break;
                }
            }

            if (allCompatible) {
                document.getElementById('single-move-content').classList.add('d-none');
                document.getElementById('bulk-move-content').classList.remove('d-none');
                document.getElementById('bulk-pro-count').textContent = data.pros.length;
                document.getElementById('bulk-wcTujuanWc').textContent = wcTujuan;
                
                const proListContainer = document.getElementById('pro-list-modal');
                proListContainer.innerHTML = data.pros.map(pro => `
                    <div class="list-group-item d-flex justify-content-between align-items-center list-group-item-action py-2">
                        <span><i class="fa-solid fa-file-invoice me-2 text-muted"></i><strong>${pro.proCode}</strong></span>
                        <small class="text-muted">dari ${pro.wcAsal}</small>
                    </div>`).join('');

                const form = document.getElementById('changeWcForm');
                form.action = `/changeWCBulk/${plantKode}/${wcTujuan}`;
                document.getElementById('formBulkPros').value = JSON.stringify(data.pros);
                
                document.getElementById('formAufnr').value = '';
                document.getElementById('formVornr').value = '';
                document.getElementById('formPwwrk').value = '';
                document.getElementById('formPlant').value = '';
                document.getElementById('formWcTujuan').value = '';
                modal.show();
            } else { showToast(`Satu atau lebih PRO tidak kompatibel dengan Work Center ${wcTujuan}.`); }
        }
        
        const bulkActionBar = document.getElementById('bulk-action-bar');
        const selectionCountSpan = document.getElementById('selection-count');
        const allCheckboxes = document.querySelectorAll('.pro-select-checkbox');
        const selectAllCheckbox = document.getElementById('select-all-pro');

        function updateBulkSelection() {
            const selected = document.querySelectorAll('.pro-select-checkbox:checked');
            const count = selected.length;
            if (count > 0) {
                selectionCountSpan.textContent = `${count} PRO(s) selected`;
                bulkActionBar.classList.remove('d-none');
                bulkActionBar.classList.add('d-flex');
            } else {
                bulkActionBar.classList.add('d-none');
                bulkActionBar.classList.remove('d-flex');
            }
            const visibleCheckboxes = document.querySelectorAll('.pro-row:not([style*="display: none"]) .pro-select-checkbox');
            selectAllCheckbox.checked = count > 0 && count === visibleCheckboxes.length;
            selectAllCheckbox.indeterminate = count > 0 && count < visibleCheckboxes.length;
        }

        window.clearBulkSelection = function() {
            allCheckboxes.forEach(cb => cb.checked = false);
            updateBulkSelection();
        }

        allCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkSelection));
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.pro-row:not([style*="display: none"]) .pro-select-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkSelection();
        });
        
        document.getElementById('wcQuickNavBtn')?.addEventListener('click', () => {
            const selectedWc = document.getElementById('wcQuickNavSelect').value;
            if (selectedWc) window.location.href = `/wc-mapping/details/${plantKode}/${selectedWc}`;
        });
        document.getElementById('proSearchInput')?.addEventListener('keyup', function() {
            const filterText = this.value.toUpperCase();
            document.querySelectorAll('#proTableBody tr').forEach(row => {
                const proCodeCell = row.cells[2];
                if (proCodeCell) row.style.display = proCodeCell.textContent.toUpperCase().includes(filterText) ? "" : "none";
            });
            updateBulkSelection();
        });

        document.getElementById('changeWcForm')?.addEventListener('submit', function(e) {
            document.getElementById('loading-overlay').classList.remove('d-none');
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Memproses...
                `;
            }
        });
    });
    </script>
    @endpush
</x-layouts.app>