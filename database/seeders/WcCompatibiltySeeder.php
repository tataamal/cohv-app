<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WcCompatibility;
class WcCompatibiltySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WcCompatibility::truncate();

        $csvFilePath = public_path('document/List WC Kompatible - Completed.csv');

        // 1. Cek apakah file benar-benar ada
        if (!file_exists($csvFilePath)) {
            $this->command->error("File CSV tidak ditemukan di lokasi: " . $csvFilePath);
            return;
        }

        // 2. Coba buka file
        $csvFile = fopen($csvFilePath, 'r');

        // 3. (INI BAGIAN PENTING) Cek apakah file berhasil dibuka
        if ($csvFile === FALSE) {
            $this->command->error("GAGAL MEMBUKA FILE CSV. Kemungkinan besar karena masalah hak akses (file permissions).");
            return; // Hentikan seeder
        }

        // Lewati baris header pertama
        fgetcsv($csvFile);

        // Looping setiap baris data
        while (($data = fgetcsv($csvFile, 2000, ',')) !== FALSE) {
            // Pastikan baris memiliki data yang cukup untuk menghindari error
            if (isset($data[1]) && isset($data[3])) {
                WcCompatibility::create([
                    'wc_asal'   => $data[1],
                    'wc_tujuan' => $data[2] ?? null, // Gunakan null jika kosong
                    'status'    => $data[3],
                    'plant'     => $data[4] ?? null, // Gunakan null jika kosong
                ]);
            }
        }

        // Tutup file setelah selesai
        fclose($csvFile);

        $this->command->info('Seeding data kompatibilitas WC berhasil!');
    }
}
