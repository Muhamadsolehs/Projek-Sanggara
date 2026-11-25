<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Controllers\Api\DataNotaController;
use Illuminate\Http\Request;

$ctrl = new DataNotaController();
$res = $ctrl->index(new Request());
$data = json_decode($res->getContent(), true);

foreach ($data['data'] as $r) {
    echo "NOTA: {$r['no_nota']} | CUST: {$r['customer']} | DP: {$r['total_dp']} | STATUS: {$r['status']} | METODE: {$r['metode_bayar']} (DP: {$r['metode_bayar_dp']}, Pelunasan: {$r['metode_bayar_pelunasan']})\n";
}
