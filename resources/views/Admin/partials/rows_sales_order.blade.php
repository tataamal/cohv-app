@foreach($salesOrderData ?? [] as $item)
    <tr class="clickable-row" data-order="{{ $item->KDAUF ?? '-' }}"
        data-item="{{ $item->KDPOS ?? '-' }}"
        data-material="{{ $item->MATFG ? ltrim((string) $item->MATFG, '0') : '-' }}"
        data-description="{{ $item->MAKFG ?? '-' }}"
        data-buyer="{{ $item->NAME1 ?? '-' }}"
        data-searchable-text="{{ strtolower(($item->KDAUF ?? '') . ' ' . ($item->MATFG ?? '') . ' ' . ($item->MAKFG ?? '') . ' ' . ($item->NAME1 ?? '')) }}">
        
        {{-- 1. No --}}
        <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration + ($salesOrderData->currentPage() - 1) * $salesOrderData->perPage() }}
        </td>

        {{-- 2. SO-Item (MERGED) - MOVED TO LEFT --}}
        <td class="text-start small" data-col="order">
            <span class="d-block text-dark fw-medium">{{ $item->KDAUF ?? '-' }}</span>
            <span class="text-muted" style="font-size: 0.75rem;">
               {{ $item->KDPOS ? ltrim((string) $item->KDPOS, '0') : '-' }}
            </span>
        </td>

        {{-- 3. Buyer (MOVED TO RIGHT) --}}
        <td class="small" data-col="buyer">
            <span class="fw-bold text-dark">{{ $item->NAME1 ?? '-' }}</span><br>
            <span class="text-muted" style="font-size: 0.75rem;">{{ $item->KUNNR ?? '-' }}</span>
        </td>

        {{-- 4. Material (MERGED) --}}
        <td class="small d-none d-md-table-cell" data-col="material">
            <div class="d-flex flex-column">
                <span class="fw-medium text-dark">{{ $item->MATFG ? ltrim((string) $item->MATFG, '0') : '-' }}</span>
                <span class="text-muted text-wrap" style="font-size: 0.75rem; max-width: 250px;">
                    {{ $item->MAKFG ?? '-' }}
                </span>
            </div>
        </td>

        {{-- 5. Date (NEW) --}}
        <td class="small d-none d-md-table-cell text-center" data-col="date">
            {{ $item->EDATU ? \Carbon\Carbon::parse($item->EDATU)->format('d-m-Y') : '-' }}
        </td>
    </tr>
@endforeach

@if($salesOrderData->isEmpty() && $salesOrderData->currentPage() == 1)
    <tr>
        <td colspan="5" class="text-center p-5 text-muted">Tidak ada data Sales
            Order.</td>
    </tr>
@endif
