{{-- resources/views/admin/partials/components-table.blade.php --}}

@forelse ($componentData as $aufnr => $components)
<div class="alert alert-info small">
    Data Komponen pada PRO : {{ $aufnr }}
</div>
    <h6 class="mt-4 mb-2">PRO: {{ $aufnr }}</h6>
    <div class="table-responsive border rounded mb-4">
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr class="align-middle">
                    {{-- BARU: KOLOM CHECKBOX UNTUK BULK SELECT --}}
                    <th class="text-center" style="width: 3%;">
                        <input type="checkbox" 
                               id="select-all-components-{{ $aufnr }}" 
                               class="form-check-input" 
                               onchange="toggleSelectAllComponents('{{ $aufnr }}')">
                    </th>
                    <th class="text-center" style="width: 4%;">No.</th>
                    <th class="text-center">Number Reservasi</th>
                    <th class="text-center">Item Reservasi</th>
                    <th class="text-center">Material</th>
                    <th class="text-center">Description</th>
                    <th class="text-center">Req. Qty</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Outs. Req</th>
                    <th class="text-center">S.Log</th>
                    <th class="text-center">UOM</th>
                    <th class="text-center">Spec. Procurement</th>
                    <th class="text-center" style="width: 5%;">Action</th> 
                </tr>
            </thead>
            <tbody>
                @foreach ($components as $comp)
                    <tr class="align-middle">
                        {{-- DATA: CHECKBOX BARIS --}}
                        <td class="text-center">
                            <input type="checkbox" 
                                   class="component-select-{{ $aufnr }} form-check-input" 
                                   data-aufnr="{{ $aufnr }}" 
                                   data-rspos="{{ $comp->RSPOS ?? '' }}" 
                                   data-material="{{ $comp->MATNR ? trim($comp->MATNR, '0') : '' }}"
                                   onchange="handleComponentSelect('{{ $aufnr }}')"> 
                        </td>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">{{ $comp->RSNUM ?? '-' }}</td>
                        <td class="text-center">{{ $comp->RSPOS ?? '-' }}</td>
                        <td class="text-center">{{ $comp->MATNR ? trim($comp->MATNR, '0') : '-' }}</td>
                        <td class="text-center">{{ $comp->MAKTX ?? '-' }}</td>
                        <td class="text-center">{{ $comp->BDMNG ?? $comp->MENGE ?? '-' }}</td>
                        <td class="text-center">{{ $comp->KALAB ?? '-' }}</td>
                        <td class="text-center">{{ $comp->OUTSREQ ?? '-' }}</td>
                        <td class="text-center">{{ $comp->LGORT ?? '-' }}</td>
                        <td class="text-center">{{ $comp->MEINS ?? '-' }}</td>
                        <td class="text-center">{{ $comp->LTEXT ?? "No Value" }}</td>
                        
                        {{-- DATA: ACTION PER BARIS (EDIT) --}}
                        <td class="d-flex align-items-center justify-content-center gap-2">
                            {{-- Tombol Edit --}}
                            <button type="button" 
                                    title="Edit Component" 
                                    class="btn btn-warning btn-sm py-1 px-2 edit-component-btn"
                                    data-aufnr="{{ $comp->AUFNR ?? '' }}"
                                    data-rspos="{{ $comp->RSPOS ?? '' }}"
                                    data-matnr="{{ $comp->MATNR ?? '' }}"
                                    data-bdmng="{{ $comp->BDMNG ?? '' }}"
                                    data-lgort="{{ $comp->LGORT ?? '' }}"
                                    data-sobkz="{{ $comp->SOBKZ ?? '' }}"
                                    data-plant="{{ $comp->WERKSX ?? 'default_plant' }}"
                                    onclick="handleEditClick(this)">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="alert alert-warning">Tidak ada data Komponen (TData4) ditemukan untuk PRO terkait.</div>
@endforelse

