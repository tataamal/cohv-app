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
    </style>
    @endpush
    <div class="container-fluid my-4">
        {{-- Memanggil komponen notifikasi --}}
        <x-notification.notification />
        {{-- BARIS 1: CHART BAR (Tampilan Disesuaikan) --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 position-relative"> <div class="position-absolute top-0 end-0 p-4" style="z-index: 10;">
                            <ul class="list-unstyled d-flex flex-column flex-sm-row gap-3">
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
                        <div style="height: 20rem;">
                            <canvas id="wcChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 2: KONTEN UTAMA --}}
        <div class="row g-4">
            {{-- KOLOM KIRI: TABEL PRO (8-COL) --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                        <h5 class="mb-0 me-3">Daftar PRO di: <span class="text-primary fw-bold">{{ $workCenter }}</span></h5>
                        <div class="d-flex align-items-center mt-2 mt-md-0">
                            {{-- 1. FITUR PINDAH WC CEPAT --}}
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
                        {{-- 3. FITUR SEARCH PRO --}}
                        <div class="pt-3 pb-3">
                            <input type="search" id="proSearchInput" class="form-control" placeholder="🔍 Cari berdasarkan Kode PRO (AUFNR)...">
                        </div>
                        
                        {{-- 2. WADAH TABEL DENGAN SCROLL --}}
                        <div class="table-container">
                            <table class="table table-hover table-striped mb-0 table-sticky">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Kode PRO (AUFNR)</th>
                                        <th scope="col">Work Center (ARBPL)</th>
                                        <th scope="col">Operation (VORNR)</th>
                                        <th scope="col">Opration Key (STEUS)</th>
                                    </tr>
                                </thead>
                                <tbody id="proTableBody">
                                    @forelse ($pros as $pro)
                                        <tr class="pro-row" draggable="true" 
                                            data-pro-code="{{ $pro->AUFNR }}" 
                                            data-wc-asal="{{ $pro->ARBPL }}"
                                            data-oper="{{ $pro->VORNR }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="fw-bold">{{ $pro->AUFNR }}</td>
                                            <td>{{ $pro->ARBPL }}</td>
                                            <td>{{ $pro->VORNR }}</td>
                                            <td>{{ $pro->STEUS }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data PRO di Work Center ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KARTU INFORMASI (4-COL) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="mb-0 fw-bold">Petunjuk Penggunaan</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="mb-4">
                            <h6 class="text-dark fw-semibold">Langkah-langkah:</h6>
                            <ul class="list-unstyled">
                                <li class="d-flex mb-2"><span class="me-2">1.</span> <span><b>Klik</b> baris PRO pada tabel untuk melihat status kompatibilitas pada chart.</span></li>
                                <li class="d-flex mb-2"><span class="me-2">2.</span> <span><b>Drag & Drop</b> baris PRO ke bar chart WC tujuan untuk memulai proses pemindahan.</span></li>
                                <li class="d-flex"><span class="me-2">3.</span> <span>Sebuah <b>popup konfirmasi</b> akan muncul sesuai status WC tujuan.</span></li>
                            </ul>
                        </div>
                        <hr>
                        <div class="mt-auto">
                            <h6 class="text-dark fw-semibold">Keterangan Warna Chart:</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center px-0">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 15px; height: 15px; background-color: #198754;"></span>
                                    <div><b>Compatible</b> <br><small class="text-muted">PRO dapat langsung dipindahkan.</small></div>
                                </li>
                                <li class="list-group-item d-flex align-items-center px-0">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 15px; height: 15px; background-color: #ffc107;"></span>
                                    <div><b>With Condition</b> <br><small class="text-muted">PRO perlu penyesuaian (PV).</small></div>
                                </li>
                                <li class="list-group-item d-flex align-items-center px-0">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 15px; height: 15px; background-color: #6c757d;"></span>
                                    <div><b>Lainnya</b> <br><small class="text-muted">Tidak memiliki workcenter yang kompatibel</small></div>
                                </li>
                            </ul>
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

    {{-- MODAL UNTUK PERUBAHAN PV (diasumsikan ada) --}}
    <div class="modal fade" id="changePvModal" tabindex="-1">
        {{-- Konten Modal untuk Perubahan PV --}}
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // SETUP DATA DARI BLADE
        const ctx = document.getElementById('wcChart').getContext('2d');
        const chartLabels = @json($chartLabels ?? []);
        const chartData = @json($chartDensityData ?? []);
        const compatibilities = @json($compatibilities ?? (object)[]);
        const plantKode = @json($kode ?? '');

        // Warna default untuk chart
        const defaultColor = 'rgba(13, 110, 253, 0.6)';
        const compatibleColor = 'rgba(25, 135, 84, 0.6)';
        const conditionalColor = 'rgba(255, 193, 7, 0.6)';
        const otherColor = 'rgba(108, 117, 125, 0.6)';
        let chartColors = Array(chartLabels.length).fill(defaultColor);

        // INISIALISASI CHART
        const wcChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Jumlah PRO',
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // LOGIKA KLIK PADA BARIS TABEL
        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('click', function () {
                const wcAsal = this.dataset.wcAsal;
                const compatibilityRules = compatibilities[wcAsal] || [];
                const rulesMap = Object.fromEntries(compatibilityRules.map(rule => [rule.wc_tujuan, rule.status]));

                const newColors = chartLabels.map(targetWc => {
                    if (targetWc === wcAsal) return defaultColor;
                    const status = rulesMap[targetWc];
                    if (status === 'Compatible') return compatibleColor;
                    if (status === 'Compatible With Condition') return conditionalColor;
                    return otherColor;
                });
                wcChart.data.datasets[0].backgroundColor = newColors;
                wcChart.update();
            });
        });

        // LOGIKA DRAG AND DROP
        let draggedItem = null;
        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('dragstart', function(e) {
                draggedItem = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    proCode: this.dataset.proCode,
                    wcAsal: this.dataset.wcAsal,
                    oper: this.dataset.oper
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
                const wcTujuan = chartLabels[barIndex];
                
                if (wcTujuan === droppedData.wcAsal) return;

                const compatibilityRules = compatibilities[droppedData.wcAsal] || [];
                const rule = compatibilityRules.find(r => r.wc_tujuan === wcTujuan);
                const status = rule ? rule.status : 'Tidak Ada Aturan';
                
                if (status === 'Compatible') {
                    document.getElementById('proCodeWc').textContent = droppedData.proCode;
                    document.getElementById('wcAsalWc').textContent = droppedData.wcAsal;
                    document.getElementById('wcTujuanWc').textContent = wcTujuan;

                    // Membangun URL secara langsung menggunakan variabel JavaScript
                    const finalUrl = `/changeWC/${plantKode}/${wcTujuan}`;
                    
                    const changeWcForm = document.getElementById('changeWcForm');
                    changeWcForm.setAttribute('action', finalUrl);
                    
                    document.getElementById('formProCodeWc').value = droppedData.proCode;
                    document.getElementById('formOperCodeWc').value = droppedData.oper;
                    document.getElementById('formWcAsalWc').value = droppedData.wcAsal;
                    
                    new bootstrap.Modal(document.getElementById('changeWcModal')).show();

                } else if (status === 'Compatible With Condition') {
                    // Logika untuk menampilkan modal perubahan PV bisa ditambahkan di sini
                } else {
                    alert(`Pemindahan PRO ${droppedData.proCode} ke Work Center ${wcTujuan} tidak diizinkan.`);
                }
            }
            draggedItem = null;
        });
        
        // FITUR NAVIGASI WC CEPAT
        const quickNavBtn = document.getElementById('wcQuickNavBtn');
        const quickNavSelect = document.getElementById('wcQuickNavSelect');
        if (quickNavBtn) {
            quickNavBtn.addEventListener('click', function() {
                const selectedWc = quickNavSelect.value;
                if (selectedWc) {
                    // Ganti dengan route name Anda jika ada
                    const baseUrl = `/wc-mapping/details/${plantKode}`; 
                    window.location.href = `${baseUrl}/${selectedWc}`;
                }
            });
        }

        // FITUR PENCARIAN PRO DI TABEL
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
    });
    </script>
    @endpush
</x-layouts.app>

