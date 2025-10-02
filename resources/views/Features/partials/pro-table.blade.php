<div class="table-scroll-container">
    {{-- [DIUBAH] Tambahkan class .table-sm dan .table-extra-sm --}}
    <table class="table table-hover table-sm table-extra-sm align-middle">
        <thead>
            <tr class="align-middle text-center">
                <th>No.</th>
                <th>SO</th>
                <th>SO Item</th>
                <th>PRO</th>
                <th>Status</th>
                <th>Material Code</th>
                <th>Description</th>
                <th>Plant</th>
                <th>MRP</th>
                <th>Qty. Order</th>
                <th>Qty. GR</th>
                <th>Outs. GR</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
            <tbody>
                @forelse ($pros as $pro)
                    <tr class="align-middle text-center">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $pro->KDAUF }}</td>
                        <td>{{ $pro->KDPOS }}</td>
                        <td>{{ $pro->AUFNR }}</td>
                        <td>{{ $pro->STATS }}</td>
                        <td>{{ $pro->MATNR }}</td>
                        <td>{{ $pro->MAKTX }}</td>
                        <td>{{ $pro->PWWRK }}</td>
                        <td>{{ $pro->DISPO }}</td>
                        <td>{{ $pro->PSMNG }}</td>
                        <td>{{ $pro->WEMNG }}</td>
            
                        {{-- [DIPERBAIKI] Gunakan null coalescing operator (??) untuk keamanan --}}
                        <td>{{ ($pro->KALAB ?? 0) - ($pro->WEMNG ?? 0) }}</td>
            
                        {{-- [DIPERBAIKI] Tambahkan pengecekan @if sebelum memformat tanggal --}}
                        <td>
                            @if($pro->GLTRP)
                                {{ \Carbon\Carbon::parse($pro->GLTRP)->format('d M Y') }}
                            @else
                                - {{-- Tampilkan strip jika tanggalnya null --}}
                            @endif
                        </td>
                        <td>
                            @if($pro->GSTRP)
                                {{ \Carbon\Carbon::parse($pro->GSTRP)->format('d M Y') }}
                            @else
                                - {{-- Tampilkan strip jika tanggalnya null --}}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-4">Data tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
    </table>
</div>
