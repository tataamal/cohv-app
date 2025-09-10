<x-layouts.guest>
    <x-slot:title>
        Admin Login
    </x-slot:title>

    {{-- KONTEN UTAMA HALAMAN LOGIN --}}
    <div class="container-fluid">
        <div class="row min-vh-100 g-0">
            
            {{-- Kolom Kiri: Form Login --}}
            <div class="col-lg-5 d-flex flex-column justify-content-center py-5 px-4 px-sm-5">
                <div class="mx-auto w-100" style="max-width: 28rem;">
                    {{-- Logo dan Judul Perusahaan --}}
                    <div class="d-flex align-items-center mb-5">
                        <div class="shadow-sm bg-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; padding: 0.375rem;">
                            <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="img-fluid">
                        </div>
                        <h1 class="h6 fw-semibold text-body-secondary mb-0">PT. Kayu Mabel Indonesia</h1>
                    </div>

                    {{-- Judul Form --}}
                    <div class="text-left mb-4">
                        <h2 id="form-title" class="h2 fw-bold text-dark">Login Admin</h2>
                        <p class="mt-2 text-muted">Silakan masukkan kredensial Anda untuk melanjutkan.</p>
                    </div>
                    
                    {{-- Alert Error --}}
                    @if($errors->any())
                        <div class="alert alert-danger border-0 border-start border-4 border-danger" role="alert">
                            <p class="fw-bold mb-1">Terjadi Kesalahan</p>
                            <p class="mb-0">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    {{-- Card Wrapper untuk Form --}}
                    <div class="bg-white p-4 p-sm-5 rounded-4 shadow-lg">
                        <form id="admin-form" class="row g-3" action="{{ route('login.admin') }}" method="POST">
                            @csrf
                            <div class="col-12">
                                <label for="admin_sap_id" class="form-label">SAP ID</label>
                                <input id="admin_sap_id" name="sap_id" type="text" required autofocus placeholder="Masukkan SAP ID"
                                       class="form-control form-control-lg">
                            </div>
                            <div class="col-12">
                                <label for="admin_password" class="form-label">Password</label>
                                <input id="admin_password" name="password" type="password" required placeholder="Masukkan Password"
                                       class="form-control form-control-lg">
                            </div>
                            <div class="col-12 pt-3">
                                <button type="submit" class="btn btn-primary btn-lg w-100 d-flex justify-content-center align-items-center">
                                    <div class="spinner-border spinner-border-sm me-3 d-none" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="button-text">Masuk</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Preview Dashboard --}}
            <div id="interactive-panel" class="col-lg-7 d-none d-lg-flex align-items-center justify-content-center p-5" style="background-color: #f8f9fa;">
                <div id="interactive-card" class="w-100 bg-white rounded-4 shadow-lg p-5" style="max-width: 42rem; transform: rotate(-3deg);">
                    
                    <h2 class="h3 fw-bold text-dark">Analytics</h2>

                    <div class="row g-4 mt-3">
                        <div class="col-sm-6">
                            <div class="p-4 bg-white border rounded-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <p class="text-muted mb-0">Sales</p>
                                    <div class="p-1 bg-light rounded-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up-arrow text-secondary" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5"/></svg>
                                    </div>
                                </div>
                                <div class="mt-2 d-flex align-items-baseline">
                                    <p id="sales-count" class="h2 fw-bold text-dark me-2 mb-0">0</p>
                                    <p class="small fw-semibold text-danger d-flex align-items-center mb-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-arrow-down me-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1"/></svg>
                                        -2%
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-4 bg-white border rounded-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <p class="text-muted mb-0">Views</p>
                                    <div class="p-1 bg-light rounded-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill text-secondary" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
                                    </div>
                                </div>
                                <div class="mt-2 d-flex align-items-baseline">
                                    <p id="views-count" class="h2 fw-bold text-dark me-2 mb-0">0</p>
                                    <p class="small fw-semibold text-success d-flex align-items-center mb-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-arrow-up me-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5"/></svg>
                                        +8%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                         <h3 class="h6 text-body-secondary">Store traffic</h3>
                         <div class="mt-3" style="height: 160px; width: 100%;">
                            <svg class="w-100 h-100" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs><linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stop-color="var(--bs-primary)" stop-opacity="0.2"/><stop offset="95%" stop-color="var(--bs-primary)" stop-opacity="0"/></linearGradient></defs>
                                <path d="M0 101.5C124.833 21.333 234.3 -46.5 354 51.5C473.7 149.5 491.5 163 609 101.5" stroke="var(--bs-primary)" stroke-width="3" stroke-linecap="round"/>
                                <path d="M0 101.5C124.833 21.333 234.3 -46.5 354 51.5C473.7 149.5 491.5 163 609 101.5V192H0V101.5Z" fill="url(#chartGradient)"/>
                            </svg>
                         </div>
                         <div class="d-flex justify-content-between small text-muted mt-2 px-2">
                             <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                         </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function initializeApp() {
            // Fungsi untuk animasi angka naik (Tidak ada perubahan, berfungsi seperti sebelumnya)
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

            // Fungsi untuk panel interaktif di sebelah kanan (Tidak ada perubahan)
            function initializeInteractivePanel() {
                const panel = document.getElementById('interactive-panel');
                const card = document.getElementById('interactive-card');
                if (!panel || !card) return;
                const maxRotate = 6; // Sedikit diturunkan agar lebih smooth
                panel.addEventListener('mousemove', (e) => {
                    const { width, height, left, top } = panel.getBoundingClientRect();
                    const mouseX = e.clientX - left; const mouseY = e.clientY - top;
                    const xPct = (mouseX / width - 0.5) * 2; const yPct = (mouseY / height - 0.5) * 2;
                    const rotateY = xPct * maxRotate; const rotateX = -yPct * maxRotate;
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) rotate(-3deg)`;
                });
                panel.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) rotate(-3deg)';
                });
            }

            // Fungsi untuk menampilkan loading spinner pada tombol saat form disubmit
            function initializeLoadingOnSubmit() {
                const adminForm = document.getElementById('admin-form');
                if (adminForm) {
                    adminForm.addEventListener('submit', function() {
                        const button = adminForm.querySelector('button[type="submit"]');
                        const buttonText = button.querySelector('.button-text');
                        const spinner = button.querySelector('.spinner-border');

                        button.disabled = true;
                        // PERUBAHAN: Menggunakan kelas 'd-none' dari Bootstrap
                        if (spinner) spinner.classList.remove('d-none');
                        if (buttonText) buttonText.classList.add('d-none');
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