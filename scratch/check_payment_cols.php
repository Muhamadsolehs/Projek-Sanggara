<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$colBooking = DB::select("DESCRIBE booking");
echo "=== BOOKING COLUMNS ===\n";
foreach ($colBooking as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}

$colPembayaran = DB::select("DESCRIBE pembayaran_transaksi");
echo "\n=== PEMBAYARAN_TRANSAKSI COLUMNS ===\n";
foreach ($colPembayaran as $c) {
    echo "{$c->Field} ({$c->Type})\n";
}
