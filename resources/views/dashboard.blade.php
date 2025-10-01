<x-layouts.landing title="COHV - PT Kayu Mebel Indonesia">

    @push('styles')
    {{-- [DIUBAH] Blok style yang sudah dirapikan --}}
    <style>
        .hero-section {
            background: linear-gradient(160deg, #10b981, #059669);
            color: white;
            padding: 2rem 1.5rem 6rem;
            position: relative;
        }
        .hero-section .text-white { color: white !important; }

        .main-content {
            margin-top: -4rem;
            z-index: 10;
            position: relative;
        }

        /* Aturan gabungan untuk .plant-card */
        .plant-card {
            border: 2px solid #e2e8f0;
            /* Transisi untuk semua properti agar lebih halus */
            transition: all 0.3s ease-in-out;
            /* Bayangan hijau transparan sebagai default */
            box-shadow: 0 0 15px 0 rgba(25, 135, 84, 0.2);
        }

        /* Efek saat card di-hover */
        .plant-card:hover {
            /* Card sedikit terangkat */
            transform: translateY(-8px);
            /* Bayangan hijau menjadi lebih tebal */
            box-shadow: 0 25px 25px 0 rgba(25, 135, 84, 0.35);
        }
    </style>
    @endpush

    @php
        $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
    @endphp

    <div class="d-flex flex-column min-vh-100">
        {{-- Hero Section --}}
        <div class="hero-section">
            <header class="w-100 mx-auto" style="max-width: 1140px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; padding: 0.375rem;">
                            <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="img-fluid">
                        </div>
                        <h1 class="h6 fw-semibold text-white d-none d-sm-block mb-0">PT. Kayu Mabel Indonesia</h1>
                    </div>
                    
                    <div class="dropdown">
                        <button id="user-menu-button" class="btn btn-link text-white text-decoration-none d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="fw-semibold text-white d-none d-sm-block me-2">{{ $user->name }}</span>
                            <i class="fa-solid fa-user text-white"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                            <li><a class="dropdown-item" href="#">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <div class="w-100 mx-auto text-center py-5" style="max-width: 1140px;">
                <h1 class="display-4 fw-bold">Selamat Datang!</h1>
                <p class="lead text-white-75 mt-2" style="min-height: 28px;">
                    <span id="typing-effect"></span>
                    <span id="typing-cursor" class="d-inline-block bg-white" style="width: 2px; height: 1.5rem; animation: pulse 1s infinite; margin-bottom: -4px;"></span>
                </p>
            </div>
        </div>
        
        {{-- Konten Utama (Kartu dan Kalender) --}}
        <main class="w-100 mx-auto p-3 p-md-4 flex-grow-1 main-content" style="max-width: 1140px;">
            <div class="row g-4">
                @php
                    $colorClasses = [
                        ['bg' => 'bg-primary-subtle', 'text' => 'text-primary-emphasis'],
                        ['bg' => 'bg-success-subtle', 'text' => 'text-success-emphasis'],
                        ['bg' => 'bg-info-subtle', 'text' => 'text-info-emphasis'],
                    ];
                @endphp

                @forelse ($plants as $plant)
                    @php
                        $colors = $colorClasses[$loop->index % count($colorClasses)];
                    @endphp
                    
                    <div class="col-12 col-md-6 col-lg-4 d-flex">
                        <a href="{{ route('manufaktur.dashboard.show', [$plant->kode]) }}"
                            onclick="event.preventDefault(); appLoader.show(); setTimeout(() => { window.location.href = this.href }, 150)"
                            class="card w-100 text-decoration-none text-center p-3 rounded-4 shadow-sm plant-card">
                            <div class="card-body">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-4 {{ $colors['bg'] }}" style="width: 64px; height: 64px;">
                                    <svg class="{{ $colors['text'] }}" width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                <h3 class="card-title h5 fw-bold text-dark">{{ $plant->nama_bagian }}</h3>
                                <p class="card-text text-muted">Kategori: {{ $plant->kategori }}</p>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5 bg-white rounded-4 border">
                            <h3 class="h5 fw-semibold text-body-secondary">Tidak Ada Plant</h3>
                            <p class="text-muted mt-2">Tidak ada plant yang terhubung dengan akun Anda saat ini.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <footer class="mt-5">
                <div id="calendar-container" class="card shadow-sm rounded-4 border-0">
                    {{-- Konten kalender tetap sama --}}
                </div>
            </footer>
        </main>
    </div>
    
    @push('scripts')
    @endpush

</x-layouts.landing>