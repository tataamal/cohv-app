<x-layouts.landing title="Welcome to KMI System">

    @push('styles')
    <style>
        /* CSS Kustom untuk efek yang tidak ada di Bootstrap */
        .plant-card-lift:hover {
            transform: translateY(-0.5rem);
            box-shadow: var(--bs-box-shadow-lg) !important;
        }
    </style>
    @endpush

    @php
        // Variabel $allUsers tidak diperlukan lagi
        $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
    @endphp

    <div class="d-flex flex-column min-vh-100 p-3 p-md-4">
        {{-- Header --}}
        <header class="w-100 mx-auto mb-4" style="max-width: 1140px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="shadow-sm bg-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; padding: 0.375rem;">
                        <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="img-fluid">
                    </div>
                    <h1 class="h6 fw-semibold text-body-secondary d-none d-sm-block mb-0">PT. Kayu Mabel Indonesia</h1>
                </div>
                
                <div class="dropdown">
                    <button id="user-menu-button" class="btn btn-link text-decoration-none d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fw-semibold text-secondary d-none d-sm-block me-2">{{ $user->name }}</span>
                        <i class="fa-solid fa-user"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
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
        
        {{-- Main Content --}}
        <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center">
            <h1 class="display-5 fw-bold text-dark">Selamat Datang!</h1>
            <p class="lead text-muted mt-2" style="min-height: 28px;">
                <span id="typing-effect"></span>
                <span id="typing-cursor" class="d-inline-block bg-dark" style="width: 2px; height: 1.5rem; animation: pulse 1s infinite; margin-bottom: -4px;"></span>
            </p>
            
            <div class="w-100 mx-auto mt-5" style="max-width: 1140px;">
                <div class="row g-4">
                    @php
                        $colorClasses = [
                            'purple' => ['bg' => 'bg-primary-subtle', 'text' => 'text-primary'],
                            'info' => ['bg' => 'bg-info-subtle', 'text' => 'text-info-emphasis'],
                            'pink'   => ['bg' => 'bg-danger-subtle', 'text' => 'text-danger-emphasis'],
                            'success' => ['bg' => 'bg-success-subtle', 'text' => 'text-success-emphasis'],
                        ];
                        $colorKeys = array_keys($colorClasses);
                    @endphp

                    @forelse ($plants as $plant)
                        @php
                            $colorName = $colorKeys[$loop->index % count($colorKeys)];
                            $colors = $colorClasses[$colorName];
                            $columnClass = ($loop->first && $loop->count > 2) ? 'col-lg-8' : 'col-lg-4';
                        @endphp
                        
                        <div class="col-md-6 {{ $columnClass }}">
                            <a href="{{ route('dashboard.show', [$plant->kode]) }}"
                               onclick="event.preventDefault(); appLoader.show(); setTimeout(() => { window.location.href = this.href }, 150)"
                               class="card h-100 text-decoration-none text-center p-4 rounded-4 shadow-sm plant-card-lift border-2 border-transparent"
                               style="transition: all 0.3s ease;">
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
                            <div class="text-center py-5 bg-light rounded-4">
                                <h3 class="h5 fw-semibold text-body-secondary">Tidak Ada Plant</h3>
                                <p class="text-muted mt-2">Tidak ada plant yang terhubung dengan akun Anda saat ini.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="w-100 mx-auto mt-5" style="max-width: 1140px;">
            <div class="row g-4">
                {{-- Kalender (dibuat menjadi lebar penuh) --}}
                <div class="col-12">
                    <div id="calendar-container" class="card shadow-lg rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                
                                <h2 id="calendar-month-year" class="h5 fw-bold text-dark mb-0"></h2>
                                
                                <div class="d-flex align-items-center">
                                    <button id="prev-month" class="btn btn-light btn-sm rounded-circle" style="width: 32px; height: 32px;"><i class="fas fa-chevron-left"></i></button>
                                    
                                    <button id="next-month" class="btn btn-light btn-sm rounded-circle ms-2" style="width: 32px; height: 32px;"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>

                            <div id="calendar-grid" class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 0.25rem;"></div>

                        </div>
                    </div>
                </div>
        </footer>
    </div>
    
    @push('scripts')
    @endpush

</x-layouts.landing>