<x-layouts.app title="Good Receipt Report">
    <!-- Header Halaman dengan Design Lebih Modern -->
    <div class="mb-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-2 lg:p-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex-1">
                    @isset($kode)
                        <div class="flex items-center space-x-3 mb-2">
                            <div class="w-2 h-6 bg-green-500 rounded-full"></div>
                            <h1 class="text-md font-medium text-gray-900">
                                Report untuk Kode: <span class="text-green-600">{{ $kode }}</span>
                            </h1>
                        </div>
                        <p class="text-sm text-gray-600 ml-5">List Good Receipt (GR)</p>
                    @else
                        <div class="flex items-center space-x-3 mb-2">
                            <div class="w-2 h-4 bg-purple-500 rounded-full"></div>
                            <h1 class="text-lg font-medium text-gray-900">Good Receipt Report</h1>
                        </div>
                        <p class="text-sm text-gray-600 ml-5">Laporan Penerimaan Material</p>
                    @endisset
                    @isset($error)
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl ml-5">
                            <p class="text-sm text-red-600">{{ $error }}</p>
                        </div>
                    @endisset
                </div>
                <div class="mt-6 lg:mt-0 lg:ml-8">
                    <div class="inline-flex items-center px-4 py-2 bg-gray-50 rounded-xl border border-gray-200">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">{{ now()->format('l, d F Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten Utama: Kalender sebagai fokus utama -->
    <div class="space-y-4">
        
        <!-- Container Kalender - Full Width -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-medium text-gray-900">Kalender Good Receipt</h3>
                        <p class="text-sm text-gray-600 mt-1">Klik pada tanggal untuk melihat detail lengkap</p>
                    </div>
                    <div class="w-8 h-8 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <div id="calendar" class="modern-calendar"></div>>
            </div>
        </div>

        <!-- Container Tabel Utama dengan Design Modern -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-blue-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-medium text-gray-900">Detail Semua Data</h3>
                        <p class="text-sm text-gray-600 mt-1">Rincian lengkap semua item material yang diterima</p>
                    </div>
                    <div class="w-8 h-8 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="overflow-hidden">
                <div class="overflow-y-auto" style="max-height: 500px;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-purple-50 to-indigo-50 sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    PRO
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Material Desctiption
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Sales Order
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    SO Item
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Quantity PRO
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Quantity GR
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">
                                    Tgl. Posting
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($dataGr as $index => $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-200 {{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50/50' }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-1.5 h-1.5 bg-purple-400 rounded-full"></div>
                                            <span class="text-xs font-medium  text-gray-900">{{ $item->AUFNR ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-xs text-gray-900 max-w-xs">{{ $item->MAKTX ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $item->KDAUF ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $item->KDPOS ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="text-xs text-gray-900">{{ number_format($item->PSMNG ?? 0) }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="text-xs font-medium text-green-600">{{ number_format($item->MENGE ?? 0) }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ \Carbon\Carbon::parse($item->BUDAT_MKPF)->format('d M Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center space-y-3">
                                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Tidak ada data</p>
                                                <p class="text-sm text-gray-500">Tidak ada data yang cocok untuk ditampilkan</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup dengan Design Modern -->
    <div id="detail-modal" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg sm:max-w-2xl lg:max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-8 bg-purple-500 rounded-full"></div>
                    <h3 id="modal-title" class="text-base font-medium text-gray-900">Detail Tanggal:</h3>
                </div>
                <button id="close-modal-btn" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-purple-50 to-indigo-50 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">PRO</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Material Description</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Sales Order</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">SO Item</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Quantity PRO</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Quantity GR</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Posting Date</th>
                        </tr>
                    </thead>
                    <tbody id="modal-table-body" class="bg-white divide-y divide-gray-100">
                        <!-- Diisi oleh JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Style untuk kalender tetap dibutuhkan di sini agar spesifik untuk halaman ini --}}
    <style>
        .modern-calendar {
            --fc-border-color: #f3f4f6;
            --fc-button-bg-color: #6366f1;
            --fc-button-border-color: #6366f1;
            --fc-button-hover-bg-color: #4f46e5;
            --fc-button-hover-border-color: #4f46e5;
            --fc-button-active-bg-color: #4338ca;
            --fc-button-active-border-color: #4338ca;
            --fc-today-bg-color: #f8fafc;
        }
        /* ... (Sisa style Anda yang lain) ... */
        .calendar-summary {
            padding: 4px !important;
            font-size: 0.75rem !important;
        }

        .calendar-summary .summary-total {
            padding: 2px 4px !important;
            font-size: 0.75rem !important;
            margin-bottom: 2px !important;
        }

        .calendar-summary .summary-breakdown {
            padding: 2px 4px !important;
            font-size: 0.75rem !important;
        }
    </style>

    <script>
        // Satu-satunya tugas skrip ini adalah "mengoper" data dari PHP (server) 
        // ke JavaScript (browser) dalam sebuah variabel global yang bisa dibaca oleh app.js.
        window.processedCalendarData = @json($processedData ?? []);
    </script>
    @endpush
</x-layouts.app>