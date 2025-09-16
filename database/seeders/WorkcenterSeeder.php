<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\workcenter;

class WorkcenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $surabayaFile = database_path('seeders/data/List WC-Surabaya.csv');
        $semarangFile = database_path('seeders/data/List WC-Semarang.csv');

        $surabayaMapping = ['kode_wc' => 'ARBPL', 'description' => 'Deskripsi', 'werksx' => 'WERKSX', 'werks' => 'WERKS'];
        $semarangMapping = ['kode_wc' => 'Kode Workcenter', 'description' => 'Deskripsi', 'werksx' => 'WERKSX', 'werks' => 'WERKS'];

        $this->seedFromFile($surabayaFile, $surabayaMapping);
        $this->seedFromFile($semarangFile, $semarangMapping);
    }

    /**
     * Membaca CSV, memperbaiki encoding, dan memasukkan data ke database.
     */
    protected function seedFromFile(string $filePath, array $mapping): void
    {
        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan: " . $filePath);
            return;
        }

        $this->command->info("Memulai proses seeding dari: " . basename($filePath));

        $fileHandle = fopen($filePath, 'r');
        if ($fileHandle === false) {
            $this->command->error("Gagal membuka file: " . $filePath);
            return;
        }

        // Baca header secara terpisah
        $header = fgetcsv($fileHandle);
        $rowCount = 0;

        // Loop melalui sisa file baris per baris
        while (($line = fgets($fileHandle)) !== false) {
            // **LANGKAH KUNCI: Konversi encoding dari ISO-8859-1 (ANSI) ke UTF-8**
            $utf8Line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');

            // Parse baris yang sudah dikonversi menjadi array
            $row = str_getcsv($utf8Line);

            // Lewati baris yang kosong atau tidak sesuai format
            if (count($row) !== count($header) || !isset($row[0])) {
                continue;
            }
            
            $record = array_combine($header, $row);

            // Lewati baris jika kode_wc kosong
            if (empty(trim($record[$mapping['kode_wc']]))) {
                continue;
            }

            // Gunakan updateOrCreate untuk efisiensi
            workcenter::updateOrCreate(
                ['kode_wc' => trim($record[$mapping['kode_wc']])],
                [
                    'werks'       => trim($record[$mapping['werks']]),
                    'werksx'      => trim($record[$mapping['werksx']]),
                    'description' => trim($record[$mapping['description']]),
                ]
            );
            $rowCount++;
        }

        fclose($fileHandle);
        $this->command->info("Selesai. Sebanyak $rowCount baris data berhasil diproses.");
    }
}
