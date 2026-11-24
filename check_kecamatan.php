<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kecamatan;
use App\Models\Pelanggan;

echo "=== TABEL KECAMATAN ===\n";
$kecamatans = Kecamatan::all();
echo "Total kecamatan di DB: " . $kecamatans->count() . "\n";
foreach ($kecamatans as $k) {
    echo "ID: {$k->id_kecamatan} | Nama: {$k->nama_kecamatan} | Zona: {$k->zona_wilayah} | Target: {$k->target_pasar}\n";
}

echo "\n=== TABEL PELANGGAN ===\n";
$pelanggans = Pelanggan::with('kecamatan')->get();
foreach ($pelanggans as $p) {
    $kecName = $p->kecamatan ? $p->kecamatan->nama_kecamatan : ($p->nama_kecamatan ?? 'NULL');
    echo "ID: {$p->id_pelanggan} | Nama: {$p->nama_pelanggan} | ID Kec: {$p->id_kecamatan} | Nama Kec: {$kecName} | Alamat: {$p->alamat_lengkap}\n";
}
