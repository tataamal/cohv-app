// script untuk handle single readpp
// function openReadPP(aufnr) {
// Swal.fire({
//     title: 'Konfirmasi Read PP',
//     text: `Anda yakin ingin melakukan Read PP (Re-explode BOM) untuk order ${aufnr}? Proses ini akan memperbarui komponen di production order.`,
//     icon: 'info',
//     showCancelButton: true,
//     confirmButtonColor: '#3085d6',
//     cancelButtonColor: '#d33',
//     confirmButtonText: 'Ya, Lanjutkan!',
//     cancelButtonText: 'Batal'
// }).then((result) => {
//     if (result.isConfirmed) {
//         // Tampilkan loading spinner
//         Swal.fire({
//             title: 'Memproses Read PP...',
//             text: 'Mohon tunggu, sedang menghubungi SAP.',
//             allowOutsideClick: false,
//             didOpen: () => {
//                 Swal.showLoading();
//             }
//         });

//         // Kirim request ke Controller Laravel
//         fetch("{{ route('order.readpp') }}", { // Menggunakan route name baru
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },
//             body: JSON.stringify({ aufnr: aufnr })
//         })
//         .then(response => response.json())
//         .then(data => {
//             if (data.success) {
//                 Swal.fire({
//                     icon: 'success',
//                     title: 'Berhasil!',
//                     text: data.message,
//                 });
//                 // Opsional: Muat ulang data tabel jika perlu untuk melihat perubahan
//                 // location.reload(); 
//             } else {
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Gagal!',
//                     // Menampilkan pesan error yang lebih detail dari SAP jika ada
//                     html: data.message + (data.errors ? `<br><br><strong>Detail:</strong><br><pre style="text-align:center; font-size: 0.8em;">${data.errors.join('<br>')}</pre>` : ''),
//                 });
//             }
//         })
//         .catch(error => {
//             console.error('Error:', error);
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Oops...',
//                 text: 'Terjadi kesalahan saat mengirim permintaan!',
//             });
//         });
//     }
// });
// }


// script untuk handle bulk readpp

const confirmBulkReadPpBtn = document.getElementById('confirmBulkReadPpBtn');

// Tambahkan event listener untuk 'click'
confirmBulkReadPpBtn.addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...`;

    const proNumbers = Array.from(selectedPRO); // Ambil data dari Set 'selectedPRO'
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Kirim data menggunakan Fetch API ke URL baru
    fetch('/bulk-read-pp-process', { // <-- URL baru untuk proses ini
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            pro_list: proNumbers
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => { throw errorData; });
        }
        return response.json();
    })
    .then(data => {
        // Tampilkan notifikasi sukses menggunakan SweetAlert
        Swal.fire({
            title: 'Berhasil!',
            text: data.message, // Pesan dari Controller Laravel
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            location.reload(); // Reload halaman setelah notifikasi ditutup
        });
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Terjadi kesalahan saat memproses data.';
        
        // Tampilkan notifikasi gagal menggunakan SweetAlert
        Swal.fire({
            title: 'Gagal!',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'Tutup'
        }).then(() => {
            location.reload(); // Reload halaman setelah notifikasi ditutup
        });
    })
    .finally(() => {
        // Logika ini akan tetap berjalan, meskipun halaman akan di-reload
        this.disabled = true;
        this.innerHTML = 'Reload Halaman ...';
    });
});