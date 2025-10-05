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
                <th class="text-center" style="width: 10%;">Finished Qty</th>
                <th class="text-center" style="width: 10%;">Start Date</th>
                <th class="text-center" style="width: 10%;">Finish Date</th>
            </tr>
        </thead>
        <tbody id="tdata3-body">
            {{-- Loop melalui data TData3 yang sudah dikelompokkan --}}
            @forelse ($proOrders->flatten() as $d3) 
                @php
                    $statusClass = [
                        'CRTD' => 'bg-secondary',
                        'REL' => 'bg-warning text-dark',
                        'CNF REL' => 'bg-warning text-dark',
                        'PCNF' => 'bg-warning text-dark',
                        'TECO' => 'bg-success',
                    ][$d3->STATS ?? 'OTHER'] ?? 'bg-light text-muted';

                    // HELPER LOKAL YANG AMAN: Memastikan pemformatan hanya terjadi pada string tanggal valid
                    $formatDateSafe = function($date) {
                        return (!empty($date) && $date != '00000000') ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '-';
                    };
                @endphp
                
                {{-- Data-key krusial untuk JavaScript auto-load --}}
                <tr data-aufnr="{{ $d3->AUFNR }}" class="align-middle">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">
                        <div class="pro-cell-inner d-flex align-items-center justify-content-between">
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
                    
                    {{-- ✅ PERBAIKAN: MENGGUNAKAN HELPER LOKAL AMAN DI SINI --}}
                    <td class="text-center">{{ $formatDateSafe($d3->GSTRP) }}</td>
                    <td class="text-center">{{ $formatDateSafe($d3->GLTRP) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center p-4 text-muted">Tidak ada Production Order (PRO) ditemukan untuk item ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Elemen tambahan untuk menampung Routing/Component details (di bawah tabel T3) --}}
<div id="additional-data-container" class="mt-4">
    {{-- Konten Routing (T1) atau Component (T4) akan diinjeksikan di sini oleh JavaScript --}}
</div>