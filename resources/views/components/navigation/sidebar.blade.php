@php
    $menuItems = $menuItems ?? [];
    $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
@endphp

<aside id="sidebar" class="sidebar">
    {{-- Brand --}}
    <div class="sidebar-brand">
        <a href="{{ route('dashboard-landing') }}">
            <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="sidebar-brand-logo">
            <span class="sidebar-brand-text">KMI-COHV</span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav">
        <ul class="nav-list">
            @forelse($menuItems as $section)
                <li class="nav-section">
                    <span class="nav-section-title">{{ $section['title'] }}</span>
                </li>

                @foreach($section['items'] as $item)
                    <li class="nav-item">
                        {{-- JIKA ITEM PUNYA SUBMENU --}}
                        @if(!empty($item['submenu']))
                            @php
                                $collapseId = 'submenu-' . Str::slug($item['name']);
                            @endphp
                            
                            <a class="nav-link {{ $item['is_active'] ? 'active-parent' : '' }}"
                                {{-- href ini sekarang mengarah ke route utama buyer --}}
                                href="{{ route($item['route_name'], $item['route_params']) }}"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $collapseId }}" {{-- Gunakan data-bs-target untuk collapse --}}
                                aria-expanded="{{ $item['is_active'] ? 'true' : 'false' }}">
                                <span class="nav-text">{{ $item['name'] }}</span>
                                @if(isset($item['badge']))
                                    <span class="badge bg-success ms-auto me-2p p-1">{{ $item['badge'] }}</span>
                                @endif
                                <i class="nav-arrow fas fa-chevron-down"></i>
                            </a>
                            
                            <div class="collapse {{ $item['is_active'] ? 'show' : '' }}" id="{{ $collapseId }}">
                                <ul class="submenu">
                                    @foreach($item['submenu'] as $subitem)
                                        <li>
                                            <a href="{{ $subitem['route_name'] ? route($subitem['route_name'], $subitem['route_params'] ?? []) : '#' }}" 
                                               class="submenu-link {{ $subitem['is_active'] ? 'active' : '' }}">
                                                <i class="fas fa-database"></i>
                                                <span>{{ $subitem['name'] }}</span>
                                                @if(isset($subitem['badge']))
                                                    <span class="badge {{ $subitem['name'] === 'Overdue' ? 'bg-danger-soft' : 'bg-secondary-soft' }} ms-auto">
                                                        {{ $subitem['badge'] }}
                                                    </span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        
                        {{-- JIKA ITEM ADALAH LINK BIASA (TIDAK PUNYA SUBMENU) --}}
                        @else
                            <a href="{{ $item['route_name'] ? route($item['route_name'], $item['route_params'] ?? []) : '#' }}" 
                               class="nav-link {{ $item['is_active'] ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fas fa-file' }}"></i>
                                <span class="nav-text">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            @empty
                <li class="nav-empty">Menu tidak ditemukan.</li>
            @endforelse
        </ul>
    </nav>

    {{-- Footer --}}
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fas fa-circle-user"></i>
            <div class="user-details">
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-role">{{ $user->role }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="fas fa-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

    {{-- Toggle Button --}}
    <button id="sidebar-toggle" class="sidebar-toggle">
        <i class="fas fa-chevron-left"></i>
    </button>
</aside>

<div id="sidebar-overlay" class="sidebar-overlay"></div>