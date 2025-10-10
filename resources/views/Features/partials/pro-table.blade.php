{{-- 
    File ini sekarang berisi logika untuk merender DAFTAR KARTU.
    Ini dipanggil oleh controller dan AJAX untuk menampilkan hasil filter.
    CSS telah dipindahkan ke view utama untuk memastikan selalu dimuat.
--}}

@forelse ($pros as $pro)
    @php
        // Helper untuk mem-parsing tanggal dengan aman
        $safeCarbon = function($date) {
            if (empty($date) || $date == '0000-00-00' || $date == '00000000') return null;
            try {
                return \Carbon\Carbon::parse($date)->startOfDay();
            } catch (\Exception $e) {
                return null;
            }
        };

        $stats = strtoupper($pro->STATS ?? '');
        $today = \Carbon\Carbon::today();
        $startDate = $safeCarbon($pro->GSTRP);
        $finishDate = $safeCarbon($pro->GLTRP);

        // Perhitungan Kuantitas & Progress
        $reqQty = (float)($pro->PSMNG ?? 0);
        $grQty = (float)($pro->WEMNG ?? 0);
        $outsQty = $reqQty - $grQty;
        $progress = ($reqQty > 0) ? ($grQty / $reqQty) * 100 : 0;
        $progress = min($progress, 100);

        // Logika Status & Warna
        $statusConfig = ['icon' => 'bi-question-circle', 'color' => 'dark', 'text' => $pro->STATS ?? 'Unknown'];

        if (str_contains($stats, 'CRTD')) {
            $statusConfig = ['icon' => 'bi-file-earmark-plus', 'color' => 'secondary', 'text' => 'Created'];
        } 
        elseif (str_contains($stats, 'TECO')) {
            $statusConfig = ['icon' => 'bi-check-circle-fill', 'color' => 'primary', 'text' => 'Completed'];
        }
        elseif (str_contains($stats, 'REL') || str_contains($stats, 'PCNF')) {
            if ($finishDate && $today->gt($finishDate)) {
                $statusConfig = ['icon' => 'bi-clock-history', 'color' => 'danger', 'text' => 'Overdue'];
            } elseif ($startDate && $today->isSameDay($startDate)) {
                $statusConfig = ['icon' => 'bi-arrow-right-circle-fill', 'color' => 'warning', 'text' => 'Ongoing'];
            } elseif ($startDate && $finishDate && $today->between($startDate, $finishDate, true)) {
                $statusConfig = ['icon' => 'bi-calendar-check', 'color' => 'success', 'text' => 'On Schedule'];
            } else {
                $statusConfig = ['icon' => 'bi-play-circle', 'color' => 'dark', 'text' => 'Released'];
            }
        }
    @endphp

    <div class="pro-card border-start-{{ $statusConfig['color'] }}">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-7">
                <h5 class="pro-card-pro-number mb-1">{{ $pro->AUFNR ?? '-' }}</h5>
                <p class="pro-card-material text-muted mb-2">
                    {{ (int)($pro->MATNR ?? '') }} - {{ $pro->MAKTX ?? 'No description' }}
                </p>
                <div class="pro-card-buyer-so">
                    <span class="fw-bold text-dark" title="Buyer">{{ $pro->NAME1 ?? 'Unknown Buyer' }}</span>
                    <span class="text-muted small mx-1">•</span>
                    <span title="Sales Order">SO: <strong class="text-primary">{{ $pro->KDAUF ?? '' }}-{{ $pro->KDPOS ?? '' }}</strong></span>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge text-bg-{{ $statusConfig['color'] }} bg-opacity-10 text-{{ $statusConfig['color'] }}-emphasis border border-{{ $statusConfig['color'] }}-subtle">
                        <i class="bi {{ $statusConfig['icon'] }} me-1"></i>
                        {{ $statusConfig['text'] }}
                    </span>
                    <span class="text-muted small" title="Finish Date">
                        <i class="bi bi-calendar-event me-1"></i>
                        {{ $finishDate ? $finishDate->format('d M Y') : '-' }}
                    </span>
                </div>
                <div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Progress</small>
                        <small class="fw-bold">{{ number_format($grQty) }} / {{ number_format($reqQty) }}</small>
                    </div>
                    <div class="progress" style="height: 8px;" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-{{ $statusConfig['color'] }}" style="width: {{ $progress }}%;"></div>
                    </div>
                    @if($outsQty > 0)
                        <small class="text-muted d-block text-end mt-1">Outstanding: {{ number_format($outsQty) }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

@empty
    <div class="text-center p-5">
        <i class="bi bi-inbox fs-2 text-muted"></i>
        <h5 class="mt-2">Tidak Ada Data PRO</h5>
        <p class="text-muted">Tidak ada data produksi yang cocok untuk filter ini.</p>
    </div>
@endforelse

