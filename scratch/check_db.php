<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "DATABASE: " . DB::connection()->getDatabaseName() . PHP_EOL;
echo "TABLES:" . PHP_EOL;
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $vals = array_values((array)$t);
    echo " - " . $vals[0] . PHP_EOL;
}

echo "COLUMNS OF produk:" . PHP_EOL;
print_r(Schema::getColumnListing('produk'));

echo "TRIGGERS:" . PHP_EOL;
$trg = DB::select("SHOW CREATE TRIGGER trg_after_insert_detail_booking");
print_r($trg);
