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
        
        /* 2. CSS untuk Tabel dengan Sticky Header & Scroll */
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
                                        <th scope="col">Opration Key (STEUS)</th>
                                        <th scope="col">PV1</th>
                                        <th scope="col">PV2</th>
                                        <th scope="col">PV3</th>
                                    </tr>
                                </thead>
                                <tbody id="proTableBody">
                                    @forelse ($pros as $pro)
                                        <tr class="pro-row" draggable="true" data-pro-code="{{ $pro->AUFNR }}" data-wc-asal="{{ $pro->ARBPL }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="fw-bold">{{ $pro->AUFNR }}</td>
                                            <td>{{ $pro->ARBPL }}</td>
                                            <td>{{ $pro->STEUS }}</td>
                                            {{-- Langsung panggil, karena sudah diolah di controller --}}
                                            <td>{{ $pro->PV1 }}</td>
                                            <td>{{ $pro->PV2 }}</td>
                                            <td>{{ $pro->PV3 }}</td>
                                        </tr>
                                    @empty
                                        {{-- Baris jika data kosong --}}
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KARTU INFORMASI (4-COL) --}}
            <div class="col-lg-4">
                {{-- 4. KARTU INFO YANG DIRAPIKAN --}}
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

    {{-- ================================================= --}}
    {{-- MODAL UNTUK KONFIRMASI PEMINDAHAN --}}
    {{-- ================================================= --}}
    <div class="modal fade" id="changeWcModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="changeWcForm" method="POST" action="">
                    @csrf  <input type="hidden" name="pro_code" id="formProCodeWc">

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

    <div class="modal fade" id="changePvModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="changePvForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="pro_code" id="formProCodePv">

                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Pindah (Dengan Kondisi)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>PRO <strong id="proCodePv"></strong> dari WC <strong id="wcAsalPv"></strong> akan dipindahkan ke WC <strong id="wcTujuanPv"></strong>.</p>
                        <p class="text-warning">WC tujuan memerlukan penyesuaian Production Version (PV). Proses ini akan mengarahkan Anda ke halaman perubahan PV.</p>
                        <p>Lanjutkan?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Ya, Lanjutkan ke Perubahan PV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // =================================================
        // SETUP DATA DARI BLADE
        // =================================================
        const ctx = document.getElementById('wcChart').getContext('2d');
        const chartLabels = @json($chartLabels ?? []);
        const chartData = @json($chartDensityData ?? []);
        const compatibilities = @json($compatibilities ?? (object)[]); // Cast ke object agar menjadi {}
        const plantKode = @json($kode ?? '');

        // Warna default untuk chart
        const defaultColor = 'rgba(54, 162, 235, 0.6)';
        const compatibleColor = 'rgba(25, 135, 84, 0.6)'; // Hijau
        const conditionalColor = 'rgba(255, 193, 7, 0.6)'; // Kuning
        const otherColor = 'rgba(108, 117, 125, 0.6)';   // Abu-abu

        let chartColors = Array(chartLabels.length).fill(defaultColor);

        // =================================================
        // INISIALISASI CHART
        // =================================================
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

        // =================================================
        // LOGIKA KLIK PADA BARIS TABEL
        // =================================================
        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('click', function () {
                const wcAsal = this.dataset.wcAsal;
                const compatibilityRules = compatibilities[wcAsal] || [];
                
                // Buat map untuk pencarian cepat: {'WC Tujuan': 'Status'}
                const rulesMap = Object.fromEntries(compatibilityRules.map(rule => [rule.wc_tujuan, rule.status]));

                // Tentukan warna baru berdasarkan aturan
                const newColors = chartLabels.map(targetWc => {
                    if (targetWc === wcAsal) {
                        return defaultColor; // Warna asli untuk WC asal
                    }
                    const status = rulesMap[targetWc];
                    if (status === 'Compatible') {
                        return compatibleColor;
                    } else if (status === 'Compatible With Condition') {
                        return conditionalColor;
                    } else {
                        return otherColor;
                    }
                });

                // Update warna chart dan render ulang
                wcChart.data.datasets[0].backgroundColor = newColors;
                wcChart.update();
            });
        });

        // =================================================
        // LOGIKA DRAG AND DROP
        // =================================================
        let draggedItem = null;

        document.querySelectorAll('.pro-row').forEach(row => {
            row.addEventListener('dragstart', function(e) {
                draggedItem = this;
                e.dataTransfer.effectAllowed = 'move';
                // Simpan data yang akan digunakan saat drop
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    proCode: this.dataset.proCode,
                    wcAsal: this.dataset.wcAsal
                }));
            });
        });

        const chartCanvas = document.getElementById('wcChart');
        chartCanvas.addEventListener('dragover', function(e) {
            e.preventDefault(); // Wajib untuk mengizinkan drop
        });

        chartCanvas.addEventListener('drop', function(e) {
            e.preventDefault();
            if (!draggedItem) return;

            const droppedData = JSON.parse(e.dataTransfer.getData('text/plain'));
            const elements = wcChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);

            if (elements.length) {
                const barIndex = elements[0].index;
                const wcTujuan = chartLabels[barIndex];
                
                // Jangan lakukan apa-apa jika drop di WC yang sama
                if (wcTujuan === droppedData.wcAsal) {
                    console.log("Drop di WC yang sama, tidak ada aksi.");
                    return;
                }

                // Cek status kompatibilitas
                const compatibilityRules = compatibilities[droppedData.wcAsal] || [];
                const rule = compatibilityRules.find(r => r.wc_tujuan === wcTujuan);
                const status = rule ? rule.status : 'Tidak ada Keterangan';
                
                if (status === 'Compatible') {
                // Siapkan dan tampilkan modal Change WC
                document.getElementById('proCodeWc').textContent = droppedData.proCode;
                document.getElementById('wcAsalWc').textContent = droppedData.wcAsal;
                document.getElementById('wcTujuanWc').textContent = wcTujuan;

                // Bangun URL action untuk form menggunakan route name
                const changeWcUrl = `/changeWC/${plantKode}/${wcTujuan}`;
                
                // Set atribut form
                const changeWcForm = document.getElementById('changeWcForm');
                changeWcForm.setAttribute('action', changeWcUrl);
                document.getElementById('formProCodeWc').value = droppedData.proCode;
                
                new bootstrap.Modal(document.getElementById('changeWcModal')).show();

            } else if (status === 'Compatible With Condition') {
                // Siapkan dan tampilkan modal Change PV
                document.getElementById('proCodePv').textContent = droppedData.proCode;
                document.getElementById('wcAsalPv').textContent = droppedData.wcAsal;
                document.getElementById('wcTujuanPv').textContent = wcTujuan;

                // Bangun URL action untuk form menggunakan route name
                const changePvUrl = `/changePV/${plantKode}/${wcTujuan}`;

                // Set atribut form
                const changePvForm = document.getElementById('changePvForm');
                changePvForm.setAttribute('action', changePvUrl);
                document.getElementById('formProCodePv').value = droppedData.proCode;

                new bootstrap.Modal(document.getElementById('changePvModal')).show();

            } else {
                alert(`Pemindahan ke ${wcTujuan} tidak diizinkan.`);
            }
        }
        draggedItem = null;
    });
    });
    // =================================================
    // FITUR BARU 1: NAVIGASI WC CEPAT
    // =================================================
    const quickNavBtn = document.getElementById('wcQuickNavBtn');
    const quickNavSelect = document.getElementById('wcQuickNavSelect');
    const plantKode = @json($kode ?? '');

    if (quickNavBtn) {
        quickNavBtn.addEventListener('click', function() {
            const selectedWc = quickNavSelect.value;
            if (selectedWc) {
                // Ganti '/wc-mapping/details/' dengan path route Anda yang sebenarnya
                window.location.href = `/wc-mapping/details/${plantKode}/${selectedWc}`;
            }
        });
    }

    // =================================================
    // FITUR BARU 2: PENCARIAN PRO DI TABEL
    // =================================================
    const proSearchInput = document.getElementById('proSearchInput');
    const proTableBody = document.getElementById('proTableBody');
    const tableRows = proTableBody.getElementsByTagName('tr');

    if (proSearchInput) {
        proSearchInput.addEventListener('keyup', function() {
            const filterText = proSearchInput.value.toUpperCase();

            for (let i = 0; i < tableRows.length; i++) {
                // Cari di kolom kedua (indeks 1), yaitu Kode PRO (AUFNR)
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
    </script>
    @endpush
</x-layouts.app>