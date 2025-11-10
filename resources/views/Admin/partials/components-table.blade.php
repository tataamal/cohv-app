<div class="table-responsive">
    <table class="table table-sm table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th class="text-center" style="width: 5%;">No.</th>
                <th>Item</th>
                <th>Material</th>
                <th>Description</th>
                <th class="text-center">Req. Qty</th>
                <th class="text-center">UoM</th>
                <th class="text-center">Storage Loc.</th>
                <th class="text-center">Item Cat.</th>
                <th class="text-center">Outs. Req</th>
            </tr>
        </thead>
        <tbody>
            {{-- [PERBAIKAN] Mengubah $comp->FIELD menjadi $comp['FIELD'] --}}
            @foreach($components as $comp)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $comp['RSPOS'] }}</td>
                    <td>{{ $comp['MATNR'] }}</td>
                    <td>{{ $comp['MAKTX'] }}</td>
                    <td class="text-center">{{ $comp['BDMNG'] }}</td>
                    <td class="text-center">{{ $comp['MEINS'] }}</td>
                    <td class="text-center">{{ $comp['LGORT'] }}</td>
                    <td class="text-center">{{ $comp['SOBSL'] }}</td>
                    <td class="text-center">{{ $comp['OUTSREQ'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>