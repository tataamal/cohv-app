// script untuk single teco
// function openTeco(aufnr) {
// Swal.fire({
//     title: 'Konfirmasi TECO',
//     text: `Anda yakin ingin melakukan TECO untuk order ${aufnr}?`,
//     icon: 'warning',
//     showCancelButton: true,
//     confirmButtonColor: '#3085d6',
//     cancelButtonColor: '#d33',
//     confirmButtonText: 'Ya, Lanjutkan!',
//     cancelButtonText: 'Batal'
// }).then((result) => {
//     if (result.isConfirmed) {
//         // Tampilkan loading spinner
//         Swal.fire({
//             title: 'Memproses TECO...',
//             text: 'Mohon tunggu, sedang menghubungi SAP.',
//             allowOutsideClick: false,
//             didOpen: () => {
//                 Swal.showLoading();
//             }
//         });

//         // Kirim request ke Controller Laravel
//         fetch("{{ route('order.teco') }}", { // Gunakan route name agar lebih aman
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
//                 }).then(() => { // <-- TAMBAHKAN .then() DI SINI
//                     // Periksa apakah backend mengirim sinyal 'refresh'
//                     if (data.action === 'refresh') {
//                         location.reload(); // Muat ulang halaman
//                     }
//                 });;
//             } else {
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Gagal!',
//                     text: data.message,
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


// script untuk bulk teco
const confirmBtn = document.getElementById('confirmBulkTecoBtn');

confirmBtn.addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...`;

    const proNumbers = Array.from(selectedPRO);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('/bulk-teco-process', {
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
            return response.json().then(errorData => {
                throw errorData;
            });
        }
        return response.json();
    })
    .then(data => {
        // --- PENANGANAN JIKA SUKSES ---
        Swal.fire({
            title: 'Berhasil!',
            text: data.message,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload();
            }
        });
    })
    .catch((error) => {
        console.error('Error:', error);
        
        const errorMessage = error.message || 'Terjadi kesalahan saat mengirim data. Silakan coba lagi.';

        Swal.fire({
            title: 'Gagal!',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'Tutup'
        }).then(() => {
            location.reload();
        });
    })
    .finally(() => {
        this.disabled = true;
        this.innerHTML = 'Reload Halaman ...';
    });
});