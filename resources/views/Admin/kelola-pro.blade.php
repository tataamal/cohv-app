<x-layouts.app title="Kelola Work Center">
    @push('styles')
    <style>
        /* Style untuk memberikan efek modern dan bersih */
        body {
            background-color: #f4f7f6;
        }
        .card {
            border-radius: 0.75rem; /* Sudut lebih tumpul */
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }
        
        /* CSS untuk Tabel dengan Sticky Header & Scroll */
        .table-container {
            max-height: 450px; /* Batas tinggi tabel sebelum scroll muncul */
            overflow-y: auto;
        }
        .table-sticky thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #f8f9fa; /* Warna latar belakang header saat sticky */
        }

        /* CSS untuk Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }
        .loader {
            position: relative;
            width: 108px;
            display: flex;
            justify-content: space-between;
        }
        .text-color-loading {
            color: #ffffff;
        }
        .loader::after , .loader::before  {
            content: '';
            display: inline-block;
            width: 48px;
            height: 48px;
            background-color: #FFF;
            background-image:  radial-gradient(circle 14px, #0d161b 100%, transparent 0);
            background-repeat: no-repeat;
            border-radius: 50%;
            animation: eyeMove 10s infinite , blink 10s infinite;
        }
        @keyframes eyeMove {
            0%  , 10% {  background-position: 0px 0px}
            13%  , 40% {  background-position: -15px 0px}
            43%  , 70% {  background-position: 15px 0px}
            73%  , 90% {  background-position: 0px 15px}
            93%  , 100% {  background-position: 0px 0px}
        }
        @keyframes blink {
            0%  , 10% , 12% , 20%, 22%, 40%, 42% , 60%, 62%,  70%, 72% , 90%, 92%, 98% , 100%
            { height: 48px}
            11% , 21% ,41% , 61% , 71% , 91% , 99%
            { height: 18px}
        }
    </style>
    @endpush

    <div class="container-fluid my-4">
        {{-- Memanggil komponen notifikasi --}}
        <x-notification.notification />

        {{-- Loading Overlay --}}
        <div id="loading-overlay" class="loading-overlay d-none d-flex justify-content-center align-items-center flex-column">
            <div class="loader mb-4"></div>
            <h2 class="h4 fw-semibold text-secondary text-color-loading">Memproses Pemindahan Workcenter...</h2>
        </div>

        {{-- BARIS 1: CHART BAR --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 position-relative">
    
                        <div class="position-absolute top-0 end-0 p-2 d-md-none" style="z-index: 10;">
                            <button id="legend-popover-btn" class="btn btn-sm btn-outline-secondary rounded-circle" 
                                    type="button" 
                                    data-bs-toggle="popover" 
                                    data-bs-title="Kode Warna Chart" 
                                    data-bs-html="true">
                                <i class="fa-solid fa-info"></i>
                            </button>
                        </div>
                    
                        <div class="position-absolute top-0 end-0 p-4 d-none d-md-flex" style="z-index: 10;">
                            <ul id="legend-content" class="list-unstyled flex-sm-row gap-3">
                                <li class="d-flex align-items-center small text-muted">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #0d6efd;"></span> WC Saat Ini
                                </li>
                                <li class="d-flex align-items-center small text-muted">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #198754;"></span> Compatible
                                </li>
                                <li class="d-flex align-items-center small text-muted">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #ffc107;"></span> With Condition
                                </li>
                                <li class="d-flex align-items-center small text-muted">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #6c757d;"></span> Not Compatible
                                </li>
                            </ul>
                        </div>
                    
                        <h5 class="card-title fw-bold">Peta Kepadatan Work Center</h5>
                        <p class="card-subtitle text-muted mb-3">Drag & drop baris PRO ke bar chart untuk memindahkan.</p>
                        <div class="chart-container" style="height: 20rem;">
                            <canvas id="wcChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 2: KONTEN UTAMA --}}
        <div class="row g-4">
            {{-- KOLOM KIRI: TABEL PRO --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        
                        {{-- Grup Kiri: Judul & Tombol Petunjuk --}}
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 me-3">Daftar PRO di: <span class="text-primary fw-bold">{{ $workCenter }}</span></h5>
                            
                            {{-- ✨ BARU: Tombol untuk membuka modal petunjuk --}}
                            <button class="btn btn-sm btn-outline-info rounded-circle" data-bs-toggle="modal" data-bs-target="#petunjukModal" title="Lihat Petunjuk">
                                <i class="fa-solid fa-question"></i>
                            </button>
                        </div>
        
                        {{-- Grup Kanan: Navigasi Cepat --}}
                        <div class="d-flex align-items-center">
                            <select class="form-select form-select-sm me-2" id="wcQuickNavSelect" style="width: 150px;">
                                @foreach($allWcs as $wc_item)
                                    <option value="{{ $wc_item->ARBPL }}" {{ $wc_item->ARBPL == $workCenter ? 'selected' : '' }}>
                                        {{ $wc_item->ARBPL }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary" id="wcQuickNavBtn">Pindah</button>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        {{-- ... (Isi card-body seperti search dan tabel tidak berubah) ... --}}
                        <div class="pt-3 pb-3">
                            <input type="search" id="proSearchInput" class="form-control" placeholder="🔍 Cari berdasarkan Kode PRO (AUFNR)...">
                        </div>
                        
                        <div class="table-container">
                            <div class="table-container">
                                <table class="table table-hover table-striped mb-0 table-sticky">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Kode PRO</th>
                                            <th scope="col">KD. Workcenter</th>
                                            <th scope="col">Material Description</th>
                                            <th scope="col">Opration Key</th>
                                            <th scope="col">PV1</th>
                                            <th scope="col">PV2</th>
                                            <th scope="col">PV3</th>
                                        </tr>
                                    </thead>
                                    <tbody id="proTableBody">
                                        @forelse ($pros as $pro)
                                            <tr class="pro-row" draggable="true" 
                                                data-pro-code="{{ $pro->AUFNR }}" 
                                                data-wc-asal="{{ $pro->ARBPL }}"
                                                data-oper="{{ $pro->VORNR }}"
                                                data-pwwrk="{{ $pro->PWWRK }}">
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="fw-bold">{{ $pro->AUFNR }}</td>
                                                <td>{{ $pro->ARBPL }}</td>
                                                <td>{{ $pro->MAKTX }}</td>
                                                <td>{{ $pro->STEUS }}</td>
                                                <td>{{ $pro->PV1 }}</td>
                                                <td>{{ $pro->PV2 }}</td>
                                                <td>{{ $pro->PV3 }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">Tidak ada data PRO di Work Center ini.</td>
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
    </div>

    {{-- MODAL UNTUK KONFIRMASI PEMINDAHAN --}}
    <div class="modal fade" id="changeWcModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="changeWcForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="pro_code" id="formProCodeWc">
                    <input type="hidden" name="oper_code" id="formOperCodeWc">
                    <input type="hidden" name="wc_asal" id="formWcAsalWc">
                    <input type="hidden" name="pwwrk" id="formPwwrkWc">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Pindah Work Center</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda akan memindahkan PRO <strong id="proCodeWc"></strong> dari WC <strong id="wcAsalWc"></strong> ke WC <strong id="wcTujuanWc"></strong>.</p>
                        <p>Apakah Anda yakin ingin melanjutkan?</p>
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
        // === INISIALISASI POPOVER LEGEND ===
        const popoverTriggerEl = document.getElementById('legend-popover-btn');
        const legendContentEl = document.getElementById('legend-content');
        if (popoverTriggerEl && legendContentEl) {
            const legendHTML = legendContentEl.innerHTML;
            const popover = new bootstrap.Popover(popoverTriggerEl, {
                content: `<ul class="list-unstyled">${legendHTML}</ul>`,
                html: true,
                sanitize: false,
                placement: 'bottom'
            });
        }

        // === SETUP DATA & CHART ===
        const ctx = document.getElementById('wcChart').getContext('2d');
        const originalChartLabels = @json($chartLabels ?? []);
        const originalChartDataPro = @json($chartProData ?? []);
        const originalChartDataCapacity = @json($chartCapacityData ?? []);
        const compatibilities = @json($compatibilities ?? (object)[]);
        const plantKode = @json($kode ?? '');
        const wcDescriptionMap = @json($wcDescriptionMap);

        // Warna-warna yang digunakan
        const defaultColor = 'rgba(13, 110, 253, 0.6)';
        const compatibleColor = 'rgba(25, 135, 84, 0.6)';
        const conditionalColor = 'rgba(255, 193, 7, 0.6)';
        const proColor = 'rgba(102, 16, 242, 0.6)';
        const capacityColor = 'rgba(253, 126, 20, 0.6)';
        const notCompatibleColor = 'rgba(108, 117, 125, 0.6)';

        const wcChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: originalChartLabels,
                datasets: [
                    { label: 'Jumlah PRO', data: originalChartDataPro, backgroundColor: proColor, borderWidth: 1 },
                    { label: 'Jumlah Capacity', data: originalChartDataCapacity, backgroundColor: capacityColor, borderWidth: 1 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                const item = tooltipItems[0];
                                const wcCode = item.label;
                                return wcDescriptionMap[wcCode] || wcCode;
                            },
                            label: function(context) {
                                const datasetLabel = context.dataset.label || '';
                                const value = context.parsed.y;
                                const formattedValue = value.toLocaleString('id-ID');
                                let unit = (datasetLabel === 'Jumlah PRO') ? ' PRO' : (datasetLabel === 'Jumlah Capacity') ? ' Jam' : '';
                                return `${datasetLabel}: ${formattedValue}${unit}`;
                            }
                        }
                    }
                }
            }
        });

        // === LOGIKA FILTER CHART SAAT KLIK TABEL ===
        let activeWcAsal = null;
        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('click', function () {
                const wcAsal = this.dataset.wcAsal;
                const allRows = document.querySelectorAll('.pro-row');

                if (wcAsal === activeWcAsal) {
                    // KEMBALIKAN KE SEMULA (RESET FILTER)
                    wcChart.data.labels = originalChartLabels;
                    wcChart.data.datasets[0].data = originalChartDataPro;
                    wcChart.data.datasets[1].data = originalChartDataCapacity;
                    
                    // ✨ FIX: Buat array warna lengkap untuk setiap dataset saat reset
                    wcChart.data.datasets[0].backgroundColor = Array(originalChartLabels.length).fill(proColor);
                    wcChart.data.datasets[1].backgroundColor = Array(originalChartLabels.length).fill(capacityColor);
                    
                    activeWcAsal = null;
                    this.classList.remove('table-active');
                } else {
                    // TERAPKAN FILTER BARU
                    activeWcAsal = wcAsal;
                    allRows.forEach(r => r.classList.remove('table-active'));
                    this.classList.add('table-active');

                    const compatibilityRules = compatibilities[wcAsal] || [];
                    const rulesMap = Object.fromEntries(compatibilityRules.map(rule => [rule.wc_tujuan_code, rule.status]));

                    const filteredLabels = [];
                    const filteredProData = [];
                    const filteredCapacityData = [];
                    const filteredColors = []; // Ini akan digunakan untuk kedua dataset

                    originalChartLabels.forEach((targetWc, index) => {
                        const status = rulesMap[targetWc];
                        const isVisible = (targetWc === wcAsal || status === 'compatible' || status === 'compatible with condition');

                        if (isVisible) {
                            filteredLabels.push(targetWc);
                            filteredProData.push(originalChartDataPro[index]);
                            filteredCapacityData.push(originalChartDataCapacity[index]);
                            
                            // Menentukan warna untuk bar yang visible
                            if (targetWc === wcAsal) {
                                filteredColors.push(defaultColor);
                            } else if (status === 'compatible') {
                                filteredColors.push(compatibleColor);
                            } else if (status === 'compatible with condition') {
                                filteredColors.push(conditionalColor);
                            }
                        }
                    });

                    wcChart.data.labels = filteredLabels;
                    wcChart.data.datasets[0].data = filteredProData;
                    wcChart.data.datasets[1].data = filteredCapacityData;
                    
                    // Terapkan array warna yang sudah difilter
                    wcChart.data.datasets[0].backgroundColor = filteredColors;
                    wcChart.data.datasets[1].backgroundColor = filteredColors;
                }
                
                wcChart.update();
            });
        });
        // === LOGIKA DRAG & DROP ===
        let draggedItem = null;
        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('dragstart', function(e) {
                draggedItem = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    proCode: this.dataset.proCode,
                    wcAsal: this.dataset.wcAsal,
                    oper: this.dataset.oper,
                    pwwrk: this.dataset.pwwrk
                }));
            });
        });

        const chartCanvas = document.getElementById('wcChart');
        chartCanvas.addEventListener('dragover', function(e) { e.preventDefault(); });
        chartCanvas.addEventListener('drop', function(e) {
            e.preventDefault();
            if (!draggedItem) return;
            const droppedData = JSON.parse(e.dataTransfer.getData('text/plain'));
            const elements = wcChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            if (elements.length) {
                const barIndex = elements[0].index;
                // Ambil WC Tujuan dari label chart yang sedang ditampilkan (bisa jadi sudah terfilter)
                const wcTujuan = wcChart.data.labels[barIndex];
                
                if (wcTujuan === droppedData.wcAsal) return;

                const compatibilityRules = compatibilities[droppedData.wcAsal] || [];
                const rule = compatibilityRules.find(r => r.wc_tujuan_code === wcTujuan);
                const status = rule ? rule.status : 'Tidak Ada Aturan';

                if (status === 'compatible') {
                    document.getElementById('proCodeWc').textContent = droppedData.proCode;
                    document.getElementById('wcAsalWc').textContent = droppedData.wcAsal;
                    document.getElementById('wcTujuanWc').textContent = wcTujuan;
                    const finalUrl = `/changeWC/${plantKode}/${wcTujuan}`;
                    const changeWcForm = document.getElementById('changeWcForm');
                    changeWcForm.setAttribute('action', finalUrl);
                    document.getElementById('formProCodeWc').value = droppedData.proCode;
                    document.getElementById('formOperCodeWc').value = droppedData.oper;
                    document.getElementById('formWcAsalWc').value = droppedData.wcAsal;
                    document.getElementById('formPwwrkWc').value = droppedData.pwwrk;
                    new bootstrap.Modal(document.getElementById('changeWcModal')).show();
                } else if (status === 'compatible with condition') {
                    // new bootstrap.Modal(document.getElementById('changePvModal')).show(); // Tampilkan modal PV
                } else {
                    alert(`Pemindahan PRO ${droppedData.proCode} ke Work Center ${wcTujuan} tidak diizinkan.`);
                }
            }
            draggedItem = null;
        });
        
        // === FITUR TAMBAHAN: QUICK NAV & SEARCH ===
        const quickNavBtn = document.getElementById('wcQuickNavBtn');
        const quickNavSelect = document.getElementById('wcQuickNavSelect');
        if (quickNavBtn) {
            quickNavBtn.addEventListener('click', function() {
                const selectedWc = quickNavSelect.value;
                if (selectedWc) {
                    const baseUrl = `/wc-mapping/details/${plantKode}`; 
                    window.location.href = `${baseUrl}/${selectedWc}`;
                }
            });
        }

        const proSearchInput = document.getElementById('proSearchInput');
        const proTableBody = document.getElementById('proTableBody');
        const tableRows = proTableBody.getElementsByTagName('tr');
        if (proSearchInput) {
            proSearchInput.addEventListener('keyup', function() {
                const filterText = proSearchInput.value.toUpperCase();
                for (let i = 0; i < tableRows.length; i++) {
                    let td = tableRows[i].getElementsByTagName('td')[1]; 
                    if (td) {
                        let textValue = td.textContent || td.innerText;
                        if (textValue.toUpperCase().indexOf(filterText) > -1) {
                            tableRows[i].style.display = "";
                        } else {
                            tableRows[i].style.display = "none";
                        }
                    }
                }
            });
        }

        // === LOGIKA LOADING OVERLAY SAAT SUBMIT FORM ===
        const loaderOverlay = document.getElementById('loading-overlay');
        const changeWcForm = document.getElementById('changeWcForm');
        // const changePvForm = document.getElementById('changePvForm'); // Jika ada form PV
        if (changeWcForm) {
            changeWcForm.addEventListener('submit', function() {
                loaderOverlay.classList.remove('d-none');
            });
        }
    });
    </script>
    @endpush
</x-layouts.app>