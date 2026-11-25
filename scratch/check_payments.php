<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PembayaranTransaksi;

$payments = PembayaranTransaksi::orderByDesc('id_pembayaran')->take(10)->get();
echo json_encode($payments, JSON_PRETTY_PRINT);
