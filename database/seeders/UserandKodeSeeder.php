<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SapUser;
use App\Models\Kode;
use App\Models\MRP;

class UserandKodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFilePath = public_path('document/mapping.csv');

        // Cek apakah file ada
        if (!file_exists($csvFilePath)) {
            $this->command->error("File CSV tidak ditemukan di: " . $csvFilePath);
            return;
        }

        // Buka file CSV untuk dibaca
        $file = fopen($csvFilePath, 'r');

        // Baca baris header
        $header_raw = fgetcsv($file, 0, ',');

        // Bersihkan SETIAP nama kolom di header dari BOM dan spasi ekstra
        $header = array_map(function($h) {
            // Trim spasi dan hapus karakter non-cetak/BOM
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
        }, $header_raw);

        // Inisialisasi progress bar di terminal
        $totalRows = count(file($csvFilePath)) - 1;
        $this->command->getOutput()->progressStart($totalRows > 0 ? $totalRows : 0);

        // Memulai transaksi database
        DB::transaction(function () use ($file, $header) {
            // Loop melalui setiap baris data di file CSV
            while (($row = fgetcsv($file, 0, ',')) !== false) {
                
                // Gabungkan header yang sudah bersih dengan baris data
                $data = @array_combine($header, $row);

                // Lewati baris kosong
                if ($data === false || empty(array_filter($data))) {
                    continue;
                }

                // Ambil data dari CSV berdasarkan nama kolom yang baru
                // (USER, SAP_USER, KODE LARAVEL, MRP)
                $id_sap = trim($data['SAP_USER'] ?? null);
                $nama_user = trim($data['USER'] ?? null);
                $kode_val = trim($data['KODE LARAVEL'] ?? null);
                $mrp_val = trim($data['MRP'] ?? null);
                
                // Validasi data inti
                if (!empty($id_sap) && !empty($nama_user) && !empty($kode_val)) {
                    
                    // BENAR (Sesuai Poin 2): SapUser unik berdasarkan 'sap_id'
                    // Cari berdasarkan 'sap_id' (dari 'SAP_USER'), jika ada perbarui 'nama', jika tidak ada buat baru.
                    $sapUser = SapUser::updateOrCreate(
                        ['sap_id' => $id_sap],
                        ['nama' => $nama_user]
                    );

                    // BENAR (Sesuai Poin 3 & 4): Selalu buat entri Kode baru untuk setiap baris
                    // dan petakan kolom sesuai permintaan.
                    $kode = Kode::create([
                        'kode' => $kode_val,
                        'sap_user_id' => $sapUser->id,
                        'nama_bagian' => trim($data['SUB DEVISI'] ?? null), // Poin 3: nama_bagian -> SUB DEVISI
                        'kategori' => trim($data['PLANT'] ?? null),      // Poin 3: kategori -> PLANT
                        'sub_kategori' => trim($data['DEVISI'] ?? null), // Poin 3: sub_kategori -> DEVISI
                    ]);
                    
                    // Hanya buat MRP jika nilainya tidak kosong (Sesuai Poin 4)
                    if (!empty($mrp_val)) {
                        // MRP dibuat baru karena berelasi dengan Kode yang baru dibuat.
                        MRP::create([
                            'mrp' => $mrp_val,
                            'kode' => $kode->id
                        ]);
                    }
                }
                
                // Lanjutkan progress bar
                $this->command->getOutput()->progressAdvance();
            }
        });

        // Tutup file
        fclose($file);

        // Selesaikan progress bar
        $this->command->getOutput()->progressFinish();
        $this->command->info('Seeding dari file CSV berhasil diselesaikan!');
    }
}