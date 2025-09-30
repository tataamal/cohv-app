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

        // 2. Buat peta lookup (kode_wc => id) dari tabel workcenters untuk efisiensi
        $this->command->info('Memuat data workcenter ke memori...');
        $wcLookup = workcenter::pluck('id', 'kode_wc');
        $this->command->info(count($wcLookup) . ' workcenter berhasil dimuat.');

        // 3. Path ke file CSV yang baru dan sudah diproses
        $filePath = database_path('seeders/Data/data_compatible_all.csv');
        
        // 4. Proses file CSV
        $allRelations = $this->processCsvFile($filePath, $wcLookup);

        // 5. Masukkan semua data relasi yang valid ke database
        if (empty($allRelations)) {
            $this->command->error('GAGAL: Tidak ada relasi valid yang bisa diproses dari file CSV.');
            $this->command->warn('Pastikan kolom "wc_asal" dan "wc_tujuan" di CSV cocok dengan "kode_wc" di tabel workcenters.');
            return;
        }

        $this->command->info("Memasukkan total " . count($allRelations) . " relasi ke database...");
        
        // Memasukkan data dalam batch (potongan kecil) agar lebih efisien
        foreach (array_chunk($allRelations, 500) as $chunk) {
            DB::table('wc_relations')->insert($chunk);
        }

        $this->command->info("Proses seeding relasi selesai dengan sukses! ✅");
    }

    /**
     * Membaca file CSV yang sudah diproses dan mengubahnya menjadi array relasi.
     */
    protected function processCsvFile(string $filePath, $wcLookup): array
    {
        if (!file_exists($filePath)) {
            $this->command->error("KRITIS: File tidak ditemukan di: " . $filePath);
            return [];
        }

        $this->command->info("Memproses file: " . basename($filePath));
        $fileHandle = fopen($filePath, 'r');
        
        // Baca header untuk mendapatkan nama kolom
        $header = fgetcsv($fileHandle);
        
        $relations = [];
        $rowCount = 0;
        $skippedWcs = []; // Untuk mencatat semua WC yang tidak ditemukan di DB

        // Baca file baris per baris
        while (($row = fgetcsv($fileHandle)) !== false) {
            // Lewati baris kosong
            if (empty(array_filter($row))) continue;
            
            $rowCount++;
            $record = @array_combine($header, $row);

            if ($record === false) continue;

            // Ambil data dari kolom yang sesuai
            $wcAsalKode   = trim($record['wc_asal'] ?? '');
            $wcTujuanKode = trim($record['wc_tujuan'] ?? '');
            $status       = trim($record['status'] ?? '');

            // Validasi: pastikan semua data yang dibutuhkan ada
            if (empty($wcAsalKode) || empty($wcTujuanKode) || empty($status)) {
                continue;
            }

            // Cari ID dari kode WC menggunakan peta lookup
            $wcAsalId   = $wcLookup->get($wcAsalKode);
            $wcTujuanId = $wcLookup->get($wcTujuanKode);

            // Jika salah satu ID tidak ditemukan, catat dan lewati baris ini
            if (!$wcAsalId) {
                $skippedWcs[] = $wcAsalKode;
                continue;
            }
            if (!$wcTujuanId) {
                $skippedWcs[] = $wcTujuanKode;
                continue;
            }

            // Jika semua valid, tambahkan ke daftar untuk di-insert
            $relations[] = [
                'wc_asal_id'   => $wcAsalId,
                'wc_tujuan_id' => $wcTujuanId,
                'status'       => $status,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        fclose($fileHandle);
        $this->command->info("Selesai memproses $rowCount baris. Menghasilkan " . count($relations) . " relasi valid.");
        
        // Tampilkan peringatan jika ada WC di CSV yang tidak ada di database
        if (!empty($skippedWcs)) {
            $uniqueSkipped = array_unique($skippedWcs);
            $this->command->warn(count($uniqueSkipped) . " WC unik berikut dilewati karena tidak ditemukan di DB: " . implode(', ', $uniqueSkipped));
        }

        return $relations;
    }
}