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

<x-layouts.landing title="Mapping Sementara">
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h2 class="h5 fw-bold mb-0">Mapping Sementara</h2>
                        <p class="text-muted small">Hubungkan data antar tabel (Bisa pilih banyak sekaligus, ketik untuk mencari).</p>
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

                        <form action="{{ route('mapping.store') }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-md-6 col-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="user_sap_id" class="form-label fw-semibold small mb-0">User SAP</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="user_sap_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="user_sap_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="user_sap_id" name="user_sap_id[]" multiple required>
                                    @foreach($userSaps as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->user_sap }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="kode_laravel_id" class="form-label fw-semibold small mb-0">Kode Laravel</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="kode_laravel_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="kode_laravel_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="kode_laravel_id" name="kode_laravel_id[]" multiple required>
                                    @foreach($kodeLaravels as $item)
                                        <option value="{{ $item->id }}">{{ $item->laravel_code }} - {{ $item->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="mrp_id" class="form-label fw-semibold small mb-0">MRP</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="mrp_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="mrp_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="mrp_id" name="mrp_id[]" multiple required>
                                    @foreach($mrps as $item)
                                        <option value="{{ $item->id }}">{{ $item->mrp }} ({{ $item->plant }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="workcenter_id" class="form-label fw-semibold small mb-0">Workcenter</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="workcenter_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="workcenter_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="workcenter_id" name="workcenter_id[]" multiple required>
                                    @foreach($workcenters as $item)
                                        <option value="{{ $item->id }}">{{ $item->kode_wc }} - {{ $item->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fa-solid fa-save me-2"></i> Simpan Mapping
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
                        <h2 class="h6 fw-bold mb-0">Daftar Mapping Tersimpan</h2>
                        
                        <form action="{{ route('mapping.index') }}" method="GET" class="d-flex gap-2 align-items-center">
                            <input type="text" name="search_sap" class="form-control form-control-sm" placeholder="Cari User SAP..." value="{{ request('search_sap') }}">
                            <input type="text" name="search_kode" class="form-control form-control-sm" placeholder="Cari Kode Bagian..." value="{{ request('search_kode') }}">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-search"></i>
                            </button>
                            @if(request()->hasAny(['search_sap', 'search_kode']))
                                <a href="{{ route('mapping.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                                    <i class="fa-solid fa-times"></i>
                                </a>
                            @endif
                        </form>
                    </div>
                    <div class="card-body px-0">
                        <!-- Bulk Delete Form -->
                        <form action="{{ route('mapping.bulk_destroy') }}" method="POST" id="bulk-delete-form" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data yang dipilih?');">
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
                                            <th>User SAP</th>
                                            <th>Kode Laravel</th>
                                            <th>MRP</th>
                                            <th>Workcenter</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($mappings as $index => $map)
                                            <tr>
                                                <td class="ps-4">
                                                    <input type="checkbox" name="ids[]" value="{{ $map->id }}" class="form-check-input row-checkbox">
                                                </td>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $map->userSap->name ?? '-' }}</div>
                                                    <small class="text-muted">{{ $map->userSap->user_sap ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $map->kodeLaravel->laravel_code ?? '-' }}</div>
                                                    <small class="text-muted">{{ Str::limit($map->kodeLaravel->description ?? '-', 20) }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $map->mrp->mrp ?? '-' }}</div>
                                                    <small class="text-muted">{{ $map->mrp->plant ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $map->workcenter->kode_wc ?? '-' }}</div>
                                                    <small class="text-muted">{{ Str::limit($map->workcenter->description ?? '-', 20) }}</small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    {{-- Individual delete needs to be separate form or just link --}}
                                                    {{-- Since we are inside a form, we cannot nest forms. 
                                                         We will use a button that submits to delete specific ID via JS or simpler: 
                                                         We can have a separate 'delete' button that changes form action temporarily or 
                                                         just use a standalone form outside loop? No, that's messy.
                                                         Best practice: Keep this form for bulk. Individual delete buttons should be BUTTON types that trigger a separate form submission via JS/hidden form.
                                                    --}}
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-single me-1" 
                                                        data-id="{{ $map->id }}"
                                                        data-user="{{ $map->user_sap_id }}"
                                                        data-kode="{{ $map->kode_laravel_id }}"
                                                        data-mrp="{{ $map->mrp_id }}"
                                                        data-wc="{{ $map->workcenter_id }}"
                                                        title="Edit">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-single" data-id="{{ $map->id }}" title="Hapus">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                                    Belum ada data mapping.
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
    
    {{-- Edit Modal --}}
    <div class="modal fade" id="editMappingModal" tabindex="-1" aria-labelledby="editMappingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="edit-mapping-form" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMappingModalLabel">Edit Mapping</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_user_sap_id" class="form-label small fw-semibold">User SAP</label>
                            <select class="form-select" id="edit_user_sap_id" name="user_sap_id" required>
                                @foreach($userSaps as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->user_sap }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_kode_laravel_id" class="form-label small fw-semibold">Kode Laravel</label>
                            <select class="form-select" id="edit_kode_laravel_id" name="kode_laravel_id" required>
                                @foreach($kodeLaravels as $item)
                                    <option value="{{ $item->id }}">{{ $item->laravel_code }} - {{ $item->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_mrp_id" class="form-label small fw-semibold">MRP</label>
                            <select class="form-select" id="edit_mrp_id" name="mrp_id" required>
                                @foreach($mrps as $item)
                                    <option value="{{ $item->id }}">{{ $item->mrp }} ({{ $item->plant }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_workcenter_id" class="form-label small fw-semibold">Workcenter</label>
                            <select class="form-select" id="edit_workcenter_id" name="workcenter_id" required>
                                @foreach($workcenters as $item)
                                    <option value="{{ $item->id }}">{{ $item->kode_wc }} - {{ $item->description }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editMappingModal" tabindex="-1" aria-labelledby="editMappingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="edit-mapping-form" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMappingModalLabel">Edit Mapping</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_user_sap_id" class="form-label small fw-semibold">User SAP</label>
                            <select class="form-select" id="edit_user_sap_id" name="user_sap_id" required>
                                @foreach($userSaps as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->user_sap }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_kode_laravel_id" class="form-label small fw-semibold">Kode Laravel</label>
                            <select class="form-select" id="edit_kode_laravel_id" name="kode_laravel_id" required>
                                @foreach($kodeLaravels as $item)
                                    <option value="{{ $item->id }}">{{ $item->laravel_code }} - {{ $item->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_mrp_id" class="form-label small fw-semibold">MRP</label>
                            <select class="form-select" id="edit_mrp_id" name="mrp_id" required>
                                @foreach($mrps as $item)
                                    <option value="{{ $item->id }}">{{ $item->mrp }} ({{ $item->plant }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_workcenter_id" class="form-label small fw-semibold">Workcenter</label>
                            <select class="form-select" id="edit_workcenter_id" name="workcenter_id" required>
                                @foreach($workcenters as $item)
                                    <option value="{{ $item->id }}">{{ $item->kode_wc }} - {{ $item->description }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
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
                        // Get all available choices from the store that are not already selected
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
            
             // Handle Reset (Optional, good for UX)
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

            // Single Delete Logic (since we are inside another form)
            document.querySelectorAll('.btn-delete-single').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Apakah Anda yakin ingin menghapus mapping ini?')) {
                        const id = this.dataset.id;
                        const form = document.getElementById('single-delete-form');
                        // Construct the correct URL dynamically. 
                        // Route: /mapping-sementara/{id}
                        // We can't use route() js helper easily here without a package, so we reconstruct it.
                        // Base URL is current page minus query params usually.
                        // Safe way: use a template or data attribute. 
                        // Let's assume URL structure /mapping-sementara/ID
                        form.action = `/mapping-sementara/${id}`;
                        form.submit();
                    }
                });
            });

            // Edit Logic
            const editModalEl = document.getElementById('editMappingModal');
            let editModal;
            if (editModalEl) {
                editModal = new bootstrap.Modal(editModalEl);
            }

            document.querySelectorAll('.btn-edit-single').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const user = this.dataset.user;
                    const kode = this.dataset.kode;
                    const mrp = this.dataset.mrp;
                    const wc = this.dataset.wc;

                    // Set Form Action
                    const form = document.getElementById('edit-mapping-form');
                    form.action = `/mapping-sementara/${id}`;

                    // Set Values
                    const setSelectValue = (id, value) => {
                        const select = document.getElementById(id);
                        if(select) select.value = value;
                    };

                    setSelectValue('edit_user_sap_id', user);
                    setSelectValue('edit_kode_laravel_id', kode);
                    setSelectValue('edit_mrp_id', mrp);
                    setSelectValue('edit_workcenter_id', wc);

                    // Show Modal
                    if(editModal) editModal.show();
                });
            });
        });
    </script>
    @endpush
</x-layouts.landing>
