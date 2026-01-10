<x-layouts.app title="Kelola Work Center">

    {{-- Style dan Script sekarang dikelola secara eksternal --}}

    <div class="container-fluid p-3 p-lg-4">
        <x-notification.notification />

        {{-- Loading Overlay --}}
        <div id="loading-overlay" class="loading-overlay d-none d-flex justify-content-center align-items-center flex-column text-center">
            <div class="loader mb-4"></div>
            <h2 class="h4 fw-semibold text-dark"Wait a Moment, Processing Data...</h2>
        </div>
        
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold text-dark">Workcenter Management</h1>
                <p class="mt-1 text-muted">Halaman untuk Melakukan Perubahan Workcenter PRO</p>
            </div>
            <div>
                <a href="{{ route('manufaktur.dashboard.show', ['kode' => $kode]) }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                            <div>
                                <h5 class="card-title fw-bold text-dark mb-0">Your Compatible Workcenter</h5>
                                <p class="card-subtitle text-muted small mt-1">Pilih PRO di tabel, lalu drag handle hijau untuk memindahkan.</p>
                            </div>
                            <div class="d-none d-md-flex flex-wrap gap-3" id="legend-content">
                                <span class="d-flex align-items-center small text-muted"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #0d6efd;"></span>Workcenter Now</span>
                                <span class="d-flex align-items-center small text-muted"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: #198754;"></span>Workcenter Compatible</span>
                            </div>
                        </div>
                    </div>
                    {{-- [DIUBAH] Menambahkan ID pada card-body untuk dijadikan drop-area --}}
                    <div id="chart-drop-area" class="card-body p-4 position-relative">
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
                            <h5 class="mb-0 me-3 fs-6">List PRO on : <span class="text-primary fw-bold">{{ $workCenter }}</span></h5>
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
                            <button class="btn btn-sm btn-primary flex-shrink-0" id="wcQuickNavBtn">Move</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
            
                        {{-- [PERUBAHAN 1] Lokasi baru untuk bulk-action-bar --}}
                        <div id="bulk-action-bar" class="d-none align-items-center justify-content-between p-2 mx-3 mt-3 rounded-3">
                            <div class="d-flex align-items-center">
                                {{-- [PERUBAHAN 2] Handle diperbesar dengan padding (px-3 py-2) dan ukuran ikon (fs-4) --}}
                                <span id="bulk-drag-handle" class="px-3 py-2" draggable="true" title="Drag to move selected items">
                                    <i class="fa-solid fa-grip-vertical fs-4"></i>
                                </span>
                                <span class="fw-semibold text-primary" id="selection-count">0 PRO Selected</span>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearBulkSelection()">Remove Selected</button>
                        </div>
                        
                        <div class="p-3">
                            <input type="search" id="proSearchInput" class="form-control" placeholder="🔍 Cari berdasarkan Kode PRO (AUFNR)...">
                        </div>
            
                        <div class="table-container-scroll border rounded-0">
                            <table class="table table-sm table-bordered table-hover mb-0 table-sticky align-middle" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr class="text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                        <th class="text-center bg-light" style="width: 40px;"><input class="form-check-input " type="checkbox" id="select-all-pro" title="Pilih semua"></th>
                                        <th class="text-center bg-light" scope="col">No</th>
                                        <th class="text-center bg-light" scope="col">PRO</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">SO - Item</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">WC</th>
                                        <th class="text-start bg-light" scope="col">Material Description</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">Op Key</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">Qty Order</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">Qty GR</th>
                                        <th class="text-center bg-light" scope="col">Qty Sisa</th>
                                        <th class="text-center bg-light" scope="col">Time Req (Min)</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">PV1</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">PV2</th>
                                        <th class="text-center bg-light d-none-mobile" scope="col">PV3</th>
                                    </tr>
                                </thead>
                                <tbody id="proTableBody">
                                    @forelse ($pros as $pro)
                                        @php
                                            $stdValue = $pro->VGW01 ?? 0;
                                            $unit = $pro->VGE01 ?? 'MIN';
                                            $qtySisa = $pro->MENGE2 ?? 0;
                                            
                                            $timeReq = $qtySisa * $stdValue;
                                            if (strtoupper($unit) === 'S') {
                                                $timeReq = $timeReq / 60;
                                            }
                                        @endphp
                                        <tr class="pro-row" 
                                            data-pro-code="{{ $pro->AUFNR }}" 
                                            data-wc-asal="{{ $pro->ARBPL }}"
                                            data-oper="{{ $pro->VORNR }}"
                                            data-pwwrk="{{ $pro->PWWRK }}"
                                            data-psmng="{{ $pro->MGVRG2 }}"
                                            data-wemng="{{ $pro->LMNGA }}">
                                            <td class="text-center"><input class="form-check-input pro-select-checkbox" type="checkbox"></td>
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td class="text-center font-monospace fw-semibold text-dark">{{ $pro->AUFNR }}</td>
                                            <td class="text-center d-none-mobile font-monospace text-secondary">{{ $pro->KDAUF }}{{ ($pro->KDAUF && stripos($pro->KDAUF, 'make stock') === false) ? ' - ' . (int)$pro->KDPOS : '' }}</td>
                                            <td class="text-center d-none-mobile">
                                                <span class="badge bg-secondary rounded-0 fw-normal font-monospace">{{ $pro->ARBPL }}</span>
                                            </td>
                                            <td class="text-start text-dark fw-medium">{{ $pro->MAKTX }}</td>
                                            <td class="text-center d-none-mobile">
                                                <span class="badge bg-dark rounded-0 fw-normal font-monospace">{{ $pro->STEUS }}</span>
                                            </td>
                                            <td class="text-center d-none-mobile font-monospace">{{ number_format($pro->MGVRG2, 0, ',', '.') }}</td>
                                            <td class="text-center d-none-mobile font-monospace text-muted">{{ number_format($pro->LMNGA, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success border border-success rounded-0 fw-bold font-monospace px-2">
                                                    {{ number_format($qtySisa, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="text-center font-monospace fw-bold text-primary">
                                                {{ number_format($timeReq, 1, ',', '.') }}
                                            </td>
                                            <td class="text-center d-none-mobile small text-muted">{{ $pro->PV1 ?? '-'}}</td>
                                            <td class="text-center d-none-mobile small text-muted">{{ $pro->PV2 ?? '-'}}</td>
                                            <td class="text-center d-none-mobile small text-muted">{{ $pro->PV3 ?? '-'}}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center py-5 text-muted fst-italic">Nothing PRO On This Workcenter</td>
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
                        <h5 class="modal-title" id="changeWcModalLabel">Change Workcenter Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="single-move-content">
                            You will move PRO <strong id="proCodeWc"></strong> From <strong id="wcAsalWc"></strong> to <strong id="wcTujuanWc"></strong>.
                        </div>
                        <div id="bulk-move-content" class="d-none">
                            You will move PRO <strong id="bulk-pro-count"></strong> PRO to <strong id="bulk-wcTujuanWc"></strong>.
                            <p class="mt-2">PRO Selected List:</p>
                            <div id="pro-list-modal" class="list-group" style="max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>
                        <p class="mt-3">Are you sure, you want to continue?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Yes, Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    {{-- MODAL UNTUK PETUNJUK (Struktur tidak berubah) --}}
    @include('components.modals.petunjuk-penggunaan-modal')
    @include('components.modals.kelola-pro.detail-pro-modal')

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js/helpers.js"></script>
    <script>
        // [BARU] Passing data dari PHP ke JavaScript global
        window.currentWorkCenter = @json($workCenter);
        window.compatibilitiesData = @json($compatibilities ?? (object)[]);
        window.plantKode = @json($kode ?? '');
        window.wcDescriptionMap = @json($wcDescriptionMap);
        window.chartLabels = @json($chartLabels ?? []);
        window.chartProData = @json($chartProData ?? []);
        window.chartCapacityData = @json($chartCapacityData ?? []);
    </script>
    @endpush
</x-layouts.app>
