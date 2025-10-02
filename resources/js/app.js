// ===================================
// IMPORT COMPONENT
// ===================================

import './bootstrap';
import 'bootstrap';
import './sidebar.js';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap; 
import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
// [DIUBAH] Pastikan file ini diimpor agar fungsinya tersedia
import './pages/_dashboard_admin.js';
import './pages/_kelola-pro.js';
import './pages/_monitoring-pro.js'; 

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
        // --- Inisialisasi Modal (tidak berubah) ---
        const modalElement = document.getElementById('detailModal');
        const detailModal = new bootstrap.Modal(modalElement);
        const modalTitle = document.getElementById('modalTitle');
        const modalTableBody = document.getElementById('modal-table-body');

        const isMobile = window.innerWidth < 768;

        const calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, interactionPlugin, listPlugin], // Pastikan listPlugin ada
            initialView: isMobile ? 'listWeek' : 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            events: window.processedCalendarData || [],
            eventContent: function(arg) {
                // ... (Logika eventContent responsif Anda tidak perlu diubah) ...
                let container = document.createElement('div');
                const { totalGrCount, dispoBreakdown } = arg.event.extendedProps;

                if (isMobile) {
                    container.classList.add('d-flex', 'justify-content-between', 'align-items-center', 'w-100', 'p-2');
                    let titleEl = document.createElement('div');
                    titleEl.innerHTML = `<strong>Total Good Receipt:</strong>`;
                    let valueEl = document.createElement('div');
                    valueEl.innerHTML = `<span class="badge bg-success">${new Intl.NumberFormat().format(totalGrCount || 0)}</span>`;
                    container.appendChild(titleEl);
                    container.appendChild(valueEl);
                } else {
                    container.classList.add('fc-event-main-content', 'p-1', 'small');
                    let totalGrHighlight = document.createElement('div');
                    totalGrHighlight.classList.add('badge', 'bg-success', 'text-white', 'text-start', 'w-100', 'py-2', 'mb-1');
                    totalGrHighlight.style.whiteSpace = 'normal';
                    totalGrHighlight.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Total GR Hari ini: <strong>${formatNumber(totalGrCount || 0)}</strong>`;
                    container.appendChild(totalGrHighlight);
                    if (dispoBreakdown && dispoBreakdown.length > 0) {
                        let dispoList = document.createElement('ul');
                        dispoList.classList.add('list-unstyled', 'mb-1', 'small');
                        dispoBreakdown.forEach(item => {
                            let listItem = document.createElement('li');
                            listItem.innerHTML = `<span class="text-black">
                            <i class="bi bi-dot me-1"></i>
                            MRP <strong>${item.dispo || '-'}</strong>: ${formatNumber(item.gr_count || 0)}
                            </span>`;
                            dispoList.appendChild(listItem);
                        });
                        container.appendChild(dispoList);
                    }
                }
                return { domNodes: [container] };
            },

            // ✨ REVISI 1: Tambahkan cursor pointer untuk menandakan bisa di-klik
            eventDidMount: function(info) {
                info.el.style.backgroundColor = 'transparent';
                info.el.style.borderColor = 'transparent';
                info.el.style.cursor = 'pointer'; // Menambahkan ikon tangan saat hover
            },
            
            // ✨ REVISI 2: Hapus dateClick atau kosongkan isinya
            dateClick: function(info) {
                // Dikosongkan agar klik pada area kosong tanggal tidak melakukan apa-apa
            },

            // ✨ REVISI 3: Pindahkan semua logika modal ke eventClick
            eventClick: function(info) {
                // Ambil data detail langsung dari event yang di-klik
                const eventDetails = info.event.extendedProps.details;

                if (eventDetails && eventDetails.length > 0) {
                    const eventDate = info.event.start;
                    const formattedDate = eventDate.toLocaleDateString('id-ID', {
                        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                    });
                    
                    // Isi judul modal
                    modalTitle.textContent = 'Detail Good Receipt untuk: ' + formattedDate;

                    // Kosongkan dan isi tabel modal
                    modalTableBody.innerHTML = ''; 
                    eventDetails.forEach(item => {
                        const row = `
                            <tr>
                                <td class="text-center align-middle small fw-medium">${item.AUFNR || '-'}</td>
                                <td class="align-middle small">${item.MAKTX || '-'}</td>
                                <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDAUF || '-'}</span></td>
                                <td class="text-center align-middle"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${item.KDPOS || '-'}</span></td>
                                <td class="text-center align-middle small">${formatNumber(item.PSMNG)}</td>
                                <td class="text-center align-middle small fw-bold text-success">${formatNumber(item.MENGE)}</td>
                                <td class="text-center align-middle"><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">${formatDate(item.BUDAT_MKPF)}</span></td>
                            </tr>
                        `;
                        modalTableBody.insertAdjacentHTML('beforeend', row);
                    });

                    // Tampilkan modal
                    detailModal.show();
                }
            }
        });

        calendar.render();

        // Fitur resize saat sidebar di-toggle (tidak berubah)
        const sidebarToggle = document.querySelector('#sidebar-collapse-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                setTimeout(() => { calendar.updateSize(); }, 350);
            });
        }
    }
}

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