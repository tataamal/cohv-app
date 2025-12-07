<?php

namespace Database\Seeders;

use App\Models\WorkcenterMapping;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class WorkcenterMappingSeeder extends Seeder
{
    /**
     * Jalankan seed database.
     */
    public function run(): void
    {
        // 1. Definisikan path ke file CSV (Gunakan nama file yang sudah dibersihkan jika Anda mengikuti langkah sebelumnya)
        $csvPath = database_path('seeders/Data/data-induk.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("File CSV tidak ditemukan: " . $csvPath);
            return;
        }

        // KOSONGKAN TABEL DI LUAR TRANSAKSI
        // Ini tidak akan menyebabkan implicit commit dan menutup transaksi yang akan datang.
        WorkcenterMapping::truncate(); 

        try {
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();

            // MULAI TRANSAKSI DI SINI
            DB::beginTransaction(); 

            $dataToInsert = [];
            foreach ($records as $record) {
                // ... (mapping data tetap sama) ...
                $dataToInsert[] = [
                    'wc_induk' => $record['wc_induk'] ?? null,
                    'nama_wc_induk' => $record['nama_wc_induk'] ?? null,
                    'workcenter' => $record['workcenter'] ?? null,
                    'nama_workcenter' => $record['nama_workcenter'] ?? null,
                    'kode_laravel' => $record['kode_laravel'] ?? null,
                    'plant' => $record['plant'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Memasukkan data secara massal (bulk insert)
            $chunkedData = array_chunk($dataToInsert, 1000);
            foreach ($chunkedData as $chunk) {
                // Gunakan Model::insert() yang cepat dan tidak terpengaruh oleh truncate
                WorkcenterMapping::insert($chunk);
            }

            // COMMIT HANYA DILAKUKAN UNTUK OPERASI INSERT
            DB::commit();
            $this->command->info('Data Workcenter Mapping berhasil di-seed: ' . count($dataToInsert) . ' baris.');

        } catch (\Exception $e) {
            // Rollback hanya jika transaksi aktif (karena kita memindahkan truncate)
            if (DB::transactionLevel() > 0) { 
                DB::rollBack();
            }
            $this->command->error('Gagal melakukan seeding Workcenter Mapping: ' . $e->getMessage());
        }
    }
}