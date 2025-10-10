// ===================================
// IMPORT COMPONENT
// ===================================

import './bootstrap';
import 'bootstrap';
import './sidebar.js';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap; 
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
// [DIUBAH] Pastikan file ini diimpor agar fungsinya tersedia
import './pages/_kelola-pro.js';
import './pages/_monitoring-pro.js'; 
import './pages/_dashboard_admin.js';
import './pages/_pro-transaction.js';

// =================================================================
// 1. DEFINISIKAN OBJECT & FUNGSI GLOBAL
// =================================================================
window.appLoader = {
    overlay: document.getElementById('loading-overlay'),
    show() {
        if (this.overlay) this.overlay.classList.remove('d-none');
    },
    hide() {
        if (this.overlay) this.overlay.classList.add('d-none');
    }
};
window.addEventListener('load', () => {
    window.appLoader.hide();
});

// =================================================================
// 2. INISIALISASI UTAMA (Setelah HTML siap)
// =================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ===============================================================
    // Router Sederhana: Jalankan skrip yang sesuai untuk setiap halaman.
    // ===============================================================

    // Cek apakah kita berada di HALAMAN LANDING
    if (document.getElementById('typing-effect')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Landing...');
        // Catatan: Pastikan fungsi `runTypingEffect` dan `initializeCalendar` terdefinisi di sini atau diimpor.
        // Untuk sekarang, saya asumsikan fungsi tersebut ada.
        // runTypingEffect('typing-effect', 'typing-cursor', "Bagian mana yang ingin anda kerjakan hari ini?");
        // initializeCalendar();
    }

    // [DIUBAH] Logika untuk dashboard sekarang memanggil fungsi spesifik
    if (document.querySelector('.stat-value')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Dashboard...');
        document.querySelectorAll('.stat-value').forEach(el => animateCountUp(el));
        
        // Panggil fungsi inisialisasi dari file _dashboard_admin.js
        if (window.initializeDashboardAdmin) {
            window.initializeDashboardAdmin();
        }
    }

    // Cek apakah kita berada di LAYOUT APLIKASI UTAMA (yang memiliki sidebar)
    if (document.getElementById('sidebar')) {
        console.log('✅ Inisialisasi skrip untuk App Layout...');
        initAppLayout();
    }

    if (document.getElementById('wcChart')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Kelola PRO...');
        if (window.initializeKelolaProPage) {
            window.initializeKelolaProPage();
        }
    }
});

// =================================================================
// 3. DEFINISI SEMUA FUNGSI APLIKASI
// =================================================================

// --- FUNGSI UNTUK APP LAYOUT (SIDEBAR & TOPBAR) ---
function initAppLayout() {
    // ... (Fungsi ini tidak berubah, biarkan seperti adanya)
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('sidebar-mobile-toggle');
    const collapseToggle = document.getElementById('sidebar-collapse-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (!sidebar || !mobileToggle || !collapseToggle || !overlay) return;
    
    const dropdownToggles = sidebar.querySelectorAll('[data-bs-toggle="collapse"]');
    
    const checkScreenWidth = () => {
        if (window.innerWidth < 992) {
            body.classList.add('sidebar-collapsed');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    };
    
    mobileToggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
    collapseToggle.addEventListener('click', () => body.classList.toggle('sidebar-collapsed'));
    overlay.addEventListener('click', () => body.classList.remove('sidebar-open'));
    window.addEventListener('resize', checkScreenWidth);

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(event) {
            if (body.classList.contains('sidebar-collapsed')) {
                event.preventDefault();
                body.classList.remove('sidebar-collapsed');
            }
        });
    });

    const navLoaderLinks = document.querySelectorAll('.nav-loader-link');
    navLoaderLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            if (!this.getAttribute('href') || this.getAttribute('href') === '#') {
                return;
            }
            event.preventDefault();
            const destination = this.href;
            window.appLoader.show();
            setTimeout(() => {
                window.location.href = destination;
            }, 150);
        });
    });
    checkScreenWidth();
}

// --- FUNGSI UNTUK HALAMAN DASHBOARD (Hanya yang global) ---
function animateCountUp(element) {
    // ... (Fungsi ini tidak berubah, biarkan seperti adanya)
    const target = parseInt(element.dataset.target, 10);
    if (isNaN(target)) return;
    const duration = 1500;
    let startTime = null;
    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = Math.min((timestamp - startTime) / duration, 1);
        const currentValue = Math.floor(progress * target);
        element.textContent = currentValue.toLocaleString('id-ID');
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            element.textContent = target.toLocaleString('id-ID');
        }
    }
    window.requestAnimationFrame(step);
}
// [BARU] Fungsi untuk menambahkan style border & sorting indicator ke halaman
function injectCustomStyles() {
    // Cek agar style tidak ditambahkan berulang kali
    if (document.getElementById('custom-calendar-styles')) return;

    const style = document.createElement('style');
    style.id = 'custom-calendar-styles';
    style.textContent = `
        /* Border hijau untuk setiap kotak tanggal di kalender */
        .fc-daygrid-day {
            border: 1px solid #c3e6cb !important;
        }

        /* Indikator panah untuk sorting di header tabel utama */
        #main-detail-table thead th.sort-asc::after,
        #main-detail-table thead th.sort-desc::after {
            font-family: 'bootstrap-icons'; /* Pastikan font Bootstrap Icons dimuat */
            margin-left: 0.5em;
            font-size: 0.9em;
            vertical-align: middle;
        }
        #main-detail-table thead th.sort-asc::after {
            content: '\\F537'; /* Kode ikon Bootstrap: arrow-up-short */
        }
        #main-detail-table thead th.sort-desc::after {
            content: '\\F282'; /* Kode ikon Bootstrap: arrow-down-short */
        }
    `;
    document.head.appendChild(style);
}


function initializeGoodReceiptCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    injectCustomStyles(); // Panggil fungsi untuk menambahkan border hijau

    // --- Elemen Modal & Helper ---
    const modalElement = document.getElementById('detailModal');
    if (!modalElement) return console.error('Modal #detailModal tidak ditemukan.');
    const detailModal = new bootstrap.Modal(modalElement);
    const modalTitle = document.getElementById('modalTitle');
    const modalTableBody = document.getElementById('modal-table-body');

    const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0);
    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00') return '-';
        return new Date(dateStr + 'T00:00:00Z').toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    // --- [STATE] Variabel untuk data & status sorting modal ---
    let currentModalData = [];
    let sortState = { key: 'BUDAT_MKPF', order: 'desc' }; // Default sort

    // --- [HELPER] Fungsi untuk merender konten modal (responsif & sorting) ---
    function renderModalContent() {
        modalTableBody.innerHTML = '';
        const isMobile = window.innerWidth < 768;

        const thead = modalElement.querySelector('thead');
        if (thead) thead.style.display = isMobile ? 'none' : '';

        // Logika Sorting Data
        currentModalData.sort((a, b) => {
            const valA = a[sortState.key];
            const valB = b[sortState.key];

            let comparison = 0;
            if (sortState.key === 'PSMNG' || sortState.key === 'MENGE') {
                comparison = (Number(valA) || 0) - (Number(valB) || 0);
            } else {
                comparison = String(valA || '').localeCompare(String(valB || ''));
            }
            return sortState.order === 'asc' ? comparison : -comparison;
        });

        // Loop dan Render HTML
        currentModalData.forEach(item => {
            let contentHtml = '';
            if (isMobile) {
                contentHtml = `
                    <div class="modal-card">
                        <div class="modal-card-header">
                            <strong class="text-primary">${item.AUFNR || '-'}</strong>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">${formatDate(item.BUDAT_MKPF)}</span>
                        </div>
                        <div class="modal-card-body">
                            <p class="mb-2">${item.MAKTX || '-'}</p>
                            <div class="modal-card-grid">
                                <div><strong>Sales Order:</strong></div> <div>${item.KDAUF || '-'} (${item.KDPOS || '-'})</div>
                                <div><strong>Qty PRO:</strong></div> <div>${formatNumber(item.PSMNG)}</div>
                                <div><strong>Qty GR:</strong></div> <div class="fw-bold text-success">${formatNumber(item.MENGE)}</div>
                            </div>
                        </div>
                    </div>`;
            } else {
                contentHtml = `
                    <tr>
                        <td class="text-center align-middle small fw-medium">${item.AUFNR || '-'}</td>
                        <td class="align-middle small">${item.MAKTX || '-'}</td>
                        <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDAUF || '-'}</span></td>
                        <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDPOS || '-'}</span></td>
                        <td class="text-center align-middle small">${formatNumber(item.PSMNG)}</td>
                        <td class="text-center align-middle small fw-bold text-success">${formatNumber(item.MENGE)}</td>
                        <td class="text-center align-middle"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">${formatDate(item.BUDAT_MKPF)}</span></td>
                    </tr>`;
            }
            modalTableBody.insertAdjacentHTML('beforeend', contentHtml);
        });
    }

    // --- [SETUP] Fungsi untuk event listener di header tabel ---
    function setupModalSorting() {
        modalElement.querySelectorAll('thead th[data-sort-key]').forEach(header => {
            header.addEventListener('click', () => {
                const key = header.dataset.sortKey;
                const currentOrder = sortState.key === key ? sortState.order : 'desc';
                sortState.key = key;
                sortState.order = currentOrder === 'desc' ? 'asc' : 'desc';
                renderModalContent();
            });
        });
    }

    // --- Inisialisasi FullCalendar ---
    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev',
            center: 'title',
            right: 'next today'
        },
        dayHeaderFormat: {
            weekday: 'narrow'
        },
        events: window.processedCalendarData || [],

        eventContent: function (arg) {
            const grCount = arg.event.extendedProps.totalGrCount;
            const formattedGrCount = formatNumber(grCount);
            let eventCardEl = document.createElement('div');
            eventCardEl.classList.add('fc-event-card-gr');
            eventCardEl.innerHTML = `GR: <span class="fw-bold">${formattedGrCount}</span>`;
            return {
                domNodes: [eventCardEl]
            };
        },

        eventDidMount: function (info) {
            info.el.style.cursor = 'pointer';
            const props = info.event.extendedProps;

            if (!props || !props.dispoBreakdown) return;

            const dispoBreakdownHtml = props.dispoBreakdown.map(item =>
                `<div class="d-flex justify-content-between">
                    <span>• MRP ${item.dispo}:</span>
                    <span class="fw-bold">${formatNumber(item.gr_count)}</span>
                </div>`
            ).join('');

            const popoverContent = `
                <div class="popover-summary">
                    <div class="d-flex justify-content-between">
                        <span>Total PRO:</span>
                        <span class="fw-bold">${formatNumber(props.totalPro)}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total GR:</span>
                        <span class="fw-bold text-success">${formatNumber(props.totalGrCount)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Value:</span>
                        <span class="fw-bold">${formatNumber(props.totalValue)}</span>
                    </div>
                    <hr class="my-1">
                    <div class="fw-semibold small mb-1">Breakdown per MRP:</div>
                    ${dispoBreakdownHtml}
                </div>`;

            new bootstrap.Popover(info.el, {
                title: `Ringkasan GR - ${formatDate(info.event.startStr)}`,
                content: popoverContent,
                trigger: 'hover',
                placement: 'top',
                html: true,
                container: 'body',
                sanitize: false
            });
        },

        eventClick: function (info) {
            const eventDetails = info.event.extendedProps.details;
            if (!eventDetails || !eventDetails.length) return;

            currentModalData = [...eventDetails];
            sortState = { key: 'BUDAT_MKPF', order: 'desc' };

            const eventDate = info.event.start;
            modalTitle.textContent = 'Detail GR: ' + eventDate.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            renderModalContent();
            detailModal.show();
        },
    });

    calendar.render();
    setupModalSorting();

    const sidebarToggle = document.querySelector('#sidebar-collapse-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setTimeout(() => {
                calendar.updateSize();
            }, 350);
        });
    }
}

