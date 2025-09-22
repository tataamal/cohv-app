function handleConfirmChangePV() {
    const confirmBtn = document.getElementById('confirmBulkChangePvBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memproses...`;

    const allVeridSelects = document.querySelectorAll('#pairedDataBody .verid-select');
    
    const finalData = [];
    allVeridSelects.forEach(selectElement => {
        // Ambil nomor PRO dari atribut 'data-pro'
        const proNumber = selectElement.dataset.pro;
        // Ambil nilai VERID yang terbaru dari dropdown
        const selectedVerid = selectElement.value;

        finalData.push({
            pro: proNumber,
            verid: selectedVerid
        });
    });
    const payload = {
        plant: bulkActionPlantCode,
        data: finalData 
    };
    
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
        // Jika server mengembalikan error HTTP (misal: 500, 404)
        if (!ok) {
            throw new Error(body.message || 'Terjadi kesalahan pada server.');
        }

        console.log('Proses selesai, respons dari Laravel:', body);
        bulkChangePvModal.hide();
        
        let swalTitle = 'Gagal!';
        let swalIcon = 'error';
        let swalMessage = body.message || 'Terjadi kesalahan yang tidak diketahui.';

        // Cek status logis yang dikirim oleh controller
        // Asumsi controller mengembalikan 'status' dari Flask atau 'success' dari dirinya sendiri
        const status = body.status || (body.success ? 'sukses' : 'gagal');

        if (status === 'sukses') {
            swalTitle = 'Sukses!';
            swalIcon = 'success';
        } else if (status === 'sukses_parsial') {
            swalTitle = 'Selesai dengan Catatan';
            swalIcon = 'warning';
        }
        
        // Tampilkan notifikasi yang dinamis
        Swal.fire({
            title: swalTitle,
            text: swalMessage,
            icon: swalIcon,
            didClose: () => {
                // Muat ulang data di tabel utama hanya jika ada perubahan
                if (status === 'sukses' || status === 'sukses_parsial') {
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
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
        confirmBtn.disabled = true;
        confirmBtn.innerHTML ='Tunggu sebentar, sedang memuat data baru...';location.reload();
        location.reload();
    });
}
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
