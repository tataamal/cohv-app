var barChart = null;
var lollipopChart = null;
var proStatusChart = null;

function initializeDashboardAdmin() {
    // Mengaktifkan semua popover
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // =======================================================
    // PART 1: INITIALIZE ALL CHARTS (Tidak Berubah)
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
                                return `${context.label}: ${context.dataset.descriptions[context.dataIndex] || ''}`;
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                const value = isMobile ? context.parsed.x : context.parsed.y;
                                const unit = context.dataset.satuan || '';
                                return `${label}${new Intl.NumberFormat('id-ID').format(value)} ${unit}`;
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
        
        if (barChart) barChart.destroy();
        barChart = new Chart(chartCanvas.getContext('2d'), {
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
        
        if (lollipopChart) lollipopChart.destroy();
        lollipopChart = new Chart(lollipopCanvas.getContext('2d'), {
            type: 'bar',
            data: { labels, datasets },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { beginAtZero: true, title: { display: true, text: 'Total Kapasitas' }}, y: { grid: { display: false }} },
                plugins: { legend: { display: false } }
            }
        });
    }

    function initPieChart() {
        const ctx = document.getElementById('pieChart');
        if (!ctx) return;
        if (proStatusChart) proStatusChart.destroy();
        try {
            const labels = JSON.parse(ctx.dataset.labels);
            const datasets = JSON.parse(ctx.dataset.datasets);
            proStatusChart = new Chart(ctx.getContext('2d'), { 
                type: 'pie',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function(event, elements) {
                        if (elements.length > 0) {
                            const clickedStatus = this.data.labels[elements[0].index]; 
                            loadProDetails(clickedStatus);
                        }
                    }
                }
            });
        } catch (e) { console.error("Gagal memuat data Chart:", e); }
    }

    initBarChart();
    initLollipopChart();
    initPieChart();

    // =======================================================
    // PART 2: DASHBOARD SHOW/HIDE LOGIC (Tidak Berubah)
    // =======================================================
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
        if(mainDashboardContent) mainDashboardContent.style.display = 'none';
        if(outstandingReservasiSection) outstandingReservasiSection.style.display = 'none';
        if(ongoingProSection) ongoingProSection.style.display = 'none';
        if(totalProSection) totalProSection.style.display = 'none';
        if(outstandingSoSection) outstandingSoSection.style.display = 'none';
        if (sectionToShow) sectionToShow.style.display = 'block';
    }

    if (cardOutstandingReservasi) cardOutstandingReservasi.addEventListener('click', () => showSection(outstandingReservasiSection));
    if (cardOutgoingPro) cardOutgoingPro.addEventListener('click', () => showSection(ongoingProSection));
    if (cardTotalPro) cardTotalPro.addEventListener('click', () => showSection(totalProSection));
    if (cardOutstandingSo) cardOutstandingSo.addEventListener('click', () => showSection(outstandingSoSection));

    backToDashboardBtns.forEach(btn => btn.addEventListener('click', () => showSection(mainDashboardContent)));

    // =======================================================
    // PART 3: SEARCH & FILTER GABUNGAN (UPDATED WITH RESET)
    // =======================================================
    function setupCombinedFilters(searchId, tableBodyId, noResultsRowId, config = {}) {
        const searchInput = document.getElementById(searchId);
        const tableBody = document.getElementById(tableBodyId);
        const noResultsRow = document.getElementById(noResultsRowId);
        
        const statusFilterId = config.statusFilterId || null;
        const multiMatnrInputId = config.multiMatnrInputId || null;
        const applyBtnId = config.applyBtnId || null;
        const clearBtnId = config.clearBtnId || null;
        const resetBtnId = config.resetBtnId || null; // ID Tombol Reset Utama
        const badgeId = config.badgeId || null;
        const useSoItemLogic = config.useSoItemLogic || false;

        const statusFilter = statusFilterId ? document.getElementById(statusFilterId) : null;
        let activeMaterialList = []; 

        if (!searchInput || !tableBody || !noResultsRow) return;

        const tableRows = Array.from(tableBody.querySelectorAll('tr:not(#' + noResultsRowId + ')'));

        function performFilters() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedStatus = statusFilter && statusFilter.value ? statusFilter.value.toUpperCase() : '';
            let visibleRowsCount = 0;

            tableRows.forEach(row => {
                let matchesStatus = true;
                if (statusFilter) {
                    const rowStatus = row.dataset.status ? row.dataset.status.toUpperCase() : '';
                    matchesStatus = selectedStatus === '' || rowStatus === selectedStatus;
                }

                let matchesMaterial = true;
                if (activeMaterialList.length > 0) {
                    const rowMatnr = row.dataset.materialCode ? row.dataset.materialCode.trim() : '';
                    matchesMaterial = activeMaterialList.includes(rowMatnr);
                }

                let matchesSearch = false;
                if (searchTerm.length === 0) {
                    matchesSearch = true;
                } else {
                    if (useSoItemLogic) {
                        const so = row.dataset.so ? row.dataset.so.toLowerCase() : '';
                        const item = row.dataset.soItem ? row.dataset.soItem.toLowerCase() : '';
                        const combinedSoItem = `${so}-${item} ${so} ${item} ${so}${item}`;
                        if (combinedSoItem.includes(searchTerm)) matchesSearch = true;
                    }
                    if (!matchesSearch) {
                        const cells = row.querySelectorAll('td');
                        for (let cell of cells) {
                            if (cell.textContent.trim().toLowerCase().includes(searchTerm)) {
                                matchesSearch = true;
                                break;
                            }
                        }
                    }
                }

                if (matchesSearch && matchesStatus && matchesMaterial) {
                    row.style.display = '';
                    visibleRowsCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            noResultsRow.style.display = visibleRowsCount === 0 ? '' : 'none';
        }

        searchInput.addEventListener('input', performFilters);
        if (statusFilter) statusFilter.addEventListener('change', performFilters);

        // LOGIC MULTI MATERIAL FILTER
        if (multiMatnrInputId && applyBtnId) {
            const matnrTextarea = document.getElementById(multiMatnrInputId);
            const applyBtn = document.getElementById(applyBtnId);
            const clearBtn = document.getElementById(clearBtnId);
            const badge = document.getElementById(badgeId);
            const countInfo = document.getElementById('matnrCountInfo');

            function parseMaterialInput() {
                const rawVal = matnrTextarea.value;
                return rawVal.split(/[,\s\n]+/).map(s => s.trim()).filter(s => s !== '');
            }

            matnrTextarea.addEventListener('input', function() {
                const count = parseMaterialInput().length;
                if(countInfo) countInfo.textContent = `${count} kode terdeteksi`;
            });

            applyBtn.addEventListener('click', function() {
                activeMaterialList = parseMaterialInput();
                if (badge) {
                    if (activeMaterialList.length > 0) {
                        badge.textContent = activeMaterialList.length;
                        badge.classList.remove('d-none');
                        document.getElementById('btnMultiMatnr').classList.add('btn-primary');
                        document.getElementById('btnMultiMatnr').classList.remove('btn-outline-secondary');
                    } else {
                        badge.classList.add('d-none');
                        document.getElementById('btnMultiMatnr').classList.remove('btn-primary');
                        document.getElementById('btnMultiMatnr').classList.add('btn-outline-secondary');
                    }
                }
                const modalEl = document.getElementById('multiMatnrModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                performFilters();
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    matnrTextarea.value = '';
                    activeMaterialList = [];
                    if(countInfo) countInfo.textContent = `0 kode terdeteksi`;
                    applyBtn.click(); 
                });
            }
        }
        
        // --- LOGIC RESET BUTTON UTAMA ---
        if (resetBtnId) {
            const resetBtn = document.getElementById(resetBtnId);
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    // 1. Reset Text Search
                    searchInput.value = '';
                    
                    // 2. Reset Status Filter
                    if (statusFilter) {
                        statusFilter.value = ''; 
                    }
                    
                    // 3. Reset Material Filter Logic
                    activeMaterialList = [];
                    
                    // 4. Reset UI Material Filter (Badge, Button Color, Textarea)
                    if (multiMatnrInputId) {
                        const matnrTextarea = document.getElementById(multiMatnrInputId);
                        if(matnrTextarea) matnrTextarea.value = '';
                        
                        const countInfo = document.getElementById('matnrCountInfo');
                        if(countInfo) countInfo.textContent = '0 kode terdeteksi';

                        if (badgeId) {
                            const badge = document.getElementById(badgeId);
                            if(badge) badge.classList.add('d-none');
                        }
                        
                        const btnMulti = document.getElementById('btnMultiMatnr');
                        if(btnMulti) {
                            btnMulti.classList.remove('btn-primary');
                            btnMulti.classList.add('btn-outline-secondary');
                        }
                    }

                    // 5. Refresh Tabel (Tampilkan semua data)
                    performFilters();
                });
            }
        }
        
        performFilters();
    }

    // --- INISIALISASI FILTER ---
    setupCombinedFilters('realtimeSearchInput', 'reservasiTableBody', 'noResultsRow');
    setupCombinedFilters('realtimeSearchInputPro', 'ongoingProTableBody', 'noResultsProRow');
    setupCombinedFilters('realtimeSearchInputSo', 'outstandingSoTableBody', 'noResultsSoRow');
    
    // 4. Total PRO (DENGAN FITUR RESET)
    setupCombinedFilters(
        'realtimeSearchInputTotalPro', 
        'totalProTableBody', 
        'noResultsTotalProRow', 
        {
            statusFilterId: 'statusFilterTotalPro',
            multiMatnrInputId: 'multiMatnrInput',
            applyBtnId: 'applyMatnrFilter',
            clearBtnId: 'clearMatnrFilter',
            badgeId: 'matnrBadge',
            resetBtnId: 'btnClearTotalProFilter', // ID Tombol Reset ditambahkan
            useSoItemLogic: true 
        }
    );

    // =======================================================
    // PART 4: AJAX DETAIL VIEW LOGIC (Tidak Berubah)
    // =======================================================
    function loadProDetails(status) {
        $('#chartView').hide();
        $('#tableView').empty().hide(); 
        $('#loadingSpinner').show();
        const kodePlant = $('#proStatusCardContainer').data('kode');
        const namaBagian = $('#proStatusCardContainer').data('bagian');
        const kategori = $('#proStatusCardContainer').data('kategori');
    
        $.ajax({
            url: '/api/pro-details/' + encodeURIComponent(status),
            method: 'GET',
            data: { kode: kodePlant, nama_bagian: namaBagian, kategori: kategori },
            success: function(response) {
                $('#tableView').html(response.htmlTable); 
                const backButton = `<button id="backToChartBtn" class="btn btn-sm btn-outline-secondary me-2 mb-3"><i class="fas fa-arrow-left"></i> Kembali</button>`;
                const initialTitleText = document.getElementById('cardHeaderContent').querySelector('h3').textContent;
                $('#cardHeaderContent').html(backButton + `<h3 class="h5 fw-semibold text-dark">Detail PRO Status: ${status}</h3>`);
                $('#loadingSpinner').hide();
                $('#tableView').show();
                $('#backToChartBtn').on('click', function() { backToChartView(initialTitleText); });
            },
            error: function(xhr) {
                const initialTitleText = document.getElementById('cardHeaderContent').querySelector('h3').textContent;
                $('#tableView').html('<div class="alert alert-danger">Gagal memuat data. Silakan coba lagi.</div>');
                const backButton = `<button id="backToChartBtn" class="btn btn-sm btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Kembali</button>`;
                $('#cardHeaderContent').html(backButton + `<h3 class="h5 fw-semibold text-danger">Gagal Memuat Detail Status: ${status}</h3>`);
                $('#loadingSpinner').hide();
                $('#tableView').show();
                $('#backToChartBtn').on('click', function() { backToChartView(initialTitleText); });
            }
        });
    }

    function backToChartView(initialTitle) {
        const defaultTitle = initialTitle || 'PRO Status Distribution';
        const initialHeader = `<div class="d-flex align-items-center justify-content-between"><div><h3 class="h5 mb-1 fw-bold text-dark">${defaultTitle}</h3><p class="small text-muted mb-0"><i class="fas fa-chart-pie me-1"></i> Distribusi status pada Production Order</p></div></div>`;
        $('#cardHeaderContent').fadeOut(200, function() { $(this).html(initialHeader).fadeIn(200); });
        $('#tableView').fadeOut(300, function() { $(this).empty(); $('#chartView').fadeIn(300); });
    }
}

document.addEventListener('DOMContentLoaded', initializeDashboardAdmin);