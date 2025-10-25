<x-layouts.app title="PRO Transaction">
    <div class="container-fluid py-4">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold text-primary mb-0">
                        PRO Transaction Detail
                    </h3>
    
                    <a href="{{ route('manufaktur.dashboard.show', ['kode' => request()->route('werksCode') ?? request()->route('kode')]) }}" 
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
    
                <p class="text-muted">Production Order: {{ $proData->AUFNR ?? 'N/A' }} (Bagian: {{ $bagian ?? 'N/A' }})</p>
            </div>
        </div>
    
        {{-- --- BAGIAN I: DATA HEADER DAN BADGE --- --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Production Order Overview</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <p class="mb-1 text-muted small">Buyer Name</p>
                        <span class="badge bg-success fs-6 p-2 w-100 text-start">
                            <i class="fas fa-user me-2"></i> {{ $buyerData->NAME1 ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted small">SO</p>
                        <span class="badge bg-primary fs-6 p-2 w-100 text-start">
                            <i class="fas fa-receipt me-2"></i> {{ $proData->KDAUF ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="col-md-2">
                        <p class="mb-1 text-muted small">SO Item</p>
                        <span class="badge bg-info text-dark fs-6 p-2 w-100 text-start">
                            {{ $proData->KDPOS ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted small">PRO Status</p>
                        @php
                            $stats = strtoupper($proData->STATS ?? '');
                            $statusClass = 'bg-secondary'; 
                            if (str_contains($stats, 'REL') || $stats === 'PCNF') {
                                $statusClass = 'bg-warning text-dark'; 
                            } elseif ($stats === 'TECO') {
                                $statusClass = 'bg-success';
                            }
                        @endphp
                        <span class="badge {{ $statusClass }} fs-6 p-2 w-100 text-start">
                            {{ $proData->STATS ?? 'N/A' }}
                        </span>
                    </div>
                </div>
                <hr>
                <div class="row small text-muted g-1">
                    <div class="col-md-3 text-center">Material: <strong>{{ $proData->MAKTX ?? '-' }}</strong></div>
                    <div class="col-md-3 text-center">Start Date: 
                        <strong>
                            @if ($proData->GSTRP && !empty($proData->GSTRP) && $proData->GSTRP != '00000000')
                                {{ \Carbon\Carbon::parse($proData->GSTRP)->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                    <div class="col-md-3 text-center">End Date: 
                        <strong>
                            @if ($proData->GLTRP && !empty($proData->GLTRP) && $proData->GLTRP != '00000000')
                                {{ \Carbon\Carbon::parse($proData->GLTRP)->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                    <div class="col-md-3 text-center">GR Quantity: <strong>{{ $proData->WEMNG ?? '0' }}</strong></div>
                </div>
            </div>
        </div>
    
        {{-- --- BAGIAN II: ACTION BUTTONS --- --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h6 class="mb-3 text-primary"><i class="fas fa-cogs me-2"></i> Transaction Actions</h6>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-warning" 
                        onclick="openSchedule(
                            '{{ $proData->AUFNR ?? '' }}', 
                            '{{ $proData->SSAVD && !empty($proData->SSAVD) && $proData->SSAVD != '00000000' ? \Carbon\Carbon::parse($proData->SSAVD)->format('d/m/Y') : '' }}'
                        )"
                        @if(empty($proData->AUFNR)) disabled @endif>
                        <i class="fas fa-clock-rotate-left me-1"></i> Reschedule
                    </button>
                    <button type="button" class="btn btn-sm btn-info" onclick="openRefresh('{{ $proData->AUFNR ?? '' }}', '{{ $proData->WERKSX ?? '' }}')"
                        @if(empty($proData->AUFNR)) disabled @endif>
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh PRO
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="openChangePvModal('{{ $proData->AUFNR ?? '' }}', '{{ $proData->VERID ?? '' }}', '{{ $proData->WERKSX ?? '' }}')"
                        @if(empty($proData->AUFNR)) disabled @endif>
                        <i class="fa-solid fa-code-compare me-1"></i> Change PV
                    </button>
                    <button type="button" class="btn btn-sm btn-info" onclick="openReadPP('{{ $proData->AUFNR ?? '' }}')"
                        @if(empty($proData->AUFNR)) disabled @endif>
                        <i class="fas fa-book-open me-1"></i> READ PP
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" 
                        onclick="openChangeQuantityModal('{{ $proData->AUFNR ?? '' }}', '{{ $proData->PSMNG ?? '' }}', '{{ $proData->WERKSX ?? '' }}')"
                        @if(empty($proData->AUFNR)) disabled @endif>
                        <i class="fa-solid fa-file-pen"></i> Change Quantity
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="openTeco('{{ $proData->AUFNR ?? '' }}')"
                        @if(empty($proData->AUFNR)) disabled @endif>
                        <i class="fas fa-circle-check me-1"></i> TECO
                    </button>
                </div>
            </div>
        </div>
    
        {{-- --- BAGIAN III: TABEL DETAIL --- --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="proTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Order Overview</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="routing-tab" data-bs-toggle="tab" data-bs-target="#routing" type="button" role="tab">Routing Detail</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="components-tab" data-bs-toggle="tab" data-bs-target="#components" type="button" role="tab">Component Detail</button>
                            </li>
                        </ul>
    
                        <div class="tab-content pt-3" id="proTabsContent">
                            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                @include('Admin.partials.pro-overview-table', ['proOrders' => $allTData3])
                            </div>
                            <div class="tab-pane fade" id="routing" role="tabpanel">
                                @include('Admin.partials.routing-table', ['routingData' => $allTData1])
                            </div>
    
                            <div class="tab-pane fade" id="components" role="tabpanel">
                                {{-- PERBAIKAN 2: Toolbar diperbaiki agar tidak menggeser tombol lain --}}
                                <div id="component-actions-toolbar" class="d-flex justify-content-between align-items-center mb-3 gap-3">
                                    <div id="bulk-actions-container" class="d-none align-items-center gap-2 p-2 border rounded bg-light shadow-sm">
                                        <span id="selection-count" class="fw-bold text-primary small me-2"></span>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="bulkDeleteComponents('{{ $proData->AUFNR ?? '' }}', '{{ request()->route('werksCode') ?? request()->route('kode') }}')">
                                            <i class="fas fa-trash me-1"></i> Hapus
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection('{{ $proData->AUFNR ?? '' }}')">
                                            <i class="fas fa-times me-1"></i> Batal
                                        </button>
                                    </div>

                                    <div id="add-component-container" class="d-flex gap-2 ms-auto">
                                        {{-- PERBAIKAN 2: Tombol Select All untuk Mobile dengan fungsi toggle --}}
                                        <button type="button" class="btn btn-sm btn-outline-primary d-block d-md-none" 
                                                onclick="toggleSelectAllComponents('{{ $proData->AUFNR ?? '' }}')">
                                            <i class="fas fa-check-double"></i> Select / Unselect All
                                        </button>

                                        <button type="button" class="btn btn-sm btn-success text-white" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#addComponentModal"
                                                data-aufnr="{{ $proData->AUFNR ?? '' }}"
                                                data-vornr="{{ $proData->VORNR ?? '' }}"
                                                data-arbpl="{{ $proData->ARBPL ?? '' }}"
                                                data-pwwrk="{{ $proData->PWWRK ?? '' }}">
                                            <i class="fas fa-plus me-1"></i> Add Component
                                        </button>
                                    </div>
                                </div>
                                @include('Admin.partials.components-table', ['componentData' => $allTData4ByAufnr])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.modals.schedule-modal')
    @include('components.modals.resultModal')
    @include('components.modals.refreshModal')
    @include('components.modals.changeWCmodal')
    @include('components.modals.changePVmodal')
    @include('components.modals.add-component-modal')
    @include('components.modals.edit-component')
    @include('components.modals.add-component-modal')
    @include('components.modals.change-quantity-modal')
    @push('scripts')
    <script src="{{ asset('js/readpp.js') }}"></script>
    <script src="{{ asset('js/refresh.js') }}"></script>
    <script src="{{ asset('js/schedule.js') }}"></script>
    <script src="{{ asset('js/teco.js') }}"></script>
    <script src="{{ asset('js/component.js') }}"></script>
    <script src="{{ asset('js/changePv.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ======================= START SCRIPT RESCHEDULE =========================
            // Keluar jika elemen-elemen penting tidak ada di halaman
            const scheduleModalElement = document.getElementById('scheduleModal');
            const visibleDateInput = document.getElementById('visibleDate');
            const scheduleDateInput = document.getElementById('scheduleDate'); // Input tersembunyi untuk backend
            
            if (!scheduleModalElement || !visibleDateInput || !scheduleDateInput) {
                console.warn('Schedule modal elements not found, schedule.js will not run.');
                return;
            }

            // Inisialisasi Modal Bootstrap
            const scheduleModal = new bootstrap.Modal(scheduleModalElement);
            
            // Inisialisasi Flatpickr pada input yang TERLIHAT
            const flatpickrInstance = flatpickr(visibleDateInput, {
                dateFormat: "Y-m-d",    // Format dasar yang akan digunakan untuk sinkronisasi
                altInput: true,         // Membuat input kedua yang ramah pengguna (ini adalah #visibleDate)
                altFormat: "d/m/Y",     // Format yang dilihat oleh pengguna
                minDate: "today",
                
                // FUNGSI KUNCI UNTUK SINKRONISASI
                onChange: function(selectedDates, dateStr, instance) {
                    // Setiap kali tanggal di kalender (visibleDate) berubah,
                    // salin nilainya ke input 'scheduleDate' yang akan dikirim ke backend.
                    // 'dateStr' sudah dalam format 'Y-m-d' sesuai 'dateFormat'.
                    scheduleDateInput.value = dateStr;

                    // Logika validasi untuk menampilkan pesan error
                    const dateError = document.getElementById('dateError');
                    if (dateError) {
                        if (selectedDates.length > 0) {
                            instance.altInput.classList.remove('is-invalid');
                            dateError.style.display = 'none';
                        }
                    }
                }
            });


            // Fungsi global untuk membuka modal
            window.openSchedule = function(aufnr, scheduleDate) {
                document.getElementById('scheduleAufnr').value = aufnr;

                let initialDate = "today";
                if (scheduleDate && scheduleDate.includes('/')) {
                    const parts = scheduleDate.split('/'); // Format: d/m/Y
                    if (parts.length === 3) {
                        initialDate = `${parts[2]}-${parts[1]}-${parts[0]}`; // Konversi ke: Y-m-d
                    }
                }
                
                // Mengatur tanggal di Flatpickr. Ini akan otomatis memicu 'onChange'
                // dan menyinkronkan nilai ke input 'scheduleDate' juga.
                flatpickrInstance.setDate(initialDate, true);

                document.getElementById('scheduleTime').value = '00:00:00';
                scheduleModal.show();
            }


            // Logika untuk submit form (AJAX)
            const scheduleForm = document.getElementById('scheduleForm');
            if (scheduleForm) {
                scheduleForm.addEventListener('submit', function(event) {
                    event.preventDefault();

                    // Validasi terakhir sebelum mengirim
                    if (!scheduleDateInput.value) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tanggal Belum Dipilih',
                            text: 'Silakan pilih tanggal penjadwalan ulang.',
                        });
                        // Tampilkan error di input visual juga
                        flatpickrInstance.altInput.classList.add('is-invalid');
                        const dateError = document.getElementById('dateError');
                        if(dateError) dateError.style.display = 'block';
                        return; // Hentikan proses submit
                    }

                    const formData = new FormData(this);
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalButtonText = submitButton.innerHTML;

                    submitButton.disabled = true;
                    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Processing...`;

                    // URL diambil dari atribut data-* di form
                    const actionUrl = scheduleForm.dataset.actionUrl; 
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        scheduleModal.hide();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => {
                        scheduleModal.hide();
                        console.error('Fetch Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Request Failed',
                            text: 'Could not connect to the server.',
                        });
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    });
                });
            }
            // ====================== END SCRIPT RESCHEDULE =======================
            const addComponentModal = document.getElementById('addComponentModal');
        
            if (addComponentModal) {
                // Listener ini akan berjalan SETIAP KALI modal akan ditampilkan
                addComponentModal.addEventListener('show.bs.modal', function (event) {
                    // event.relatedTarget adalah tombol yang memicu modal
                    const button = event.relatedTarget;
                    if (!button) return; // Keluar jika modal tidak dipicu oleh tombol

                    // Ambil semua data dari atribut data-* tombol
                    const aufnr = button.dataset.aufnr;
                    const vornr = button.dataset.vornr;
                    const pwwrk = button.dataset.pwwrk;

                    console.log('Passing data to Add Component Modal:', { aufnr, vornr, pwwrk });

                    // Temukan input di dalam modal dan isi nilainya
                    // Pastikan ID input di modal Anda sudah benar
                    const aufnrInput = addComponentModal.querySelector('#addComponentAufnr');
                    const vornrInput = addComponentModal.querySelector('#addComponentVornr');
                    const plantInput = addComponentModal.querySelector('#addComponentPlant');

                    if (aufnrInput) aufnrInput.value = aufnr || '';
                    if (vornrInput) vornrInput.value = vornr || '';
                    if (plantInput) plantInput.value = pwwrk || '';
                    
                    // Jika Anda punya display field (bukan input)
                    const displayField = addComponentModal.querySelector('#displayAufnr');
                    if (displayField) {
                        displayField.textContent = aufnr || '';
                    }
                });
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('confirmScheduleBtn');
            const form = document.getElementById('scheduleForm');
            const searchInput = document.getElementById('proSearchInput');
            const tableBody = document.getElementById('tdata3-body');
            const noResultsRow = document.getElementById('tdata3-no-results');

            // ======================= START SCRIPT RESCHEDULE =========================

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    // Ambil referensi tombol dan simpan teks aslinya
                    const button = this;
                    const originalButtonText = button.innerHTML;
                    const cancelButton = button.previousElementSibling; // Tombol "Batal"

                    // Tampilkan loading & sembunyikan tombol Batal
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
                    cancelButton.style.display = 'none';

                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    fetch("{{ route('reschedule.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json().then(resData => ({ status: response.status, body: resData })))
                    .then(({ status, body }) => {
                        if (status >= 400) { throw new Error(body.message); }
                        
                        // Tutup modal dan tampilkan notifikasi sukses
                        const modalElement = document.getElementById('scheduleModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) { modalInstance.hide(); }

                        showSwal(body.message, 'success');
                        // Lakukan reload tabel jika perlu
                        // if (typeof dataTable !== 'undefined') dataTable.ajax.reload();
                    })
                    .catch(error => {
                        showSwal(error.message, 'error');
                    })
                    .finally(() => {
                        // Kembalikan tombol ke keadaan semula
                        button.disabled = false;
                        button.innerHTML = originalButtonText;
                        cancelButton.style.display = 'inline-block';
                    });
                });
            }
            // ======================= END SCRIPT RESCHEDULE =========================
        });

        function openReadPP(aufnr) {
        Swal.fire({
            title: 'Konfirmasi Read PP',
            text: `Anda yakin ingin melakukan Read PP (Re-explode BOM) untuk order ${aufnr}? Proses ini akan memperbarui komponen di production order.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tampilkan loading spinner
                Swal.fire({
                    title: 'Memproses Read PP...',
                    text: 'Mohon tunggu, sedang menghubungi SAP.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Kirim request ke Controller Laravel
                fetch("{{ route('order.readpp') }}", { // Menggunakan route name baru
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ aufnr: aufnr })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                        });
                        // Opsional: Muat ulang data tabel jika perlu untuk melihat perubahan
                        // location.reload(); 
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            // Menampilkan pesan error yang lebih detail dari SAP jika ada
                            html: data.message + (data.errors ? `<br><br><strong>Detail:</strong><br><pre style="text-align:center; font-size: 0.8em;">${data.errors.join('<br>')}</pre>` : ''),
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Terjadi kesalahan saat mengirim permintaan!',
                    });
                });
            }
        });
        }

        document.addEventListener('DOMContentLoaded', function () {

            // Pastikan elemennya ada
            if (searchInput && tableBody && noResultsRow) {
                
                // Gunakan event 'input' agar lebih responsif daripada 'keyup'
                searchInput.addEventListener('input', function() {
                    
                    const filterText = searchInput.value.toUpperCase().trim();
                    const tableRows = tableBody.getElementsByTagName('tr');
                    let visibleRowsCount = 0;

                    // Loop melalui setiap baris (kecuali baris "no-results")
                    for (let i = 0; i < tableRows.length; i++) {
                        const row = tableRows[i];
                        
                        // Lewati baris "no-results" dalam loop
                        if (row.id === 'tdata3-no-results') continue;

                        const cells = row.getElementsByTagName('td');
                        let rowText = '';

                        // ✨ PERUBAHAN UTAMA: Gabungkan teks dari semua sel (td) dalam satu baris
                        for (let j = 0; j < cells.length; j++) {
                            rowText += (cells[j].textContent || cells[j].innerText) + ' ';
                        }
                        
                        // Cek apakah gabungan teks baris mengandung teks pencarian
                        if (rowText.toUpperCase().indexOf(filterText) > -1) {
                            row.style.display = ""; // Tampilkan baris
                            visibleRowsCount++;
                        } else {
                            row.style.display = "none"; // Sembunyikan baris
                        }
                    }

                    // ✨ BARU: Tampilkan atau sembunyikan pesan "tidak ada hasil"
                    if (visibleRowsCount === 0) {
                        noResultsRow.style.display = ''; // Tampilkan pesan jika tidak ada baris yang terlihat
                    } else {
                        noResultsRow.style.display = 'none'; // Sembunyikan pesan jika ada hasil
                    }
                });
            }
        });
        function openTeco(aufnr) {
            Swal.fire({
                title: 'Konfirmasi TECO',
                text: `Anda yakin ingin melakukan TECO untuk order ${aufnr}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading spinner
                    Swal.fire({
                        title: 'Memproses TECO...',
                        text: 'Mohon tunggu, sedang menghubungi SAP.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Kirim request ke Controller Laravel
                    fetch("{{ route('order.teco') }}", { // Gunakan route name agar lebih aman
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ aufnr: aufnr })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                            }).then(() => { // <-- TAMBAHKAN .then() DI SINI
                                // Periksa apakah backend mengirim sinyal 'refresh'
                                if (data.action === 'refresh') {
                                    location.reload(); // Muat ulang halaman
                                }
                            });;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Terjadi kesalahan saat mengirim permintaan!',
                        });
                    });
                }
            });
        }
        function openChangePvModal(aufnr, currentPV = '', plantVal = null) {
            const defaultPlant = @json($plant);

            // isi hidden form
            document.getElementById('changePvAufnr').value = (aufnr || '').trim();
            document.getElementById('changePvWerks').value = (plantVal || defaultPlant || '').trim();

            // set select (opsional)
            const sel = document.getElementById('changePvInput');
            sel.value = (currentPV || '').trim();               // kalau '0001' dsb
            document.getElementById('changePvCurrent').textContent =
            currentPV ? `Current PV: ${currentPV}` : '';

            // tampilkan modal
            (bootstrap.Modal.getOrCreateInstance(document.getElementById('changePvModal'))).show();
        }       
        document.addEventListener('DOMContentLoaded', () => {
            // Pastikan tombol "Simpan" di modal Change PV Anda memiliki id="changePvSubmitBtn"
            const confirmChangePvBtn = document.getElementById('changePvSubmitBtn');

            if (confirmChangePvBtn) {
                confirmChangePvBtn.addEventListener('click', function() {
                    const button = this;
                    const originalText = button.innerHTML;
                    
                    // Pastikan form di modal Anda memiliki id="changePvForm"
                    const form = document.getElementById('changePvForm');
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    // 1. Validasi Sederhana
                    if (!data.AUFNR) {
                        return showSwal('AUFNR tidak ditemukan di form.', 'error');
                    }
                    if (!data.PROD_VERSION) {
                        return showSwal('Isi Production Version (PV) dahulu.', 'error');
                    }
                    if (!data.plant) {
                        return showSwal('Plant (WERKS) tidak ditemukan di form.', 'error');
                    }

                    // Tampilkan status loading
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                    
                    // Kirim request ke endpoint Laravel
                    fetch("{{ route('change-pv') }}", { 
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        // Menggunakan pola yang sama untuk menangani respons
                        return response.json().then(resData => ({ status: response.status, body: resData }));
                    })
                    .then(({ status, body }) => {
                        if (status >= 400) { 
                            // Jika ada error dari server, lempar pesan errornya
                            throw new Error(body.error || body.message || 'Terjadi kesalahan di server.'); 
                        }
                        
                        // Logika sukses dibuat sama persis dengan change WC
                        const modalElement = document.getElementById('changePvModal'); // Pastikan ID modal Anda benar
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        showSwal(body.message, 'success');

                        // Tunggu sejenak, lalu reload halaman
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    })
                    .catch(error => {
                        // Menampilkan error menggunakan SweetAlert
                        showSwal(error.message, 'error');
                    })
                    .finally(() => {
                        // Mengembalikan tombol ke keadaan semula
                        button.disabled = false;
                        button.innerHTML = originalText;
                    });
                });
            }
        });
        const materialInput = document.getElementById('materialInput');
        // Pastikan elemennya ada untuk menghindari error
        if (materialInput) {
            // 2. Tambahkan event listener 'blur'. 
            //    Kode ini akan berjalan saat pengguna mengklik di luar input field.
            materialInput.addEventListener('blur', function() {
                
                // Ambil nilai input saat ini dan hapus spasi di awal/akhir
                const currentValue = this.value.trim();

                // 3. Buat regular expression untuk mengecek apakah SEMUA karakter adalah angka
                const isOnlyNumbers = /^\d+$/.test(currentValue);

                // 4. Jika semua karakter adalah angka dan input tidak kosong
                if (isOnlyNumbers && currentValue.length > 0) { 
                    
                    // Tambahkan angka '0' di depan hingga total panjangnya 18 karakter
                    this.value = currentValue.padStart(18, '0');
                }
                
                // Jika input berisi huruf atau karakter lain, tidak ada yang terjadi.
            });
        }
        const submitBtn = document.getElementById('confirmAddComponentBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.innerHTML;
                const form = document.getElementById('addComponentForm');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                // Validasi sederhana
                if (!data.iv_matnr || !data.iv_bdmng || !data.iv_meins || !data.iv_lgort) {
                    // Ganti 'showSwal' dengan fungsi notifikasi Anda
                    return showSwal('Harap isi semua field yang wajib diisi (*).', 'error');
                }

                button.disabled = true;
                button.innerHTML = 'Menyimpan...'; // Tampilan loading sederhana

                fetch("{{ route('component.add') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json().then(resData => ({ status: response.status, body: resData })))
                .then(({ status, body }) => {
                    if (status >= 400) { throw new Error(body.message || 'Gagal menambahkan komponen.'); }
                    showSwal(body.message, 'success'); // Tampilkan notifikasi
                    setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    showSwal(error.message, 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }
        async function bulkDeleteComponents(aufnr, kode) {
            const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);

            const payload = {
                aufnr: aufnr,
                components: Array.from(selectedCheckboxes).map(cb => {
                    return { rspos: cb.dataset.rspos };
                }),
                plant: kode
            };

            if (payload.components.length === 0) {
                Swal.fire('Tidak Ada yang Dipilih', 'Silakan pilih komponen yang ingin dihapus terlebih dahulu.', 'info');
                return;
            }

            // Membuat daftar RSPOS di dalam sebuah kotak yang bisa di-scroll
            const rsposListHtml =
                `<div style="max-height: 150px; overflow-y: auto; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <ul style="text-align: left; margin: 0; padding: 0; list-style-position: inside;">` +
                    payload.components.map(comp => `<li>RSPOS: <strong>${comp.rspos}</strong></li>`).join('') +
                    `</ul>
                </div>`;

            const result = await Swal.fire({
                title: 'Konfirmasi Hapus',
                icon: 'warning',
                html:
                    `Anda yakin ingin menghapus <strong>${payload.components.length} komponen</strong> berikut?` +
                    rsposListHtml,
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Harap tunggu, sedang memproses permintaan.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                try {
                    const response = await fetch('/component/delete-bulk', { // Pastikan URL ini benar
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const responseData = await response.json();

                    if (response.ok) {
                        await Swal.fire({
                            title: 'Berhasil, Refresh PRO Agar Data yang tampil Update',
                            text: responseData.message,
                            icon: 'success'
                        });
                        location.reload();
                    } else {
                        let errorText = responseData.message;
                        if (responseData.errors && responseData.errors.length > 0) {
                            errorText += '<br><br><strong>Detail:</strong><br>' + responseData.errors.join('<br>');
                        }
                        Swal.fire({
                            title: 'Gagal!',
                            html: errorText,
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    Swal.fire('Error Jaringan', 'Tidak dapat terhubung ke server. Silakan coba lagi.', 'error');
                }
            }
        }
        function handleComponentSelect(aufnr) {
            if (!aufnr) return;
            const allCheckboxes = document.querySelectorAll(`.component-select-${aufnr}`);
            const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
            const selectAllTableCheckbox = document.getElementById(`select-all-components-${aufnr}`);
            const bulkContainer = document.getElementById('bulk-actions-container');
            const selectionCountSpan = document.getElementById('selection-count');

            if (selectedCheckboxes.length > 0) {
                selectionCountSpan.textContent = `${selectedCheckboxes.length} item dipilih`;
                bulkContainer.classList.remove('d-none');
                bulkContainer.classList.add('d-flex');
            } else {
                bulkContainer.classList.add('d-none');
                bulkContainer.classList.remove('d-flex');
            }

            if (selectAllTableCheckbox) {
                if (selectedCheckboxes.length === allCheckboxes.length && allCheckboxes.length > 0) {
                    selectAllTableCheckbox.checked = true;
                    selectAllTableCheckbox.indeterminate = false;
                } else if (selectedCheckboxes.length > 0) {
                    selectAllTableCheckbox.checked = false;
                    selectAllTableCheckbox.indeterminate = true;
                } else {
                    selectAllTableCheckbox.checked = false;
                    selectAllTableCheckbox.indeterminate = false;
                }
            }
        }
        
        /* PERBAIKAN 2: Fungsi toggle yang lebih cerdas untuk desktop dan mobile */
        function toggleSelectAllComponents(aufnr) {
            if (!aufnr) return;
            const allCheckboxes = document.querySelectorAll(`.component-select-${aufnr}`);
            const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
            const selectAllHeaderCheckbox = document.getElementById(`select-all-components-${aufnr}`);

            // Tentukan state berikutnya: jika belum semua terpilih, maka targetnya adalah memilih semua.
            // Jika sudah semua terpilih, maka targetnya adalah membatalkan semua pilihan.
            const newCheckedState = allCheckboxes.length !== selectedCheckboxes.length;

            allCheckboxes.forEach(checkbox => {
                checkbox.checked = newCheckedState;
            });

            // Update juga state checkbox di header tabel jika ada
            if (selectAllHeaderCheckbox) {
                selectAllHeaderCheckbox.checked = newCheckedState;
            }

            // Panggil handler utama untuk memperbarui UI toolbar
            handleComponentSelect(aufnr);
        }

        function clearSelection(aufnr) {
            if (!aufnr) return;
            const allCheckboxes = document.querySelectorAll(`.component-select-${aufnr}`);
            allCheckboxes.forEach(checkbox => { checkbox.checked = false; });
            handleComponentSelect(aufnr);
        }

        async function bulkDeleteComponents(aufnr, plant) {
            const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
            if (selectedCheckboxes.length === 0) {
                Swal.fire('Tidak Ada yang Dipilih', 'Silakan pilih komponen yang ingin dihapus terlebih dahulu.', 'info');
                return;
            }
            const payload = { aufnr: aufnr, plant: plant, components: Array.from(selectedCheckboxes).map(cb => ({ rspos: cb.dataset.rspos })) };
            const rsposListHtml = `<div style="max-height: 150px; overflow-y: auto; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 10px;"><ul style="text-align: left; margin: 0; padding: 0; list-style-position: inside;">` + payload.components.map(comp => `<li>RSPOS: <strong>${comp.rspos}</strong></li>`).join('') + `</ul></div>`;
            const result = await Swal.fire({
                title: 'Konfirmasi Hapus',
                icon: 'warning',
                html: `Anda yakin ingin menghapus <strong>${payload.components.length} komponen</strong> berikut?` + rsposListHtml,
                showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                Swal.fire({ title: 'Menghapus...', text: 'Harap tunggu, sedang memproses permintaan.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                try {
                    const response = await fetch("{{ route('component.delete.bulk') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify(payload)
                    });
                    const responseData = await response.json();
                    if (response.ok && responseData.success) {
                        await Swal.fire({ title: 'Berhasil!', text: responseData.message, icon: 'success' });
                        location.reload();
                    } else {
                        let errorText = responseData.message || 'Terjadi kesalahan.';
                        if (responseData.errors && responseData.errors.length > 0) {
                            errorText += '<br><br><strong>Detail:</strong><br>' + responseData.errors.join('<br>');
                        }
                        Swal.fire({ title: 'Gagal!', html: errorText, icon: 'error' });
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    Swal.fire('Error Jaringan', 'Tidak dapat terhubung ke server. Silakan coba lagi.', 'error');
                }
            }
        }
        // Variabel untuk menyimpan instance modal
        let changeQtyModal;

        // Inisialisasi modal saat dokumen siap
        document.addEventListener("DOMContentLoaded", function() {
            const modalEl = document.getElementById('changeQuantityModal');
            if (modalEl) {
                changeQtyModal = new bootstrap.Modal(modalEl);
            }
        });

        // Fungsi untuk membuka dan mengisi data modal
        function openChangeQuantityModal(aufnr, psmng, werks) {
            // Mengisi data ke dalam form
            document.getElementById('modal_aufnr').value = aufnr;
            document.getElementById('modal_werks').value = werks;
            
            // Mengisi data yang hanya untuk ditampilkan
            document.getElementById('display_aufnr').value = aufnr;
            document.getElementById('display_current_qty').value = psmng; // Menampilkan qty asli
            
            // Mengisi nilai awal qty baru sama dengan qty saat ini
            document.getElementById('modal_new_quantity').value = psmng; 

            // Reset tampilan error/processing
            document.getElementById('processing-message').style.display = 'none';
            document.getElementById('submitChangeQtyBtn').disabled = false;
            
            // Tampilkan modal
            if (changeQtyModal) {
                changeQtyModal.show();
            }
        }

        document.getElementById('changeQtyForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitChangeQtyBtn');
            const processingMsg = document.getElementById('processing-message');
            const form = this;

            // Tampilkan status proses & nonaktifkan tombol
            submitBtn.disabled = true;
            processingMsg.style.display = 'block';

            const formData = new FormData(form);
            fetch("{{ route('order.changeQuantity') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Sukses
                    changeQtyModal.hide(); // Tutup modal
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Request Failed!',
                    text: 'An unexpected error occurred. Please try again.',
                    icon: 'error'
                });
            })
            .finally(() => {
                // Selalu jalankan ini: kembalikan tombol ke keadaan normal
                submitBtn.disabled = false;
                processingMsg.style.display = 'none';
            });
        });
    </script>
    @endpush
</x-layouts.app>