<div class="header mb-4">
    {{-- Judul utama --}}
    <h4 class="page-title">Outstanding Reservasi: {{ $nama_bagian }} ({{ $kode }})</h4>
    
    {{-- Info tambahan (breadcrumbs/sub-judul) --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb" style="background-color: #f8f9fa; padding: 0.5rem 1rem;">
            <li class="breadcrumb-item">
                <a href="#">{{ $kategori }}</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ $sub_kategori }}
            </li>
        </ol>
    </nav>
</div>