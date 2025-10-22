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
                           placeholder="Material (Optional)" >
                </div>
            </div>

            <div class="col-md-3" id="slocContainer">
                <div class="input-group input-group-lg">
                    <span class="input-group-text" title="Storage Location">S.Loc</span>
                    <input type="text"
                           class="form-control"
                           id="search_sloc"
                           name="search_sloc"
                           placeholder="S.Loc (Optional)">
                </div>
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
            
            // --- FUNGSI RENDER STOK (DIUBAH TOTAL SESUAI PERMINTAAN) ---
            function renderStockCards(data) {
                resultsContainer.innerHTML = ''; // Kosongkan kontainer

                // Handle jika tidak ada data
                if (!data || data.length === 0) {
                    resultsContainer.innerHTML = `<div class="col-12"><p class="text-center text-muted fs-5 mt-3">Tidak ada data stok ditemukan.</p></div>`;
                    return;
                }
                const tableWrapperHtml = `
                    <div class="col-12">
                        <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr class="align-middle">
                                        <th class="text-center" >Storage Location</th>
                                        <th class="text-center" >Material</th>
                                        <th class="text-center" >Description</th>
                                        <th class="text-center" >Batch</th>
                                        <th class="text-center" >Stock Amount</th>
                                        <th class="text-center" >UOM</th>
                                        <th class="text-center" >Sales Order</th>
                                        <th class="text-center" >SO Item</th>
                                    </tr>
                                </thead>
                                <tbody id="stockTableBody">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                resultsContainer.innerHTML = tableWrapperHtml;
                
                const tableBody = document.getElementById('stockTableBody');
                if (!tableBody) return; // Safety check

                // Loop data dan buat <tr> (baris tabel)
                data.forEach(item => {
                    let displayMeins = (item.MEINS || 'Unit').toUpperCase();
                    if (displayMeins === 'ST') {
                        displayMeins = 'PC';
                    }
                    const displayClabs = Math.floor(parseFloat(item.CLABS || '0'));
                    
                    const rowHtml = `
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-primary">${item.LGORT || '-'}</span>
                            </td>
                            
                            <td class="text-center">${item.MATNR || '-'}</td>
                            <td class="text-muted text-center">${item.MAKTX || 'Deskripsi tidak tersedia'}</td>
                            
                            <td class="text-center">
                                <span class="badge bg-secondary text-center">${item.CHARG || '-'}</span>
                            </td>
                            
                            <td class="fw-bold fs-5 text-success text-center">${displayClabs}</td>
                            
                            <td class="text-center">
                                <span class="badge bg-info text-dark text-center">${displayMeins}</span>
                            </td>
                            
                            <td class="text-center">${item.VBELN || '-'}</td>
                            <td class="text-center">${item.POSNR || '-'}</td>
                        </tr>
                    `;
                    tableBody.innerHTML += rowHtml;
                });
            }
            form.addEventListener('submit', function(event) {

                event.preventDefault();
                errorDiv.classList.add('d-none');
                errorDiv.textContent = '';
                resultsContainer.innerHTML = ''; 
                spinner.classList.remove('d-none');
                searchButton.disabled = true;
                buttonSpinner.classList.remove('d-none');
                buttonIcon.classList.add('d-none');
                buttonText.textContent = 'Searching...';

                const currentSearchType = searchTypeSelect.value;
                const searchValue = searchValueInput.value;
                const slocValue = slocInput.value;

                // Buat URL
                const url = new URL(form.action);
                url.searchParams.append('search_type', currentSearchType);
                url.searchParams.append('search_value', searchValue);
                if (currentSearchType === 'matnr' && slocValue.trim() !== '') {
                    url.searchParams.append('search_sloc', slocValue);
                }
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
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
                    spinner.classList.add('d-none'); 

                    if (currentSearchType === 'matnr') {
                        renderStockCards(data);
                    } else {
                        renderMaterialSelectionCards(data);
                    }
                })
                .catch(error => {
                    spinner.classList.add('d-none');
                    errorDiv.textContent = error.message; 
                    errorDiv.classList.remove('d-none');
                    console.error('Fetch Error:', error);
                })
                .finally(() => {
                    searchButton.disabled = false;
                    buttonSpinner.classList.add('d-none');
                    buttonIcon.classList.remove('d-none');
                    buttonText.textContent = 'Search';
                });
            });
            resultsContainer.addEventListener('click', function(event) {
                const clickedCard = event.target.closest('.material-select-card');

                if (clickedCard) {
                    const matnr = clickedCard.dataset.matnr; 

                    if (matnr) {
                        searchTypeSelect.value = 'matnr';
                        searchValueInput.value = matnr;
                        searchValueInput.placeholder = 'Masukkan Kode Material (MATNR)...';
                        searchValueInput.removeEventListener('blur', applyMatnrPadding); 
                        searchValueInput.addEventListener('blur', applyMatnrPadding); 
                        applyMatnrPadding.call(searchValueInput); 
                        slocContainer.classList.remove('d-none');
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        slocInput.focus(); 
                    }
                }
            });

        });
    </script>
</x-layouts.app>