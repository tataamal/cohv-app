{{-- resources/views/admin/partials/routing-table.blade.php --}}

<div class="alert alert-info small">
    Data Routing (TData1) secara otomatis terkait dengan PRO yang Anda lihat. Detail per PRO dapat dilihat di tab **Order Overview** dengan mengklik tombol **Route**.
</div>

@forelse ($routingData as $aufnr => $routes)
    <h6 class="mt-4 mb-2">PRO: {{ $aufnr }}</h6>
    <div class="table-responsive border rounded mb-4">
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr>
                    <th class="text-center" style="width: 5%;">No.</th>
                    <th class="text-center">Activity</th>
                    <th class="text-center">Work Center</th>
                    <th class="text-center">Description</th>
                    <th class="text-center">Kapasitas</th>
                    <th class="text-center">Control Key</th>
                    <th>PV 1/2/3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($routes as $route)
                    <tr>
                        <td class="text-center">{{ $route->VORNR ?? '-' }}</td>
                        <td class="text-center">{{ $route->KTEXT ?? '-' }}</td>
                        <td class="text-center">{{ $route->ARBPL ?? '-' }}</td>
                        <td>{{ $route->LTXA1 ?? '-' }}</td>
                        <td class="text-center">{{ $route->KAPAZ ?? '-' }}</td>
                        <td class="text-center">{{ $route->STEUS ?? '-' }}</td>
                        <td>{{ $route->PV1 ?? '-' }} / {{ $route->PV2 ?? '-' }} / {{ $route->PV3 ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="alert alert-warning">Tidak ada data Routing (TData1) yang ditemukan untuk PRO terkait.</div>
@endforelse