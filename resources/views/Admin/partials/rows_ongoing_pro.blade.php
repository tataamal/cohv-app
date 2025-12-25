@foreach($ongoingProData ?? [] as $item)
    @php
        $status = strtoupper($item->STATS ?? '');
        $badgeClass = 'bg-secondary-subtle text-secondary-emphasis';
        if (in_array($status, ['REL', 'PCNF', 'CNF'])) {
            $badgeClass = 'bg-success-subtle text-success-emphasis';
        } elseif ($status === 'CRTD') {
            $badgeClass = 'bg-info-subtle text-info-emphasis';
        } elseif ($status === 'TECO') {
            $badgeClass = 'bg-dark-subtle text-dark-emphasis';
        }
    @endphp
    <tr class="clickable-row" data-no="{{ $loop->iteration + ($ongoingProData->currentPage() - 1) * $ongoingProData->perPage() }}"
        data-pro="{{ $item->AUFNR ?? '-' }}" data-status="{{ $status ?: '-' }}"
        data-status-class="{{ $badgeClass }}" data-so="{{ $item->KDAUF ?? '-' }}"
        data-so-item="{{ $item->KDPOS ?? '-' }}"
        data-material-code="{{ $item->MATNR ? ltrim((string) $item->MATNR, '0') : '-' }}"
        data-description="{{ $item->MAKTX ?? '-' }}"
        data-plant="{{ $item->PWWRK ?? '-' }}" data-mrp="{{ $item->DISPO ?? '-' }}"
        data-qty-order="{{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}"
        data-qty-gr="{{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}"
        data-outs-gr="{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}"
        data-start-date="{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}"
        data-end-date="{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}"
        data-searchable-text="{{ strtolower(($item->KDAUF ?? '') . ' ' . ($item->AUFNR ?? '') . ' ' . ($item->MATNR ?? '') . ' ' . ($item->MAKTX ?? '')) }}">

        {{-- [DISEMBUNYIKAN DI MOBILE] --}}
        <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration + ($ongoingProData->currentPage() - 1) * $ongoingProData->perPage() }}</td>
        <td class="text-center small d-none d-md-table-cell" data-col="so">
            {{ $item->KDAUF ?? '-' }}</td>
        <td class="text-center small d-none d-md-table-cell" data-col="so_item">
            {{ $item->KDPOS ?? '-' }}</td>

        {{-- [TETAP TAMPIL DI MOBILE] --}}
        <td class="text-center small" data-col="pro">{{ $item->AUFNR ?? '-' }}</td>
        <td class="text-center" data-col="status"><span
                class="badge rounded-pill {{ $badgeClass }}">{{ $status ?: '-' }}</span>
        </td>

        {{-- [DISEMBUNYIKAN DI MOBILE] --}}
        <td class="text-center small d-none d-md-table-cell" data-col="material_code">
            {{ $item->MATNR ? ltrim((string) $item->MATNR, '0') : '-' }}</td>
        <td class="text-center small d-none d-md-table-cell" data-col="description">
            {{ $item->MAKTX ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="plant">
            {{ $item->PWWRK ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="mrp">
            {{ $item->DISPO ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="qty_order">
            {{ number_format($item->PSMNG ?? 0, 0, ',', '.') }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="qty_gr">
            {{ number_format($item->WEMNG ?? 0, 0, ',', '.') }}</td>
        <td class="small text-center fw-bold d-none d-md-table-cell" data-col="outs_gr">
            {{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 0, ',', '.') }}
        </td>
        <td class="small text-center d-none d-md-table-cell" data-col="start_date"
            data-sort-value="{{ $item->GSTRP ?? '0' }}">
            {{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}
        </td>
        <td class="small text-center d-none d-md-table-cell" data-col="end_date"
            data-sort-value="{{ $item->GLTRP ?? '0' }}">
            {{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}
        </td>
    </tr>
@endforeach

@if($ongoingProData->isEmpty() && $ongoingProData->currentPage() == 1)
    <tr>
        <td colspan="14" class="text-center p-5 text-muted">Tidak ada data Ongoing PRO.
        </td>
    </tr>
@endif
