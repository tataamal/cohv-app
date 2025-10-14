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
        // Path ke public/document/mapping.csv
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
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
        }, $header_raw);

        // Inisialisasi progress bar di terminal
        $totalRows = count(file($csvFilePath)) - 1;
        $this->command->getOutput()->progressStart($totalRows > 0 ? $totalRows : 0);

        // Memulai transaksi database
        DB::transaction(function () use ($file, $header) {
            // Loop melalui setiap baris data di file CSV
            while (($row = fgetcsv($file, 0, ',')) !== false) {
                
                $data = @array_combine($header, $row);

                if ($data === false || empty(array_filter($data))) {
                    continue;
                }

                $id_sap = trim($data['id_sap'] ?? null);
                $nama_user = trim($data['NAMA'] ?? null);
                $kode_val = trim($data['Kode'] ?? null);
                $mrp_val = trim($data['MRP'] ?? null);
                
                if (!empty($id_sap) && !empty($nama_user) && !empty($kode_val)) {
                    
                    // BENAR: SapUser harus UNIK. Gunakan updateOrCreate.
                    // Cari berdasarkan 'sap_id', jika ada perbarui 'nama', jika tidak ada buat baru.
                    $sapUser = SapUser::updateOrCreate(
                        ['sap_id' => $id_sap],
                        ['nama' => $nama_user]
                    );

                    // BENAR: Kode TIDAK unik. SELALU buat entri baru untuk setiap baris CSV.
                    $kode = Kode::create([
                        'kode' => $kode_val,
                        'sap_user_id' => $sapUser->id,
                        'nama_bagian' => trim($data['Nama Bagian'] ?? null),
                        'kategori' => trim($data['Kategori'] ?? null),
                        'sub_kategori' => trim($data['Sub Kategori'] ?? null),
                    ]);
                    
                    // Hanya buat MRP jika nilainya tidak kosong
                    if (!empty($mrp_val)) {
                        // MRP juga dibuat baru karena berelasi dengan Kode yang baru dibuat.
                        MRP::create([
                            'mrp' => $mrp_val,
                            'kode_id' => $kode->id
                        ]);
                    }
                }
                
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
