// ===================================
// IMPORT COMPONENT
// ===================================

import './bootstrap';
import 'bootstrap';
// import './sidebar.js'; // Jika logika sidebar sudah ada di initAppLayout di bawah, ini mungkin redundan
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap; 
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

// FullCalendar Imports
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

// Page Scripts
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
    
    // --- Router Sederhana ---

    // 1. Halaman Landing
    if (document.getElementById('typing-effect')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Landing...');
        // runTypingEffect(...); 
    }

    // 2. Halaman Dashboard
    if (document.querySelector('.stat-value')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Dashboard...');
        document.querySelectorAll('.stat-value').forEach(el => animateCountUp(el));
        if (window.initializeDashboardAdmin) window.initializeDashboardAdmin();
    }

    // 3. Layout Aplikasi (Sidebar)
    if (document.getElementById('sidebar')) {
        console.log('✅ Inisialisasi skrip untuk App Layout...');
        initAppLayout();
    }

    // 4. Halaman Kelola PRO
    if (document.getElementById('wcChart')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Kelola PRO...');
        if (window.initializeKelolaProPage) window.initializeKelolaProPage();
    }

    // 5. [BARU] Halaman List GR (Kalender)
    if (document.getElementById('calendar')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Calendar GR...');
        injectCustomStyles();
        initializeGoodReceiptCalendar(); // Fungsi baru (definisinya ada di bawah)
        initializeMainDetailTable();     // Fungsi untuk tabel statis di bawah
    }
});

// =================================================================
// 3. DEFINISI SEMUA FUNGSI APLIKASI
// =================================================================

// --- FUNGSI UNTUK APP LAYOUT (SIDEBAR & TOPBAR) ---
function initAppLayout() {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('sidebar-mobile-toggle');
    const collapseToggle = document.getElementById('sidebar-collapse-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (!sidebar || !mobileToggle || !collapseToggle || !overlay) return;
    
    const checkScreenWidth = () => {
        if (window.innerWidth < 992) {
            body.classList.add('sidebar-collapsed');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    };
    
    mobileToggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
    
    collapseToggle.addEventListener('click', () => {
        body.classList.toggle('sidebar-collapsed');
        // Trigger event resize agar chart/calendar menyesuaikan diri
        window.dispatchEvent(new Event('resize')); 
    });

    overlay.addEventListener('click', () => body.classList.remove('sidebar-open'));
    window.addEventListener('resize', checkScreenWidth);

    // Dropdown toggle logic inside sidebar
    const dropdownToggles = sidebar.querySelectorAll('[data-bs-toggle="collapse"]');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(event) {
            if (body.classList.contains('sidebar-collapsed')) {
                event.preventDefault();
                body.classList.remove('sidebar-collapsed');
            }
        });
    });

    // Nav Loader
    const navLoaderLinks = document.querySelectorAll('.nav-loader-link');
    navLoaderLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            if (!this.getAttribute('href') || this.getAttribute('href') === '#') return;
            event.preventDefault();
            const destination = this.href;
            window.appLoader.show();
            setTimeout(() => { window.location.href = destination; }, 150);
        });
    });
    checkScreenWidth();
}

