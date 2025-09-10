@php
    $menuItems = $menuItems ?? [];
    $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
@endphp

<!-- Sidebar yang Sudah Diperbaiki dengan Tampilan Modern -->
<aside id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 bg-body-tertiary border-end">
    
    <!-- Header Logo -->
    <a href="{{ route('dashboard-landing') }}" class="sidebar-brand d-flex align-items-center text-decoration-none text-dark p-3">
        <img src="{{ asset('images/KMI.png') }}" alt="Logo" class="logo-img rounded-circle bg-light p-1">
        <span class="sidebar-text fs-5 fw-semibold ms-3">KMI-COHV</span>
    </a>

    <!-- Navigasi Menu -->
    <nav class="flex-grow-1 p-2" style="overflow-y: auto;">
        <ul class="nav nav-pills flex-column">
            @forelse($menuItems as $section)
                <li class="nav-section-title">
                    <h2 class="sidebar-text px-3 my-2 small text-muted text-uppercase fw-semibold">{{ $section['title'] }}</h2>
                </li>
                
                @foreach($section['items'] as $item)
                    <li class="nav-item mb-1">
                        @if(!empty($item['submenu']))
                            @php
                                $currentPath = request()->path();
                                $currentKode = last(explode('/', $currentPath));
                                $isParentActive = collect($item['submenu'])->contains(function ($subitem) use ($currentKode) {
                                    $routeKode = $subitem['route_params']['kode'] ?? null;
                                    return $currentKode && $routeKode && $currentKode === $routeKode;
                                });
                                $collapseId = 'submenu-' . Str::slug($item['name']);
                            @endphp
                            <!-- Item Menu dengan Submenu (Dropdown) -->
                            <a href="#{{ $collapseId }}" 
                               data-bs-toggle="collapse" 
                               role="button" 
                               aria-expanded="{{ $isParentActive ? 'true' : 'false' }}"
                               class="nav-link d-flex justify-content-between align-items-center {{ $isParentActive ? 'active-parent' : '' }}"
                               title="{{ $item['name'] }}">
                                <div class="d-flex align-items-center">
                                    <i class="fa-fw {{ $item['icon'] }} nav-icon"></i>
                                    <span class="sidebar-text">{{ $item['name'] }}</span>
                                </div>
                                <i class="sidebar-text fa-solid fa-chevron-down collapse-arrow"></i>
                            </a>
                            <div class="collapse submenu {{ $isParentActive ? 'show' : '' }}" id="{{ $collapseId }}">
                                <ul class="nav flex-column ps-4 ms-2 py-1">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            $routeKode = $subitem['route_params']['kode'] ?? null;
                                            $isSubmenuActive = $currentKode && $routeKode && $currentKode === $routeKode;
                                        @endphp
                                        <li class="nav-item">
                                            <a href="{{ route($subitem['route_name'], $subitem['route_params']) }}" 
                                               class="nav-link small py-2 rounded {{ $isSubmenuActive ? 'active' : '' }}">
                                               {{ $subitem['name'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            @php $isActive = request()->routeIs($item['route_name']); @endphp
                            <!-- Item Menu Tunggal -->
                            <a href="{{ route($item['route_name']) }}" 
                               class="nav-link d-flex align-items-center {{ $isActive ? 'active' : '' }}"
                               title="{{ $item['name'] }}">
                                <i class="fa-fw {{ $item['icon'] }} nav-icon"></i>
                                <span class="sidebar-text">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            @empty
                <li class="p-3 text-muted small">No menu items found.</li>
            @endforelse
        </ul>
    </nav>

    <!-- Footer Sidebar -->
    <div class="sidebar-footer p-3 border-top mt-auto">
        <!-- Info Pengguna -->
        <div class="user-info d-flex align-items-center mb-3">
            <i class="fa-solid fa-user fa-fw fs-5"></i>
            <div class="sidebar-text ms-3">
                <p class="small fw-semibold text-dark mb-0">{{ $user->name }}</p>
                <p class="small text-muted mb-0 text-capitalize">{{ $user->role }}</p>
            </div>
        </div>

        <!-- Tombol Collapse -->
        <button id="sidebar-collapse-toggle" class="btn btn-light w-100 d-flex align-items-center justify-content-center">
            <i class="fas fa-chevron-left fa-fw collapse-icon"></i>
            <span class="sidebar-text ms-2">Collapse</span>
        </button>
        <!-- Tombol Logout -->
        <form method="POST" action="{{ route('logout') }}" class="mb-2">
            @csrf
            <button type="submit" class="btn btn-danger w-100 d-flex align-items-center justify-content-center btn-logout">
                <i class="fas fa-sign-out-alt fa-fw"></i>
                <span class="sidebar-text ms-2">Logout</span>
            </button>
        </form>

    </div>
</aside>