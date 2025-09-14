@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
<strong>Berhasil!</strong> {{ session('success') }}
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
<strong>Gagal!</strong> {{ session('error') }}
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif