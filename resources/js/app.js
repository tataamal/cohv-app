// ===================================
// IMPORT COMPONENT
// ===================================

import './bootstrap';
import 'bootstrap';
// import './sidebar.js'; 
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap; 
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

// Import SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal;

// [FIX] Import Flatpickr & Locale Indonesia
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

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
    
    // 1. Halaman Landing
    if (document.getElementById('typing-effect')) {
        // console.log('✅ Inisialisasi skrip untuk Halaman Landing...');
        // runTypingEffect(...); 
    }

    // 2. Halaman Dashboard
    if (document.querySelector('.stat-value')) {
        // console.log('✅ Inisialisasi skrip untuk Halaman Dashboard...');
        document.querySelectorAll('.stat-value').forEach(el => animateCountUp(el));
        if (window.initializeDashboardAdmin) window.initializeDashboardAdmin();
    }

    // 3. Layout Aplikasi (Sidebar)
    if (document.getElementById('sidebar')) {
        // console.log('✅ Inisialisasi skrip untuk App Layout...');
        initAppLayout();
    }

    // 4. Halaman Kelola PRO
    if (document.getElementById('wcChart')) {
        // console.log('✅ Inisialisasi skrip untuk Halaman Kelola PRO...');
        if (window.initializeKelolaProPage) window.initializeKelolaProPage();
    }

    // 5. [FIXED] Halaman List GR (Kalender & Filter)
    if (document.getElementById('calendar')) {
        // console.log('✅ Inisialisasi skrip untuk Halaman Calendar GR...');
        
        injectCustomStyles();
        
        // Render Kalender (Hanya panggil SEKALI)
        initializeGoodReceiptCalendar(); 
        
        // Render Tabel Detail Bawah
        initializeMainDetailTable();     
        
        // Inisialisasi Logika Filter & Print
        initializeGrFilterLogic(); 
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

    // [FIX] Cek keberadaan modal secara aman
    let detailModal = null;
    let modalTitle = null;
    let modalTableBody = null;

    const modalElement = document.getElementById('detailModal');
    if (modalElement) {
        detailModal = new bootstrap.Modal(modalElement);
        modalTitle = document.getElementById('modalTitle');
        modalTableBody = document.getElementById('modal-table-body');
    } else {
        console.warn('⚠️ Modal #detailModal tidak ditemukan. Fitur klik detail tanggal akan dinonaktifkan.');
    }

    // --- State & Render Logic Modal ---
    let currentModalData = [];
    let currentEventDateStr = null; // Menyimpan tanggal yang sedang dibuka
    let sortState = { key: 'BUDAT_MKPF', order: 'desc' };

    // --- [NEW] INJECT TOMBOL PRINT DI HEADER MODAL ---
    if (modalElement) {
        let printBtn = document.getElementById('btn-print-daily');
        // Jika belum ada, buat tombolnya
        if (!printBtn) {
            const modalHeader = modalElement.querySelector('.modal-header');
            if (modalHeader) {
                printBtn = document.createElement('button');
                printBtn.id = 'btn-print-daily';
                printBtn.className = 'btn btn-sm btn-outline-success me-2 d-flex align-items-center gap-2 fw-bold shadow-sm';
                printBtn.innerHTML = '<i class="bi bi-printer-fill"></i> Print Hari Ini';
                printBtn.title = "Cetak data hanya untuk tanggal ini";
                printBtn.onclick = function() {
                    if (!currentModalData || currentModalData.length === 0) return;
                    
                    Swal.fire({
                        title: 'Print Laporan Harian',
                        // [UPDATED] SweetAlert Center Alignment
                        html: `
                            <div class="text-center">
                                <p class="mb-2">Anda akan mencetak <b>${currentModalData.length} data</b></p>
                                <div class="badge bg-light text-dark border p-2 fs-6">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Tanggal: ${formatDate(currentEventDateStr)}
                                </div>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754', 
                        confirmButtonText: '<i class="bi bi-printer"></i> Ya, Cetak',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitPrintForm(currentModalData, 'daily', currentEventDateStr);
                        }
                    });
                };

                // Insert sebelum tombol close
                const closeBtn = modalHeader.querySelector('.btn-close');
                if (closeBtn) {
                    const title = modalHeader.querySelector('.modal-title');
                    if(title) {
                        title.classList.add('flex-grow-1'); 
                        title.classList.remove('ms-auto'); 
                    }
                    modalHeader.insertBefore(printBtn, closeBtn);
                } else {
                    modalHeader.appendChild(printBtn);
                }
            }
        }
    }

    // --- Formatters ---
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID', { 
            maximumFractionDigits: 2,
            minimumFractionDigits: 0 
        }).format(num || 0);
    }
    
    // Formatter USD
    const formatCurrency = (num) => new Intl.NumberFormat('en-US', {
        style: 'currency', currency: 'USD', minimumFractionDigits: 2
    }).format(num || 0);

    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00') return '-';
        return new Date(dateStr + 'T00:00:00Z').toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
    };

    function renderModalContent() {
        if (!modalTableBody || !modalElement) return;

        modalTableBody.innerHTML = '';
        const isMobile = window.innerWidth < 768;
        const thead = modalElement.querySelector('thead');
        if (thead) thead.style.display = isMobile ? 'none' : '';
    
        // 1. Grouping Data
        const groupedData = {};
    
        currentModalData.forEach(item => {
            const wcKey = item.ARBPL || 'Unassigned'; 
            
            if (!groupedData[wcKey]) {
                groupedData[wcKey] = {
                    items: [],
                    totalPro: 0,
                    totalPsmng: 0,
                    totalMenge: 0,
                    totalValues: {} 
                };
            }
    
            const psmng = parseFloat(item.PSMNG) || 0;
            const menge = parseFloat(item.MENGE) || 0;
            const netpr = parseFloat(item.NETPR) || 0;
            const currency = item.WAERS || 'IDR';
            
            groupedData[wcKey].items.push(item);
            groupedData[wcKey].totalPro += 1;
            groupedData[wcKey].totalPsmng += psmng;
            groupedData[wcKey].totalMenge += menge;
            
            if (!groupedData[wcKey].totalValues[currency]) {
                groupedData[wcKey].totalValues[currency] = 0;
            }
            groupedData[wcKey].totalValues[currency] += (netpr * menge);
        });
    
        // 2. Render Loop
        const sortedKeys = Object.keys(groupedData).sort();
        
        sortedKeys.forEach((wc, index) => {
            const group = groupedData[wc];
    
            // Sorting
            group.items.sort((a, b) => {
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
    
            const fmtPsmng = formatNumber(group.totalPsmng);
            const fmtMenge = formatNumber(group.totalMenge);
            const wcLabel = wc === 'Unassigned' ? 'Tanpa Workcenter' : wc;
            
            const generateValueHtml = (isMobileView) => {
                let html = '';
                const currencies = Object.keys(group.totalValues);
                currencies.forEach(curr => {
                    const val = formatNumber(group.totalValues[curr]);
                    if (isMobileView) {
                        html += `<div class="fw-bold text-dark" style="font-size: 0.8rem;">${curr} ${val}</div>`;
                    } else {
                        html += `<div class="fw-bold text-dark font-monospace">${curr} ${val}</div>`;
                    }
                });
                return html;
            };
    
            // A. SPACER
            if (index > 0 && !isMobile) {
                modalTableBody.insertAdjacentHTML('beforeend', `<tr class="table-spacer"><td colspan="9"></td></tr>`);
            }
    
            // B. HEADER GROUP
            if (isMobile) {
                const mobileHeaderHtml = `
                    <div class="mt-4 mb-2 p-3 bg-white border border-primary border-opacity-25 rounded shadow-sm position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 h-100 bg-primary opacity-10" style="width: 4px;"></div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-gear-wide-connected fs-4 text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">${wcLabel}</h6>
                                    <span class="badge bg-secondary rounded-pill" style="font-size: 0.65rem;">${group.totalPro} Items</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small bg-light p-2 rounded">
                            <div class="text-center px-2">
                                <div class="text-muted" style="font-size: 0.7rem;">PRO</div>
                                <div class="fw-bold">${fmtPsmng}</div>
                            </div>
                            <div class="vr"></div>
                            <div class="text-center px-2">
                                <div class="text-muted" style="font-size: 0.7rem;">GR</div>
                                <div class="fw-bold text-success">${fmtMenge}</div>
                            </div>
                            <div class="vr"></div>
                            <div class="text-center px-2">
                                <div class="text-muted" style="font-size: 0.7rem;">TOTAL VALUE</div>
                                ${generateValueHtml(true)}
                            </div>
                        </div>
                    </div>`;
                modalTableBody.insertAdjacentHTML('beforeend', mobileHeaderHtml);
            } else {
                // Desktop Header
                const desktopHeaderHtml = `
                    <tr class="group-header-row">
                        <td colspan="3" class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="wc-icon"><i class="bi bi-gear-wide-connected"></i></div>
                                <div>
                                    <div class="fw-bold text-dark fs-6">${wcLabel}</div>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="badge bg-secondary rounded-pill me-2">${group.totalPro} Items</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total PRO</small>
                            <span class="fw-bold fs-6 text-dark">${fmtPsmng}</span>
                        </td>
                        <td class="text-center">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total GR</small>
                            <span class="fw-bold fs-5 text-success">${fmtMenge}</span>
                        </td>
                        <td colspan="4" class="pe-3 text-end">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; margin-bottom: 2px;">Total Est. Value</small>
                            ${generateValueHtml(false)}
                        </td>
                    </tr>
                `;
                modalTableBody.insertAdjacentHTML('beforeend', desktopHeaderHtml);
            }
    
            // C. DETAIL ITEMS
            group.items.forEach(item => {
                const cleanKdpos = item.KDPOS ? parseInt(item.KDPOS) : '-';
                const soCombined = item.KDAUF ? `${item.KDAUF} / ${cleanKdpos}` : '-';
                
                const lineValue = (parseFloat(item.NETPR) || 0) * (parseFloat(item.MENGE) || 0);
                const currency = item.WAERS || 'IDR';
    
                let contentHtml = '';
    
                if (isMobile) {
                    contentHtml = `
                        <div class="modal-card ms-3 mb-2 border-start border-3 border-secondary border-opacity-25">
                             <div class="modal-card-body p-2">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-primary">${item.AUFNR || '-'}</strong>
                                    <span class="badge bg-light text-dark border">${formatDate(item.BUDAT_MKPF)}</span>
                                </div>
                                <div class="text-truncate mb-1 text-muted small">${item.MAKTX || '-'}</div>
                                <div class="d-flex justify-content-between border-top pt-1 mt-1 small">
                                    <span>SO: ${soCombined}</span>
                                    <span class="fw-bold text-success">GR: ${formatNumber(item.MENGE)}</span>
                                </div>
                                 <div class="d-flex justify-content-between small mt-1">
                                    <span class="text-muted">WC: ${item.ARBPL || '-'}</span>
                                    <span class="fw-bold text-dark">${currency} ${formatNumber(lineValue)}</span>
                                </div>
                            </div>
                        </div>`;
                } else {
                    contentHtml = `
                        <tr>
                            <td class="text-center align-middle small fw-medium">${item.AUFNR || '-'}</td>
                            <td class="align-middle small text-truncate" style="max-width: 250px;" title="${item.MAKTX}">${item.MAKTX || '-'}</td>
                            <td class="text-center align-middle small text-muted">${soCombined}</td>
                            <td class="text-center align-middle small text-muted">${formatNumber(item.PSMNG)}</td>
                            <td class="text-center align-middle small fw-bold text-dark bg-success-subtle bg-opacity-10">${formatNumber(item.MENGE)}</td>
                            
                            <td class="text-end align-middle small pe-3">
                                <span class="text-muted" style="font-size: 0.75rem; margin-right: 2px;">${currency}</span>
                                <span class="fw-bold text-dark font-monospace">${formatNumber(lineValue)}</span>
                            </td>
    
                            <td class="text-center align-middle">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill" style="font-weight: 500;">
                                    ${item.ARBPL || '-'}
                                </span>
                            </td>
                            
                            <td class="text-center align-middle small">${item.DISPO || '-'}</td>
                            <td class="text-center align-middle small text-muted">${formatDate(item.BUDAT_MKPF)}</td>
                        </tr>`;
                }
                modalTableBody.insertAdjacentHTML('beforeend', contentHtml);
            });
        });
    }

    // Setup Header Click Sorting
    if (modalElement) {
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
        eventClick: function (info) {
            // [Safety Check] Jika modal tidak ada, jangan lakukan apa-apa
            if (!detailModal) return;

            const eventDetails = info.event.extendedProps.details;
            if (!eventDetails || !eventDetails.length) return;

            // --- AGREGASI DATA ---
            const groupedData = {};
            eventDetails.forEach(item => {
                const key = item.AUFNR;
                const currentMENGE = parseFloat(item.MENGE) || 0;
                
                if (!groupedData[key]) {
                    groupedData[key] = { ...item, MENGE: currentMENGE };
                } else {
                    groupedData[key].MENGE += currentMENGE;
                }
            });

            currentModalData = Object.values(groupedData);
            sortState = { key: 'BUDAT_MKPF', order: 'desc' }; // Reset sort

            // [NEW] Simpan tanggal event yang diklik untuk fungsi print
            currentEventDateStr = info.event.startStr; // format YYYY-MM-DD

            const eventDate = info.event.start;
            if (modalTitle) {
                modalTitle.textContent = 'Detail GR: ' + eventDate.toLocaleDateString('id-ID', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                });
            }

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
    
    // Event Listener Sorting
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sortKey;
            const dataKey = (key === 'MENGE') ? 'MENGE' : key;

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

    updateSortIndicator();
}

// =================================================================
// 6. LOGIKA FILTER & PRINT COUNTER (SWEETALERT INTEGRATED)
// =================================================================
let currentFilteredData = [];

function initializeGrFilterLogic() {
    if (!document.getElementById('filter-date')) return;
    
    // [FIX] Gunakan variabel flatpickr yang diimport + Locale Indonesia
    flatpickr("#filter-date", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: Indonesian, 
        placeholder: "Pilih Rentang Tanggal",
        onChange: function(selectedDates, dateStr, instance) {
            updatePrintState(); 
        }
    });

    // 2. Event Listener - [Hapus print-type]
    const inputs = ['filter-mrp', 'filter-wc'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updatePrintState);
    });
    
    // 3. Init State Awal
    setTimeout(() => {
        updatePrintState();
    }, 100);

    // Expose update function for reset
    window.triggerFilterUpdate = updatePrintState;
}

// Fungsi Reset
window.resetFilters = function() {
    const datePicker = document.getElementById('filter-date')._flatpickr;
    if (datePicker) datePicker.clear();
    
    const selects = ['filter-mrp', 'filter-wc'];
    selects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });

    // [REMOVED] printType logic
    
    if (typeof updatePrintState === 'function') {
        updatePrintState(); 
    } else {
        // Fallback reset
        document.getElementById('print-count-text').textContent = "0";
        const badge = document.getElementById('print-count-badge');
        if (badge) badge.style.display = 'none';
        const btn = document.getElementById('btn-print');
        if (btn) {
            btn.setAttribute('disabled', 'true');
            btn.title = 'Lengkapi filter';
        }
        if (typeof currentFilteredData !== 'undefined') currentFilteredData = [];
    }

    if (window.triggerFilterUpdate) window.triggerFilterUpdate();
};

// Fungsi Utama Logic Filter
function updatePrintState() {
    if (typeof window.allGrData === 'undefined') return;

    const datePicker = document.getElementById('filter-date')._flatpickr;
    const dateRange = datePicker ? datePicker.selectedDates : [];
    
    const mrpVal = document.getElementById('filter-mrp').value;
    const wcVal = document.getElementById('filter-wc').value;
    
    const btnPrint = document.getElementById('btn-print');
    const badgeCount = document.getElementById('print-count-badge');
    const textCount = document.getElementById('print-count-text');

    // [VALIDASI BARU] Wajib ada minimal 1 tanggal
    let isValid = dateRange.length > 0; // Updated from === 2 to > 0

    if (isValid) {
        currentFilteredData = getFilteredDataLogic(dateRange, mrpVal, wcVal);
        const count = currentFilteredData.length;
        
        if (textCount) textCount.textContent = new Intl.NumberFormat('id-ID').format(count);
        if (badgeCount) badgeCount.textContent = count;
        
        if (count > 0) {
             if (badgeCount) badgeCount.style.display = 'block';
             btnPrint.removeAttribute('disabled');
             btnPrint.title = `Cetak ${count} data terpilih`;
        } else {
             if (badgeCount) badgeCount.style.display = 'none';
             btnPrint.setAttribute('disabled', 'true');
             btnPrint.title = 'Tidak ada data dengan filter ini';
        }
    } else {
        // Jika tidak valid (Tanggal Kosong), maka anggap data = 0
        currentFilteredData = [];
        if (textCount) textCount.textContent = "0";
        if (badgeCount) badgeCount.style.display = 'none';
        btnPrint.setAttribute('disabled', 'true');
        btnPrint.title = 'Wajib memilih Range Tanggal';
    }
}

function getFilteredDataLogic(dateRange, mrpVal, wcVal) {
    let filtered = window.allGrData || [];
    
    // [FIX] Gunakan Perbandingan STRING 'YYYY-MM-DD' untuk menghindari Timezone Shift
    if (dateRange && dateRange.length > 0) { // Changed condition
        // Helper: Convert Date Object to YYYY-MM-DD string using local time
        const toYmd = (date) => {
            const offset = date.getTimezoneOffset();
            const local = new Date(date.getTime() - (offset * 60 * 1000));
            return local.toISOString().split('T')[0];
        };

        const startStr = toYmd(dateRange[0]);
        // [FIX] Jika hanya ada 1 tanggal (length=1), maka endStr = startStr (Single Day)
        const endStr = dateRange.length === 2 ? toYmd(dateRange[1]) : startStr;
        
        filtered = filtered.filter(item => {
            // item.BUDAT_MKPF formatnya "YYYY-MM-DD" dari backend
            return item.BUDAT_MKPF >= startStr && item.BUDAT_MKPF <= endStr;
        });
    }
    
    if (mrpVal) filtered = filtered.filter(item => item.DISPO === mrpVal);
    if (wcVal) filtered = filtered.filter(item => item.ARBPL === wcVal);
    
    return filtered;
}

// Fungsi Submit Form - [UPDATED] parameter `dateOverride` untuk mode harian
function submitPrintForm(data, type, dateOverride = null) {
    const selectedIds = data.map(item => item.AUFNR);
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.routeGrPrint || "/gr/print-pdf"; 
    form.target = '_blank';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }
    
    // [UPDATED] Kirim parameter Filter Type (meski dropdown hilang, backend mungkin butuh)
    if(type) {
        const filterTypeInput = document.createElement('input');
        filterTypeInput.type = 'hidden';
        filterTypeInput.name = 'filter_type';
        filterTypeInput.value = type;
        form.appendChild(filterTypeInput);
    }
    
    // Helper to format date safely
    const toYmd = (date) => {
        const offset = date.getTimezoneOffset();
        const local = new Date(date.getTime() - (offset * 60 * 1000));
        return local.toISOString().split('T')[0];
    };

    // [UPDATED] Prioritas Tanggal: Override (Modal) > Filter (Range)
    if (dateOverride) {
         // Jika override, set start & end ke tanggal yang sama
         const startInput = document.createElement('input');
         startInput.type = 'hidden';
         startInput.name = 'date_start';
         startInput.value = dateOverride;
         form.appendChild(startInput);

         const endInput = document.createElement('input');
         endInput.type = 'hidden';
         endInput.name = 'date_end';
         endInput.value = dateOverride;
         form.appendChild(endInput);
    } else {
        // Ambil dari filter utama
        const datePicker = document.getElementById('filter-date')._flatpickr;
        if (datePicker && datePicker.selectedDates.length > 0) { // Changed to > 0
             const startInput = document.createElement('input');
             startInput.type = 'hidden';
             startInput.name = 'date_start';
             // Use toYmd helper here too for consistency
             startInput.value = toYmd(datePicker.selectedDates[0]);
             form.appendChild(startInput);

             const endInput = document.createElement('input');
             endInput.type = 'hidden';
             endInput.name = 'date_end';
             // If length is 1, end date is same as start
             const endDate = datePicker.selectedDates.length === 2 ? datePicker.selectedDates[1] : datePicker.selectedDates[0];
             endInput.value = toYmd(endDate);
             form.appendChild(endInput);
        }
    }
    
    const mrpVal = document.getElementById('filter-mrp').value;
    if(mrpVal) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'mrp';
        input.value = mrpVal;
        form.appendChild(input);
    }

    const wcVal = document.getElementById('filter-wc').value;
    if(wcVal) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'wc';
        input.value = wcVal;
        form.appendChild(input);
    }
    
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// [PENTING] Expose handlePrint dengan SweetAlert Logic
window.handlePrint = function() {
    if (currentFilteredData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Filter Belum Lengkap',
            text: 'Silakan pilih Range Tanggal terlebih dahulu.',
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Mengerti'
        });
        return;
    }
    
    // Ambil info filter untuk konfirmasi
    const datePicker = document.getElementById('filter-date')._flatpickr;
    let dateStr = '-';
    if (datePicker && datePicker.selectedDates.length > 0) {
        const startStr = datePicker.selectedDates[0].toLocaleDateString('id-ID');
        if (datePicker.selectedDates.length === 2) {
            dateStr = `${startStr} - ${datePicker.selectedDates[1].toLocaleDateString('id-ID')}`;
        } else {
            dateStr = startStr; // Single date display
        }
    }
        
    const mrpVal = document.getElementById('filter-mrp').value || 'Semua';
    const wcVal = document.getElementById('filter-wc').value || 'Semua';
    const printType = document.getElementById('print-type') ? document.getElementById('print-type').value : 'range';

    Swal.fire({
        title: 'Cetak Laporan Custom?',
        // [UPDATED] SweetAlert Center Alignment (cleaner HTML)
        html: `
            <div class="text-center fs-6 bg-light p-3 rounded">
                <p class="mb-2 fw-bold text-dark">Detail Filter Laporan:</p>
                <div class="text-secondary small">
                    <div class="mb-1">Total Data: <b class="text-primary">${currentFilteredData.length}</b> baris</div>
                    <div class="mb-1">Periode: <b>${dateStr}</b></div>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-printer-fill me-1"></i> Ya, Cetak',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sedang Memproses...',
                text: 'Mohon tunggu sebentar, PDF sedang digenerate.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                timer: 1500
            }).then(() => {
                submitPrintForm(currentFilteredData, printType);
            });
        }
    });
};

// Global Exports
window.initializeGoodReceiptCalendar = initializeGoodReceiptCalendar;
window.initializeMainDetailTable = initializeMainDetailTable;