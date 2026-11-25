<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$colProduk = DB::select("DESCRIBE produk");
echo "PRODUK:\n" . json_encode($colProduk, JSON_PRETTY_PRINT) . "\n";

$colDetail = DB::select("DESCRIBE detail_booking");
echo "DETAIL_BOOKING:\n" . json_encode($colDetail, JSON_PRETTY_PRINT) . "\n";
