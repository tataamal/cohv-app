@foreach($allProData ?? [] as $item)
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
        $dataStatus = in_array($status, ['REL', 'PCNF', 'CNF', 'CRTD', 'TECO'])
            ? $status
            : 'Lainnya';
    @endphp
    <tr class="clickable-row" data-status="{{ $dataStatus }}"
        data-no="{{ $loop->iteration + ($allProData->currentPage() - 1) * $allProData->perPage() }}" data-so="{{ $item->KDAUF ?? '-' }}"
        data-so-item="{{ $item->KDPOS ? ltrim((string) $item->KDPOS, '0') : '-' }}" data-pro="{{ $item->AUFNR ?? '-' }}"
        data-status-text="{{ $status ?: '-' }}" data-status-class="{{ $badgeClass }}"
        data-material-code="{{ $item->MATNR ? ltrim((string) $item->MATNR, '0') : '-' }}"
        data-description="{{ $item->MAKTX ?? '-' }}"
        data-plant="{{ $item->PWWRK ?? '-' }}" data-mrp="{{ $item->DISPO ?? '-' }}"
        data-qty-order="{{ number_format($item->PSMNG ?? 0, 2, ',', '.') }}"
        data-qty-gr="{{ number_format($item->WEMNG ?? 0, 2, ',', '.') }}"
        data-outs-gr="{{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 2, ',', '.') }}"
        data-start-date="{{ $item->GSTRP && $item->GSTRP != '00000000' ? \Carbon\Carbon::parse($item->GSTRP)->format('d M Y') : '-' }}"
        data-end-date="{{ $item->GLTRP && $item->GLTRP != '00000000' ? \Carbon\Carbon::parse($item->GLTRP)->format('d M Y') : '-' }}">
        <td class="text-center">
            <input class="form-check-input pro-checkbox" type="checkbox"
                value="{{ $item->AUFNR ?? '' }}"
                id="pro-check-{{ $loop->iteration + ($allProData->currentPage() - 1) * $allProData->perPage() }}">
        </td>
        <td class="text-center small d-none d-md-table-cell">{{ $loop->iteration + ($allProData->currentPage() - 1) * $allProData->perPage() }}</td>
        <td class="text-center small d-none d-md-table-cell" data-col="so">
            {{ $item->KDAUF ?? '-' }}</td>
        <td class="text-center small d-none d-md-table-cell" data-col="so_item">
            {{ $item->KDPOS ? ltrim((string) $item->KDPOS, '0') : '-' }}</td>
        <td class="text-center small" data-col="pro">{{ $item->AUFNR ?? '-' }}</td>
        <td class="text-center" data-col="status"><span
                class="badge rounded-pill {{ $badgeClass }}">{{ $status ?: '-' }}</span>
        </td>
        <td class="text-center small d-none d-md-table-cell" data-col="material_code">
            {{ $item->MATNR ? ltrim((string) $item->MATNR, '0') : '-' }}</td>
        <td class="text-center small d-none d-md-table-cell" data-col="description">
            {{ $item->MAKTX ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="plant">
            {{ $item->PWWRK ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="mrp">
            {{ $item->DISPO ?? '-' }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="qty_order">
            {{ number_format($item->PSMNG ?? 0, 2, ',', '.') }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="qty_gr">
            {{ number_format($item->WEMNG ?? 0, 2, ',', '.') }}</td>
        <td class="small text-center d-none d-md-table-cell" data-col="outs_gr">
            {{ number_format(($item->PSMNG ?? 0) - ($item->WEMNG ?? 0), 2, ',', '.') }}
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

@if($allProData->isEmpty() && $allProData->currentPage() == 1)
    <tr>
        <td colspan="15" class="text-center p-5 text-muted">Tidak ada data Total Pro.</td>
    </tr>
@endif
