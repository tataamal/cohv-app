// Fungsi ini akan menangani logika saat tombol konfirmasi di modal diklik
function handleConfirmChangePV() {
    const confirmBtn = document.getElementById('confirmBulkChangePvBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memproses...`;

    // 1. === PERUBAHAN UTAMA: CARA MENGAMBIL DATA ===
    // Ambil VERID tujuan dari satu-satunya dropdown
    const targetVerid = document.getElementById('targetVeridSelect').value;

    // Ambil daftar PRO dari variabel 'mappedPRO' yang sudah kita simpan
    const proNumbers = Array.from(mappedPRO.keys());

    // 2. === PERUBAHAN UTAMA: STRUKTUR PAYLOAD BARU ===
    const payload = {
        plant: bulkActionPlantCode,
        verid: targetVerid,
        pro_list: proNumbers
    };
    
    // 3. PROSES FETCH (TETAP SAMA)
    fetch('/bulk-change-and-refresh', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json().then(data => ({ok: response.ok, body: data})))
    .then(({ok, body}) => {
        if (!ok) {
            throw new Error(body.message || 'Terjadi kesalahan pada server.');
        }

        bulkChangePvModal.hide();
        
        // Logika SweetAlert Anda sudah sangat bagus, tidak perlu diubah
        let swalTitle = 'Gagal!';
        let swalIcon = 'error';
        let swalMessage = body.message || 'Terjadi kesalahan yang tidak diketahui.';
        const status = body.status || (body.success ? 'sukses' : 'gagal');

        if (status === 'sukses') {
            swalTitle = 'Sukses!';
            swalIcon = 'success';
        } else if (status === 'sukses_parsial') {
            swalTitle = 'Selesai dengan Catatan';
            swalIcon = 'warning';
        }
        
        Swal.fire({
            title: swalTitle,
            text: swalMessage,
            icon: swalIcon,
            didClose: () => {
                // Muat ulang data tabel via AJAX (ini lebih baik dari location.reload)
                if (status === 'sukses' || status === 'sukses_parsial') {
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    } else {
                        // Fallback jika dataTable tidak ada
                        location.reload(); 
                    }
                }
            }
        });
        
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Gagal!', error.message, 'error');
    })
    .finally(() => {
        // 4. === PERBAIKAN: KEMBALIKAN TOMBOL KE KONDISI SEMULA ===
        confirmBtn.disabled = false;
        confirmBtn.innerHTML ='Ya, Lanjutkan Proses';
    });
}

// Event listener untuk memanggil fungsi di atas
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmBulkChangePvBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', handleConfirmChangePV);
    }
});




















// function handleConfirmChangePV() {
//     const confirmBtn = document.getElementById('confirmBulkChangePvBtn');
//     const originalBtnText = confirmBtn.innerHTML;

//     // 1. Ubah tombol menjadi status loading
//     confirmBtn.disabled = true;
//     confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memproses...`;

//     // 2. Kumpulkan semua data yang dipilih dari setiap dropdown di dalam tabel modal
//     const allVeridSelects = document.querySelectorAll('#pairedDataBody .verid-select');
    
//     const finalData = [];
//     allVeridSelects.forEach(selectElement => {
//         // Ambil nomor PRO dari atribut 'data-pro'
//         const proNumber = selectElement.dataset.pro;
//         // Ambil nilai VERID yang terbaru dari dropdown
//         const selectedVerid = selectElement.value;

//         finalData.push({
//             pro: proNumber,
//             verid: selectedVerid
//         });
//     });

//     // 3. Siapkan data lengkap (payload) untuk dikirim ke controller
//     const payload = {
//         plant: bulkActionPlantCode, // Diambil dari variabel global saat checkbox dipilih
//         data: finalData 
//     };
    
//     // 4. Kirim data menggunakan Fetch API ke route Laravel
//     // Pastikan URL '/api/bulk-change-and-refresh' sesuai dengan route Anda
//     fetch('/bulk-change-and-refresh', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//             'X-Requested-With': 'XMLHttpRequest',
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
//         },
//         body: JSON.stringify(payload)
//     })
//     .then(response => {
//         // Cek jika respons dari server adalah error
//         if (!response.ok) {
//             // Ubah respons error menjadi format yang bisa dibaca
//             return response.json().then(err => { throw new Error(err.message); });
//         }
//         return response.json();
//     })
//     .then(result => {
//         // 5. Jika berhasil, tutup modal dan tampilkan pesan sukses
//         console.log('Respons dari Controller:', result);
//         bulkChangePvModal.hide();
//         Swal.fire('Sukses!', result.message, 'success');
//     })
//     .catch(error => {
//         // 6. Jika gagal, tampilkan pesan error
//         console.error('Error:', error);
//         Swal.fire('Gagal!', error.message, 'error');
//     })
//     .finally(() => {
//         confirmBtn.disabled = true;
//         confirmBtn.innerHTML = 'Tunggu sebentar, sedang memuat data baru...';
//         location.reload();
//     });
// }
