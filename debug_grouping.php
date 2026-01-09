<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kode;

$codes = ['3014', '3015'];

echo "--- Simulating Fix ---\n";

foreach ($codes as $code) {
    echo "Checking Code: $code\n";
    
    // OLD LOGIC
    $old = Kode::find($code); 
    $oldName = $old ? $old->nama_bagian : 'UNKNOWN';
    echo "  [OLD] Kode::find('$code') => " . ($old ? "FOUND: $oldName" : "NULL (Result: UNKNOWN)") . "\n";

    // NEW LOGIC
    $new = Kode::where('kode', $code)->first();
    $newName = $new ? $new->nama_bagian : 'UNKNOWN';
    echo "  [NEW] Kode::where('kode', '$code')->first() => " . ($new ? "FOUND: $newName" : "NULL (Result: UNKNOWN)") . "\n";
    
    // Slug Check
    if ($new) {
        $slug = preg_replace('/[^A-Z0-9]/', '', strtoupper($newName));
        echo "  [SLUG] $slug\n";
    }
    echo "\n";
}
