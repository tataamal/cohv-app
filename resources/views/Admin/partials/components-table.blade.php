{{-- resources/views/admin/partials/components-table.blade.php --}}

<div class="alert alert-info small">
    Data Komponen (TData4) secara otomatis terkait dengan PRO yang Anda lihat. Detail per PRO dapat dilihat di tab **Order Overview** dengan mengklik tombol **Comp**.
</div>

@forelse ($componentData as $aufnr => $components)
    <h6 class="mt-4 mb-2">PRO: {{ $aufnr }}</h6>
    <div class="table-responsive border rounded mb-4">
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr class="align-middle">
                    <th class="text-center" style="width: 5%;">No.</th>
                    <th class="text-center">Material</th>
                    <th class="text-center">Description</th>
                    <th class="text-center">Required Qty</th>
                    <th class="text-center">Storage Loc.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($components as $comp)
                    <tr class="align-middle">
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">{{ $comp->MATNR ? trim($comp->MATNR, '0') : '-' }}</td>
                        <td class="text-center">{{ $comp->MAKTX ?? '-' }}</td>
                        <td class="text-center">{{ $comp->BDMNG ?? '-' }} {{ $comp->MEINS ?? '-' }}</td>
                        <td class="text-center">{{ $comp->LGORT ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="alert alert-warning">Tidak ada data Komponen (TData4) ditemukan untuk PRO terkait.</div>
@endforelse