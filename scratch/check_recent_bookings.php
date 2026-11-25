<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Booking;

$b = Booking::with('pelanggan')->orderByDesc('id_booking')->take(5)->get();
foreach ($b as $item) {
    echo "ID: {$item->id_booking} | Nota: {$item->no_nota} | Cust: " . ($item->pelanggan ? $item->pelanggan->nama_pelanggan : 'None') . " | Tagihan: {$item->total_tagihan} | Bayar: {$item->total_bayar} | Sisa: {$item->sisa_tagihan}\n";
}
