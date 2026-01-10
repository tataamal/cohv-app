<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        
        <div class="p-3 bg-white border-bottom">
            <h6 class="fw-semibold mb-0 text-dark">Routings</h6>
        </div>

        <div class="table-responsive @if($routings->count() > 2) table-responsive-custom-scroll @endif">
            
            <table class="table table-sm table-bordered table-hover mb-0 align-middle" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr class="text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                        <th class="text-center bg-light" style="width: 40px;"><input class="form-check-input " type="checkbox" id="select-all-pro" title="Pilih semua"></th>
                        <th class="text-center bg-light" scope="col">No</th>
                        <th class="text-center bg-light" scope="col">PRO</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">SO - Item</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">WC</th>
                        <th class="text-start bg-light" scope="col">Material Description</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">Op Key</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">Qty Order</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">Qty GR</th>
                        <th class="text-center bg-light" scope="col">Qty Sisa</th>
                        <th class="text-center bg-light" scope="col">Time Req (Min)</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">PV1</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">PV2</th>
                        <th class="text-center bg-light d-none-mobile" scope="col">PV3</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routings as $route)
                        @php
                            // Mapping Data
                            $routeVornr = $route['VORNR'] ?? '-';
                            $routeArbpl = $route['ARBPL'] ?? '-';
                            $routeSteus = $route['STEUS'] ?? '-';
                            $routePv1 = $route['PV1'] ?? '-';
                            $routePv2 = $route['PV2'] ?? '-';
                            $routePv3 = $route['PV3'] ?? '-';

                            // Numeric Values from Array
                            $qtyOrder = isset($route['MGVRG2']) ? floatval($route['MGVRG2']) : 0;
                            $qtyGr    = isset($route['LMNGA']) ? floatval($route['LMNGA']) : 0;
                            $qtySisa  = isset($route['MENGE2']) ? floatval($route['MENGE2']) : 0;

                            // Time Calculation
                            $stdValue = isset($route['VGW01']) ? floatval($route['VGW01']) : 0;
                            $unit     = $route['VGE01'] ?? 'MIN'; // Default MIN
                            
                            $timeReq = $qtySisa * $stdValue;
                            if (strtoupper($unit) === 'S') {
                                $timeReq = $timeReq / 60;
                            }
                        @endphp

                        <tr>
                            <td class="text-center"><input class="form-check-input pro-select-checkbox" type="checkbox" value="{{ $parentPro->AUFNR }}"></td>
                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                            <td class="text-center font-monospace fw-semibold text-dark">{{ $parentPro->AUFNR }}</td>
                            <td class="text-center d-none-mobile font-monospace text-secondary">
                                {{ $parentPro->KDAUF }}{{ ($parentPro->KDAUF && stripos($parentPro->KDAUF, 'make stock') === false) ? ' - ' . (int)$parentPro->KDPOS : '' }}
                            </td>
                            <td class="text-center d-none-mobile">
                                <span class="badge bg-secondary rounded-0 fw-normal font-monospace">{{ $routeArbpl }}</span>
                            </td>
                            <td class="text-start text-dark fw-medium">{{ $parentPro->MAKTX }}</td>
                            <td class="text-center d-none-mobile">
                                <span class="badge bg-dark rounded-0 fw-normal font-monospace">{{ $routeSteus }}</span>
                            </td>
                            <td class="text-center d-none-mobile font-monospace">{{ number_format($qtyOrder, 0, ',', '.') }}</td>
                            <td class="text-center d-none-mobile font-monospace text-muted">{{ number_format($qtyGr, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success rounded-0 fw-bold font-monospace px-2">
                                    {{ number_format($qtySisa, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center font-monospace fw-bold text-primary">
                                {{ number_format($timeReq, 1, ',', '.') }}
                            </td>
                            <td class="text-center d-none-mobile small text-muted">{{ $routePv1 }}</td>
                            <td class="text-center d-none-mobile small text-muted">{{ $routePv2 }}</td>
                            <td class="text-center d-none-mobile small text-muted">{{ $routePv3 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>