<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VIEW v_ranking_kecamatan ===\n";
try {
    $rows = DB::table('v_ranking_kecamatan')->get();
    foreach ($rows as $r) {
        echo json_encode($r) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== CREATE VIEW STATEMENT ===\n";
try {
    $viewDef = DB::select("SHOW CREATE VIEW v_ranking_kecamatan");
    echo json_encode($viewDef, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
