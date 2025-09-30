<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class sync_cohv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync_cohv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan proses sinkronisasi data COHV dari SAP ke database lokal dengan output real-time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Memulai tugas sinkronisasi COHV...');
        $this->info('Memulai tugas sinkronisasi COHV...');

        try {
            $pythonScriptPath = base_path('sync_cohv.py');

            if (!File::exists($pythonScriptPath)) {
                $errorMessage = "File python tidak ditemukan di lokasi: {$pythonScriptPath}";
                $this->error($errorMessage);
                Log::error("GAGAL: File sync_cohv.py tidak ditemukan.", ['path' => $pythonScriptPath]);
                return 1;
            }

            $pythonExecutablePath = env('PYTHON_EXECUTABLE', 'python3');
            $command = "{$pythonExecutablePath} -u \"{$pythonScriptPath}\"";
            
            $this->info("Menjalankan perintah: {$command}");
            $this->line('--------------------- [ REAL-TIME LOG START ] ---------------------');

            // === PERUBAHAN UTAMA: Gunakan Process::start() untuk mode non-blocking ===
            $process = Process::timeout(3600)->start($command);

            // Loop untuk memantau output selagi proses berjalan
            while ($process->running()) {
                // Ambil output terbaru (tanpa menumpuk) dan log jika ada
                $latestOutput = $process->latestOutput();
                $latestErrorOutput = $process->latestErrorOutput();

                if (!empty($latestOutput)) {
                    // Cetak ke konsol dan tulis ke file log
                    $this->line(trim($latestOutput));
                    Log::channel('daily')->info(trim($latestOutput));
                }
                
                if (!empty($latestErrorOutput)) {
                    $this->error(trim($latestErrorOutput));
                    Log::channel('daily')->error(trim($latestErrorOutput));
                }

                // Beri jeda 1 detik agar tidak membebani CPU
                sleep(1);
            }

            // Tunggu proses benar-benar selesai dan dapatkan hasilnya
            $result = $process->wait();

            $this->line('---------------------- [ REAL-TIME LOG END ] ----------------------');

            if ($result->successful()) {
                $successMessage = "✅ Skrip Python berhasil dijalankan dan selesai.";
                $this->info($successMessage);
                Log::info($successMessage);
            } else {
                $this->error("❌ Terjadi kesalahan saat menjalankan skrip Python.");
                $this->error("Error Code: " . $result->exitCode());
                
                // Tampilkan error output terakhir jika ada yang belum tertangkap
                $errorOutput = $result->errorOutput();
                if (!empty($errorOutput)) {
                    $this->error("Final Error Output: " . $errorOutput);
                }

                Log::error('GAGAL: Error saat eksekusi sync_cohv.py', [
                    'exit_code' => $result->exitCode(),
                    'error_output' => $errorOutput
                ]);
            }

            return $result->exitCode();

        } catch (Throwable $e) {
            $this->error("Terjadi error kritis pada command sync_cohv: " . $e->getMessage());
            Log::critical('CRITICAL ERROR pada command sync_cohv', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(), // Tambahkan trace untuk debug
            ]);
            return 1;
        }
    }
}
