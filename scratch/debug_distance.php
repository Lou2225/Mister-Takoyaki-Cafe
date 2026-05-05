<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 2);
}

$mainBranch = Branch::where('is_main', true)->first();
if (!$mainBranch) {
    echo "NO MAIN BRANCH FOUND\n";
    exit;
}

$mainAddr = is_array($mainBranch->address) ? $mainBranch->address : json_decode($mainBranch->address, true);
$mainLat = $mainAddr['lat'] ?? 0;
$mainLng = $mainAddr['lng'] ?? 0;

$branches = Branch::where('is_main', false)->get();
foreach($branches as $branch) {
    $addr = is_array($branch->address) ? $branch->address : json_decode($branch->address, true);
    $lat = $addr['lat'] ?? 0;
    $lng = $addr['lng'] ?? 0;
    
    $dist = calculateDistance($mainLat, $mainLng, $lat, $lng);
    $branch->update(['distance_from_main' => $dist]);
    echo "Updated " . $branch->branch_name . " to " . $dist . " KM\n";
}
echo "SYNC COMPLETE\n";
