<x-layouts.app>
    {{-- Atur judul halaman --}}
    @section('title', 'Laporan COGI - Plant ' . $kode)

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    <style>
        /* ... CSS tidak ada perubahan ... */
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
        #cogiTable tbody tr { cursor: pointer; }
        @media (max-width: 767.98px) { .mobile-hidden { display: none; } }
    </style>
    @endpush

    <div class="container-fluid p-0">
        <div class="p-4">
            {{-- BAGIAN HEADER HALAMAN --}}
            {{-- [PERBAIKAN] Menggunakan class flexbox responsif agar rapi di mobile --}}
            <div class="page-header mb-4 d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-3">
                <div class="text-center text-sm-start">
                    <h4 class="page-title">Laporan COGI - Plant {{ $kode }}</h4>
                    <p class="page-subtitle text-muted mb-0">Menampilkan daftar COGI untuk plant terkait.</p>
                </div>
                <div class="text-center text-sm-end">
                    <span class="page-date text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>{{ now()->format('l, d F Y') }}
                    </span>
                </div>
            </div>

            {{-- BARIS KARTU STATISTIK (Tidak diubah, sudah rapi) --}}
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <a href="{{ route('cogi.report', ['kode' => $kode]) }}" class="stat-card-link">
                        <div class="stat-card-modern @if(!$filter) active-filter @endif d-flex flex-column flex-sm-row align-items-sm-center text-center text-sm-start">
                            <div class="info flex-grow-1"><p>Total COGI</p><h3>{{ number_format($totalError) }}</h3></div>
                            <div class="icon text-danger mt-3 mt-sm-0"><i class="fas fa-triangle-exclamation"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <a href="{{ route('cogi.report', ['kode' => $kode, 'filter' => 'baru']) }}" class="stat-card-link">
                        <div class="stat-card-modern @if($filter === 'baru') active-filter @endif d-flex flex-column flex-sm-row align-items-sm-center text-center text-sm-start">
                            <div class="info flex-grow-1"><p>COGI Baru (Kurang dari 7 Hari)</p><h3>{{ number_format($errorBaru) }}</h3></div>
                            <div class="icon text-primary mt-3 mt-sm-0"><i class="fas fa-bolt"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <a href="{{ route('cogi.report', ['kode' => $kode, 'filter' => 'lama']) }}" class="stat-card-link">
                        <div class="stat-card-modern @if($filter === 'lama') active-filter @endif d-flex flex-column flex-sm-row align-items-sm-center text-center text-sm-start">
                            <div class="info flex-grow-1"><p>COGI Lama (Lebih dari 7 Hari)</p><h3>{{ number_format($errorLama) }}</h3></div>
                            <div class="icon text-warning mt-3 mt-sm-0"><i class="fas fa-history"></i></div>
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
                                <th class="text-center mobile-hidden">Reservasi Number</th>
                                <th class="text-center">Material Number</th>
                                <th class="text-center mobile-hidden">Description</th>
                                <th class="text-center mobile-hidden">MRP</th>
                                <th class="text-center mobile-hidden">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cogiData as $item)
                                <tr class="align-middle text-center" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#detailModal"
                                    data-pro="{{ $item->AUFNR }}"
                                    data-posting-date="{{ $item->BUDAT ? $item->BUDAT->format('d F Y') : '-' }}"
                                    data-reservation="{{ $item->RSNUM }}"
                                    data-sales-order="{{ $item->KDAUF }}"
                                    data-item="{{ $item->KDPOS }}"
                                    data-plant="{{ $item->DWERK }}"
                                    data-sloc-header="{{ $item->LGORTH }}"
                                    data-material-header="{{ is_numeric($item->MATNRH) ? ltrim($item->MATNRH, '0') : $item->MATNRH }}"
                                    data-desc-header="{{ $item->MAKTXH }}"
                                    data-mrp-header="{{ $item->DISPOH }}"
                                    data-qty-pro="{{ number_format($item->PSMNG, 3, ',', '.') }}"
                                    data-qty-dlv="{{ number_format($item->WEMNG, 3, ',', '.') }}"
                                    data-sloc-comp="{{ $item->LGORT }}"
                                    data-material-comp="{{ is_numeric($item->MATNR) ? ltrim($item->MATNR, '0') : $item->MATNR }}"
                                    data-desc-comp="{{ $item->MAKTX }}"
                                    data-mrp-comp="{{ $item->DISPO }}"
                                    data-qty-cogi="{{ number_format($item->ERFMG, 3, ',', '.') }}"
                                    data-stock="{{ number_format($item->MENGE, 3, ',', '.') }}"
                                    data-uom="{{ $item->MEINS === 'ST' ? 'PC' : $item->MEINS }}"
                                    data-pro-cogi="{{ $item->AUFNRX }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->AUFNR }}</td>
                                    <td class="mobile-hidden">{{ $item->RSNUM }}</td>
                                    <td>{{ is_numeric($item->MATNR) ? ltrim($item->MATNR, '0') : $item->MATNR }}</td>
                                    <td class="mobile-hidden">{{ $item->MAKTX }}</td>
                                    <td class="mobile-hidden">{{ $item->DISPOH }}</td>
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
    </div>
