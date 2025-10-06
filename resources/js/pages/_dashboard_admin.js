var barChart = null;
var lollipopChart = null;
var proStatusChart = null; 

function initializeDashboardAdmin() {
    // Mengaktifkan semua popover di halaman
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // =======================================================
    // PART 1: INITIALIZE ALL CHARTS
    // =======================================================
    function debounce(func, delay = 250) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function initBarChart() {
        const chartCanvas = document.getElementById('myBarChart');
        if (!chartCanvas) return;
        const chartLabels = JSON.parse(chartCanvas.dataset.labels || '[]');
        const chartDatasets = JSON.parse(chartCanvas.dataset.datasets || '[]');
        const targetUrls = JSON.parse(chartCanvas.dataset.urls || '[]');

        function getResponsiveChartOptions() {
            const isMobile = window.innerWidth < 768;
            return {
                indexAxis: isMobile ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, title: { display: !isMobile, text: 'Workcenter' }},
                    y: { title: { display: true, text: 'Jumlah' }}
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                if (!tooltipItems.length) return '';
                                const context = tooltipItems[0];
                                const wcLabel = context.label;
                                const wcDesc = context.dataset.descriptions[context.dataIndex];
                                return `${wcLabel}: ${wcDesc || ''}`;
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                const value = isMobile ? context.parsed.x : context.parsed.y;
                                const formattedValue = new Intl.NumberFormat('id-ID').format(value);
                                const unit = context.dataset.satuan || '';
                                return `${label}${formattedValue} ${unit}`;
                            }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const url = targetUrls[elements[0].index];
                        if (url) window.location.href = url;
                    }
                },
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                }
            };
        }
        
        // Hancurkan instance lama sebelum membuat yang baru (Opsional, tapi direkomendasikan)
        if (barChart) barChart.destroy();

        barChart = new Chart(chartCanvas.getContext('2d'), { // <--- Set ke variabel global
            type: 'bar',
            data: { labels: chartLabels, datasets: chartDatasets },
            options: getResponsiveChartOptions()
        });

        window.addEventListener('resize', debounce(() => {
            barChart.options = getResponsiveChartOptions();
            barChart.update();
        }));
    }

    function initLollipopChart() {
        const lollipopCanvas = document.getElementById('lollipopChart');
        if (!lollipopCanvas) return;
        
        const labels = JSON.parse(lollipopCanvas.dataset.labels || '[]');
        const datasets = JSON.parse(lollipopCanvas.dataset.datasets || '[]');
        
        // Hancurkan instance lama sebelum membuat yang baru (Opsional, tapi direkomendasikan)
        if (lollipopChart) lollipopChart.destroy();

        lollipopChart = new Chart(lollipopCanvas.getContext('2d'), { // <--- Set ke variabel global
            type: 'bar',
            data: { labels, datasets },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Total Kapasitas' }},
                    y: { grid: { display: false }}
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                if (!tooltipItems.length) return '';
                                const context = tooltipItems[0];
                                const wcLabel = context.label;
                                const wcDesc = context.dataset.descriptions[context.dataIndex];
                                return `${wcLabel}: ${wcDesc || ''}`;
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                const value = context.parsed.x; // Horizontal chart
                                const formattedValue = new Intl.NumberFormat('id-ID').format(value);
                                const unit = context.dataset.satuan || '';
                                return `${label}${formattedValue} ${unit}`;
                            }
                        }
                    }
                }
            }
        });
    }

    function initPieChart() {
        const ctx = document.getElementById('pieChart');
    
        // Cek apakah elemen canvas ada
        if (!ctx) {
            console.warn("Elemen canvas 'pieChart' tidak ditemukan.");
            return; 
        }
    
        // PENTING: Hancurkan instance chart yang sudah ada sebelum membuat yang baru
        if (proStatusChart) {
            proStatusChart.destroy();
        }
        
        // Ambil data dari dataset HTML
        try {
            const labels = JSON.parse(ctx.dataset.labels);
            const datasets = JSON.parse(ctx.dataset.datasets);
    
            // Buat instance chart baru, dan set ke variabel GLOBAL (TANPA const/let/var)
            proStatusChart = new Chart(ctx.getContext('2d'), { 
                type: 'pie', // Bisa juga 'doughnut'
                data: { 
                    labels: labels, 
                    datasets: datasets 
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    // Logika KLIK utama untuk interaktivitas
                    onClick: function(event, elements) {
                        if (elements.length > 0) {
                            const firstElement = elements[0];
                            const clickedStatus = this.data.labels[firstElement.index]; 
                            
                            // Panggil fungsi untuk memuat detail tabel
                            // Pastikan fungsi ini dideklarasikan (lihat di bawah)
                            loadProDetails(clickedStatus);
                        }
                    }
                }
            });
    
        } catch (e) {
            console.error("Gagal memuat data Chart dari dataset:", e);
        }
    }

    // Panggil semua inisialisasi chart
    initBarChart();
    initLollipopChart();
    initPieChart();
    // =======================================================
    
    // ... SISA KODE PART 2 (DASHBOARD SHOW/HIDE LOGIC) ...
    const mainDashboardContent = document.getElementById('mainDashboardContent');
    const outstandingReservasiSection = document.getElementById('outstandingReservasiSection');
    const ongoingProSection = document.getElementById('ongoingProSection');
    const totalProSection = document.getElementById('totalProSection');
    const outstandingSoSection = document.getElementById('outstandingSoSection');

    const cardOutstandingReservasi = document.getElementById('card-outstanding-reservasi');
    const cardOutgoingPro = document.getElementById('card-outgoing-pro');
    const cardTotalPro = document.getElementById('card-total-pro');
    const cardOutstandingSo = document.getElementById('card-outstanding-so');

    const backToDashboardBtns = [
        document.getElementById('backToDashboardBtn'),
        document.getElementById('backToDashboardBtnPro'),
        document.getElementById('backToDashboardBtnTotalPro'),
        document.getElementById('backToDashboardBtnSo')
    ].filter(Boolean);

    function showSection(sectionToShow) {
        mainDashboardContent.style.display = 'none';
        outstandingReservasiSection.style.display = 'none';
        ongoingProSection.style.display = 'none';
        totalProSection.style.display = 'none';
        outstandingSoSection.style.display = 'none';

        if (sectionToShow) {
            sectionToShow.style.display = 'block';
        }
    }

    if (cardOutstandingReservasi) {
        cardOutstandingReservasi.addEventListener('click', () => showSection(outstandingReservasiSection));
    }

    if (cardOutgoingPro) {
        cardOutgoingPro.addEventListener('click', () => showSection(ongoingProSection));
    }
    
    if (cardTotalPro) {
        cardTotalPro.addEventListener('click', () => showSection(totalProSection));
    }

    if (cardOutstandingSo) {
        cardOutstandingSo.addEventListener('click', () => showSection(outstandingSoSection));
    }

    backToDashboardBtns.forEach(btn => {
        btn.addEventListener('click', () => showSection(mainDashboardContent));
    });

    // ... SISA KODE PART 3 (SEARCH & FILTER GABUNGAN) ...
    
    /**
     * Menyiapkan logika pencarian real-time (di semua sel <td>) dan pemfilteran status (opsional).
     * @param {string} searchId ID dari input pencarian.
     * @param {string} tableBodyId ID dari tbody tabel.
     * @param {string} noResultsRowId ID dari baris 'Tidak Ada Hasil'.
     * @param {string} [statusFilterId] ID OPSIONAL dari dropdown filter status (hanya untuk tabel Total PRO).
     */
    function setupCombinedFilters(searchId, tableBodyId, noResultsRowId, statusFilterId = null) {
        const searchInput = document.getElementById(searchId);
        const tableBody = document.getElementById(tableBodyId);
        const noResultsRow = document.getElementById(noResultsRowId);
        
        // Ambil elemen filter status jika ID disediakan
        const statusFilter = statusFilterId ? document.getElementById(statusFilterId) : null; 

        if (!searchInput || !tableBody || !noResultsRow) return;

        // Ambil semua baris data
        const tableRows = Array.from(tableBody.querySelectorAll('tr:not(#' + noResultsRowId + ')'));

        // Fungsi utama yang menjalankan pencarian & filter
        function performFilters() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            // Ambil nilai status yang dipilih (jika ada)
            const selectedStatus = statusFilter && statusFilter.value ? statusFilter.value.toUpperCase() : '';
            let visibleRowsCount = 0;

            tableRows.forEach(row => {
                // --- 1. SEARCH LOGIC (Search by All Fields) ---
                let matchesSearch = false;
                
                if (searchTerm.length > 0) {
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        const cellText = cell.textContent.trim().toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            matchesSearch = true; 
                        }
                    });
                } else {
                    matchesSearch = true; // Jika kotak pencarian kosong, selalu cocok
                }
                
                // --- 2. STATUS FILTER LOGIC ---
                let matchesStatus = true;
                if (statusFilter) {
                    // Cek data-status yang sudah ada di HTML/Blade
                    const rowStatus = row.dataset.status ? row.dataset.status.toUpperCase() : ''; 
                    // Cocok jika filter tidak dipilih ('') ATAU status baris sama dengan yang dipilih
                    matchesStatus = selectedStatus === '' || rowStatus === selectedStatus;
                }

                // --- 3. TAMPILKAN/SEMBUNYIKAN ---
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleRowsCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Tampilkan/Sembunyikan pesan 'Tidak ada hasil'
            noResultsRow.style.display = visibleRowsCount === 0 ? '' : 'none';
        }
        
        // Pasang event listener untuk Pencarian
        searchInput.addEventListener('input', performFilters);
        
        // Pasang event listener untuk Filter Status (hanya jika ada)
        if (statusFilter) {
            statusFilter.addEventListener('change', performFilters);
        }
        
        // Panggil fungsi saat inisialisasi
        performFilters(); 
    }
    
    // PANGGILAN SEARCH & FILTER GABUNGAN
    
    // 1. Reservasi (hanya Search)
    setupCombinedFilters('realtimeSearchInput', 'reservasiTableBody', 'noResultsRow');

    // 2. Ongoing PRO (hanya Search)
    setupCombinedFilters('realtimeSearchInputPro', 'ongoingProTableBody', 'noResultsProRow');

    // 3. Outstanding SO (hanya Search)
    setupCombinedFilters('realtimeSearchInputSo', 'outstandingSoTableBody', 'noResultsSoRow');

    // 4. Total PRO (Search + Filter Status)
    setupCombinedFilters(
        'realtimeSearchInputTotalPro', 
        'totalProTableBody', 
        'noResultsTotalProRow', 
        'statusFilterTotalPro' // ID filter status ditambahkan
    );
    
    // =======================================================
    // 🚀 LOGIKA INTERAKTIF CHART-KE-TABEL
    // =======================================================

    /**
     * Fungsi untuk memuat detail PRO dari backend dan menampilkan tabel.
     * Perhatikan: Fungsi ini harus dideklarasikan di scope yang dapat diakses oleh onClick Chart.js
     * @param {string} status Status PRO yang diklik.
     */
    function loadProDetails(status) {
        // 1. Tampilkan spinner dan sembunyikan bagan
        $('#chartView').hide();
        $('#tableView').empty().hide(); 
        $('#loadingSpinner').show();
    
        // Dapatkan nilai filter yang diperlukan dari elemen HTML (pastikan data-attribute ada!)
        const kodePlant = $('#proStatusCardContainer').data('kode'); // Ambil nilai untuk filter WERKSX
        const namaBagian = $('#proStatusCardContainer').data('bagian');
        const kategori = $('#proStatusCardContainer').data('kategori');
    
        // 2. Lakukan Panggilan AJAX ke endpoint backend
        $.ajax({
            url: '/api/pro-details/' + encodeURIComponent(status),
            method: 'GET',
            data: {
                // KIRIMKAN SEMUA FILTER
                kode: kodePlant, 
                nama_bagian: namaBagian, 
                kategori: kategori
            },
            success: function(response) {
                // 3. Muat konten respons (HTML tabel) ke dalam tableView
                $('#tableView').html(response.htmlTable); 
                
                // 4. Update Judul/Header Card
                const backButton = `
                    <button id="backToChartBtn" class="btn btn-sm btn-outline-secondary me-2 mb-3">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>`;
                
                // Ambil judul awal dari card sebelum diubah
                const initialTitleText = document.getElementById('cardHeaderContent').querySelector('h3').textContent;
    
                $('#cardHeaderContent').html(backButton + `<h3 class="h5 fw-semibold text-dark">Detail PRO Status: ${status}</h3>`);
        
                // 5. Tampilkan Tabel, Sembunyikan Spinner
                $('#loadingSpinner').hide();
                $('#tableView').show();
        
                // 6. Atur event klik untuk tombol Kembali
                $('#backToChartBtn').on('click', function() {
                    // Panggil backToChartView dengan judul awal
                    backToChartView(initialTitleText);
                });
            },
            error: function(xhr) {
                console.error("Gagal memuat detail PRO:", xhr.responseText);
                
                // Dapatkan judul awal card untuk tombol kembali
                const initialTitleText = document.getElementById('cardHeaderContent').querySelector('h3').textContent;
    
                // Tampilkan pesan error sederhana
                $('#tableView').html('<div class="alert alert-danger">Gagal memuat data. Silakan coba lagi.</div>');
                
                // Tampilkan kembali tombol 'Kembali' agar user tidak terjebak
                const backButton = `
                    <button id="backToChartBtn" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>`;
                
                // Update header dengan pesan status error (tetap ada tombol kembali)
                $('#cardHeaderContent').html(backButton + `<h3 class="h5 fw-semibold text-danger">Gagal Memuat Detail Status: ${status}</h3>`);
                
                $('#loadingSpinner').hide();
                $('#tableView').show();
                
                // Atur event klik untuk tombol Kembali
                $('#backToChartBtn').on('click', function() {
                    backToChartView(initialTitleText);
                });
            }
        });
    }
    /**
     * Fungsi untuk kembali ke tampilan bagan awal.
     */
    function backToChartView(initialTitle) {
        // Default title jika tidak disediakan
        const defaultTitle = initialTitle || 'PRO Status Distribution';
        
        // Header yang akan ditampilkan saat kembali ke chart
        const initialHeader = `
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="h5 mb-1 fw-bold text-dark">${defaultTitle}</h3>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-chart-pie me-1"></i>
                        Distribusi status pada Production Order
                    </p>
                </div>
            </div>`;
        
        // Update header dengan animasi fade
        $('#cardHeaderContent').fadeOut(200, function() {
            $(this).html(initialHeader).fadeIn(200);
        });
        
        // Toggle view: Sembunyikan tabel, tampilkan chart
        $('#tableView').fadeOut(300, function() {
            // Kosongkan konten tabel setelah tersembunyi (untuk performa)
            $(this).empty();
            
            // Tampilkan chart
            $('#chartView').fadeIn(300);
        });
        
        // Optional: Scroll ke atas card dengan smooth
        $('html, body').animate({
            scrollTop: $('#cardHeaderContent').offset().top - 100
        }, 400);
    }
}