// --- [DIPERBARUI] FUNGSI UNTUK MENGELOLA TABEL DETAIL UTAMA ---
function initializeMainDetailTable() {
    const table = document.getElementById('main-detail-table');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const headers = table.querySelectorAll('thead th[data-sort-key]');
    const initialRows = Array.from(tbody.querySelectorAll('tr.detail-row'));
    if (initialRows.length === 0) return; // Keluar jika tidak ada data

    const itemDetailModalEl = document.getElementById('itemDetailModal');
    const itemDetailContentEl = document.getElementById('itemDetailContent');
    if (!itemDetailModalEl || !itemDetailContentEl) return;
    
    const itemDetailModal = new bootstrap.Modal(itemDetailModalEl);
    
    let tableData = initialRows.map(row => {
        const cells = row.querySelectorAll('td');
        return {
            AUFNR: cells[0].textContent.trim(),
            MAKTX: row.dataset.material,
            KDAUF: row.dataset.so,
            KDPOS: row.dataset.soItem,
            PSMNG: parseFloat(cells[4].textContent.replace(/\./g, '').replace(',', '.')) || 0,
            MENGE: parseFloat(cells[5].textContent.replace(/\./g, '').replace(',', '.')) || 0,
            BUDAT_MKPF: row.dataset.postingDate,
            element: row
        };
    });

    let tableSortState = { key: 'BUDAT_MKPF', order: 'desc' };

    function updateSortIndicator() {
        headers.forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
            if (header.dataset.sortKey === tableSortState.key) {
                header.classList.add(tableSortState.order === 'asc' ? 'sort-asc' : 'sort-desc');
            }
        });
    }

    function renderTableBody() {
        tableData.sort((a, b) => {
            const valA = a[tableSortState.key];
            const valB = b[tableSortState.key];
            let comparison = 0;
            if (typeof valA === 'number' && typeof valB === 'number') {
                comparison = valA - valB;
            } else {
                comparison = String(valA).localeCompare(String(valB));
            }
            return tableSortState.order === 'asc' ? comparison : -comparison;
        });

        tbody.innerHTML = '';
        tableData.forEach(item => tbody.appendChild(item.element));
    }
    
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sortKey;
            tableSortState.order = (tableSortState.key === key && tableSortState.order === 'asc') ? 'desc' : 'asc';
            tableSortState.key = key;
            updateSortIndicator();
            renderTableBody();
        });
    });

    tbody.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            const row = e.target.closest('tr.detail-row');
            if (row) {
                const { material, so, soItem, postingDate } = row.dataset;
                itemDetailContentEl.innerHTML = `
                    <div class="modal-card-body p-2">
                        <p class="mb-3">${material}</p>
                        <div class="modal-card-grid">
                            <div><strong>Sales Order:</strong></div>
                            <div>${so} (${soItem})</div>
                            <div><strong>Tgl. Posting:</strong></div>
                            <div>${postingDate}</div>
                        </div>
                    </div>`;
                itemDetailModal.show();
            }
        }
    });

    // Terapkan sort default saat pertama kali dimuat
    updateSortIndicator();
}


// Panggil kedua fungsi utama setelah halaman dimuat
document.addEventListener('DOMContentLoaded', function () {
    initializeGoodReceiptCalendar();
    initializeMainDetailTable();
});