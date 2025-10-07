<x-layouts.app>
    {{-- Atur judul halaman --}}
    @section('title', 'Laporan COGI - Plant ' . $kode)

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    <style>
        /* ... CSS sebelumnya tidak berubah ... */
        .stat-card-modern { background-color: #fff; border-radius: 0.75rem; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); transition: all 0.3s ease-in-out; }
        .stat-card-modern:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.1); }
        .stat-card-modern .info h3 { font-size: 1.75rem; font-weight: 700; color: #344767; }
        .stat-card-modern .info p { font-size: 0.875rem; color: #6c757d; margin: 0; }
        .stat-card-modern .icon { font-size: 2.5rem; opacity: 0.3; }
        a.stat-card-link, a.stat-card-link:hover { text-decoration: none; }
        .active-filter { box-shadow: 0 0 0 2px #5e72e4; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; }
        .search-box-minimalist { width: 250px; }
        .datatable-search { display: none; }
        .datatable-container { max-height: 60vh; overflow-y: auto; }
        #cogiTable thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; vertical-align: middle; padding-top: 1rem; padding-bottom: 1rem; }
        
        /* [BARU] CSS untuk Tabel Responsif & Modal */
        #cogiTable tbody tr {
            cursor: pointer; /* Tambahkan cursor pointer pada baris tabel */
        }

        /* Sembunyikan kolom di layar kecil (lebar di bawah 768px) */
        @media (max-width: 767.98px) {
            .mobile-hidden {
                display: none;
            }
        }
    </style>
    @endpush

    <div class="container-fluid py-4">
        {{-- BAGIAN HEADER HALAMAN --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col"><h4 class="page-title">Laporan COGI - Plant {{ $kode }}</h4><p class="page-subtitle text-muted">Menampilkan daftar COGI untuk plant terkait.</p></div>
                <div class="col-auto"><span class="page-date text-muted"><i class="fas fa-calendar-alt me-1"></i>{{ now()->format('l, d F Y') }}</span></div>
            </div>
        </div>

        {{-- BARIS KARTU STATISTIK --}}
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('cogi.report', ['kode' => $kode]) }}" class="stat-card-link">
                    <div class="stat-card-modern @if(!$filter) active-filter @endif">
                        <div class="info"><p>Total COGI</p><h3>{{ number_format($totalError) }}</h3></div><div class="icon text-danger"><i class="fas fa-triangle-exclamation"></i></div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="{{ route('cogi.report', ['kode' => $kode, 'filter' => 'baru']) }}" class="stat-card-link">
                    <div class="stat-card-modern @if($filter === 'baru') active-filter @endif">
                        <div class="info"><p>COGI Baru (Hari Ini)</p><h3>{{ number_format($errorBaru) }}</h3></div><div class="icon text-primary"><i class="fas fa-bolt"></i></div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <a href="{{ route('cogi.report', ['kode' => $kode, 'filter' => 'lama']) }}" class="stat-card-link">
                    <div class="stat-card-modern @if($filter === 'lama') active-filter @endif">
                        <div class="info"><p>COGI Lama (> 7 Hari)</p><h3>{{ number_format($errorLama) }}</h3></div><div class="icon text-warning"><i class="fas fa-history"></i></div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Tabel Data COGI --}}
        <div class="card">
            <div class="card-header card-header-flex">
                <h5 class="card-title mb-0">Detail COGI</h5>
                <div class="search-box-minimalist"><input type="text" id="customSearchBox" class="form-control" placeholder="Cari di hasil ini..."></div>
            </div>
            <div class="card-body">
                <table id="cogiTable" class="table table-hover">
                    <thead>
                        <tr class="align-middle">
                            <th class="text-center">No</th>
                            <th class="text-center">PRO</th>
                            {{-- [DIUBAH] Tambahkan class 'mobile-hidden' pada kolom yang ingin disembunyikan --}}
                            <th class="text-center mobile-hidden">Reservasi Number</th>
                            <th class="text-center">Material Number</th>
                            <th class="text-center mobile-hidden">Description</th>
                            <th class="text-center mobile-hidden">Plant</th>
                            <th class="text-center mobile-hidden">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cogiData as $item)
                            {{-- [DIUBAH] Tambahkan data-* attributes untuk menyimpan semua informasi per baris --}}
                            <tr class="align-middle text-center" 
                                data-bs-toggle="modal" 
                                data-bs-target="#detailModal"
                                data-pro="{{ $item->AUFNR }}"
                                data-reservasi="{{ $item->RSNUM }}"
                                data-material="{{ $item->MATNR }}"
                                data-deskripsi="{{ $item->MAKTX }}"
                                data-plant="{{ $item->DWERK }}"
                                data-tanggal="{{ $item->BUDAT ? $item->BUDAT->format('d F Y') : '-' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->AUFNR }}</td>
                                <td class="mobile-hidden">{{ $item->RSNUM }}</td>
                                <td>{{ $item->MATNR }}</td>
                                <td class="mobile-hidden">{{ $item->MAKTX }}</td>
                                <td class="mobile-hidden">{{ $item->DWERK }}</td>
                                <td class="mobile-hidden">{{ $item->BUDAT ? $item->BUDAT->format('d-m-Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <p>Tidak ada data COGI yang cocok dengan filter yang dipilih.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Data COGI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><strong>PRO:</strong> <span id="modal-pro"></span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Reservasi Number:</strong> <span id="modal-reservasi"></span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Material Number:</strong> <span id="modal-material"></span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Description:</strong> <span id="modal-deskripsi"></span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Plant:</strong> <span id="modal-plant"></span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Date:</strong> <span id="modal-tanggal"></span></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Inisialisasi DataTable
            const dataTable = new simpleDatatables.DataTable("#cogiTable", {
                paging: false,
                info: false,
            });

            // Hubungkan search box untuk memfilter hasil yang sudah ada
            const customSearch = document.getElementById('customSearchBox');
            customSearch.addEventListener('keyup', function() {
                dataTable.search(this.value);
            });

            // [BARU] Logika untuk mengisi dan menampilkan modal
            const detailModal = document.getElementById('detailModal');
            detailModal.addEventListener('show.bs.modal', function (event) {
                // Tombol/baris yang di-klik
                const row = event.relatedTarget;

                // Ambil data dari atribut data-*
                const pro = row.getAttribute('data-pro');
                const reservasi = row.getAttribute('data-reservasi');
                const material = row.getAttribute('data-material');
                const deskripsi = row.getAttribute('data-deskripsi');
                const plant = row.getAttribute('data-plant');
                const tanggal = row.getAttribute('data-tanggal');

                // Cari elemen di dalam modal dan isi dengan data
                document.getElementById('modal-pro').textContent = pro;
                document.getElementById('modal-reservasi').textContent = reservasi;
                document.getElementById('modal-material').textContent = material;
                document.getElementById('modal-deskripsi').textContent = deskripsi;
                document.getElementById('modal-plant').textContent = plant;
                document.getElementById('modal-tanggal').textContent = tanggal;
            });
        });
    </script>
    @endpush
</x-layouts.app>