// --- FUNGSI ANIMASI ANGKA (DASHBOARD) ---
function animateCountUp(element) {
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

// --- FUNGSI CSS INJECTION ---
function injectCustomStyles() {
    if (document.getElementById('custom-calendar-styles')) return;

    const style = document.createElement('style');
    style.id = 'custom-calendar-styles';
    style.textContent = `
        /* Border hijau untuk setiap kotak tanggal di kalender */
        .fc-daygrid-day { border: 1px solid #c3e6cb !important; }

        /* Indikator panah sorting tabel */
        #main-detail-table thead th.sort-asc::after,
        #main-detail-table thead th.sort-desc::after {
            font-family: 'bootstrap-icons';
            margin-left: 0.5em; font-size: 0.9em; vertical-align: middle;
        }
        #main-detail-table thead th.sort-asc::after { content: '\\F537'; }
        #main-detail-table thead th.sort-desc::after { content: '\\F282'; }
    `;
    document.head.appendChild(style);
}

// =================================================================
// 4. [BARU] LOGIKA KALENDER (FullCalendar)
// =================================================================
function initializeGoodReceiptCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    // --- Elemen Modal Detail Tanggal ---
    const modalElement = document.getElementById('detailModal');
    if (!modalElement) return console.error('Modal #detailModal tidak ditemukan.');
    
    const detailModal = new bootstrap.Modal(modalElement);
    const modalTitle = document.getElementById('modalTitle');
    const modalTableBody = document.getElementById('modal-table-body');

    // --- Formatters ---
    const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0);
    
    // [PENTING] Formatter USD
    const formatCurrency = (num) => new Intl.NumberFormat('en-US', {
        style: 'currency', currency: 'USD', minimumFractionDigits: 2
    }).format(num || 0);

    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00') return '-';
        return new Date(dateStr + 'T00:00:00Z').toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
    };

    // --- State & Render Logic Modal ---
    let currentModalData = [];
    let sortState = { key: 'BUDAT_MKPF', order: 'desc' };

    function renderModalContent() {
        modalTableBody.innerHTML = '';
        const isMobile = window.innerWidth < 768;
        const thead = modalElement.querySelector('thead');
        if (thead) thead.style.display = isMobile ? 'none' : '';

        // Sorting
        currentModalData.sort((a, b) => {
            const valA = a[sortState.key];
            const valB = b[sortState.key];
            let comparison = 0;
            // Cek apakah angka (untuk quantity) atau string
            // [UPDATE] Gunakan WEMNG untuk sorting Quantity GR
            if (sortState.key === 'PSMNG' || sortState.key === 'WEMNG') {
                comparison = (Number(valA) || 0) - (Number(valB) || 0);
            } else {
                comparison = String(valA || '').localeCompare(String(valB || ''));
            }
            return sortState.order === 'asc' ? comparison : -comparison;
        });

        // Render Rows/Cards
        currentModalData.forEach(item => {
            let contentHtml = '';
            // [UPDATE] Gunakan item.WEMNG untuk Quantity GR
            if (isMobile) {
                // Tampilan Card untuk Mobile
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
                                <div><strong>Qty GR:</strong></div> <div class="fw-bold text-success">${formatNumber(item.WEMNG)}</div>
                            </div>
                        </div>
                    </div>`;
            } else {
                // Tampilan Table Row untuk Desktop
                contentHtml = `
                    <tr>
                        <td class="text-center align-middle small fw-medium">${item.AUFNR || '-'}</td>
                        <td class="align-middle small">${item.MAKTX || '-'}</td>
                        <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDAUF || '-'}</span></td>
                        <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDPOS || '-'}</span></td>
                        <td class="text-center align-middle small">${formatNumber(item.PSMNG)}</td>
                        <td class="text-center align-middle small fw-bold text-success">${formatNumber(item.WEMNG)}</td>
                        <td class="text-center align-middle"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">${formatDate(item.BUDAT_MKPF)}</span></td>
                    </tr>`;
            }
            modalTableBody.insertAdjacentHTML('beforeend', contentHtml);
        });
    }

    // Setup Header Click Sorting
    modalElement.querySelectorAll('thead th[data-sort-key]').forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sortKey;
            const currentOrder = sortState.key === key ? sortState.order : 'desc';
            sortState.key = key;
            sortState.order = currentOrder === 'desc' ? 'asc' : 'desc';
            renderModalContent();
        });
    });

    // --- FullCalendar Init ---
    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev', center: 'title', right: 'next today'
        },
        dayHeaderFormat: { weekday: 'narrow' },
        events: window.processedCalendarData || [],

        // 1. Tampilan Kartu di Kalender
        eventContent: function (arg) {
            const grCount = arg.event.extendedProps.totalGrCount;
            const formattedGrCount = formatNumber(grCount);
            let eventCardEl = document.createElement('div');
            eventCardEl.classList.add('fc-event-card-gr');
            eventCardEl.innerHTML = `GR: <span class="fw-bold">${formattedGrCount}</span>`;
            return { domNodes: [eventCardEl] };
        },

        // 2. Popover (Hover)
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

            // [FIX] Menggunakan formatCurrency (USD) untuk Total Value
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
                        <span class="fw-bold text-primary">${formatCurrency(props.totalValue)}</span>
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

        // 3. Klik Tanggal -> Buka Modal
        // [PERBAIKAN] Data di grouping berdasarkan AUFNR agar unik di modal
        eventClick: function (info) {
            const eventDetails = info.event.extendedProps.details;
            if (!eventDetails || !eventDetails.length) return;

            // --- AGREGASI DATA ---
            // Menggabungkan transaksi dengan AUFNR yang sama menjadi satu baris
            const groupedData = {};
            eventDetails.forEach(item => {
                const key = item.AUFNR;
                // [PERBAIKAN UTAMA] Gunakan WEMNG (Qty Aktual GR)
                const currentWemng = parseFloat(item.WEMNG) || 0;
                
                if (!groupedData[key]) {
                    // Copy object, set WEMNG awal
                    groupedData[key] = { ...item, WEMNG: currentWemng };
                } else {
                    // Akumulasi WEMNG
                    groupedData[key].WEMNG += currentWemng;
                }
            });

            // Konversi kembali object ke array untuk ditampilkan
            currentModalData = Object.values(groupedData);
            
            sortState = { key: 'BUDAT_MKPF', order: 'desc' }; // Reset sort

            const eventDate = info.event.start;
            modalTitle.textContent = 'Detail GR: ' + eventDate.toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });

            renderModalContent();
            detailModal.show();
        },
    });

    calendar.render();

    // Resize Helper jika sidebar di-toggle
    const sidebarToggle = document.querySelector('#sidebar-collapse-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setTimeout(() => calendar.updateSize(), 350);
        });
    }
}

// =================================================================
// 5. FUNGSI UNTUK MENGELOLA TABEL DETAIL UTAMA (STATIS DI BAWAH)
// =================================================================
function initializeMainDetailTable() {
    const table = document.getElementById('main-detail-table');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const headers = table.querySelectorAll('thead th[data-sort-key]');
    const initialRows = Array.from(tbody.querySelectorAll('tr.detail-row'));
    if (initialRows.length === 0) return;

    // Modal Item Detail (untuk klik baris tabel di mobile)
    const itemDetailModalEl = document.getElementById('itemDetailModal');
    const itemDetailContentEl = document.getElementById('itemDetailContent');
    let itemDetailModal = null;
    if (itemDetailModalEl && itemDetailContentEl) {
        itemDetailModal = new bootstrap.Modal(itemDetailModalEl);
    }
    
    // Parsing Data dari DOM
    let tableData = initialRows.map(row => {
        const cells = row.querySelectorAll('td');
        return {
            AUFNR: cells[0].textContent.trim(),
            MAKTX: row.dataset.material,
            KDAUF: row.dataset.so,
            KDPOS: row.dataset.soItem,
            // Parsing angka format ID (hapus titik, ganti koma dengan titik)
            PSMNG: parseFloat(cells[4].textContent.replace(/\./g, '').replace(',', '.')) || 0,
            // [UPDATE] Penamaan properti diganti ke WEMNG (Quantity GR)
            WEMNG: parseFloat(cells[5].textContent.replace(/\./g, '').replace(',', '.')) || 0,
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
    
    // Event Listener Sorting
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sortKey;
            // Jika sorting key adalah MENGE (dari html lama), map ke WEMNG di data kita
            const dataKey = (key === 'MENGE') ? 'WEMNG' : key;

            tableSortState.order = (tableSortState.key === dataKey && tableSortState.order === 'asc') ? 'desc' : 'asc';
            tableSortState.key = dataKey;
            updateSortIndicator();
            renderTableBody();
        });
    });

    // Event Listener Klik Baris (Khusus Mobile)
    tbody.addEventListener('click', function(e) {
        if (window.innerWidth < 768 && itemDetailModal) {
            const row = e.target.closest('tr.detail-row');
            if (row) {
                const { material, so, soItem, postingDate } = row.dataset;
                itemDetailContentEl.innerHTML = `
                    <div class="modal-card-body p-2">
                        <p class="mb-3">${material}</p>
                        <div class="modal-card-grid">
                            <div><strong>Sales Order:</strong></div> <div>${so} (${soItem})</div>
                            <div><strong>Tgl. Posting:</strong></div> <div>${postingDate}</div>
                        </div>
                    </div>`;
                itemDetailModal.show();
            }
        }
    });

    // Init sort awal
    updateSortIndicator();
}

// Ekspor global jika diperlukan oleh modul lain (opsional)
window.initializeGoodReceiptCalendar = initializeGoodReceiptCalendar;
window.initializeMainDetailTable = initializeMainDetailTable;