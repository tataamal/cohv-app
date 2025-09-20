const confirmBulkScheduleBtn = document.getElementById('confirmBulkScheduleBtn');
const scheduleDateInput = document.getElementById('bulkScheduleDate');
const scheduleTimeInput = document.getElementById('bulkScheduleTime');

confirmBulkScheduleBtn.addEventListener('click', function() {
    // Client-side validation
    const scheduleDate = scheduleDateInput.value;
    const scheduleTime = scheduleTimeInput.value;

    if (!scheduleDate) {
        Swal.fire('Input Tidak Lengkap', 'Silakan tentukan tanggal tujuan.', 'warning');
        return;
    }
    if (!scheduleTime) {
        Swal.fire('Input Tidak Lengkap', 'Silakan tentukan jam tujuan.', 'warning');
        return;
    }

    // Ubah state tombol menjadi loading
    this.disabled = true;
    this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menjadwalkan ...`;

    // Ambil data PRO dari Set dan plant dari variabel global
    const proNumbers = Array.from(selectedPRO);
    const plant = bulkActionPlantCode;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Kirim semua data yang relevan menggunakan Fetch API
    fetch('/bulk-schedule-process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            pro_list: proNumbers,
            plant: plant,
            schedule_date: scheduleDate,
            schedule_time: scheduleTime
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => { throw errorData; });
        }
        return response.json();
    })
    .then(data => {
        Swal.fire({
            title: 'Berhasil!',
            text: data.message,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            location.reload();
        });
    })
    .catch(error => {
        console.error('Error:', error);
        let errorMessage = error.message || 'Terjadi kesalahan saat memproses data.';
        
        Swal.fire({
            title: 'Gagal!',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'Tutup'
        }).then(() => {
            // Aktifkan kembali tombol jika terjadi error agar bisa diperbaiki
            this.disabled = false;
            this.innerHTML = 'Simpan & Schedule';
        });
    });
});