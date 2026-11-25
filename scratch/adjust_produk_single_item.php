<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating produk records to single item (no stock count)..." . PHP_EOL;

$affected = DB::table('produk')->update([
    'stok_total' => 1,
    'stok_siap' => DB::raw("CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END"),
    'stok_disewa' => DB::raw("CASE WHEN status = 'habis' THEN 1 ELSE 0 END"),
    'stok_cuci' => 0,
    'stok_rusak' => DB::raw("CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END")
]);

echo "Updated {$affected} produk records." . PHP_EOL;

$prods = DB::table('produk')->select('id_produk', 'nama_produk', 'status', 'stok_total', 'stok_siap', 'stok_disewa')->get();
foreach ($prods as $p) {
    echo " - #{$p->id_produk} {$p->nama_produk}: status={$p->status}, total={$p->stok_total}, siap={$p->stok_siap}, disewa={$p->stok_disewa}" . PHP_EOL;
}
