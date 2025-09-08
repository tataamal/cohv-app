import './bootstrap';

// =================================================================
// 1. IMPOR SEMUA LIBRARY YANG DIBUTUHKAN
// =================================================================
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';

// Impor FullCalendar dan plugin-pluginnya
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

// =================================================================
// 2. PERSIAPKAN ALPINE
// =================================================================
Alpine.plugin(collapse);
window.Alpine = Alpine; // Dibuat global agar bisa diakses dari Blade

// =================================================================
// 3. TUNGGU HTML SIAP, LALU INISIALISASI SEMUANYA
// =================================================================
document.addEventListener('DOMContentLoaded', () => {

    // Jalankan Alpine
    Alpine.start();
    console.log('✅ Alpine.js started.');

    // Inisialisasi semua Chart
    initCharts();
    
    // ✅ PANGGIL FUNGSI: Panggil fungsi untuk membuat kalender
    initFullCalendar();
});

// =================================================================
// 4. DEFINISIKAN FUNGSI-FUNGSI INISIALISASI
// =================================================================

/**
 * Fungsi untuk mencari semua elemen canvas chart dan merendernya.
 */
function initCharts() {
    const chartCanvases = document.querySelectorAll('.chart-canvas');
    chartCanvases.forEach(canvas => {
        try {
            const ctx = canvas.getContext('2d');
            const type = canvas.dataset.type;
            const labels = JSON.parse(canvas.dataset.labels);
            const datasets = JSON.parse(canvas.dataset.datasets);

            new Chart(ctx, {
                type: type,
                data: { labels, datasets },
            
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'top' } }
                }
            });
        } catch (error) {
            console.error('Chart rendering error:', error, canvas);
        }
    });
    console.log(`✅ Chart initializer ran for ${chartCanvases.length} canvases.`);
}

/**
 * Fungsi untuk mencari elemen kalender dan merendernya.
 */
function initFullCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return; // Hanya jalankan jika elemen #calendar ada

    const processedData = window.processedCalendarData || {};
    const modal = document.getElementById('detail-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const modalTitle = document.getElementById('modal-title');
    const modalTableBody = document.getElementById('modal-table-body');

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, listPlugin, interactionPlugin],
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        timeZone: 'local',
        locale: 'id',
        height: 'auto',
        firstDay: 1, // Senin
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        windowResize: function(view) {
            if (window.innerWidth < 768) {
                calendar.changeView('listWeek');
            } else {
                calendar.changeView('dayGridMonth');
            }
        },
        dayCellDidMount: function(info) {
            // Fix bug tanggal dengan menggunakan local date
            const localDate = new Date(info.date.getTime() - info.date.getTimezoneOffset() * 60000);
            const dateStr = localDate.toISOString().slice(0, 10);
            
            if (processedData[dateStr] && info.view.type === 'dayGridMonth') {
                const dayData = processedData[dateStr];
                const summaryEl = document.createElement('div');
                summaryEl.className = 'calendar-summary text-xs cursor-pointer';
                
                let breakdownHtml = '';
                for (const mrp in dayData.mrp_breakdown) {
                    breakdownHtml += `<div class="flex items-center justify-between mb-1">
                        <span class="text-gray-700 text-xs">${mrp}:</span> 
                        <span class="text-green-600 text-xs">${dayData.mrp_breakdown[mrp]}</span>
                    </div>`;
                }
                
                summaryEl.innerHTML = `
                    <div class="bg-green-500 text-white rounded summary-total text-center shadow-sm">
                        <div class="text-xs">Total: ${dayData.total_gr}</div>
                    </div>
                    <div class="hidden sm:block space-y-1 bg-white summary-breakdown rounded border border-gray-200 shadow-sm">
                        ${breakdownHtml}
                    </div>
                `;
                info.el.querySelector('.fc-daygrid-day-frame').appendChild(summaryEl);
            }
        },
        dateClick: function(info) {
            // Fix bug tanggal dengan menggunakan local date
            const clickedDate = new Date(info.date.getTime() - info.date.getTimezoneOffset() * 60000);
            const dateStr = clickedDate.toISOString().slice(0, 10);
            
            if (processedData[dateStr]) {
                const dayData = processedData[dateStr];
                modalTitle.innerText = 'Detail untuk ' + clickedDate.toLocaleDateString('id-ID', { 
                    weekday: 'long',
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric' 
                });
                modalTableBody.innerHTML = '';
                
                let tableRowsHtml = '';
                dayData.records.forEach((record, index) => {
                const rowClass = index % 2 == 0 ? 'bg-white' : 'bg-gray-50/50';
                const formattedDate = new Date(record.BUDAT_MKPF).toLocaleDateString('id-ID', {
                    day: 'numeric', month: 'short', year: 'numeric'
                });

                tableRowsHtml += `
                    <tr class="hover:bg-gray-100 transition-colors duration-200 ${rowClass}">
                        <!-- Kolom 1: PRO -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-1.5 h-1.5 bg-purple-400 rounded-full"></div>
                                <span class="text-xs font-medium text-gray-900">${record.AUFNR || '-'}</span>
                            </div>
                        </td>
                        <!-- Kolom 2: Material Description -->
                        <td class="px-4 py-3 text-center">
                            <div class="text-xs text-gray-900">${record.MAKTX || '-'}</div>
                        </td>
                        <!-- Kolom 3: Sales Order -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                ${record.KDAUF || '-'}
                            </span>
                        </td>
                        <!-- Kolom 4: SO Item -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                ${record.KDPOS || '-'}
                            </span>
                        </td>
                        <!-- Kolom 5: Quantity PRO (PSMNG) -->
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">${Number(record.PSMNG || 0).toLocaleString('id-ID')}</div>
                        </td>
                        <!-- Kolom 6: Quantity GR (MENGE) -->
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="text-xs font-medium text-green-600">${Number(record.MENGE || 0).toLocaleString('id-ID')}</div>
                        </td>
                        <!-- Kolom 7: Tgl. Posting -->
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                ${formattedDate}
                            </span>
                        </td>
                    </tr>
                `;
                });
                modalTableBody.innerHTML = tableRowsHtml;
                modal.classList.remove('hidden');
            }
        }
    });
    calendar.render();
    console.log('✅ FullCalendar rendered.');

    function closeModal() {
        modal.classList.add('hidden');
    }
    
    closeModalBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
}

/**
 * Fungsi untuk efek mengetik pada loader.
 */
function startLoaderTypingEffect() {
    // ... (kode fungsi loader Anda) ...
}
window.startLoaderTypingEffect = startLoaderTypingEffect;