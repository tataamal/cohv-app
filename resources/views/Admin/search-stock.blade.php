<x-layouts.app title="Search Stock">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-box-seam me-3" style="font-size: 2rem; color: #4b5563;"></i>
        <div>
            <h4 class="mb-0">Search Stock</h4>
            <p class="mb-0 text-muted">Pilih tipe pencarian dan masukkan nilainya untuk cek stok.</p>
        </div>
    </div>

    <form action="{{ route('search.stock.data') }}" method="GET" id="searchStockForm">
        <div class="row g-3 align-items-center">

            <div class="col-md-7">
                <div class="input-group input-group-lg">
                    <select class="form-select" id="search_type" name="search_type" style="max-width: 200px;">
                        <option value="matnr" selected>By Material (MATNR)</option>
                        <option value="maktx">By Description (MAKTX)</option>
                    </select>

                    <input type="text"
                        class="form-control"
                        id="search_value"
                        name="search_value"
                        placeholder="Material (Optional)" > </div>
            </div>

            <div class="col-md-3" id="slocContainer">
                <div class="input-group input-group-lg">
                    <span class="input-group-text" title="Storage Location">S.Loc</span>
                    <input type="text"
                            class="form-control"
                            id="search_sloc"
                            name="search_sloc"
                            placeholder="S.Loc (Optional)"> </div>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-lg" id="searchButton">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="buttonSpinner"></span>
                    <i class="bi bi-search" id="buttonIcon"></i>
                    <span class="d-none d-lg-inline ms-1" id="buttonText">Search</span>
                </button>
            </div>
        </div>
    </form>

    <hr class="my-4">

    <div id="searchError" class="alert alert-danger d-none" role="alert">
    </div>

    <div id="loadingSpinner" class="text-center d-none my-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 fs-5">Mencari data stok...</p>
    </div>

    <div id="stockResultsContainer" class="row g-3">
        </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- Elemen Form ---
            const searchTypeSelect = document.getElementById('search_type');
            const searchValueInput = document.getElementById('search_value');
            const form = document.getElementById('searchStockForm');
            const spinner = document.getElementById('loadingSpinner');
            const errorDiv = document.getElementById('searchError');
            const resultsContainer = document.getElementById('stockResultsContainer');
            
            // --- ELEMEN BARU: S.Loc ---
            const slocContainer = document.getElementById('slocContainer');
            const slocInput = document.getElementById('search_sloc');

            // --- Elemen Tombol ---
            const searchButton = document.getElementById('searchButton');
            const buttonSpinner = document.getElementById('buttonSpinner');
            const buttonIcon = document.getElementById('buttonIcon');
            const buttonText = document.getElementById('buttonText');

            // --- Fungsi untuk MATNR Padding ---
            const applyMatnrPadding = function() {
                let matValue = this.value;
                const isNumeric = /^\d+$/.test(matValue);
                if (matValue.trim() !== '' && isNumeric && searchTypeSelect.value === 'matnr') {
                    this.value = matValue.padStart(18, '0');
                }
            };

            // Terapkan listener padding saat halaman dimuat
            searchValueInput.addEventListener('blur', applyMatnrPadding);

            // --- Listener untuk mengubah placeholder & padding (DIUBAH) ---
            searchTypeSelect.addEventListener('change', function() {
                if (this.value === 'matnr') {
                    // Tampilkan input S.Loc
                    slocContainer.classList.remove('d-none');

                    // Placeholder untuk MATNR (sekarang opsional)
                    searchValueInput.placeholder = 'Material (Optional)'; // <-- DIUBAH
                    searchValueInput.addEventListener('blur', applyMatnrPadding);
                    // Panggil padding HANYA jika sudah ada nilainya saat switch
                    if (searchValueInput.value.trim() !== '') {
                        applyMatnrPadding.call(searchValueInput);
                    }
                } else { // MAKTX
                    // Sembunyikan dan kosongkan input S.Loc
                    slocContainer.classList.add('d-none');
                    slocInput.value = '';

                    // Placeholder untuk MAKTX (tetap required)
                    searchValueInput.placeholder = 'Masukkan Deskripsi Material (MAKTX)...'; // <-- DIUBAH
                    searchValueInput.removeEventListener('blur', applyMatnrPadding);
                }
            });

            // --- FUNGSI RENDER (Tidak Berubah) ---
            function renderMaterialSelectionCards(data) {
                // ... (Fungsi renderMaterialSelectionCards Anda tetap sama, tidak perlu diubah)
                resultsContainer.innerHTML = ''; 
                if (!data || data.length === 0) {
                    resultsContainer.innerHTML = `<div class="col-12"><p class="text-center text-muted fs-5 mt-3">Tidak ada material ditemukan...</p></div>`;
                    return;
                }
                resultsContainer.innerHTML = `<div class="col-12"><p class="text-muted fst-italic">Ditemukan ${data.length} material...</p></div>`;
                data.forEach(item => {
                    const cardHtml = `
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-sm h-100 material-select-card" 
                                 data-matnr="${item.MATNR}" 
                                 style="cursor: pointer;" 
                                 title="Klik untuk melihat stok ${item.MATNR}">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title mb-1 text-primary">${item.MATNR}</h5>
                                        <p class="card-subtitle mb-2 text-muted">${item.MAKTX || 'Deskripsi tidak tersedia'}</p>
                                    </div>
                                    <small class="text-primary mt-2">
                                        <i class="bi bi-hand-index-thumb me-1"></i> Klik untuk melihat detail stok
                                    </small>
                                </div>
                            </div>
                        </div>
                    `;
                    resultsContainer.innerHTML += cardHtml;
                });
            }
            
            function renderStockCards(data) {
                 // ... (Fungsi renderStockCards Anda tetap sama, tidak perlu diubah)
                resultsContainer.innerHTML = ''; 
                if (!data || data.length === 0) {
                    resultsContainer.innerHTML = `<div class="col-12"><p class="text-center text-muted fs-5 mt-3">Tidak ada data stok ditemukan.</p></div>`;
                    return;
                }
                data.forEach(item => {
                    const cardHtml = `
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <strong class="text-primary">Storage Location: ${item.LGORT || '-'}</strong>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title mb-1">${item.MATNR}</h5>
                                    <p class="card-subtitle mb-2 text-muted">${item.MAKTX || 'Deskripsi tidak tersedia'}</p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fs-4 fw-bold text-success">${item.CLABS || '0'}</span>
                                        <span class="badge bg-secondary">${item.MEINS || 'Unit'}</span>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span>Batch:</span>
                                            <strong>${item.CHARG || '-'}</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span>Sales Order:</span>
                                            <strong>${item.VBELN || '-'}</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span>Item:</span>
                                            <strong>${item.POSNR || '-'}</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                    resultsContainer.innerHTML += cardHtml;
                });
            }


            // --- Skrip AJAX untuk Form Submit (DIUBAH) ---
            form.addEventListener('submit', function(event) {
                // 1. Hentikan form
                event.preventDefault();

                // 2. Siapkan UI untuk loading
                errorDiv.classList.add('d-none');
                errorDiv.textContent = '';
                resultsContainer.innerHTML = ''; 
                spinner.classList.remove('d-none');
                searchButton.disabled = true;
                buttonSpinner.classList.remove('d-none');
                buttonIcon.classList.add('d-none');
                buttonText.textContent = 'Searching...';

                // 3. Ambil data dari form (DIUBAH)
                const currentSearchType = searchTypeSelect.value;
                const searchValue = searchValueInput.value;
                const slocValue = slocInput.value; // --- BARU ---

                // Buat URL
                const url = new URL(form.action);
                url.searchParams.append('search_type', currentSearchType);
                url.searchParams.append('search_value', searchValue);

                // --- BARU: Tambahkan S.Loc jika ada dan tipenya matnr ---
                if (currentSearchType === 'matnr' && slocValue.trim() !== '') {
                    url.searchParams.append('search_sloc', slocValue);
                }
                // --- AKHIR BARU ---

                // 4. Kirim request
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    // ... (Logika .then(response) Anda tetap sama)
                    const contentType = response.headers.get('content-type');
                    if (!response.ok) {
                        if (contentType && contentType.includes('application/json')) {
                            return response.json().then(errorData => {
                                let errorMessage = 'Terjadi kesalahan.';
                                if (errorData.error) {
                                    errorMessage = typeof errorData.error === 'object' ? Object.values(errorData.error).join(' ') : errorData.error;
                                } else if (errorData.message) {
                                    errorMessage = errorData.message;
                                }
                                throw new Error(errorMessage);
                            });
                        } else {
                            throw new Error(`Server error (Status: ${response.status}). Cek tab Network.`);
                        }
                    }
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        throw new Error('Respons dari server bukan JSON.');
                    }
                })
                .then(data => {
                    // 5. SUKSES: Tampilkan data
                    spinner.classList.add('d-none'); 

                    if (currentSearchType === 'matnr') {
                        renderStockCards(data);
                    } else {
                        renderMaterialSelectionCards(data);
                    }
                })
                .catch(error => {
                    // 6. GAGAL: Tampilkan error
                    spinner.classList.add('d-none');
                    errorDiv.textContent = error.message; 
                    errorDiv.classList.remove('d-none');
                    console.error('Fetch Error:', error);
                })
                .finally(() => {
                    // 7. FINALLY: Kembalikan tombol ke normal
                    searchButton.disabled = false;
                    buttonSpinner.classList.add('d-none');
                    buttonIcon.classList.remove('d-none');
                    buttonText.textContent = 'Search';
                });
            });


            // --- LISTENER Klik Kartu (DIUBAH) ---
            resultsContainer.addEventListener('click', function(event) {
                const clickedCard = event.target.closest('.material-select-card');

                if (clickedCard) {
                    const matnr = clickedCard.dataset.matnr; 

                    if (matnr) {
                        // 1. Set nilai form
                        searchTypeSelect.value = 'matnr';
                        searchValueInput.value = matnr;
                        
                        // 2. Update UI form
                        searchValueInput.placeholder = 'Masukkan Kode Material (MATNR)...';
                        searchValueInput.removeEventListener('blur', applyMatnrPadding); 
                        searchValueInput.addEventListener('blur', applyMatnrPadding);    
                        
                        // 3. Panggil padding
                        applyMatnrPadding.call(searchValueInput); 

                        // --- PERUBAHAN ALUR UTAMA ---
                        
                        // 4. Tampilkan kontainer S.Loc
                        slocContainer.classList.remove('d-none');

                        // 5. (UX) Scroll ke atas dan fokus ke input S.Loc
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        slocInput.focus(); 
                        
                        // 6. HAPUS AUTO-KLIK
                        // searchButton.click(); // <-- Baris ini dihapus
                        
                        // --- AKHIR PERUBAHAN ---
                    }
                }
            });

        });
    </script>
</x-layouts.app>