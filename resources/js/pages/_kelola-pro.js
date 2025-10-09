function initializeKelolaProPage() {
    const mainWorkCenter = window.currentWorkCenter;
    const compatibilities = window.compatibilitiesData;
    const plantKode = window.plantKode;
    const wcDescriptionMap = window.wcDescriptionMap;
    const chartCanvas = document.getElementById('wcChart');
    const dropArea = document.getElementById('chart-drop-area');

    if (!chartCanvas || !dropArea) {
        console.error("Elemen canvas chart atau drop area tidak ditemukan.");
        return;
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification'; toast.textContent = message; document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }

    const popoverTriggerEl = document.getElementById('legend-popover-btn');
    const legendContentEl = document.getElementById('legend-content');
    if (popoverTriggerEl && legendContentEl) {
        new bootstrap.Popover(popoverTriggerEl, {
            content: `<div class="d-flex flex-column gap-2">${legendContentEl.innerHTML}</div>`,
            html: true, sanitize: false, placement: 'bottom'
        });
    }

    // =================================================================
    // LOGIKA UTAMA CHART
    // =================================================================
    const ctx = chartCanvas.getContext('2d');
    const originalChartLabels = window.chartLabels;
    const originalChartDataPro = window.chartProData;
    const originalChartDataCapacity = window.chartCapacityData;

    const defaultColor = 'rgba(13, 110, 253, 0.7)';
    const compatibleColor = 'rgba(25, 135, 84, 0.7)';
    const conditionalColor = 'rgba(255, 193, 7, 0.7)';
    const notCompatibleColor = 'rgba(108, 117, 125, 0.7)';

    const compatibilityRules = Object.fromEntries((compatibilities[mainWorkCenter] || []).map(rule => [rule.wc_tujuan_code, rule.status]));
    
    const initialBarColors = originalChartLabels.map(targetWc => {
        if (targetWc === mainWorkCenter) return defaultColor;
        const status = (compatibilityRules[targetWc] || 'Not Compatible').toLowerCase();
        if (status === 'compatible') return compatibleColor;
        if (status === 'compatible with condition') return conditionalColor;
        return notCompatibleColor;
    });

    const wcChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: originalChartLabels,
            // [DIUBAH] Hanya satu dataset (Kapasitas) yang ditampilkan
            datasets: [
                {
                    label: 'Kapasitas',
                    data: originalChartDataCapacity,
                    backgroundColor: initialBarColors,
                    borderWidth: 1,
                    borderColor: 'rgba(255,255,255,0.5)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: (items) => wcDescriptionMap[items[0].label] || items[0].label,
                        // [DIUBAH] Callback label sekarang menampilkan dua baris informasi
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            const capacityValue = originalChartDataCapacity[dataIndex] ?? 0;
                            const proValue = originalChartDataPro[dataIndex] ?? 0;

                            const capacityLabel = `Kapasitas: ${capacityValue.toLocaleString('id-ID')} Jam`;
                            const proLabel = `Jumlah PRO: ${proValue.toLocaleString('id-ID')}`;

                            return [capacityLabel, proLabel]; // Kembalikan array untuk membuat multi-line tooltip
                        }
                    }
                }
            }
        }
    });

    // =================================================================
    // LOGIKA PEMILIHAN (SELECTION) & AKSI BULK (tidak ada perubahan)
    // =================================================================
    const bulkActionBar = document.getElementById('bulk-action-bar');
    const selectionCountSpan = document.getElementById('selection-count');
    const allCheckboxes = document.querySelectorAll('.pro-select-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-pro');
    const bulkDragHandle = document.getElementById('bulk-drag-handle');

    function updateBulkSelection() {
        const selected = document.querySelectorAll('.pro-select-checkbox:checked');
        const count = selected.length;
        if (count > 0) {
            selectionCountSpan.textContent = `${count} PRO terpilih`;
            bulkActionBar.classList.remove('d-none');
            bulkActionBar.classList.add('d-flex');
        } else {
            bulkActionBar.classList.add('d-none');
            bulkActionBar.classList.remove('d-flex');
        }
        const visibleCheckboxes = document.querySelectorAll('.pro-row:not([style*="display: none"]) .pro-select-checkbox');
        selectAllCheckbox.checked = count > 0 && count === visibleCheckboxes.length;
        selectAllCheckbox.indeterminate = count > 0 && count < visibleCheckboxes.length;
    }

    window.clearBulkSelection = function() {
        allCheckboxes.forEach(cb => cb.checked = false);
        updateBulkSelection();
    }

    allCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkSelection));
    selectAllCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.pro-row:not([style*="display: none"]) .pro-select-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkSelection();
    });

    // =================================================================
    // LOGIKA DRAG AND DROP BARU (tidak ada perubahan)
    // =================================================================
    bulkDragHandle.addEventListener('dragstart', function(e) {
        const selectedPros = Array.from(document.querySelectorAll('.pro-select-checkbox:checked')).map(cb => {
            const row = cb.closest('.pro-row');
            return {
                proCode: row.dataset.proCode,
                wcAsal: row.dataset.wcAsal,
                oper: row.dataset.oper,
                pwwrk: row.dataset.pwwrk
            };
        });
        if (selectedPros.length === 0) {
            e.preventDefault();
            showToast("Pilih setidaknya satu PRO untuk dipindahkan.");
            return;
        }
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'bulk', pros: selectedPros }));
    });

    dropArea.addEventListener('dragover', (e) => e.preventDefault());

    dropArea.addEventListener('drop', function(e) {
        e.preventDefault();
        try {
            const droppedData = JSON.parse(e.dataTransfer.getData('text/plain'));
            const elements = wcChart.getElementsAtEventForMode(e, 'nearest', { intersect: false }, true);
            if (elements.length) {
                const wcTujuan = wcChart.data.labels[elements[0].index];
                const modal = new bootstrap.Modal(document.getElementById('changeWcModal'));
                handleBulkDrop(droppedData, wcTujuan, modal);
            }
        } catch (error) {
            console.error("Error saat drop:", error);
        }
    });

    function handleBulkDrop(data, wcTujuan, modal) {
        if (!data.pros || data.pros.length === 0) return;
        const isMoving = data.pros.some(pro => pro.wcAsal !== wcTujuan);
        if (!isMoving) {
            showToast("Tidak ada PRO yang perlu dipindahkan.");
            return;
        }
        let allCompatible = true;
        for (const pro of data.pros) {
            if (pro.wcAsal === wcTujuan) continue;
            const status = (compatibilities[pro.wcAsal] || []).find(r => r.wc_tujuan_code === wcTujuan)?.status.toLowerCase();
            if (status !== 'compatible' && status !== 'compatible with condition') {
                allCompatible = false;
                break;
            }
        }
        if (allCompatible) {
            document.getElementById('bulk-move-content').classList.remove('d-none');
            document.getElementById('single-move-content').classList.add('d-none');
            document.getElementById('bulk-pro-count').textContent = data.pros.length;
            document.getElementById('bulk-wcTujuanWc').textContent = wcTujuan;
            const proListContainer = document.getElementById('pro-list-modal');
            proListContainer.innerHTML = data.pros.map(pro => `
                <div class="list-group-item d-flex justify-content-between align-items-center list-group-item-action py-2">
                    <span><i class="fa-solid fa-file-invoice me-2 text-muted"></i><strong>${pro.proCode}</strong></span>
                    <small class="text-muted">dari ${pro.wcAsal}</small>
                </div>`).join('');
            const form = document.getElementById('changeWcForm');
            form.action = `/changeWCBulk/${plantKode}/${wcTujuan}`;
            document.getElementById('formBulkPros').value = JSON.stringify(data.pros);
            document.getElementById('formAufnr').value = '';
            modal.show();
        } else {
            showToast(`Satu atau lebih PRO tidak kompatibel dengan Work Center ${wcTujuan}.`);
        }
    }

    // =================================================================
    // EVENT LISTENER LAINNYA (NAVIGASI, PENCARIAN, SUBMIT) (tidak ada perubahan)
    // =================================================================
    const wcSelect = document.getElementById('wcQuickNavSelect');
    if (wcSelect) {
        // Ambil semua elemen <option>, ubah menjadi array, lalu urutkan
        const options = Array.from(wcSelect.options);
        options.sort((a, b) => a.textContent.localeCompare(b.textContent));

        // Masukkan kembali options yang sudah terurut ke dalam select
        options.forEach(option => wcSelect.appendChild(option));
    }

    // Event listener untuk tombol navigasi (tidak berubah)
    document.getElementById('wcQuickNavBtn')?.addEventListener('click', () => {
        const selectedWc = wcSelect.value;
        if (selectedWc) {
            // Pastikan variabel plantKode sudah didefinisikan sebelumnya di scope ini
            window.location.href = `/wc-mapping/details/${plantKode}/${selectedWc}`;
        }
    });

    // --- Bagian 2: Modifikasi untuk Search by All ---
    document.getElementById('proSearchInput')?.addEventListener('keyup', function() {
        const filterText = this.value.toUpperCase();
        document.querySelectorAll('#proTableBody tr').forEach(row => {
            // Ambil seluruh teks dari satu baris (tr)
            const rowText = row.textContent || row.innerText;

            // Cek apakah teks baris mengandung teks filter
            // Jika iya, tampilkan. Jika tidak, sembunyikan.
            row.style.display = rowText.toUpperCase().includes(filterText) ? "" : "none";
        });
        
        // Pastikan fungsi ini ada jika Anda membutuhkannya
        if (typeof updateBulkSelection === 'function') {
            updateBulkSelection();
        }
    });
    document.getElementById('changeWcForm')?.addEventListener('submit', function() {
        document.getElementById('loading-overlay').classList.remove('d-none');
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...`;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const proDetailModalElement = document.getElementById('proDetailModal');
    
    // Pastikan elemen modal ditemukan sebelum melanjutkan
    if (!proDetailModalElement) {
        console.error('Elemen modal dengan ID "proDetailModal" tidak ditemukan.');
        return; // Hentikan eksekusi jika modal tidak ada
    }

    const proDetailModal = new bootstrap.Modal(proDetailModalElement);

    document.querySelectorAll('#proTableBody .pro-row').forEach(row => {
        row.addEventListener('click', function(event) {
            if (event.target.matches('.pro-select-checkbox')) {
                return; 
            }
            if (window.innerWidth <= 768) {
                const cells = this.getElementsByTagName('td');
                const proCode = cells[2].textContent.trim();
                
                document.getElementById('proModalTitle').textContent = 'Detail PRO: ' + proCode;
                document.getElementById('modalPro').textContent = proCode;
                document.getElementById('modalSo').textContent = cells[3].textContent.trim();
                document.getElementById('modalSoItem').textContent = cells[4].textContent.trim();
                document.getElementById('modalWc').textContent = cells[5].textContent.trim();
                document.getElementById('modalMaterial').textContent = cells[6].textContent.trim();
                document.getElementById('modalOperKey').textContent = cells[7].textContent.trim();
                document.getElementById('modalPsmng').textContent = cells[8].textContent.trim();
                document.getElementById('modalWemng').textContent = cells[9].textContent.trim();
                document.getElementById('modalPv1').textContent = cells[10].textContent.trim();
                document.getElementById('modalPv2').textContent = cells[11].textContent.trim();
                document.getElementById('modalPv3').textContent = cells[12].textContent.trim();
                
                proDetailModal.show();
            }
        });
    });
});
// Ekspor fungsi utama agar bisa dipanggil dari app.js
window.initializeKelolaProPage = initializeKelolaProPage;

