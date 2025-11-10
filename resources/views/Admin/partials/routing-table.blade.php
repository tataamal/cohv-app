<div class="table-responsive">
    <table class="table table-sm table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th class="text-center" style="width: 5%;">No.</th>
                <th>Operation</th>
                <th>Work Center</th>
                <th>Description</th>
                <th class="text-center">Control Key</th>
                <th class="text-center">Std. Time</th>
            </tr>
        </thead>
        <tbody>
            {{-- [PERBAIKAN] Mengubah $route->FIELD menjadi $route['FIELD'] --}}
            @foreach($routings as $route)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $route['VORNR'] }}</td>
                    <td>{{ $route['ARBPL'] }}</td>
                    <td>{{ $route['KTEXT'] }}</td>
                    <td class="text-center">{{ $route['STEUS'] }}</td>
                    <td class="text-center">{{ $route['VGW01'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>