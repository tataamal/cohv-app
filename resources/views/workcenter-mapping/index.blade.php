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

<x-layouts.landing title="Workcenter Mapping">
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h2 class="h5 fw-bold mb-0">Workcenter Parent-Child Mapping</h2>
                        <p class="text-muted small">Hubungkan Parent Workcenter ke Child Workcenter (Bisa pilih banyak sekaligus).</p>
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

                        <form action="{{ route('workcenter-mapping.store') }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label for="kode_laravel_id" class="form-label fw-semibold small mb-0">Kode Laravel (Section)</label>
                                <select class="form-select choices-single" name="kode_laravel_id" id="kode_laravel_id" required>
                                    <option value="">Select Section</option>
                                    @foreach($kodeLaravels as $kl)
                                        <option value="{{ $kl->id }}">{{ $kl->laravel_code }} - {{ $kl->description }} (Plant: {{ $kl->plant }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="wc_induk_id" class="form-label fw-semibold small mb-0">Parent Workcenter (Induk)</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="wc_induk_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="wc_induk_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="wc_induk_id" name="wc_induk_id[]" multiple required>
                                    @foreach($workcenters as $wc)
                                        <option value="{{ $wc->id }}">{{ $wc->description }} ({{ $wc->kode_wc }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="wc_anak_id" class="form-label fw-semibold small mb-0">Child Workcenter (Anak)</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small select-all-btn" style="font-size: 0.75rem;" data-target="wc_anak_id">Select All</button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger small reset-btn" style="font-size: 0.75rem;" data-target="wc_anak_id">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select choices-single" id="wc_anak_id" name="wc_anak_id[]" multiple required>
                                    @foreach($workcenters as $wc)
                                        <option value="{{ $wc->id }}">{{ $wc->description }} ({{ $wc->kode_wc }})</option>
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
                        
                        <form action="{{ route('workcenter-mapping.index') }}" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                            <input type="text" name="search_section" class="form-control form-control-sm" placeholder="Section" value="{{ request('search_section') }}" style="width: 150px;">
                            <input type="text" name="search_parent" class="form-control form-control-sm" placeholder="Parent WC" value="{{ request('search_parent') }}" style="width: 150px;">
                            <input type="text" name="search_child" class="form-control form-control-sm" placeholder="Child WC" value="{{ request('search_child') }}" style="width: 150px;">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-search"></i>
                            </button>
                            @if(request()->anyFilled(['search_section', 'search_parent', 'search_child']))
                                <a href="{{ route('workcenter-mapping.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            @endif
                        </form>
                    </div>
                    <div class="card-body px-0">
                        <!-- Bulk Delete Form -->
                        <form action="{{ route('workcenter-mapping.bulk_destroy') }}" method="POST" id="bulk-delete-form" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data yang dipilih?');">
                            @csrf
                            @method('DELETE')
                            
                            <div class="d-flex justify-content-end px-4 mb-2">
                                <button type="button" class="btn btn-warning text-white btn-sm d-none me-2" id="btn-edit-selected" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                                    <i class="fa-solid fa-edit me-1"></i> Edit Terpilih (<span id="selected-count-edit">0</span>)
                                </button>
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
                                            <th>Kode Laravel / Section</th>
                                            <th>Parent Workcenter (Induk)</th>
                                            <th>Child Workcenter (Anak)</th>
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
                                                    <div class="fw-semibold">{{ $map->kodeLaravel->laravel_code ?? '-' }}</div>
                                                    <small class="text-muted">{{ $map->kodeLaravel->description ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $map->parentWorkcenter->description ?? '-' }}</div>
                                                    <small class="text-muted">{{ $map->parentWorkcenter->kode_wc ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $map->childWorkcenter->description ?? '-' }}</div>
                                                    <small class="text-muted">{{ $map->childWorkcenter->kode_wc ?? '-' }}</small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-single" data-id="{{ $map->id }}" title="Hapus">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
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

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('workcenter-mapping.bulk_update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkUpdateModalLabel">Bulk Edit Mapping</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Biarkan kosong jika tidak ingin mengubah field tersebut.</p>
                        
                        <!-- Hidden Inputs for Selected IDs -->
                        <div id="bulk-update-ids"></div>

                        <div class="mb-3">
                            <label for="bulk_kode_laravel_id" class="form-label small fw-bold">Section (Kode Laravel)</label>
                            <select class="form-select" name="kode_laravel_id" id="bulk_kode_laravel_id">
                                <option value="">-- Tidak Berubah --</option>
                                @foreach($kodeLaravels as $kl)
                                    <option value="{{ $kl->id }}">{{ $kl->laravel_code }} - {{ $kl->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk_wc_induk_id" class="form-label small fw-bold">Parent WC</label>
                            <select class="form-select" name="wc_induk_id" id="bulk_wc_induk_id">
                                <option value="">-- Tidak Berubah --</option>
                                @foreach($workcenters as $wc)
                                    <option value="{{ $wc->id }}">{{ $wc->description }} ({{ $wc->kode_wc }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk_wc_anak_id" class="form-label small fw-bold">Child WC</label>
                            <select class="form-select" name="wc_anak_id" id="bulk_wc_anak_id">
                                <option value="">-- Tidak Berubah --</option>
                                @foreach($workcenters as $wc)
                                    <option value="{{ $wc->id }}">{{ $wc->description }} ({{ $wc->kode_wc }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Selected</button>
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
                    searchResultLimit: 100,
                    renderChoiceLimit: -1,
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

            // Bulk Delete Logic
            const checkAll = document.getElementById('check-all-rows');
            const rowChecks = document.querySelectorAll('.row-checkbox');
            const btnDeleteSelected = document.getElementById('btn-delete-selected');
            const btnEditSelected = document.getElementById('btn-edit-selected');
            const selectedCountSpan = document.getElementById('selected-count');
            const selectedCountEditSpan = document.getElementById('selected-count-edit');
            const bulkUpdateIdsContainer = document.getElementById('bulk-update-ids');

            function updateDeleteButton() {
                const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
                const checkedCount = checkedBoxes.length;
                
                selectedCountSpan.textContent = checkedCount;
                selectedCountEditSpan.textContent = checkedCount;
                
                if (checkedCount > 0) {
                    btnDeleteSelected.classList.remove('d-none');
                    btnEditSelected.classList.remove('d-none');
                } else {
                    btnDeleteSelected.classList.add('d-none');
                    btnEditSelected.classList.add('d-none');
                }

                // Populate Hidden IDs for Edit Modal
                bulkUpdateIdsContainer.innerHTML = '';
                checkedBoxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    bulkUpdateIdsContainer.appendChild(input);
                });
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
                    if (confirm('Apakah Anda yakin ingin menghapus mapping ini?')) {
                        const id = this.dataset.id;
                        const form = document.getElementById('single-delete-form');
                        form.action = `/workcenter-mappings/${id}`;
                        form.submit();
                    }
                });
            });
        });
    </script>
    @endpush
</x-layouts.landing>
