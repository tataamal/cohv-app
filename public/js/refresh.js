// =================== HANDLE SINGLE REFRESH PRO ===================
let refreshModal;
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('refreshProModal');
    if (modalElement) {
        refreshModal = new bootstrap.Modal(modalElement);
    }
});

function openRefresh(proNumber, werksInfo) {
    // Mengisi data ke dalam elemen span di modal
    document.getElementById('modalProNumber').textContent = proNumber;
    document.getElementById('modalWerksInfo').textContent = werksInfo;

    // Menyimpan nomor PRO di tombol "Lanjutkan" menggunakan atribut data-*
    // Ini cara yang aman untuk passing data ke event listener
    const confirmBtn = document.getElementById('confirmRefreshBtn');
    confirmBtn.dataset.pro = proNumber;
    confirmBtn.dataset.plant = werksInfo;

    // Menampilkan modal
    if (refreshModal) {
        refreshModal.show();
    }
}

function showSwal(message, type = 'success') {
    let config = {
        confirmButtonText: 'OK'
    };

    if (type === 'success') {
        config.icon = 'success';
        config.title = 'Berhasil';
        config.text = message;
    } else { // 'error'
        config.icon = 'error';
        config.title = 'Gagal';
        // Menggunakan 'html' agar bisa menampilkan baris baru jika ada
        config.html = message.replace(/\n/g, '<br>');
    }

    // Memanggil library SweetAlert untuk menampilkan notifikasi
    Swal.fire(config);
}

document.getElementById('confirmRefreshBtn').addEventListener('click', function() {
    // Ambil nomor PRO & Werks dari atribut data-*
    const proToRefresh = this.dataset.pro;
    const plantCode = this.dataset.plant;

    // Tampilkan status loading
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
    
    // Tentukan URL endpoint statis di Controller PHP Anda.
    // Data akan dikirim melalui body, bukan URL.
    const phpEndpoint = '/refresh-pro'; 

    // Kirim request ke endpoint PHP menggunakan Fetch API
    fetch(phpEndpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',

            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            pro_number: proToRefresh,
            plant: plantCode
        })
    })
    .then(response => {
        // Logika untuk memeriksa respons OK atau tidak
        return response.json().then(data => ({ status: response.status, body: data }));
    })
    .then(({ status, body }) => {
        if (status >= 400) {
            // Jika server mengembalikan status error (4xx atau 5xx)
            throw new Error(body.message || 'Terjadi kesalahan di server.');
        }
        showSwal(body.message, 'success'); // Tampilkan notifikasi sukses

        const modalElement = document.getElementById('refreshProModal');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
        setTimeout(() => {
            location.reload();
        }, 1500); // 1500 milidetik = 1.5 detik
    })
    .catch(error => {
        console.error('Error:', error);
        showSwal(error.message, 'error'); // Tampilkan notifikasi error
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = 'Lanjutkan';
    });
});


// =================== HANDLE BULK REFRESH PRO ===================
document.addEventListener('DOMContentLoaded', () => {
    // Ambil tombol konfirmasi dari modal bulk refresh
    const confirmBtn = document.getElementById('confirmBulkRefreshBtn');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            const wrapper = document.getElementById('bulk-actions-wrapper');
            const refreshUrl = wrapper.dataset.refreshUrl;
            const plant = this.dataset.plant;

            // -- BARIS TAMBAHAN UNTUK PENGECEKAN --
            console.log('Mengecek data plant:', plant); 
            // ------------------------------------

            // 1. Ambil daftar PRO yang dipilih dari Set dan ubah menjadi array
            const proList = Array.from(selectedPRO);

            if (proList.length === 0) {
                return showSwal('Tidak ada PRO yang dipilih untuk di-refresh.', 'warning');
            }

            // 2. Ubah tampilan tombol menjadi loading
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Processing...`;

            // 3. Kirim request ke endpoint Laravel
            fetch(refreshUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    pros: proList, // Key 'pros' harus sesuai dengan validasi di Laravel
                    plant: plant
                })
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                // 4. Tangani respons dari Laravel
                if (!ok) {
                    // Jika server mengembalikan error (status 4xx atau 5xx)
                    throw new Error(data.message || 'Terjadi kesalahan yang tidak diketahui.');
                }
                
                // Jika sukses, tutup modal dan tampilkan notifikasi
                bulkRefreshModal.hide();
                showSwal(data.message, 'success');

                // Reload halaman setelah 1.5 detik untuk menampilkan data terbaru
                setTimeout(() => {
                    location.reload();
                }, 1500);

            })
            .catch(error => {
                // Tangani jika ada error jaringan atau error dari server
                showSwal(error.message, 'error');
            })
            .finally(() => {
                // 5. Kembalikan tombol ke keadaan semula
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });
    }
});