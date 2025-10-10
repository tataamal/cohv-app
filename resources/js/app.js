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
function initializeGoodReceiptCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const modalElement = document.getElementById('detailModal');
        if (!modalElement) {
            console.error('Modal #detailModal tidak ditemukan.');
            return;
        }
        const detailModal = new bootstrap.Modal(modalElement);
        const modalTitle = document.getElementById('modalTitle');
        const modalTableBody = document.getElementById('modal-table-body');

        // Helper functions
        const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0);
        const formatDate = (dateStr) => {
            if (!dateStr || dateStr === '0000-00-00') return '-';
            // Menambahkan timezone UTC untuk konsistensi
            return new Date(dateStr + 'T00:00:00Z').toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        };

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

            eventContent: function(arg) {
                // Ambil jumlah GR dari extendedProps
                const grCount = arg.event.extendedProps.totalGrCount;
                const formattedGrCount = new Intl.NumberFormat('id-ID').format(grCount || 0);
            
                // Buat elemen HTML untuk kartu event
                let eventCardEl = document.createElement('div');
                eventCardEl.classList.add('fc-event-card-gr');
                eventCardEl.innerHTML = `GR: <span class="fw-bold">${formattedGrCount}</span>`;
            
                return { domNodes: [eventCardEl] };
            },

            eventClick: function (info) {
                const eventDetails = info.event.extendedProps.details;
                if (eventDetails && eventDetails.length > 0) {
                    const eventDate = info.event.start;
                    const formattedDate = eventDate.toLocaleDateString('id-ID', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });

                    modalTitle.textContent = 'Detail Good Receipt untuk: ' + formattedDate;
                    modalTableBody.innerHTML = '';

                    eventDetails.forEach(item => {
                        const row = `<tr>
                            <td class="text-center align-middle small fw-medium">${item.AUFNR || '-'}</td>
                            <td class="align-middle small">${item.MAKTX || '-'}</td>
                            <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDAUF || '-'}</span></td>
                            <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDPOS || '-'}</span></td>
                            <td class="text-center align-middle small">${formatNumber(item.PSMNG)}</td>
                            <td class="text-center align-middle small fw-bold text-success">${formatNumber(item.MENGE)}</td>
                            <td class="text-center align-middle"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">${formatDate(item.BUDAT_MKPF)}</span></td>
                        </tr>`;
                        modalTableBody.insertAdjacentHTML('beforeend', row);
                    });

                    detailModal.show();
                }
            },

            eventDidMount: function (info) {
                info.el.style.cursor = 'pointer';
                const props = info.event.extendedProps;

                // Memastikan props dan dispoBreakdown ada sebelum diakses
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
                    </div>
                `;

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
        });

        calendar.render();

        const sidebarToggle = document.querySelector('#sidebar-collapse-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                setTimeout(() => {
                    calendar.updateSize();
                }, 350);
            });
        }
    }
}

// Panggil fungsi ini setelah halaman selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    initializeGoodReceiptCalendar();
});
// Fungsi Bantuan
function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat('id-ID').format(num);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
}

document.addEventListener('DOMContentLoaded', initializeGoodReceiptCalendar);
window.initializeGoodReceiptCalendar