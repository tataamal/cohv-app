<x-layouts.app title="Dashboard Plant">

    <!-- Header Section -->
    <div class="mb-10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard Plant - {{ $nama_bagian }}</h1>
                <p class="mt-2 text-base text-gray-500">Berikut adalah report dari data COHV</p>
            </div>
            <div class="mt-4 sm:mt-0 text-sm font-medium text-gray-500">
                {{ now()->format('l, d F Y') }}
            </div>
        </div>
    </div>

    <!-- [DIPERBARUI] Stats Cards Row dengan tata letak yang lebih ringkas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        
        <!-- Card 1: Outstanding Order -->
        <div class="bg-white p-5 rounded-xl border border-gray-200">
            <div class="flex items-center">
                <div class="p-2.5 bg-sky-50 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-sm font-medium text-gray-600">Outstanding Sales Order</p>
            </div>
            <p class="stat-value text-3xl font-bold text-gray-900 mt-3" data-target="{{ $TData2 }}">0</p>
        </div>

        <!-- Card 2: Total PRO -->
        <div class="bg-white p-5 rounded-xl border border-gray-200">
            <div class="flex items-center">
                <div class="p-2.5 bg-indigo-50 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <p class="text-sm font-medium text-gray-600">Total PRO</p>
            </div>
            <p class="stat-value text-3xl font-bold text-gray-900 mt-3" data-target="{{ $TData3 }}">0</p>
        </div>

        <!-- Card 3: Total Outstanding Reservasi -->
        <div class="bg-white p-5 rounded-xl border border-gray-200">
            <div class="flex items-center">
                <div class="p-2.5 bg-amber-50 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-sm font-medium text-gray-600">Outstanding Reservasi</p>
            </div>
            <p class="stat-value text-3xl font-bold text-gray-900 mt-3" data-target="{{ $outstandingReservasi }}">0</p>
        </div>
        
    </div>

    <!-- Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Bar Chart Container -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-gray-200">
             <h3 class="text-lg font-semibold text-gray-800">Data Kapasitas Workcenter</h3>
             <p class="text-sm text-gray-500 mb-6">Perbandingan Display Jumlah PRO dan Kapasitas di setiap Workcenter</p>
            <div class="h-96">
                <x-charts.bar-chart
                    chartId="workcenterChart"
                    :labels="$labels"
                    :datasets="$datasets"
                />
            </div>
        </div>

        <!-- Doughnut Chart Container -->
        <div class="bg-white p-6 rounded-xl border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Status PRO</h3>
            <p class="text-sm text-gray-500 mb-6">Perbandingan status pada field PRO.</p>
            <div class="h-96 flex items-center justify-center">
                 <x-charts.bar-chart 
                    chartId="proStatusChart"
                    type="pie"
                    :labels="$doughnutChartLabels"
                    :datasets="$doughnutChartDatasets"
                />
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="bg-white p-6 rounded-xl border border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">List Data Reservasi</h3>
                    <p class="text-sm text-gray-500">Detail item material yang telah direservasi.</p>
                </div>
                <form method="GET" action="{{ url()->current() }}" class="flex items-center w-full sm:w-auto sm:max-w-xs">
                    <div class="relative flex-grow">
                        <input type="text" name="search_reservasi" value="{{ request('search_reservasi') }}" placeholder="Cari Reservasi..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <div class="absolute top-0 left-0 inline-flex items-center p-2.5">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full table-auto text-sm text-left">
                    <thead class="bg-purple-50 text-purple-800 font-semibold uppercase text-xs whitespace-nowrap">
                        <tr>
                            <th class="px-4 py-3 text-center">No.</th>
                            <th class="px-4 py-3 text-center">No. Reservasi</th>
                            <th class="px-4 py-3 text-center">Kode Material</th>
                            <th class="px-4 py-3 text-center">Deskripsi Material</th>
                            <th class="px-4 py-3 text-center">Req. Qty</th>
                            <th class="px-4 py-3 text-right">Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($TData4 as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 ">{{ $item->RSNUM ?? '-' }}</td>
                                <td class="px-4 py-3 ">{{ $item->MATNR ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item->MAKTX ?? '-' }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ number_format($item->BDMNG ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center font-medium text-blue-600">
                                    {{ number_format(($item->BDMNG ?? 0) - ($item->KALAB ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    Tidak ada data reservasi ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $TData4->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function animateCountUp(element) {
                const target = parseInt(element.dataset.target, 10);
                const duration = 1500; // Durasi animasi dalam milidetik
                let startTime = null;

                function step(timestamp) {
                    if (!startTime) startTime = timestamp;
                    const progress = Math.min((timestamp - startTime) / duration, 1);
                    const currentValue = Math.floor(progress * target);
                    element.textContent = currentValue.toLocaleString('id-ID');

                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        element.textContent = target.toLocaleString('id-ID');
                    }
                }
                window.requestAnimationFrame(step);
            }

            const valueElements = document.querySelectorAll('.stat-value');
            valueElements.forEach(el => animateCountUp(el));
        });
    </script>
    @endpush

</x-layouts.app>