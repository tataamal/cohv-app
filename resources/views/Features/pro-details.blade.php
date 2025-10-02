<x-layouts.app title="Production Orders - {{ $buyerName }}">

    {{-- KONTEN UTAMA HALAMAN INI --}}
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-box-seam me-3" style="font-size: 2rem; color: #4b5563;"></i>
        <div>
            <h4 class="mb-0">Ongoing Production Orders</h4>
            <p class="mb-0 text-muted">Production monitoring for {{ $buyerName }}</p>
        </div>
    </div>
    
    <div class="card card-body shadow-sm mb-4">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <div class="summary-card d-flex align-items-center">
                    <div class="icon me-3"><i class="bi bi-journal-text"></i></div>
                    <div>
                        <h6>Total PRO's</h6>
                        <span class="value">{{ $totalPro }}</span> <span class="sub-value">orders</span>
                        <div class="sub-value mt-1">Count All PRO</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <div class="summary-card d-flex align-items-center">
                    <div class="icon me-3"><i class="bi bi-box"></i></div>
                    <div>
                        <h6>Order Quantity</h6>
                        <span class="value">{{ number_format($totalOrderQuantity) }}</span> <span class="sub-value">units</span>
                        <div class="sub-value mt-1">Completed: {{ number_format($proList->sum('WEMNG')) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="summary-card d-flex align-items-center">
                    <div class="icon me-3"><i class="bi bi-pie-chart"></i></div>
                    <div>
                        <h6>Completion Rate</h6>
                        <span class="value">{{ $completionRate }}</span>
                        <div class="sub-value mt-1">Avg Progress: {{ number_format($proList->avg('progress_percentage'), 1) }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row pro-row-header d-none d-lg-flex">
        <div class="col-lg-2">PRO Number</div>
        <div class="col-lg-4">Item Details</div>
        <div class="col-lg-4">Production Progress</div>
        <div class="col-lg-2 text-end">Deadline</div>
    </div>

    <div class="pro-list-container">
        @forelse ($proList as $pro)
            <div class="pro-row {{ $pro->status_class }}">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-2">
                        <strong class="d-block">{{ $pro->AUFNR }}</strong>
                        <span class="status-badge {{ $pro->status_class }}">
                            @if ($pro->status_text === 'Overdue')
                                <i class="bi bi-clock-history me-1"></i> Overdue {{ $pro->overdue_days }}d
                            @elseif ($pro->status_text === 'Created')
                                <i class="bi bi-plus-circle me-1"></i> Created
                            @else
                                <i class="bi bi-check-circle me-1"></i> On Schedule
                            @endif
                        </span>
                    </div>
                    <div class="col-lg-4">
                        <strong class="d-block">{{ $pro->MAKTX }}</strong>
                        <span class="item-code">Code: {{ $pro->MATNR }}</span>
                    </div>
                    <div class="col-lg-4 progress-container">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Progress</span>
                            <strong>{{ round($pro->WEMNG) }} / {{ round($pro->PSMNG) }}</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pro->progress_percentage }}%;" aria-valuenow="{{ $pro->progress_percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-muted small mt-1">Outstanding: {{ number_format($pro->PSMNG - $pro->WEMNG) }}</div>
                    </div>
                    <div class="col-lg-2 text-lg-end timeline">
                        <i class="bi bi-calendar-event"></i>
                        <span>{{ $pro->formatted_deadline }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="card card-body text-center">
                <p class="mb-0">Tidak ada Production Order yang ditemukan untuk kriteria ini.</p>
            </div>
        @endforelse
    </div>
    
</x-layouts.app>