<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HistoryWi;
use App\Models\Kode;
use Carbon\Carbon;

$date = '2026-01-08';
echo "Inspecting Data for Date: $date\n";

$docs = HistoryWi::with('kode')
    ->whereYear('document_date', 2026) // Broaden search slightly to be sure
    ->whereMonth('document_date', 1)
    ->whereDay('document_date', 8)
    ->whereIn('plant_code', ['3014', '3015'])
    ->get();

echo "Found " . $docs->count() . " docs.\n";

$issues = 0;
foreach ($docs as $doc) {
    if ($doc->plant_code == '3015') {
        $kData = $doc->kode;
        $loadedName = $kData ? $kData->nama_bagian : 'NULL';
        $fallback = Kode::where('kode', $doc->plant_code)->first();
        $fallbackName = $fallback ? $fallback->nama_bagian : 'NULL';
        
        if ($kData) {
            if ($kData->kode != $doc->plant_code) {
                 echo "!!! MISMATCH FOUND !!!\n";
                 echo "Doc: {$doc->wi_document_code}\n";
                 echo "Plant Code: '{$doc->plant_code}'\n";
                 echo "Loaded Kode: '{$kData->kode}'\n";
                 echo "Loaded Name: '{$kData->nama_bagian}'\n";
                 $issues++;
            } elseif (stripos($loadedName, 'ASSEMBLY') !== false) {
                 echo "!!! 3015 IS ASSEMBLY ??? !!!\n";
                 echo "Doc: {$doc->wi_document_code}\n";
                 echo "Plant Code: '{$doc->plant_code}'\n";
                 echo "Loaded ID: '{$kData->id}'\n";
                 echo "Loaded Name: '{$loadedName}'\n";
                 $issues++;
            }
        }
    }
}

if ($issues == 0) {
    echo "No anomalies found in iteration. Standard check:\n";
    // Check one normal 3015
    $sample = $docs->where('plant_code', '3015')->first();
    if ($sample) {
        echo "Sample 3015 Doc: {$sample->wi_document_code}\n";
        echo "Relation Name: " . ($sample->kode ? $sample->kode->nama_bagian : 'NULL') . "\n";
    } else {
        echo "No 3015 docs found.\n";
    }
}
