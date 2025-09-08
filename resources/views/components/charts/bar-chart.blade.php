@props([
    'chartId',
    'type' => 'bar',
    'labels' => [],
    'datasets' => [],
])

{{-- Komponen ini sekarang hanya merender canvas dengan data. Tidak ada JS. --}}
<canvas 
    id="{{ $chartId }}" 
    class="chart-canvas" {{-- Tambahkan class ini sebagai penanda --}}
    data-type="{{ $type }}"
    data-labels="{{ json_encode($labels) }}"
    data-datasets="{{ json_encode($datasets) }}"
></canvas>