</div> {{-- Tutup div pembungkus p-4 --}}

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Data COGI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Header Information</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between"><strong>Production Order:</strong> <span id="modal-pro"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Posting Date COGI:</strong> <span id="modal-posting-date"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Reservation:</strong> <span id="modal-reservation"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Sales Order:</strong> <span id="modal-sales-order"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Item:</strong> <span id="modal-item"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Plant:</strong> <span id="modal-plant"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>SLoc:</strong> <span id="modal-sloc-header"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Material:</strong> <span id="modal-material-header"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Description:</strong> <span id="modal-desc-header"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>MRP:</strong> <span id="modal-mrp-header"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Qty PRO:</strong> <span id="modal-qty-pro"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Qty Dlv:</strong> <span id="modal-qty-dlv"></span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Component Information</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between"><strong>SLoc:</strong> <span id="modal-sloc-comp"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Component:</strong> <span id="modal-material-comp"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Description:</strong> <span id="modal-desc-comp"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>MRP:</strong> <span id="modal-mrp-comp"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Qty COGI:</strong> <span id="modal-qty-cogi"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Pro Cogi:</strong> <span id="modal-pro-cogi"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>Stock:</strong> <span id="modal-stock"></span></li>
                                <li class="list-group-item d-flex justify-content-between"><strong>UoM:</strong> <span id="modal-uom"></span></li>
                            </ul>
                        </div>
                    </div>
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
            const dataTable = new simpleDatatables.DataTable("#cogiTable", { paging: false, info: false });

            const customSearch = document.getElementById('customSearchBox');
            customSearch.addEventListener('keyup', function() { dataTable.search(this.value); });

            const detailModal = document.getElementById('detailModal');
            detailModal.addEventListener('show.bs.modal', function (event) {
                const row = event.relatedTarget;
                
                const setData = (id, attribute) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = row.getAttribute(attribute) || '-';
                    }
                };

                setData('modal-pro', 'data-pro');
                setData('modal-posting-date', 'data-posting-date');
                setData('modal-reservation', 'data-reservation');
                setData('modal-sales-order', 'data-sales-order');
                setData('modal-item', 'data-item');
                setData('modal-plant', 'data-plant');
                setData('modal-sloc-header', 'data-sloc-header');
                setData('modal-material-header', 'data-material-header');
                setData('modal-desc-header', 'data-desc-header');
                setData('modal-mrp-header', 'data-mrp-header');
                setData('modal-qty-pro', 'data-qty-pro');
                setData('modal-qty-dlv', 'data-qty-dlv');
                setData('modal-sloc-comp', 'data-sloc-comp');
                setData('modal-material-comp', 'data-material-comp');
                setData('modal-desc-comp', 'data-desc-comp');
                setData('modal-mrp-comp', 'data-mrp-comp');
                setData('modal-qty-cogi', 'data-qty-cogi');
                setData('modal-pro-cogi', 'data-pro-cogi');
                setData('modal-stock', 'data-stock');
                setData('modal-uom', 'data-uom');
            });
        });
    </script>
    @endpush
</x-layouts.app>