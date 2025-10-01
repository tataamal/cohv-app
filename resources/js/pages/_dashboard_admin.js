// [DIUBAH] Hapus event listener dan bungkus semua dalam satu fungsi
function initializeDashboardAdmin() {
    // Mengaktifkan semua popover di halaman
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // =======================================================
    // PART 1: INITIALIZE ALL CHARTS
    // =======================================================
    function initBarChart() {
        const chartCanvas = document.getElementById('myBarChart');
        if (!chartCanvas) return;
        const chartLabels = JSON.parse(chartCanvas.dataset.labels || '[]');
        const chartDatasets = JSON.parse(chartCanvas.dataset.datasets || '[]');
        const targetUrls = JSON.parse(chartCanvas.dataset.urls || '[]');

        function debounce(func, delay = 250) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

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

        const barChart = new Chart(chartCanvas.getContext('2d'), {
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

        new Chart(lollipopCanvas.getContext('2d'), {
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
        const pieCanvas = document.getElementById('pieChart');
        if (!pieCanvas) return;
        const labels = JSON.parse(pieCanvas.dataset.labels || '[]');
        const datasets = JSON.parse(pieCanvas.dataset.datasets || '[]');
        new Chart(pieCanvas.getContext('2d'), {
            type: 'pie',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    }

    initBarChart();
    initLollipopChart();
    initPieChart();

    // =======================================================
    // PART 2: DASHBOARD SHOW/HIDE LOGIC
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

    // =======================================================
    // PART 3: REALTIME SEARCH LOGIC
    // =======================================================
    function setupRealtimeSearch(inputId, tableBodyId, noResultsRowId) {
        const searchInput = document.getElementById(inputId);
        const tableBody = document.getElementById(tableBodyId);
        const noResultsRow = document.getElementById(noResultsRowId);
        if (!searchInput || !tableBody || !noResultsRow) return;

        const tableRows = tableBody.querySelectorAll('tr:not(#' + noResultsRowId + ')');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleRowsCount = 0;

            tableRows.forEach(row => {
                const searchableText = row.dataset.searchableText || '';
                if (searchableText.includes(searchTerm)) {
                    row.style.display = '';
                    visibleRowsCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            noResultsRow.style.display = visibleRowsCount === 0 ? '' : 'none';
        });
    }

    setupRealtimeSearch('realtimeSearchInput', 'reservasiTableBody', 'noResultsRow');
    setupRealtimeSearch('realtimeSearchInputPro', 'ongoingProTableBody', 'noResultsProRow');
    setupRealtimeSearch('realtimeSearchInputTotalPro', 'totalProTableBody', 'noResultsTotalProRow');
    setupRealtimeSearch('realtimeSearchInputSo', 'outstandingSoTableBody', 'noResultsSoRow');
}

// [BARU] Ekspor fungsi agar bisa diakses dari app.js
window.initializeDashboardAdmin = initializeDashboardAdmin;

