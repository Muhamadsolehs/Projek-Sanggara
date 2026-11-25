<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::unprepared("
DROP TRIGGER IF EXISTS `trg_after_insert_pembayaran_transaksi`;
CREATE TRIGGER `trg_after_insert_pembayaran_transaksi` AFTER INSERT ON `pembayaran_transaksi` FOR EACH ROW
BEGIN
  -- Update akumulasi pembayaran pada booking secara akurat
  UPDATE `booking`
  SET 
    `sisa_tagihan` = GREATEST(`total_tagihan` - (`total_bayar` + NEW.jumlah_bayar), 0.00),
    `status_pembayaran` = IF((`total_tagihan` - (`total_bayar` + NEW.jumlah_bayar)) <= 0, 'LUNAS', 'DP'),
    `metode_bayar_terakhir` = NEW.metode_bayar,
    `total_bayar` = `total_bayar` + NEW.jumlah_bayar
  WHERE `id_booking` = NEW.id_booking;

  -- Otomatis masukkan ke buku kas laporan_pemasukan
  INSERT INTO `laporan_pemasukan` (
    `no_transaksi`, `id_booking`, `tanggal`, `kategori_pemasukan`, `nominal`, `metode_pembayaran`, `id_user`, `keterangan`
  ) VALUES (
    CONCAT('BKK-', LPAD(FLOOR(RAND() * 90000 + 10000), 5, '0')),
    NEW.id_booking,
    DATE(NEW.tgl_bayar),
    IF(NEW.jenis_pembayaran = 'DP', 'Sewa Busana (DP 25%)', 'Sewa Busana (Pelunasan)'),
    NEW.jumlah_bayar,
    NEW.metode_bayar,
    NEW.id_user,
    CONCAT('Penerimaan kas dari pembayaran ', NEW.no_bukti_bayar)
  );
END;
");

echo "Trigger trg_after_insert_pembayaran_transaksi updated successfully!\n";
