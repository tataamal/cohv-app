// resources/js/_monitoring-pro.js

document.addEventListener('DOMContentLoaded', function () {
    const tableContainer = document.getElementById('pro-table-container');

    if (tableContainer) {
        const filterLinks = document.querySelectorAll('.stat-card-link');
        const activeKode = tableContainer.dataset.kode;

        const fetchTableData = (filterStatus = 'all') => {
            tableContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading data...</p></div>';
            const url = `/monitoring-pro/${activeKode}/filter?status=${filterStatus}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    tableContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    tableContainer.innerHTML = '<div class="alert alert-danger">Gagal memuat data. Silakan periksa koneksi atau coba lagi.</div>';
                });
        };

        filterLinks.forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                
                // [DIPERBAIKI] Logika untuk style 'active' dibuat lebih aman
                
                // Hapus class 'active' dari semua kartu
                filterLinks.forEach(l => {
                    const card = l.querySelector('.stat-card');
                    if (card) { // <-- Cek dulu apakah kartu ada
                        card.classList.remove('active');
                    }
                });
                
                // Tambahkan class 'active' hanya ke kartu yang diklik
                const clickedCard = this.querySelector('.stat-card');
                if (clickedCard) { // <-- Cek dulu apakah kartu ada
                    clickedCard.classList.add('active');
                }
                
                const filter = this.dataset.filter;
                fetchTableData(filter);
            });
        });
    }
});