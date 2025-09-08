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

        // Baca baris header untuk mendapatkan nama kolom (dan lewati)
        // Delimiter diubah menjadi ';'
        $header = fgetcsv($file, 0, ';');

        // FIX: Hapus karakter BOM (Byte Order Mark) yang tidak terlihat dari header pertama
        if (isset($header[0])) {
            $header[0] = str_replace("\xEF\xBB\xBF", '', $header[0]);
        }

        // Inisialisasi progress bar di terminal
        $this->command->getOutput()->progressStart(count(file($csvFilePath)) - 1);

        // Memulai transaksi database
        DB::transaction(function () use ($file, $header) {
            // Loop melalui setiap baris data di file CSV
            while (($row = fgetcsv($file, 0, ';')) !== false) {
                // Lewati baris kosong yang mungkin ada di akhir file
                if (empty(array_filter($row))) {
                    continue;
                }

                // Gabungkan header dengan baris saat ini untuk membuat array asosiatif
                $data = array_combine($header, $row);

                // Ambil data dari kolom yang relevan, trim spasi ekstra
                // Nama kolom disesuaikan dengan file mapping.csv
                $id_sap = trim($data['ID SAP']);
                $nama_user = trim($data['NAMA']);
                $kode_val = trim($data['Kode']);
                $mrp_val = trim($data['MRP']);
                
                // Lanjutkan hanya jika data penting ada
                if (!empty($id_sap) && !empty($nama_user) && !empty($kode_val)) {
                    
                    // Langkah 1: Buat atau temukan SapUser
                    $sapUser = SapUser::firstOrCreate(
                        ['sap_id' => $id_sap],
                        ['nama' => $nama_user]
                    );

                    // Langkah 2: Buat atau temukan Kode
                    $kode = Kode::firstOrCreate(
                        ['kode' => $kode_val],
                        [
                            'sap_user_id' => $sapUser->id,
                            'nama_bagian' => $data['Nama Bagian'],
                            'kategori' => $data['Kategori']
                        ]
                    );

                    MRP::create(
                        [
                            'mrp' => $mrp_val,
                            'kode_id' => $kode->id
                        ]
                    );
                }
                
                // Majukan progress bar
                $this->command->getOutput()->progressAdvance();
            }
        });

        // Tutup file
        fclose($file);

        // Selesaikan progress bar
        $this->command->getOutput()->progressFinish();
        $this->command->info('Seeding SapUser dan Kode dari file CSV berhasil diselesaikan!');
    }
}
