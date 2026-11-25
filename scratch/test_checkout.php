<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\KasirController;

$request = Request::create('/api/kasir/checkout', 'POST', [
    'id_pelanggan' => 24,
    'tgl_ambil' => '2026-12-10',
    'tgl_pakai' => '2026-12-11',
    'tgl_kembali_rencana' => '2026-12-12',
    'opsi_pembayaran' => 'lunas',
    'metode_bayar' => 'TUNAI',
    'uang_diterima' => 20000,
    'items' => [
        [
            'id_produk' => 9,
            'jumlah' => 1
        ]
    ]
]);

$controller = new KasirController();
try {
    $response = $controller->checkout($request);
    echo "STATUS CODE: " . $response->getStatusCode() . "\n";
    echo "RESPONSE: " . $response->getContent() . "\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
