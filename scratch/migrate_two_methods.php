<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Tambah kolom metode_bayar_dp dan metode_bayar_pelunasan ke tabel booking (jika belum ada)
$columns = DB::select("SHOW COLUMNS FROM `booking` LIKE 'metode_bayar_dp'");
if (empty($columns)) {
    DB::statement("ALTER TABLE `booking` ADD COLUMN `metode_bayar_dp` VARCHAR(30) NULL AFTER `total_dp`");
    echo "Added column metode_bayar_dp to booking.\n";
} else {
    echo "Column metode_bayar_dp already exists.\n";
}

$columnsPel = DB::select("SHOW COLUMNS FROM `booking` LIKE 'metode_bayar_pelunasan'");
if (empty($columnsPel)) {
    DB::statement("ALTER TABLE `booking` ADD COLUMN `metode_bayar_pelunasan` VARCHAR(30) NULL AFTER `metode_bayar_dp`");
    echo "Added column metode_bayar_pelunasan to booking.\n";
} else {
    echo "Column metode_bayar_pelunasan already exists.\n";
}

// 2. Modifikasi tipe data metode_bayar_terakhir agar bisa menampung kombinasi 2 metode
DB::statement("ALTER TABLE `booking` MODIFY COLUMN `metode_bayar_terakhir` VARCHAR(100) NULL");
echo "Modified metode_bayar_terakhir to VARCHAR(100).\n";

// 3. Backfill data dari riwayat pembayaran_transaksi
$allBookings = App\Models\Booking::with('pembayaran')->get();
foreach ($allBookings as $b) {
    $dpPay = $b->pembayaran->where('jenis_pembayaran', 'DP')->sortBy('id_pembayaran')->first();
    $pelunasanPay = $b->pembayaran->where('jenis_pembayaran', 'PELUNASAN')->sortByDesc('id_pembayaran')->first();
    $lunasLangsung = $b->pembayaran->where('jenis_pembayaran', 'LUNAS_LANGSUNG')->first();

    $metodeDp = $dpPay ? strtoupper($dpPay->metode_bayar) : null;
    $metodePelunasan = $pelunasanPay ? strtoupper($pelunasanPay->metode_bayar) : null;

    $metodeTerakhir = $b->metode_bayar_terakhir;
    if ($metodeDp && $metodePelunasan) {
        if ($metodeDp === $metodePelunasan) {
            $metodeTerakhir = "{$metodeDp} (DP & Pelunasan)";
        } else {
            $metodeTerakhir = "{$metodeDp} (DP) • {$metodePelunasan} (Pelunasan)";
        }
    } elseif ($metodeDp) {
        $metodeTerakhir = "{$metodeDp} (DP)";
    } elseif ($lunasLangsung) {
        $metodeTerakhir = strtoupper($lunasLangsung->metode_bayar);
    }

    $b->metode_bayar_dp = $metodeDp;
    $b->metode_bayar_pelunasan = $metodePelunasan;
    $b->metode_bayar_terakhir = $metodeTerakhir;
    $b->save();

    echo "Booking #{$b->id_booking} ({$b->no_nota}): DP={$metodeDp}, Pelunasan={$metodePelunasan}, Terakhir={$metodeTerakhir}\n";
}

echo "Database columns and data backfill successfully updated!\n";
