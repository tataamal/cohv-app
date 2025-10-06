{{-- resources/views/admin/partials/routing-table.blade.php --}}

@forelse ($routingData as $aufnr => $routes)
<div class="alert alert-info small">
    Informasi Routing dari PRO {{ $aufnr }} .
</div>
    <h6 class="mt-4 mb-2">PRO: {{ $aufnr }}</h6>
    <div class="table-responsive border rounded mb-4">
        <table class="table table-bordered table-sm">
            {{-- THEAD DISESUAIKAN DENGAN MARKUP JAVASCRIPT --}}
            <thead class="bg-purple-light text-purple-dark">
                <tr class="align-middle">
                    <th scope="col" class="text-center fs-6">No.</th>
                    <th scope="col" class="text-center">Activity</th>
                    <th scope="col" class="text-center">Control Key</th>
                    <th scope="col" class="text-center">Description</th>
                    <th scope="col" class="text-center">Work Center</th>
                    <th scope="col" class="text-center">Time Capacity (Hours)</th>
                    <th scope="col" class="text-center">Item/Day</th>
                    <th scope="col" class="text-center">PV 1</th>
                    <th scope="col" class="text-center">PV 2</th>
                    <th scope="col" class="text-center">PV 3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($routes as $i => $route)
                @php
                    $kapazStr = $route->KAPAZ ?? '0';
                    $vgw01Str = $route->VGW01 ?? '0';
                    $hasilPerHari = '-';

                    $kapazNum = (float) $kapazStr; // Seharusnya 6.75
                    $vgw01Num = (float) $vgw01Str; // Seharusnya 1200.0 (dari 1.200,000)

                    // Cek untuk memastikan input valid
                    if ($kapazNum > 0 && $vgw01Num > 0) {
                        $multiplier = 0;
                        if ($route->VGE01 === 'S') {
                            $multiplier = 3600;
                        } else {
                            $multiplier = 60;
                        }
                        $result = ($kapazNum * $multiplier) / $vgw01Num;
                        $hasilPerHari = floor($result); // 20.25 menjadi 20
                    }
                @endphp
                    
                    <tr class="bg-white">
                        <td class="text-center fs-6">{{ $i + 1 }}</td>
                        <td class="text-center">{{ $route->VORNR ?? '-' }}</td>
                        <td class="text-center">{{ $route->STEUS ?? '-' }}</td>
                        <td class="text-center">{{ $route->KTEXT ?? '-' }}</td>
                        <td class="text-center">{{ $route->ARBPL ?? '-' }}</td> 
                        <td class="text-center">{{ $route->KAPAZ ?? '-' }}</td>
                        <td class="text-center">{{ $hasilPerHari }}</td>
                        <td class="text-center">{{ $route->PV1 ?? '-' }}</td>
                        <td class="text-center">{{ $route->PV2 ?? '-' }}</td>
                        <td class="text-center">{{ $route->PV3 ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="alert alert-warning">Tidak ada data Routing (TData1) yang ditemukan untuk PRO terkait.</div>
@endforelse