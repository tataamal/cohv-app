{{-- resources/views/admin/partials/pro-overview-table.blade.php --}}

<div class="table-responsive">
    <table class="table table-striped table-hover table-sm" id="tdata3-table">
        <thead class="bg-primary text-white">
            <tr class="align-middle">
                <th class="text-center" style="width: 5%;">No</th>
                <th class="text-center" style="width: 15%;">PRO Number</th>
                <th class="text-center" style="width: 10%;">Status</th>
                <th class="text-center" style="width: 15%;">Material</th>
                <th class="text-center">Description</th>
                <th class="text-center" style="width: 10%;">Required Qty</th>
                <th class="text-center" style="width: 10%;">GR Qty</th>
                <th class="text-center" style="width: 10%;">Outs. Qty</th>
                <th class="text-center" style="width: 10%;">Start Date</th>
                <th class="text-center" style="width: 10%;">Finish Date</th>
            </tr>
        </thead>
        <tbody id="tdata3-body">
            {{-- Loop melalui data TData3 yang sudah dikelompokkan --}}
            @forelse ($proOrders->flatten() as $d3) 
                @php
                    // Logika Status Baru: Menggunakan str_contains untuk status 'REL'
                    $statusClass = 'bg-light text-muted'; // Default
                    if (isset($d3->STATS)) {
                        $stats = strtoupper($d3->STATS);
                        if ($stats === 'CRTD') {
                            $statusClass = 'bg-secondary';
                        } elseif (str_contains($stats, 'REL') || $stats === 'PCNF') {
                            // Menganggap semua yang mengandung REL atau PCNF sebagai Released/Partial Confirmed
                            $statusClass = 'bg-warning text-dark'; 
                        } elseif ($stats === 'TECO') {
                            $statusClass = 'bg-success';
                        }
                    }

                    // Logika Perhitungan Outstanding Qty
                    $psmng = (float) ($d3->PSMNG ?? 0);
                    $wemng = (float) ($d3->WEMNG ?? 0);
                    $outsQty = $psmng - $wemng;

                    // HELPER LOKAL YANG AMAN: Memastikan pemformatan hanya terjadi pada string tanggal valid
                    $formatDateSafe = function($date) {
                        return (!empty($date) && $date != '00000000') ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '-';
                    };
                @endphp
                
                {{-- Data-key krusial untuk JavaScript auto-load --}}
                <tr data-aufnr="{{ $d3->AUFNR }}" class="align-middle">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">
                        <div class="pro-cell-inner d-flex align-items-center justify-content-center">
                            <span class="fw-medium">{{ $d3->AUFNR ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class=" text-center badge {{ $statusClass }}">{{ $d3->STATS ?? '-' }}</span>
                    </td>
                    <td class="text-center">{{ $d3->MATNR ? trim($d3->MATNR, '0') : '-' }}</td>
                    <td class="text-center">{{ $d3->MAKTX ?? '-' }}</td>
                    <td class="text-center">{{ $d3->PSMNG ?? '-' }}</td>
                    <td class="text-center">{{ $d3->WEMNG ?? '-' }}</td>
                    
                    {{-- PERBAIKAN: Kolom Outs. Qty (MENG2) menggunakan perhitungan $outsQty --}}
                    <td class="text-center">{{ number_format($outsQty, 0) }}</td>
                    
                    <td class="text-center">{{ $formatDateSafe($d3->GSTRP) }}</td>
                    <td class="text-center">{{ $formatDateSafe($d3->GLTRP) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center p-4 text-muted">Tidak ada Production Order (PRO) ditemukan untuk item ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Elemen tambahan untuk menampung Routing/Component details (di bawah tabel T3) --}}
<div id="additional-data-container" class="mt-4">
    {{-- Konten Routing (T1) atau Component (T4) akan diinjeksikan di sini oleh JavaScript --}}
</div>