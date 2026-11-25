<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::unprepared("
DROP TRIGGER IF EXISTS `trg_after_insert_detail_booking`;
CREATE TRIGGER `trg_after_insert_detail_booking` AFTER INSERT ON `detail_booking` FOR EACH ROW
BEGIN
  UPDATE `produk`
  SET 
    `stok_siap` = IF(`stok_siap` >= NEW.jumlah, `stok_siap` - NEW.jumlah, 0),
    `stok_disewa` = `stok_disewa` + NEW.jumlah,
    `status` = IF(IF(`stok_siap` >= NEW.jumlah, `stok_siap` - NEW.jumlah, 0) = 0 AND `status` != 'perawatan', 'habis', `status`)
  WHERE `id_produk` = NEW.id_produk;
END;
");

echo "Trigger updated successfully!\n";
