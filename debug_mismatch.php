<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kode;

echo "--- Simulating Relation Mismatch Fix ---\n";

// Mock Data
$docPlantCode = '3015'; // Painting
$badRelationKode = '3014'; // Assy (Simulating bad relation)

// 1. Simulate the Bad Relation Loading
// We can't easily mock the Model structure in raw PHP script without factory, 
// so we'll simulate the logic block directly.

echo "Scenario: Doc is 3015 (Painting), but \$doc->kode returns 3014 (Assy)\n";

$pCode = $docPlantCode;
// Simulate finding the "Bad" Kode object
$badKodeObj = Kode::where('kode', $badRelationKode)->first(); 
$kData = $badKodeObj;

echo "Loaded Relation: " . ($kData ? "$kData->kode ({$kData->nama_bagian})" : "NULL") . "\n";

// --- APPLIED LOGIC START ---
if ($kData && $kData->kode != $pCode) {
    echo "[LOGIC] Mismatch detected ({$kData->kode} != {$pCode}). Invalidating relation.\n";
    $kData = null; 
} else {
    echo "[LOGIC] No mismatch detected.\n";
}

if (!$kData) {
    echo "[LOGIC] Falling back to manual lookup for $pCode...\n";
    $kData = Kode::where('kode', $pCode)->first();
}
// --- APPLIED LOGIC END ---

echo "Final Result: " . ($kData ? "$kData->kode ({$kData->nama_bagian})" : "NULL") . "\n";

if ($kData && $kData->kode == '3015') {
    echo "SUCCESS: Correctly resolved to 3015.\n";
} else {
    echo "FAILURE: Did not resolve to 3015.\n";
}
