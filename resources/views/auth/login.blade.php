<x-layouts.guest>
    <x-slot:title>
        Admin Login
    </x-slot:title>

    {{-- KONTEN UTAMA HALAMAN LOGIN --}}
    <div class="flex min-h-screen bg-gray-50 overflow-hidden">
        
        {{-- Kolom Kiri: Form Login (40% dari layar desktop) --}}
        <div class="flex w-full flex-col justify-center py-12 px-4 sm:px-6 lg:w-2/5 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-md">
                {{-- Logo dan Judul Perusahaan --}}
                <div class="flex items-center space-x-3 mb-12">
                    <div class="h-10 w-10 p-1.5 rounded-full shadow-sm bg-white flex items-center justify-center">
                        <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI">
                    </div>
                    <h1 class="text-base font-semibold text-gray-800">PT. Kayu Mabel Indonesia</h1>
                </div>

                {{-- Judul Form --}}
                <div class="text-left mb-8">
                    <h2 id="form-title" class="text-3xl font-bold tracking-tight text-gray-900">Login Admin</h2>
                    <p class="mt-2 text-sm text-gray-600">Silakan masukkan kredensial Anda untuk melanjutkan.</p>
                </div>
                
                {{-- Alert Error --}}
                @if($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p class="font-bold">Terjadi Kesalahan</p>
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                {{-- Card Wrapper untuk Form --}}
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
                    <form id="admin-form" class="space-y-4" action="{{ route('login.admin') }}" method="POST">
                        @csrf
                        <div>
                            <label for="admin_sap_id" class="block text-sm font-medium text-gray-700 mb-1">SAP ID</label>
                            <input id="admin_sap_id" name="sap_id" type="text" required autofocus placeholder="Masukkan SAP ID"
                                   class="block w-full rounded-lg border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500 sm:text-sm transition duration-150 ease-in-out">
                        </div>
                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input id="admin_password" name="password" type="password" required placeholder="Masukkan Password"
                                   class="block w-full rounded-lg border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500 sm:text-sm transition duration-150 ease-in-out">
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="group flex w-full justify-center items-center rounded-lg border border-transparent bg-purple-600 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-300 ease-in-out disabled:opacity-75 disabled:cursor-not-allowed">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="button-text">Masuk</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Preview Dashboard (60% dari layar desktop) --}}
        <div id="interactive-panel" class="relative hidden lg:flex w-3/5 items-center justify-center p-8 xl:p-12 bg-gray-100">
            <div id="interactive-card" class="w-full max-w-2xl p-8 space-y-6 bg-white rounded-2xl shadow-2xl transform -rotate-3 border xl:scale-95 transition-transform duration-300 ease-out">
                
                <h2 class="text-2xl font-bold text-gray-800">Analytics</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="p-5 bg-white border rounded-xl">
                        <div class="flex justify-between items-start">
                            <p class="text-sm font-medium text-gray-500">Sales</p>
                            <div class="p-1.5 bg-gray-100 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <p id="sales-count" class="text-3xl font-bold text-gray-900">0</p>
                            <p class="text-sm font-semibold text-red-500 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                -2%
                            </p>
                        </div>
                    </div>
                    <div class="p-5 bg-white border rounded-xl">
                        <div class="flex justify-between items-start">
                            <p class="text-sm font-medium text-gray-500">Views</p>
                            <div class="p-1.5 bg-gray-100 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <p id="views-count" class="text-3xl font-bold text-gray-900">0</p>
                            <p class="text-sm font-semibold text-green-500 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                +8%
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-medium text-gray-700">Store traffic</h3>
                    <div class="mt-4 h-40 w-full">
                        <svg class="w-full h-full" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs><linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stop-color="#A855F7" stop-opacity="0.2"/><stop offset="95%" stop-color="#A855F7" stop-opacity="0"/></linearGradient></defs>
                            <path d="M0 101.5C124.833 21.333 234.3 -46.5 354 51.5C473.7 149.5 491.5 163 609 101.5" stroke="#A855F7" stroke-width="3" stroke-linecap="round"/>
                            <path d="M0 101.5C124.833 21.333 234.3 -46.5 354 51.5C473.7 149.5 491.5 163 609 101.5V192H0V101.5Z" fill="url(#chartGradient)"/>
                        </svg>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-2 px-2">
                        <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-medium text-gray-700 mb-3">Recent customers</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <img class="h-9 w-9 rounded-full object-cover" src="https://ui-avatars.com/api/?name=Olivia+Rhye&background=f3e8ff&color=7c3aed" alt="Avatar Olivia">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">Olivia Rhye</p>
                                <p class="text-xs text-gray-500">olivia@kmi.com</p>
                            </div>
                            <span class="text-xs font-medium py-1 px-2.5 rounded-full bg-green-100 text-green-700">Sent</span>
                        </div>
                         <div class="flex items-center space-x-3">
                            <img class="h-9 w-9 rounded-full object-cover" src="https://ui-avatars.com/api/?name=Phoenix+Baker&background=e0f2fe&color=0284c7" alt="Avatar Phoenix">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">Phoenix Baker</p>
                                <p class="text-xs text-gray-500">phoenix@kmi.com</p>
                            </div>
                            <span class="text-xs font-medium py-1 px-2.5 rounded-full bg-blue-100 text-blue-700">Processing</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function initializeApp() {
            // Fungsi untuk animasi angka naik
            function animateCountUp(el, endValue, duration) {
                let startTime = null;
                const startValue = 0;
                const step = (timestamp) => {
                    if (!startTime) startTime = timestamp;
                    const progress = Math.min((timestamp - startTime) / duration, 1);
                    const currentValue = Math.floor(progress * (endValue - startValue) + startValue);
                    el.innerText = currentValue.toLocaleString('id-ID');
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        el.innerText = endValue.toLocaleString('id-ID');
                    }
                };
                window.requestAnimationFrame(step);
            }

            // Fungsi untuk panel interaktif di sebelah kanan
            function initializeInteractivePanel() {
                const panel = document.getElementById('interactive-panel');
                const card = document.getElementById('interactive-card');
                if (!panel || !card) return;
                const maxRotate = 8;
                panel.addEventListener('mousemove', (e) => {
                    const { width, height, left, top } = panel.getBoundingClientRect();
                    const mouseX = e.clientX - left; const mouseY = e.clientY - top;
                    const xPct = (mouseX / width - 0.5) * 2; const yPct = (mouseY / height - 0.5) * 2;
                    const rotateY = xPct * maxRotate; const rotateX = -yPct * maxRotate;
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(0.95) rotate(-3deg)`;
                });
                panel.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(0.95) rotate(-3deg)';
                });
            }

            // Fungsi untuk menampilkan loading spinner pada tombol saat form disubmit
            function initializeLoadingOnSubmit() {
                const adminForm = document.getElementById('admin-form');
                if (adminForm) {
                    adminForm.addEventListener('submit', function() {
                        const button = adminForm.querySelector('button[type="submit"]');
                        const buttonText = button.querySelector('.button-text');
                        const spinner = button.querySelector('svg');

                        button.disabled = true;
                        if (spinner) spinner.classList.remove('hidden');
                        if (buttonText) buttonText.classList.add('hidden');
                    });
                }
            }

            // --- MENJALANKAN SEMUA FUNGSI ---
            const salesCountEl = document.getElementById('sales-count');
            const viewsCountEl = document.getElementById('views-count');
            if(salesCountEl) animateCountUp(salesCountEl, 8224, 1500);
            if(viewsCountEl) animateCountUp(viewsCountEl, 32640, 1500);
            
            initializeInteractivePanel();
            initializeLoadingOnSubmit();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeApp);
        } else {
            initializeApp();
        }
    </script>
    @endpush
</x-layouts.guest>