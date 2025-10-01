// resources/js/pages/_landing.js

document.addEventListener('DOMContentLoaded', function () {
    const typingElement = document.getElementById('typing-effect');
    const cursorElement = document.getElementById('typing-cursor');

    // Pastikan elemen ada sebelum menjalankan skrip
    if (typingElement && cursorElement) {
        const phrases = [
            "Pantau efisiensi produksi secara real-time.",
            "Visualisasikan data dengan dashboard interaktif.",
            "Optimalkan alur kerja manufaktur Anda."
        ];
        
        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;

        function type() {
            const currentPhrase = phrases[phraseIndex];
            
            if (isDeleting) {
                // Proses menghapus
                typingElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                // Proses mengetik
                typingElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }

            // Kondisi untuk mengubah state
            if (!isDeleting && charIndex === currentPhrase.length) {
                // Selesai mengetik, jeda lalu mulai menghapus
                setTimeout(() => { isDeleting = true; }, 2000);
            } else if (isDeleting && charIndex === 0) {
                // Selesai menghapus, ganti ke kalimat berikutnya
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
            }

            // Atur kecepatan mengetik/menghapus
            const typingSpeed = isDeleting ? 50 : 120;
            setTimeout(type, typingSpeed);
        }

        type(); // Mulai efeknya
    }
});