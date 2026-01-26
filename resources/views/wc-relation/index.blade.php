@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    /* Custom styling for Choices.js to match Bootstrap */
    .choices__inner {
        min-height: 44px;
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
    .choices__input {
        background-color: transparent !important;
    }
    .choices__list--dropdown {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .choices__list--multiple .choices__item {
        background-color: #0d6efd;
        border: 1px solid #0d6efd;
    }
</style>
@endpush

<x-layouts.landing title="Workcenter Compatibility">
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h2 class="h5 fw-bold mb-0">Workcenter Relations</h2>
                        <p class="text-muted small">Definisikan kompatibilitas antara Workcenter Asal dan Workcenter Tujuan.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('wc-relation.store') }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="wc_asal_id" class="form-label fw-semibold small mb-0">Workcenter Asal</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="wc_asal_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="wc_asal_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="wc_asal_id" name="wc_asal_id[]" multiple required>
                                    @foreach($workcenters as $item)
                                        <option value="{{ $item->id }}">{{ $item->kode_wc }} - {{ $item->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="wc_tujuan_id" class="form-label fw-semibold small mb-0">Workcenter Tujuan</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="wc_tujuan_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="wc_tujuan_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="wc_tujuan_id" name="wc_tujuan_id[]" multiple required>
                                    @foreach($workcenters as $item)
                                        <option value="{{ $item->id }}">{{ $item->kode_wc }} - {{ $item->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fa-solid fa-save me-2"></i> Simpan Relasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 fw-bold mb-0">Daftar Relasi Tersimpan</h2>
                        
                        <form action="{{ route('wc-relation.index') }}" method="GET" class="d-flex gap-2 align-items-center">
                            <input type="text" name="search_wc" class="form-control form-control-sm" placeholder="Cari Kode WC..." value="{{ request('search_wc') }}">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-search"></i>
                            </button>
                            @if(request()->has('search_wc'))
                                <a href="{{ route('wc-relation.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                                    <i class="fa-solid fa-times"></i>
                                </a>
                            @endif
                        </form>
                    </div>
                    <div class="card-body px-0">
                        <!-- Bulk Delete Form -->
                        <form action="{{ route('wc-relation.bulk_destroy') }}" method="POST" id="bulk-delete-form" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data yang dipilih?');">
                            @csrf
                            @method('DELETE')
                            
                            <div class="d-flex justify-content-end px-4 mb-2">
                                <button type="submit" class="btn btn-danger btn-sm d-none" id="btn-delete-selected">
                                    <i class="fa-solid fa-trash me-1"></i> Hapus Terpilih (<span id="selected-count">0</span>)
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4" style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="check-all-rows">
                                            </th>
                                            <th>No</th>
                                            <th>WC Asal</th>
                                            <th>WC Tujuan</th>
                                            <th>Status</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($relations as $index => $rel)
                                            <tr>
                                                <td class="ps-4">
                                                    <input type="checkbox" name="ids[]" value="{{ $rel->id }}" class="form-check-input row-checkbox">
                                                </td>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $rel->wcAsal->kode_wc ?? '-' }}</div>
                                                    <small class="text-muted">{{ Str::limit($rel->wcAsal->description ?? '-', 20) }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $rel->wcTujuan->kode_wc ?? '-' }}</div>
                                                    <small class="text-muted">{{ Str::limit($rel->wcTujuan->description ?? '-', 20) }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success">{{ $rel->status }}</span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-single" data-id="{{ $rel->id }}" title="Hapus">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fa-solid fa-link fs-2 mb-3 d-block opacity-25"></i>
                                                    Belum ada data relasi.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        
                        {{-- Hidden Form for Single Delete --}}
                        <form id="single-delete-form" action="" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const choicesInstances = {};
            const elements = document.querySelectorAll('.choices-single');
            
            elements.forEach(element => {
                const instance = new Choices(element, {
                    removeItemButton: true,
                    searchEnabled: true,
                    placeholder: true,
                    placeholderValue: 'Pilih data...',
                    loadingText: 'Memuat...',
                    noResultsText: 'Tidak ada data ditemukan',
                    noChoicesText: 'Tidak ada data untuk dipilih',
                    itemSelectText: 'Tekan untuk memilih',
                    shouldSort: false,
                });
                choicesInstances[element.id] = instance;
            });

            // Handle Select All
            document.querySelectorAll('.select-all-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const instance = choicesInstances[targetId];
                    if (instance) {
                        const allChoices = instance._store.choices; 
                        const availableValues = allChoices
                            .filter(choice => !choice.selected && !choice.disabled)
                            .map(choice => choice.value);
                        
                        if (availableValues.length > 0) {
                            instance.setChoiceByValue(availableValues);
                        }
                    }
                });
            });
            
             // Handle Reset
             document.querySelectorAll('.reset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const instance = choicesInstances[targetId];
                    if (instance) {
                        instance.removeActiveItems();
                    }
                });
            });

            // --- Bulk Delete Logic ---
            const checkAll = document.getElementById('check-all-rows');
            const rowChecks = document.querySelectorAll('.row-checkbox');
            const btnDeleteSelected = document.getElementById('btn-delete-selected');
            const selectedCountSpan = document.getElementById('selected-count');

            function updateDeleteButton() {
                const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                selectedCountSpan.textContent = checkedCount;
                
                if (checkedCount > 0) {
                    btnDeleteSelected.classList.remove('d-none');
                } else {
                    btnDeleteSelected.classList.add('d-none');
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    rowChecks.forEach(cb => cb.checked = isChecked);
                    updateDeleteButton();
                });
            }

            rowChecks.forEach(cb => {
                cb.addEventListener('change', updateDeleteButton);
            });

            // Single Delete Logic
            document.querySelectorAll('.btn-delete-single').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Apakah Anda yakin ingin menghapus relasi ini?')) {
                        const id = this.dataset.id;
                        const form = document.getElementById('single-delete-form');
                        form.action = `/wc-relation/${id}`;
                        form.submit();
                    }
                });
            });
        });
    </script>
    @endpush
</x-layouts.landing>