// Panggil fungsi utama saat DOM sudah siap
document.addEventListener('DOMContentLoaded', initializeDashboardAdmin);

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchPROForm');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // 1. Ambil semua nilai yang dibutuhkan dari input
            const proNumber = document.getElementById('proNumber').value.trim();
            
            // Mengambil nilai dari input hidden
            const werksCode = document.getElementById('werksCode').value.trim();
            const bagianName = document.getElementById('bagianName').value.trim();
            const categoriesName = document.getElementById('categoriesName').value.trim();

            if (proNumber) {
                // 2. Siapkan URL utama untuk routing
                // Menggunakan segment '/t3' untuk inisialisasi tampilan detail PRO
                let detailUrl = `/manufaktur/pro-transaction/${encodeURIComponent(proNumber)}/${encodeURIComponent(werksCode)}/t3`;

                // 3. Tambahkan data header sebagai Query Parameters
                const params = new URLSearchParams();
                
                params.append('bagian', bagianName); 
                params.append('categories', categoriesName);
                
                // Gabungkan URL utama dengan Query Parameters
                detailUrl += `?${params.toString()}`;

                // 4. Redirect ke halaman detail PRO
                window.location.href = detailUrl;
            } else {
                // Tampilkan pesan error jika input kosong
                alert('Please enter a PRO number');
            }
        });

        const proInput = document.getElementById('proNumber');

        // Konversi input ke uppercase secara real-time
        proInput.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });

        // Handle Enter key pada input (memicu event submit)
        proInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    }
});