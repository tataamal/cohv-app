<x-layouts.guest>
    <x-slot:title>
        Login COHV
    </x-slot:title>

    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                background-color: #f8f9fa;
                overflow-x: hidden;
            }

            /* === TAMPILAN DESKTOP (TIDAK BERUBAH) === */
            .left-panel {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 3rem;
                overflow: hidden;
            }
            .left-panel-content { z-index: 2; }
            .logo-wrapper {
                width: 130px;
                height: 130px;
                background-color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
                padding: 1.25rem;
            }
            .logo-wrapper img { max-width: 100%; height: auto; }
            .left-panel h1 { font-weight: 800; }
            .left-panel p { color: rgba(255, 255, 255, 0.85); font-size: 1.1rem; }

            .right-panel {
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #ffffff;
            }
            .form-container { width: 100%; max-width: 400px; padding: 2rem; }
            .form-title h2 { font-weight: 800; }
            .input-group-custom {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 0.75rem;
                padding: 0.5rem 1rem;
                display: flex;
                align-items: center;
                transition: border-color 0.3s ease, box-shadow 0.3s ease;
            }
            .input-group-custom:focus-within {
                border-color: #10b981;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            }
            .input-group-custom .form-control { border: none; background: transparent; box-shadow: none !important; padding-left: 0.75rem; }
            .input-group-custom .input-icon { color: #6c757d; }
            .form-label-custom { font-weight: 600; color: #495057; margin-bottom: 0.5rem; font-size: 0.9rem; text-align: left;}
            .btn-submit-custom {
                background: linear-gradient(90deg, #10b981, #059669);
                border: none;
                padding: 0.9rem 1.5rem;
                border-radius: 0.75rem;
                font-weight: 700;
                transition: transform 0.2s ease, box-shadow 0.3s ease;
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
            }
            .btn-submit-custom:hover { transform: translateY(-2px); box-shadow: 0 7px 20px rgba(16, 185, 129, 0.35); }


            /* === [V3 - DIROMBAK TOTAL] TAMPILAN MOBILE PROFESIONAL === */
            @media (max-width: 991.98px) {
                .right-panel {
                    /* Latar gradient, menjadi flex container yang menengahkan card */
                    background: linear-gradient(160deg, #10b981, #059669);
                    display: flex;
                    align-items: center; /* Vertikal center */
                    justify-content: center; /* Horizontal center */
                    padding: 1.5rem; /* Jarak dari tepi layar */
                    min-height: 100vh;
                }

                .form-container {
                    /* Card yang ditengahkan, dengan proporsi seimbang */
                    background-color: #ffffff;
                    width: 100%;
                    max-width: 400px;
                    border-radius: 1.5rem; /* Sudut bulat di semua sisi */
                    padding: 4.5rem 1.5rem 2rem; /* Padding atas lebih besar untuk logo */
                    position: relative;
                    text-align: center;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.1); /* Shadow lebih lembut */
                    height: auto;
                }

                .mobile-logo-wrapper {
                    width: 90px;
                    height: 90px;
                    background-color: #fff;
                    border-radius: 50%;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.08); /* Shadow halus */
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1.25rem;
                    position: absolute;
                    top: -45px; /* Setengah tinggi logo */
                    left: 50%;
                    transform: translateX(-50%);
                }
                .mobile-logo-wrapper img { max-width: 100%; height: auto; }
                
                .form-title { text-align: center; margin-bottom: 2rem !important; }
                .form-title h2 { font-weight: 800; }
                
                /* Desain ulang input field */
                .input-group-custom {
                    border-radius: 50px;
                    padding: 0.5rem 1.25rem;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0; /* Border abu-abu halus */
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                }
                .input-group-custom:focus-within {
                    border-color: #059669; /* Border hijau saat fokus */
                    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15); /* Efek glow hijau */
                }
                .input-group-custom .form-control { text-align: left; }
                .input-group-custom .form-control::placeholder { font-size: 0.95rem; }

                .btn-submit-custom {
                    border-radius: 50px; /* Bentuk kapsul */
                    padding-top: 0.8rem;
                    padding-bottom: 0.8rem;
                    font-size: 1rem;
                    font-weight: 700;
                }
            }
        </style>
    @endpush

    <div class="row min-vh-100 g-0">
        <div class="col-lg-5 left-panel d-none d-lg-flex">
            <div class="left-panel-content">
                <div class="logo-wrapper mb-5"><img src="{{ asset('images/KMI.png') }}" alt="Logo KMI"></div>
                <h1 class="display-5">Welcome Back</h1>
                <p>Login to manage your COHV application features.</p>
            </div>
        </div>

        <div class="col-lg-7 right-panel">
            <div class="form-container">
                <div class="d-lg-none mobile-logo-wrapper">
                    <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI">
                </div>

                <div class="form-title">
                    <h2 class="h1 text-dark">Account Login</h2>
                    <p class="mt-2 text-muted">Please enter your credentials.</p>
                </div>
                
                @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">{{ $errors->first() }}</div>
                @endif

                <form id="admin-form" action="{{ route('login.admin') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <div class="input-group-custom">
                            <span class="input-icon pe-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person text-muted" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                            </span>
                            <input id="admin_sap_id" name="sap_id" type="text" required autofocus class="form-control" placeholder="SAP ID">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="input-group-custom">
                            <span class="input-icon pe-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-lock text-muted" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2M5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1"/></svg>
                            </span>
                            <input id="admin_password" name="password" type="password" required class="form-control" placeholder="Password">
                        </div>
                    </div>
                    <div class="col-12 pt-3">
                        <button type="submit" class="btn btn-primary btn-submit-custom w-100 d-flex justify-content-center align-items-center">
                            <div class="spinner-border spinner-border-sm me-3 d-none" role="status"><span class="visually-hidden">Loading...</span></div>
                            <span class="button-text">Log In</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminForm = document.getElementById('admin-form');
            if (adminForm) {
                adminForm.addEventListener('submit', function() {
                    const button = adminForm.querySelector('button[type="submit"]');
                    const buttonText = button.querySelector('.button-text');
                    const spinner = button.querySelector('.spinner-border');
                    if (button && buttonText && spinner) {
                        button.disabled = true;
                        spinner.classList.remove('d-none');
                        buttonText.textContent = 'Processing...';
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.guest>