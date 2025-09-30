<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable; // Import Throwable untuk menangkap semua jenis error

// PERBAIKAN: Menyamakan nama class dengan signature untuk konsistensi
class sync_gr_historical extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync_gr_historical';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PERHATIAN: Menjalankan sinkronisasi data GR historis (bisa sangat lama).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Memulai tugas sinkronisasi GR HISTORIS...');
        $this->info('Memulai tugas sinkronisasi GR HISTORIS...');

        // PERBAIKAN: Konfirmasi hanya muncul saat command dijalankan secara interaktif (manual)
        // Jika dijalankan oleh scheduler, konfirmasi akan dilewati untuk mencegah error.
        if ($this->input->isInteractive()) {
            if (!$this->confirm('Proses ini akan menarik data historis dan bisa memakan waktu berjam-jam. Lanjutkan?')) {
                $this->info('Proses dibatalkan oleh pengguna.');
                Log::info('Proses sinkronisasi GR historis dibatalkan oleh pengguna.');
                return 0;
            }
        } else {
            Log::info('Menjalankan sinkronisasi GR historis dalam mode non-interaktif (dijadwalkan).');
        }

        try {
            $pythonScriptPath = base_path('sync_gr.py');

            if (!File::exists($pythonScriptPath)) {
                $errorMessage = "File python 'sync_gr.py' tidak ditemukan.";
                $this->error($errorMessage);
                Log::error("GAGAL: File sync_gr.py tidak ditemukan.", ['path' => $pythonScriptPath]);
                return 1;
            }
            
            // Menggunakan path Python yang fleksibel dari file .env
            $pythonExecutablePath = env('PYTHON_EXECUTABLE', 'python3');
            
            // Memanggil skrip Python dengan argumen 'run_historical'
            $command = "{$pythonExecutablePath} -u \"{$pythonScriptPath}\" run_historical";

            $this->info("Menjalankan perintah: {$command}");
            $this->line('----------------------------------------------------');
            
            // Beri timeout yang sangat panjang, misal 24 jam (86400 detik)
            $result = Process::timeout(86400)->run($command, function (string $type, string $output) {
                $this->line(rtrim($output));
                Log::channel('daily')->info($output); // Catat juga output skrip ke log
            });

            $this->line('----------------------------------------------------');

            if ($result->successful()) {
                $successMessage = "✅ Sinkronisasi GR historis berhasil diselesaikan.";
                $this->info($successMessage);
                Log::info($successMessage);
            } else {
                $this->error("❌ Terjadi kesalahan saat sinkronisasi GR historis.");
                
                $errorOutput = $result->errorOutput();
                if (!empty($errorOutput)) {
                    $this->error("Error Output: " . $errorOutput);
                }

                Log::error('GAGAL: Error saat eksekusi sync_gr.py (historical)', [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $errorOutput
                ]);
            }

            return $result->exitCode();

        } catch (Throwable $e) {
            // Jaring pengaman untuk semua jenis error
            $this->error("Terjadi error kritis pada command sync_gr_historical: " . $e->getMessage());
            Log::critical('CRITICAL ERROR pada command sync_gr_historical', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return 1;
        }
    }
}