<script>
    function handleComponentSelect(aufnr) {
        // Memilih semua checkbox yang dicentang di dalam tabel PRO ini
        const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
        
        // Mengambil wadah kontrol bulk action dari DOM
        // Catatan: Pastikan ini adalah ID unik di halaman Anda
        const bulkControls = document.getElementById('bulk-action-controls'); 

        // Jika wadah kontrol ditemukan
        if (!bulkControls) return; 

        // Tentukan apakah ada item yang dipilih
        if (selectedCheckboxes.length > 0) {
            bulkControls.classList.remove('d-none');
            bulkControls.classList.add('d-flex');
            
            // Perbarui teks tombol
            // Perhatian: Ganti selector ini jika Anda menggunakan ID yang berbeda
            document.querySelector('#bulk-action-controls .btn-warning').innerHTML = 
                `<i class="fas fa-edit me-1"></i> Edit Selected (${selectedCheckboxes.length})`;
            document.querySelector('#bulk-action-controls .btn-danger').innerHTML = 
                `<i class="fas fa-trash me-1"></i> Remove Selected (${selectedCheckboxes.length})`;
                
        } else {
            bulkControls.classList.remove('d-flex');
            bulkControls.classList.add('d-none');
        }
    }

    // Fungsi untuk memilih/menghapus semua
    function toggleSelectAllComponents(aufnr) {
        const selectAllCheckbox = document.getElementById(`select-all-components-${aufnr}`);
        const checkboxes = document.querySelectorAll(`.component-select-${aufnr}`);
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        
        handleComponentSelect(aufnr);
    }

    // Logika untuk tombol Edit per baris
    function handleEditClick(buttonElement) {
        // Ambil data-attribute dan buka modal edit
        const aufnr = buttonElement.dataset.aufnr;
        const rspos = buttonElement.dataset.rspos;
        // ... logika untuk mengisi dan membuka modal edit
        console.log(`Mengedit Komponen PRO: ${aufnr}, Item: ${rspos}`);
    }

    function handleEditClick(buttonElement) {
        // 'buttonElement' adalah tombol yang baru saja Anda klik

        // Ambil data dari atribut data-* menggunakan 'dataset'
        const aufnr = buttonElement.dataset.aufnr;
        const rspos = buttonElement.dataset.rspos;
        const matnr = buttonElement.dataset.matnr;
        const bdmng = buttonElement.dataset.bdmng;
        const lgort = buttonElement.dataset.lgort;
        const sobkz = buttonElement.dataset.sobkz;
        const plant = buttonElement.dataset.plant;

        // Sekarang Anda bisa menggunakan semua variabel ini
        console.log('✅ Tombol Edit Diklik via onclick!');
        console.log('AUFNR:', aufnr);
        console.log('RSPOS:', rspos);
        console.log('MATNR:', matnr);
        console.log('PLANT:', plant);

        // ✨ CONTOH: Langsung panggil logika untuk menampilkan modal di sini
        // (Ini adalah kode dari pembahasan modal kita sebelumnya)
        
        // Isi nilai-nilai ke dalam field form di modal
        document.getElementById('formPro').value = aufnr;
        document.getElementById('formRspos').value = rspos;
        document.getElementById('formMatnr').value = matnr;
        document.getElementById('formBdmng').value = bdmng;
        document.getElementById('formLgort').value = lgort;
        document.getElementById('formSobkz').value = sobkz;
        document.getElementById('formPlant').value = plant;

        // Tampilkan modal
        const dataModal = new bootstrap.Modal(document.getElementById('dataEntryModal'));
        dataModal.show();
    }

    async function handleUpdateComponent(event) {
        event.preventDefault(); // Mencegah form submit secara normal

        const form = event.target;
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        // Tampilkan notifikasi loading
        Swal.fire({
            title: 'Menyimpan Perubahan...',
            text: 'Mohon tunggu, sedang menghubungi SAP.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch(form.action, { // 'form.action' mengambil URL dari atribut action form
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok) {
                // Cek apakah ada peringatan sinkronisasi dari backend
                if (result.sync_warning) {
                    await Swal.fire({
                        title: 'Sebagian Berhasil',
                        icon: 'warning',
                        html: `<b>Transaksi SAP Berhasil.</b><br><br>Namun, terjadi masalah saat sinkronisasi data lokal:<br><i>${result.sync_warning}</i><br><br>Data di halaman ini mungkin belum update.`
                    });
                } else {
                    await Swal.fire({
                        title: 'Berhasil!',
                        text: result.message || 'Komponen berhasil diubah.',
                        icon: 'success'
                    });
                }
                location.reload(); // Muat ulang halaman untuk melihat data terbaru
            } else {
                // Handle error dari server (4xx, 5xx)
                Swal.fire({
                    title: 'Gagal!',
                    text: result.message || 'Terjadi kesalahan saat memproses permintaan.',
                    icon: 'error'
                });
            }

        } catch (error) {
            console.error('Update Component Error:', error);
            Swal.fire('Error Jaringan', 'Tidak dapat terhubung ke server. Silakan coba lagi.', 'error');
        }
    }

    // Pastikan Anda menambahkan event listener ke form modal edit Anda
    document.addEventListener('DOMContentLoaded', () => {
        const editForm = document.getElementById('entryForm'); // Ganti dengan ID form Anda
        if (editForm) {
            editForm.addEventListener('submit', handleUpdateComponent);
        }
    });
</script>