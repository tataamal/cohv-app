@foreach($salesOrderData ?? [] as $item)
    <tr class="clickable-row" data-order="{{ $item->KDAUF ?? '-' }}"
        data-item="{{ $item->KDPOS ?? '-' }}"
        data-material="{{ $item->MATFG ? ltrim((string) $item->MATFG, '0') : '-' }}"
        data-description="{{ $item->MAKFG ?? '-' }}"
        data-searchable-text="{{ strtolower(($item->KDAUF ?? '') . ' ' . ($item->MATFG ?? '') . ' ' . ($item->MAKFG ?? '')) }}">
        <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration + ($salesOrderData->currentPage() - 1) * $salesOrderData->perPage() }}
        </td>
        <td class="text-center small" data-col="order">{{ $item->KDAUF ?? '-' }}</td>
        <td class="small text-center" data-col="item">{{ $item->KDPOS ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="material">
            {{ $item->MATFG ? ltrim((string) $item->MATFG, '0') : '-' }}</td>
        <td class="small d-none d-md-table-cell" data-col="description">
            {{ $item->MAKFG ?? '-' }}</td>
    </tr>
@endforeach

@if($salesOrderData->isEmpty() && $salesOrderData->currentPage() == 1)
    <tr>
        <td colspan="5" class="text-center p-5 text-muted">Tidak ada data Sales
            Order.</td>
    </tr>
@endif
