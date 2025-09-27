<?php

namespace Database\Seeders;

use App\Models\workcenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class WcRelationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kosongkan tabel relasi dengan aman
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('wc_relations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Buat peta lookup (kode_wc => id) dari tabel workcenters
        $this->command->info('Memuat data workcenter ke memori...');
        $wcLookup = workcenter::pluck('id', 'kode_wc');
        $this->command->info(count($wcLookup) . ' workcenter berhasil dimuat.');

        // 3. Path ke file CSV
        $filesToProcess = [
            database_path('seeders/Data/List_WC_Kompatible_Surabaya_cleaned.csv'),
            database_path('seeders/Data/List_WC_Kompatible_Semarang_cleaned.csv'),
        ];
        
        $allRelations = [];

        // 4. Proses setiap file menggunakan satu fungsi yang sama
        foreach ($filesToProcess as $filePath) {
            $relationsFromFile = $this->processFile($filePath, $wcLookup);
            $allRelations = array_merge($allRelations, $relationsFromFile);
        }

        // 5. Masukkan semua data relasi yang terkumpul
        if (empty($allRelations)) {
            $this->command->error('GAGAL: Tidak ada relasi valid yang ditemukan dari semua file CSV.');
            $this->command->warn('Pastikan kolom "WC Asal" di file CSV cocok dengan "kode_wc" di tabel workcenters.');
            return;
        }

        $this->command->info("Memasukkan total " . count($allRelations) . " relasi ke database...");
        
        // Memasukkan data dalam batch untuk efisiensi
        foreach (array_chunk($allRelations, 500) as $chunk) {
            DB::table('wc_relations')->insert($chunk);
        }

        $this->command->info("Proses seeding relasi selesai dengan sukses! ✅");
    }

    /**
     * Membaca satu file CSV dan mengubahnya menjadi array relasi.
     */
    protected function processFile(string $filePath, $wcLookup): array
    {
        if (!file_exists($filePath)) {
            $this->command->warn("Peringatan: File tidak ditemukan di: " . $filePath);
            return [];
        }

        $this->command->info("Memproses file: " . basename($filePath));
        $fileHandle = fopen($filePath, 'r');
        
        $header = fgetcsv($fileHandle, 0, ',');
        
        $relations = [];
        $rowCount = 0;
        
        // *** PERUBAHAN: Inisialisasi array untuk menyimpan WC yang dilewati ***
        $skippedAsalWcs = [];
        $skippedTujuanWcs = [];

        while (($row = fgetcsv($fileHandle, 0, ',')) !== false) {
            if (empty(array_filter($row))) continue;
            
            $rowCount++;
            $record = @array_combine($header, $row);

            if ($record === false) continue;

            $wcAsalKode = trim($record['WC Asal'] ?? '');
            $wcAsalId = $wcLookup->get($wcAsalKode);

            if (!$wcAsalId) {
                // *** PERUBAHAN: Catat WC Asal yang dilewati ***
                if (!empty($wcAsalKode)) $skippedAsalWcs[] = $wcAsalKode;
                continue;
            }

            $add = function(string $columnName, string $status) use ($record, $wcLookup, $wcAsalId, &$relations, &$skippedTujuanWcs) {
                $wcTujuanKode = trim($record[$columnName] ?? '');
                if (!empty($wcTujuanKode)) {
                    $wcTujuanId = $wcLookup->get($wcTujuanKode);
                    if ($wcTujuanId) {
                        $relations[] = [
                            'wc_asal_id'   => $wcAsalId,
                            'wc_tujuan_id' => $wcTujuanId,
                            'status'       => $status,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];
                    } else {
                        // *** PERUBAHAN: Catat WC Tujuan yang dilewati ***
                        $skippedTujuanWcs[] = $wcTujuanKode;
                    }
                }
            };
            
            $add('WC Kompatibel', 'compatible');
            $add('WC Kompatibel With Condition', 'compatible with condition');
        }

        fclose($fileHandle);
        $this->command->info("Selesai memproses $rowCount baris dari " . basename($filePath) . ". Menghasilkan " . count($relations) . " relasi.");
        
        // *** PERUBAHAN: Tampilkan daftar unik WC yang dilewati ***
        if (!empty($skippedAsalWcs)) {
            $uniqueSkipped = array_unique($skippedAsalWcs);
            $this->command->warn(count($uniqueSkipped) . " 'WC Asal' unik dilewati karena tidak ditemukan di DB: " . implode(', ', $uniqueSkipped));
        }
        if (!empty($skippedTujuanWcs)) {
            $uniqueSkipped = array_unique($skippedTujuanWcs);
            $this->command->warn(count($uniqueSkipped) . " 'WC Tujuan' unik dilewati karena tidak ditemukan di DB: " . implode(', ', $uniqueSkipped));
        }

        return $relations;
    }
}