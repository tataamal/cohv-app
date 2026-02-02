<?php
// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UserSap;
use App\Models\MappingTable;
use App\Models\KodeLaravel;
use App\Models\User;

echo "--- Debug Start ---\n";

// 1. Check UserSap for auto_email
$autoUser = UserSap::where('user_sap', 'auto_email')->first();
echo "UserSap 'auto_email': " . ($autoUser ? "Found (ID: {$autoUser->id})" : "NOT FOUND") . "\n";

// 2. Check MappingTable count
$count = MappingTable::count();
echo "MappingTable Total Count: $count\n";

// 3. Check specific mappings for other users (checking for assumed IDs 3,4,5,6)
$others = MappingTable::whereIn('user_sap_id', [3,4,5,6])->count();
echo "MappingTable Count for IDs 3,4,5,6: $others\n";

// 4. Check if 'auto_email' login would work
$email = 'auto_email@kmi.local';
$user = User::where('email', $email)->first();
echo "User (Laravel) 'auto_email@kmi.local': " . ($user ? "Found" : "NOT FOUND") . "\n";

// 5. Simulate the query in adminController
if ($autoUser) {
    echo "\nSimulating adminController query...\n";
    $mappings = MappingTable::with('kodeLaravel')->get();
    echo "Mappings retrieved via get(): " . $mappings->count() . "\n";
    
    $plants = $mappings->map(function($mapping) {
        if ($mapping->kodeLaravel) {
            return $mapping->kodeLaravel->laravel_code . ' (' . $mapping->kodeLaravel->description . ')';
        }
        return 'NULL_KODE';
    })->filter()->unique()->values();
    
    echo "Plants mapped: \n";
    print_r($plants->toArray());
}

echo "--- Debug End ---\n";
