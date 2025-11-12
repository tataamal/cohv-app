<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        
        <div class="p-3 bg-white border-bottom">
            <h6 class="fw-semibold mb-0 text-dark">Routings</h6>
        </div>

        <div class="table-responsive @if($routings->count() > 2) table-responsive-custom-scroll @endif">
            
            <table class="table table-hover table-v-bordered table-sm mb-0 small">
                <thead class="table-light sticky-header-custom">
                    <tr>
                        <th class="text-center p-2 d-none d-md-table-cell" style="width: 5%;">No.</th>
                        <th class="text-center p-2 d-none d-md-table-cell">Activity</th>
                        <th class="text-center p-2 d-none d-md-table-cell">Ctrl Key</th>
                        <th class="p-2 d-none d-md-table-cell">Description</th>
                        <th class="text-center p-2 d-none d-md-table-cell">Work Ctr</th>
                        <th class="text-center p-2 d-none d-md-table-cell">Time (H)</th>
                        <th class="text-center p-2 d-none d-md-table-cell">Item/Day</th>
                        <th class="text-center p-2 d-none d-md-table-cell">Total Time</th>
                        <th class="text-center p-2 d-none d-md-table-cell">PV 1</th>
                        <th class="text-center p-2 d-none d-md-table-cell">PV 2</th>
                        <th class="text-center p-2 d-none d-md-table-cell">PV 3</th>
                        <th class="p-2 d-md-none" colspan="10">Routing Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routings as $route)
                        @php
                            $hasilPerHari = '-';
                            $totalTime = '-';
                            $kapazRaw = $route['KAPAZ'] ?? '0';
                            $kapazStr = str_replace(',', '.', $kapazRaw);
                            $kapazNum = floatval($kapazStr) ?: 0;
                            $vgw01Raw = $route['VGW01'] ?? '0';
                            $vgw01Clean = preg_replace('/[^0-9,\.]/', '', $vgw01Raw);
                            $vgw01Str = str_replace(',', '.', $vgw01Clean);
                            $vgw01Num = floatval($vgw01Str) ?: 0;
                            $psmngRaw = $route['PSMNG'] ?? '0';
                            $psmngClean = preg_replace('/[^0-9,\.]/', '', $psmngRaw);
                            $psmngStr = str_replace(',', '.', $psmngClean);
                            $psmngNum = floatval($psmngStr) ?: 0;
                            $vge01 = trim($route['VGE01'] ?? '');

                            if ($kapazNum > 0 && $vgw01Num > 0) {
                                $result = ($vge01 === 'S') 
                                            ? ($kapazNum * 3600) / $vgw01Num 
                                            : ($kapazNum * 60) / $vgw01Num;
                                $hasilPerHari = floor($result);
                            }
                            if ($vgw01Num > 0 && $psmngNum > 0) {
                                $totalTimeVal = $vgw01Num * $psmngNum;
                                if ($vge01 === 'S') {
                                    $totalTimeVal = $totalTimeVal / 60; // ubah ke menit
                                }
                                $totalTime = round($totalTimeVal, 2);
                            }

                            $routeKtext = $route['KTEXT'] ?? '-';
                            $routeVornr = $route['VORNR'] ?? '-';
                            $routeArbpl = $route['ARBPL'] ?? '-';
                            $routeSteus = $route['STEUS'] ?? '-';
                            $routeCpctyx = $route['CPCTYX'] ?? '-';
                            $routePv1 = $route['PV1'] ?? '-';
                            $routePv2 = $route['PV2'] ?? '-';
                            $routePv3 = $route['PV3'] ?? '-';
                        @endphp

                        <tr>
                            <td class="text-center d-none d-md-table-cell">{{ $loop->iteration }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $routeVornr }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $routeSteus }}</td>
                            <td class="d-none d-md-table-cell">{{ $routeKtext }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $routeArbpl }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $routeCpctyx }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $hasilPerHari }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $totalTime }}</td> <!-- 🔹 Field baru -->
                            <td class="text-center d-none d-md-table-cell">{{ $routePv1 }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $routePv2 }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $routePv3 }}</td>

                            <td class="d-md-none" colspan="11" style="padding: 4px; background-color: #f8f9fa;">
                                <div class="bg-white border rounded-3 shadow-sm p-3">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                        <div>
                                            <div class="fw-bold text-dark">{{ $routeKtext }}</div>
                                            <div class="text-muted small">Activity: {{ $routeVornr }} | Work Ctr: {{ $routeArbpl }}</div>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2" style="grid-template-columns: 1fr 1fr;">
                                        <div>
                                            <div class="small text-muted">Time (H)</div>
                                            <div class="fw-semibold">{{ $routeCpctyx }}</div>
                                        </div>
                                        <div>
                                            <div class="small text-muted">Item/Day</div>
                                            <div class="fw-semibold">{{ $hasilPerHari }}</div>
                                        </div>
                                        <div>
                                            <div class="small text-muted">Total Time (min)</div>
                                            <div class="fw-semibold">{{ $totalTime }}</div> <!-- 🔹 Field baru -->
                                        </div>
                                        <div>
                                            <div class="small text-muted">Ctrl Key</div>
                                            <div class="fw-semibold">{{ $routeSteus }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="small text-muted mb-1">Production Version:</div>
                                        <div class="d-grid gap-1" style="grid-template-columns: 1fr 1fr 1fr;">
                                            <div class="bg-light p-2 rounded-2 text-center"><div class="text-muted small">PV 1</div><div class="fw-medium">{{ $routePv1 }}</div></div>
                                            <div class="bg-light p-2 rounded-2 text-center"><div class="text-muted small">PV 2</div><div class="fw-medium">{{ $routePv2 }}</div></div>
                                            <div class="bg-light p-2 rounded-2 text-center"><div class="text-muted small">PV 3</div><div class="fw-medium">{{ $routePv3 }}</div></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>