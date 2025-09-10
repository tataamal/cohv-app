// resources/js/app.js

import './bootstrap';

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap; 
// Impor Chart.js di paling atas agar tersedia untuk semua fungsi
import Chart from 'chart.js/auto';

// =================================================================
// 1. DEFINISIKAN OBJECT & FUNGSI GLOBAL
// =================================================================

// Membuat object global untuk mengontrol loader, dapat diakses dari mana saja.
window.appLoader = {
    overlay: document.getElementById('loading-overlay'),
    show() {
        if (this.overlay) this.overlay.classList.remove('d-none');
    },
    hide() {
        if (this.overlay) this.overlay.classList.add('d-none');
    }
};

// Sembunyikan loader setelah semua aset (gambar, dll.) selesai dimuat.
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
        runTypingEffect('typing-effect', 'typing-cursor', "Bagian mana yang ingin anda kerjakan hari ini?");
        initializeCalendar();
    }

    // Cek apakah kita berada di HALAMAN DASHBOARD
    if (document.querySelector('.stat-value')) {
        console.log('✅ Inisialisasi skrip untuk Halaman Dashboard...');
        document.querySelectorAll('.stat-value').forEach(el => animateCountUp(el));
        initDashboardCharts();
    }

    // PENAMBAHAN: Cek apakah kita berada di LAYOUT APLIKASI UTAMA (yang memiliki sidebar)
    if (document.getElementById('sidebar')) {
        console.log('✅ Inisialisasi skrip untuk App Layout...');
        initAppLayout();
    }
});


// =================================================================
// 3. DEFINISI SEMUA FUNGSI APLIKASI
// =================================================================

// --- FUNGSI UNTUK APP LAYOUT (SIDEBAR & TOPBAR) ---

/**
 * Fungsi utama untuk mengelola state dan event dari sidebar.
 */
function initAppLayout() {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('sidebar-mobile-toggle');
    const collapseToggle = document.getElementById('sidebar-collapse-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    
    // Guard clause sekarang akan menemukan semua elemen dan melanjutkan
    if (!sidebar || !mobileToggle || !collapseToggle || !overlay) return;
    
    const dropdownToggles = sidebar.querySelectorAll('[data-bs-toggle="collapse"]');

    // PERBAIKAN KECIL: Logika ini sekarang menangani saat layar dibesarkan kembali
    const checkScreenWidth = () => {
        if (window.innerWidth < 992) {
            body.classList.add('sidebar-collapsed');
        } else {
            // Hapus kelas jika layar cukup besar
            body.classList.remove('sidebar-collapsed');
        }
    };

    // Event Listeners (ini sudah benar)
    mobileToggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
    collapseToggle.addEventListener('click', () => body.classList.toggle('sidebar-collapsed'));
    overlay.addEventListener('click', () => body.classList.remove('sidebar-open'));
    window.addEventListener('resize', checkScreenWidth);

    // Logika cerdas untuk tombol dropdown (ini sudah benar)
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
            // Jangan proses jika link tidak punya href atau href-nya '#'
            if (!this.getAttribute('href') || this.getAttribute('href') === '#') {
                return;
            }
            
            event.preventDefault(); // Mencegah navigasi langsung
            const destination = this.href;
            
            window.appLoader.show(); // Tampilkan loader
            
            // Beri sedikit waktu agar loader terlihat, lalu pindah halaman
            setTimeout(() => {
                window.location.href = destination;
            }, 150); // Delay 150ms
        });
    });

    checkScreenWidth(); // Jalankan saat pertama kali dimuat
}

// --- FUNGSI UNTUK HALAMAN LANDING ---

/**
 * Fungsi untuk efek mengetik.
 */
function runTypingEffect(elementId, cursorId, text) {
    // ... (kode fungsi ini tidak diubah)
    const element = document.getElementById(elementId);
    const cursor = document.getElementById(cursorId);
    if (!element || !cursor) return;
    let index = 0;
    element.textContent = '';
    cursor.style.display = 'inline-block';
    function type() {
        if (index < text.length) {
            element.textContent += text.charAt(index);
            index++;
            setTimeout(type, 80);
        } else {
            cursor.style.animation = 'none';
            cursor.style.opacity = 0;
        }
    }
    type();
}
    
/**
 * Fungsi untuk kalender sederhana di landing page.
 */
function initializeCalendar() {
    // ... (kode fungsi ini tidak diubah)
    const monthYearEl = document.getElementById('calendar-month-year');
    const calendarGridEl = document.getElementById('calendar-grid');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    if (!calendarGridEl || !prevMonthBtn || !nextMonthBtn || !monthYearEl) return;
    let currentDate = new Date();
    function renderCalendar() {
        calendarGridEl.innerHTML = '';
        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();
        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        monthYearEl.textContent = `${monthNames[month]} ${year}`;
        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        dayNames.forEach(day => {
            const dayEl = document.createElement('div');
            dayEl.className = 'fw-semibold small text-muted text-center pb-2';
            dayEl.textContent = day;
            calendarGridEl.appendChild(dayEl);
        });
        for (let i = 0; i < firstDayOfMonth; i++) {
            calendarGridEl.appendChild(document.createElement('div'));
        }
        const today = new Date();
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'd-flex align-items-center justify-content-center small rounded-circle';
            dayCell.style.height = '36px';
            dayCell.textContent = day;
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                dayCell.classList.add('bg-primary', 'text-white', 'fw-bold');
            } else {
                dayCell.classList.add('text-body');
            }
            calendarGridEl.appendChild(dayCell);
        }
    }
    prevMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); });
    nextMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); });
    renderCalendar();
}


// --- FUNGSI UNTUK HALAMAN DASHBOARD ---

/**
 * Fungsi untuk animasi angka naik (count-up).
 */
function animateCountUp(element) {
    // ... (kode fungsi ini tidak diubah)
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

/**
 * Fungsi untuk merender chart di dashboard.
 */
function initDashboardCharts() {
    // ... (kode fungsi ini tidak diubah)
    const chartCanvases = document.querySelectorAll('.chart-canvas');
    if (chartCanvases.length === 0) return;
    chartCanvases.forEach(canvas => {
        try {
            const ctx = canvas.getContext('2d');
            const type = canvas.dataset.type || 'bar';
            const labels = JSON.parse(canvas.dataset.labels);
            const datasets = JSON.parse(canvas.dataset.datasets);
            new Chart(ctx, {
                type: type,
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: type === 'pie' || type === 'doughnut' ? 'bottom' : 'top' } }
                }
            });
        } catch (error) {
            console.error('Gagal merender chart:', error, canvas);
        }
    });
}
