@foreach($TData4 as $item)
    <tr class="clickable-row" data-no="{{ $loop->iteration + ($TData4->currentPage() - 1) * $TData4->perPage() }}"
        data-reservasi="{{ $item->RSNUM ?? '-' }}"
        data-material-code="{{ $item->MATNR ? (ltrim((string) $item->MATNR, '0') ?: '0') : '-' }}"
        data-description="{{ $item->MAKTX ?? '-' }}"
        data-req-qty="{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}"
        data-req-commited="{{ number_format($item->VMENG ?? 0, 0, ',', '.') }}"
        data-stock="{{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}"
        data-searchable-text="{{ strtolower(($item->RSNUM ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">

        <td class="text-center d-none d-md-table-cell">{{ $loop->iteration + ($TData4->currentPage() - 1) * $TData4->perPage() }}</td>
        <td data-col="reservasi">{{ $item->RSNUM ?? '-' }}</td>
        <td data-col="material_code">
            {{ $item->MATNR ? (ltrim((string) $item->MATNR, '0') ?: '0') : '-' }}</td>
        <td class="d-none d-md-table-cell" data-col="description">
            {{ $item->MAKTX ?? '-' }}</td>
        <td class="text-center d-none d-md-table-cell" data-col="req_qty">
            {{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}</td>
        <td class="text-center d-none d-md-table-cell" data-col="vmeng">
            {{ number_format($item->VMENG ?? 0, 0, ',', '.') }}</td>
        <td class="text-center d-none d-md-table-cell" data-col="stock">
            {{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}
        </td>
    </tr>
@endforeach

@if($TData4->isEmpty() && $TData4->currentPage() == 1)
    <tr>
        <td colspan="7" class="text-center p-5 text-muted">Tidak ada data reservasi
            ditemukan.</td>
    </tr>
@endif
