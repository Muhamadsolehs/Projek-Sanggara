<?php
/**
 * Script untuk sinkronisasi schema & seeder lengkap ke db_sanggara dan sanggara_db
 */

$databases = ['db_sanggara', 'sanggara_db'];

foreach ($databases as $dbName) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbName`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Users
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id_user` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama_lengkap` VARCHAR(100) NOT NULL,
            `email` VARCHAR(120) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'kasir', 'owner') NOT NULL DEFAULT 'kasir',
            `no_telepon` VARCHAR(20) NULL,
            `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Kecamatan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `kecamatan` (
            `id_kecamatan` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama_kecamatan` VARCHAR(80) NOT NULL UNIQUE,
            `zona_wilayah` VARCHAR(50) NOT NULL DEFAULT 'Purwakarta',
            `target_pasar` INT UNSIGNED NOT NULL DEFAULT 50,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 3. Pelanggan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pelanggan` (
            `id_pelanggan` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `kode_pelanggan` VARCHAR(30) NOT NULL UNIQUE,
            `nama_pelanggan` VARCHAR(120) NOT NULL,
            `no_telepon` VARCHAR(25) NOT NULL,
            `id_kecamatan` INT UNSIGNED NULL,
            `nama_kecamatan` VARCHAR(80) NULL,
            `kelurahan_desa` VARCHAR(80) NULL,
            `alamat_lengkap` TEXT NOT NULL,
            `total_transaksi` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_kecamatan`) REFERENCES `kecamatan` (`id_kecamatan`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 4. Kategori
        $pdo->exec("CREATE TABLE IF NOT EXISTS `kategori` (
            `id_kategori` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama_kategori` VARCHAR(80) NOT NULL UNIQUE,
            `slug` VARCHAR(90) NOT NULL UNIQUE,
            `deskripsi` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 5. Produk
        $pdo->exec("CREATE TABLE IF NOT EXISTS `produk` (
            `id_produk` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `kode_produk` VARCHAR(30) NOT NULL UNIQUE,
            `id_kategori` INT UNSIGNED NOT NULL,
            `nama_produk` VARCHAR(150) NOT NULL,
            `kualitas` VARCHAR(60) NOT NULL DEFAULT 'Grade A',
            `harga_sewa` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `denda_per_hari` DECIMAL(12,2) NOT NULL DEFAULT 15000.00,
            `stok_total` INT UNSIGNED NOT NULL DEFAULT 1,
            `stok_siap` INT UNSIGNED NOT NULL DEFAULT 1,
            `stok_disewa` INT UNSIGNED NOT NULL DEFAULT 0,
            `stok_cuci` INT UNSIGNED NOT NULL DEFAULT 0,
            `stok_rusak` INT UNSIGNED NOT NULL DEFAULT 0,
            `foto_url` VARCHAR(255) NULL,
            `status` ENUM('tersedia', 'habis', 'maintenance') NOT NULL DEFAULT 'tersedia',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 6. Booking
        $pdo->exec("CREATE TABLE IF NOT EXISTS `booking` (
            `id_booking` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `no_nota` VARCHAR(50) NOT NULL UNIQUE,
            `no_booking` INT UNSIGNED NOT NULL,
            `id_pelanggan` INT UNSIGNED NOT NULL,
            `id_user` INT UNSIGNED NOT NULL,
            `tgl_transaksi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `tgl_ambil` DATE NOT NULL,
            `tgl_pakai` DATE NOT NULL,
            `tgl_kembali_rencana` DATE NOT NULL,
            `durasi_sewa_hari` INT UNSIGNED NOT NULL DEFAULT 3,
            `subtotal_produk` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `total_tagihan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `opsi_pembayaran` ENUM('lunas', 'dp') NOT NULL DEFAULT 'lunas',
            `persentase_dp` DECIMAL(5,2) NOT NULL DEFAULT 25.00,
            `total_dp` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `total_bayar` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `sisa_tagihan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `metode_bayar_terakhir` VARCHAR(50) NOT NULL DEFAULT 'TUNAI',
            `status_pembayaran` ENUM('LUNAS', 'DP', 'BELUM_BAYAR') NOT NULL DEFAULT 'DP',
            `status_sewa` ENUM('booking', 'diambil', 'kembali', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'booking',
            `catatan` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE,
            FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 7. Detail Booking
        $pdo->exec("CREATE TABLE IF NOT EXISTS `detail_booking` (
            `id_detail` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_booking` INT UNSIGNED NOT NULL,
            `id_produk` INT UNSIGNED NOT NULL,
            `jumlah` INT UNSIGNED NOT NULL DEFAULT 1,
            `harga_satuan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `keterangan_kondisi` VARCHAR(100) NULL DEFAULT 'Baik',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE,
            FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 8. Pembayaran Transaksi
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pembayaran_transaksi` (
            `id_pembayaran` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `no_bukti_bayar` VARCHAR(50) NOT NULL UNIQUE,
            `id_booking` INT UNSIGNED NOT NULL,
            `tgl_bayar` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `jenis_pembayaran` ENUM('DP', 'PELUNASAN', 'DENDA') NOT NULL DEFAULT 'DP',
            `jumlah_bayar` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `uang_diterima` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `kembalian` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `metode_bayar` ENUM('TUNAI', 'BANK', 'QRIS') NOT NULL DEFAULT 'TUNAI',
            `id_user` INT UNSIGNED NOT NULL,
            `keterangan` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE,
            FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 9. Laporan Pemasukan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `laporan_pemasukan` (
            `id_pemasukan` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `no_transaksi` VARCHAR(50) NOT NULL UNIQUE,
            `id_booking` INT UNSIGNED NULL,
            `tanggal` DATE NOT NULL,
            `kategori_pemasukan` VARCHAR(60) NOT NULL DEFAULT 'Sewa Busana',
            `nominal` DECIMAL(12,2) NOT NULL,
            `metode_pembayaran` ENUM('TUNAI', 'BANK', 'QRIS') NOT NULL,
            `id_user` INT UNSIGNED NOT NULL,
            `keterangan` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE SET NULL,
            FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 10. Laporan Pengeluaran
        $pdo->exec("CREATE TABLE IF NOT EXISTS `laporan_pengeluaran` (
            `id_pengeluaran` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `no_transaksi` VARCHAR(50) NOT NULL UNIQUE,
            `tanggal` DATE NOT NULL,
            `kategori_beban` VARCHAR(80) NOT NULL,
            `nominal` DECIMAL(12,2) NOT NULL,
            `metode_pembayaran` ENUM('TUNAI', 'BANK', 'QRIS') NOT NULL,
            `pic_staf` VARCHAR(100) NOT NULL,
            `keterangan` TEXT NULL,
            `bukti_foto_url` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Laravel Session & Cache tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sessions` (
            `id` VARCHAR(255) NOT NULL PRIMARY KEY,
            `user_id` BIGINT UNSIGNED NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` TEXT NULL,
            `payload` LONGTEXT NOT NULL,
            `last_activity` INT NOT NULL,
            INDEX `idx_sessions_user_id` (`user_id`),
            INDEX `idx_sessions_last_activity` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `cache` (
            `key` VARCHAR(255) NOT NULL PRIMARY KEY,
            `value` MEDIUMTEXT NOT NULL,
            `expiration` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `cache_locks` (
            `key` VARCHAR(255) NOT NULL PRIMARY KEY,
            `owner` VARCHAR(255) NOT NULL,
            `expiration` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Views
        $pdo->exec("CREATE OR REPLACE VIEW `v_arus_kas_bulanan` AS
        SELECT 
            DATE_FORMAT(tgl_gabung, '%Y-%m') AS `periode`,
            YEAR(tgl_gabung) AS `tahun`,
            MONTH(tgl_gabung) AS `bulan_angka`,
            DATE_FORMAT(tgl_gabung, '%b') AS `nama_bulan`,
            COALESCE(SUM(pemasukan), 0) AS `total_pemasukan`,
            COALESCE(SUM(pengeluaran), 0) AS `total_pengeluaran`,
            COALESCE(SUM(pemasukan), 0) - COALESCE(SUM(pengeluaran), 0) AS `surplus_bersih`
        FROM (
            SELECT tanggal AS tgl_gabung, nominal AS pemasukan, 0 AS pengeluaran FROM laporan_pemasukan
            UNION ALL
            SELECT tanggal AS tgl_gabung, 0 AS pemasukan, nominal AS pengeluaran FROM laporan_pengeluaran
        ) AS arus_kas
        GROUP BY DATE_FORMAT(tgl_gabung, '%Y-%m'), YEAR(tgl_gabung), MONTH(tgl_gabung), DATE_FORMAT(tgl_gabung, '%b')
        ORDER BY `periode` ASC;");

        $pdo->exec("CREATE OR REPLACE VIEW `v_ranking_kecamatan` AS
        SELECT 
            kc.id_kecamatan,
            kc.nama_kecamatan,
            kc.zona_wilayah,
            COUNT(b.id_booking) AS `total_booking`,
            COALESCE(SUM(b.total_tagihan), 0) AS `total_omset`,
            ROUND((COUNT(b.id_booking) / GREATEST((SELECT COUNT(*) FROM booking), 1)) * 100, 1) AS `persentase_pasar`
        FROM kecamatan kc
        LEFT JOIN pelanggan p ON kc.id_kecamatan = p.id_kecamatan
        LEFT JOIN booking b ON p.id_pelanggan = b.id_pelanggan
        GROUP BY kc.id_kecamatan, kc.nama_kecamatan, kc.zona_wilayah
        ORDER BY `total_booking` DESC, kc.id_kecamatan ASC;");

        $pdo->exec("CREATE OR REPLACE VIEW `v_status_inventaris` AS
        SELECT 
            SUM(stok_total) AS `total_unit`,
            SUM(stok_siap) AS `siap_sewa_unit`,
            SUM(stok_disewa) AS `sedang_disewa_unit`,
            SUM(stok_cuci) AS `sedang_dicuci_unit`,
            SUM(stok_rusak) AS `perbaikan_unit`,
            ROUND((SUM(stok_siap) / GREATEST(SUM(stok_total), 1)) * 100, 1) AS `persen_siap_sewa`,
            ROUND((SUM(stok_disewa) / GREATEST(SUM(stok_total), 1)) * 100, 1) AS `persen_sedang_disewa`,
            ROUND((SUM(stok_cuci) / GREATEST(SUM(stok_total), 1)) * 100, 1) AS `persen_cuci`,
            ROUND((SUM(stok_rusak) / GREATEST(SUM(stok_total), 1)) * 100, 1) AS `persen_rusak`
        FROM produk;");

        $pdo->exec("CREATE OR REPLACE VIEW `v_data_nota_kasir` AS
        SELECT 
            b.id_booking,
            b.no_nota,
            b.no_booking,
            b.tgl_transaksi,
            b.tgl_ambil,
            b.tgl_pakai,
            b.tgl_kembali_rencana,
            b.durasi_sewa_hari,
            p.id_pelanggan,
            p.nama_pelanggan,
            p.no_telepon AS telp_pelanggan,
            p.alamat_lengkap AS alamat_pelanggan,
            kc.nama_kecamatan,
            u.nama_lengkap AS nama_kasir,
            b.total_tagihan,
            b.total_dp,
            b.total_bayar,
            b.sisa_tagihan,
            b.metode_bayar_terakhir,
            b.status_pembayaran,
            b.status_sewa,
            (SELECT COUNT(*) FROM detail_booking db WHERE db.id_booking = b.id_booking) AS total_item_busana
        FROM booking b
        JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
        LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
        JOIN users u ON b.id_user = u.id_user;");

        // Seeders: Users
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmtUser = $pdo->prepare("INSERT INTO `users` (`id_user`, `nama_lengkap`, `email`, `password`, `role`, `no_telepon`, `status`) VALUES
            (1, 'Ahmad Rivaldy', 'ahmad.rivaldy@sanggara.id', :p1, 'kasir', '081802238821', 'aktif'),
            (2, 'Ivan', 'ivan@sanggara.id', :p2, 'kasir', '081234567890', 'aktif'),
            (3, 'Amanah Solusi', 'muhamadsolehs871@gmail.com', :p3, 'admin', '081987654321', 'aktif')
            ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `role` = VALUES(`role`)");
        $stmtUser->execute(['p1' => $hash, 'p2' => $hash, 'p3' => $hash]);

        // Seeders: Kecamatan
        $kecamatans = [
            [1, 'Kec. Purwakarta', 'Pusat Kota', 200],
            [2, 'Kec. Babakancikao', 'Industri & Utara', 120],
            [3, 'Kec. Jatiluhur', 'Danau & Wisata', 90],
            [4, 'Kec. Campaka', 'Industri & Utara', 80],
            [5, 'Kec. Bungursari', 'Industri & Utara', 75],
            [6, 'Kec. Pasawahan', 'Pusat Kota', 60],
            [7, 'Kec. Sukatani', 'Industri & Utara', 55],
            [8, 'Kec. Plered', 'Pusat Kota', 50],
            [9, 'Kec. Darangdan', 'Selatan Pegunungan', 45],
            [10, 'Kec. Bojong', 'Selatan Pegunungan', 40],
            [11, 'Kec. Wanayasa', 'Selatan Pegunungan', 40],
            [12, 'Kec. Kiarapedes', 'Selatan Pegunungan', 35],
            [13, 'Kec. Cibatu', 'Industri & Utara', 35],
            [14, 'Kec. Pondoksalam', 'Pusat Kota', 30],
            [15, 'Kec. Tegalwaru', 'Danau & Wisata', 25],
            [16, 'Kec. Maniis', 'Danau & Wisata', 20],
            [17, 'Kec. Sukasari', 'Danau & Wisata', 20],
        ];
        foreach ($kecamatans as $kc) {
            $pdo->exec("INSERT INTO `kecamatan` (`id_kecamatan`, `nama_kecamatan`, `zona_wilayah`, `target_pasar`) 
                VALUES ({$kc[0]}, '{$kc[1]}', '{$kc[2]}', {$kc[3]})
                ON DUPLICATE KEY UPDATE `nama_kecamatan` = VALUES(`nama_kecamatan`);");
        }

        // Seeders: Kategori
        $kategoris = [
            [1, 'Kostum Tari', 'kostum-tari', 'Busana tari tradisional daerah nusantara'],
            [2, 'Baju Pengantin Adat', 'baju-pengantin-adat', 'Pakaian pengantin pernikahan adat nusantara'],
            [3, 'Kebaya & Wisuda', 'kebaya-wisuda', 'Kebaya modern dan busana formal wisuda'],
            [4, 'Aksesoris', 'aksesoris', 'Mahkota, siger, bros, selendang, dan perhiasan'],
        ];
        foreach ($kategoris as $kt) {
            $pdo->exec("INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `slug`, `deskripsi`) 
                VALUES ({$kt[0]}, '{$kt[1]}', '{$kt[2]}', '{$kt[3]}')
                ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);");
        }

        // Seeders: Produk
        $produks = [
            [1, 'KT-JAI-01', 1, 'Busana Tari Jaipong Sutra', 'Grade A (Sutra)', 150000.00, 25000.00, 8, 8, 0, 0, 0, 'tersedia'],
            [2, 'KT-SMN-02', 1, 'Kostum Tari Saman Aceh', 'Grade B (Lengkap)', 200000.00, 30000.00, 10, 10, 0, 0, 0, 'tersedia'],
            [3, 'AK-SGR-01', 4, 'Mahkota Siger Sunda & Bros', 'Kuningan Tebal', 120000.00, 20000.00, 3, 3, 0, 0, 0, 'tersedia'],
            [4, 'KT-PRG-03', 1, 'Tari Piring Minangkabau', 'Grade A (Songket)', 175000.00, 25000.00, 6, 6, 0, 0, 0, 'tersedia'],
            [5, 'KT-GBY-04', 1, 'Kostum Tari Gambyong', 'Grade A (Kemben Jarik)', 160000.00, 25000.00, 5, 5, 0, 0, 0, 'tersedia'],
            [6, 'PA-JWS-01', 2, 'Beskap Jawa Solo Pria', 'Grade A (Jarik & Keris)', 250000.00, 35000.00, 4, 4, 0, 0, 0, 'tersedia'],
            [7, 'KT-JAI-02', 1, 'Baju Tari Jaipong Daun Asih (Atasan)', 'Grade A', 60000.00, 15000.00, 15, 13, 2, 0, 0, 'tersedia'],
            [8, 'KT-PRD-01', 1, 'Kain Sinjang Jaipong Prada Emas', 'Grade A', 30000.00, 10000.00, 20, 18, 2, 0, 0, 'tersedia'],
            [9, 'AK-KMB-02', 4, 'Aksesoris Siger Sunda & Kembang Goyang', 'Standar', 20000.00, 5000.00, 10, 8, 2, 0, 0, 'tersedia'],
            [10, 'PK-SLD-01', 1, 'Paket Kostum Tambahan & Selendang', 'Lengkap', 265000.00, 35000.00, 5, 3, 2, 0, 0, 'tersedia'],
        ];
        foreach ($produks as $pr) {
            $pdo->exec("INSERT INTO `produk` (`id_produk`, `kode_produk`, `id_kategori`, `nama_produk`, `kualitas`, `harga_sewa`, `denda_per_hari`, `stok_total`, `stok_siap`, `stok_disewa`, `stok_cuci`, `stok_rusak`, `status`)
                VALUES ({$pr[0]}, '{$pr[1]}', {$pr[2]}, '{$pr[3]}', '{$pr[4]}', {$pr[5]}, {$pr[6]}, {$pr[7]}, {$pr[8]}, {$pr[9]}, {$pr[10]}, {$pr[11]}, '{$pr[12]}')
                ON DUPLICATE KEY UPDATE `nama_produk` = VALUES(`nama_produk`), `harga_sewa` = VALUES(`harga_sewa`);");
        }

        // Seeders: Pelanggan
        $pelanggans = [
            [1, 'CUST-001', 'Siti Nurhaliza', '0812-9988-7711', 1, 'Kec. Purwakarta', 'Nagri Kidul', 'Jln. Veteran No. 45', 3],
            [2, 'CUST-002', 'Cordelia', '0813-8822-4411', 1, 'Kec. Purwakarta', 'Cipaisan', 'Perum Cihideung Indah Blok B2', 2],
            [3, 'CUST-003', 'Rina Marlina', '0857-1122-3344', 2, 'Kec. Babakancikao', 'Maracang', 'Jl. Raya Babakancikao No. 12', 1],
            [4, 'CUST-004', 'Dedi Mulyadi', '0819-5566-7788', 3, 'Kec. Jatiluhur', 'Bunder', 'Komp. Jatiluhur Asri Kav. 8', 1],
            [5, 'CUST-005', 'Novi Rahmawati', '0821-3344-5566', 4, 'Kec. Campaka', 'Campaka', 'Jl. Raya Subang - Purwakarta', 1],
        ];
        foreach ($pelanggans as $pl) {
            $pdo->exec("INSERT INTO `pelanggan` (`id_pelanggan`, `kode_pelanggan`, `nama_pelanggan`, `no_telepon`, `id_kecamatan`, `nama_kecamatan`, `kelurahan_desa`, `alamat_lengkap`, `total_transaksi`)
                VALUES ({$pl[0]}, '{$pl[1]}', '{$pl[2]}', '{$pl[3]}', {$pl[4]}, '{$pl[5]}', '{$pl[6]}', '{$pl[7]}', {$pl[8]})
                ON DUPLICATE KEY UPDATE `nama_pelanggan` = VALUES(`nama_pelanggan`);");
        }

        // Seeders: Booking & Detail
        $pdo->exec("INSERT INTO `booking` (`id_booking`, `no_nota`, `no_booking`, `id_pelanggan`, `id_user`, `tgl_transaksi`, `tgl_ambil`, `tgl_pakai`, `tgl_kembali_rencana`, `durasi_sewa_hari`, `subtotal_produk`, `total_tagihan`, `opsi_pembayaran`, `persentase_dp`, `total_dp`, `total_bayar`, `sisa_tagihan`, `metode_bayar_terakhir`, `status_pembayaran`, `status_sewa`)
            VALUES 
            (1, 'CS/01/261116/0001', 1, 2, 2, '2026-11-16 10:50:00', '2026-11-16', '2026-11-17', '2026-11-20', 3, 375000.00, 375000.00, 'lunas', 25.00, 93750.00, 375000.00, 0.00, 'BANK', 'LUNAS', 'diambil'),
            (2, 'CS/01/2', 2, 2, 2, '2026-11-16 10:50:00', '2026-11-18', '2026-11-19', '2026-11-22', 3, 375000.00, 375000.00, 'dp', 25.00, 93750.00, 93750.00, 281250.00, 'QRIS', 'DP', 'booking'),
            (3, 'CS/01/261110/0003', 3, 1, 1, '2026-11-10 09:30:00', '2026-11-11', '2026-11-12', '2026-11-15', 3, 200000.00, 200000.00, 'lunas', 25.00, 50000.00, 200000.00, 0.00, 'TUNAI', 'LUNAS', 'selesai'),
            (4, 'CS/01/261112/0004', 4, 3, 2, '2026-11-12 14:20:00', '2026-11-13', '2026-11-14', '2026-11-17', 3, 250000.00, 250000.00, 'lunas', 25.00, 62500.00, 250000.00, 0.00, 'BANK', 'LUNAS', 'selesai'),
            (5, 'CS/01/261115/0005', 5, 4, 1, '2026-11-15 16:45:00', '2026-11-17', '2026-11-18', '2026-11-21', 3, 175000.00, 175000.00, 'dp', 25.00, 43750.00, 43750.00, 131250.00, 'QRIS', 'DP', 'booking')
            ON DUPLICATE KEY UPDATE `status_pembayaran` = VALUES(`status_pembayaran`), `total_bayar` = VALUES(`total_bayar`);");

        $pdo->exec("INSERT INTO `detail_booking` (`id_detail`, `id_booking`, `id_produk`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
            (1, 1, 7, 1, 60000.00, 60000.00),
            (2, 1, 8, 1, 30000.00, 30000.00),
            (3, 1, 9, 1, 20000.00, 20000.00),
            (4, 1, 10, 1, 265000.00, 265000.00),
            (5, 2, 7, 1, 60000.00, 60000.00),
            (6, 2, 8, 1, 30000.00, 30000.00),
            (7, 2, 9, 1, 20000.00, 20000.00),
            (8, 2, 10, 1, 265000.00, 265000.00),
            (9, 3, 2, 1, 200000.00, 20000.00),
            (10, 4, 6, 1, 250000.00, 250000.00),
            (11, 5, 4, 1, 175000.00, 175000.00)
            ON DUPLICATE KEY UPDATE `subtotal` = VALUES(`subtotal`);");

        // Seeders: Pembayaran
        $pdo->exec("INSERT INTO `pembayaran_transaksi` (`id_pembayaran`, `no_bukti_bayar`, `id_booking`, `tgl_bayar`, `jenis_pembayaran`, `jumlah_bayar`, `uang_diterima`, `kembalian`, `metode_bayar`, `id_user`, `keterangan`) VALUES
            (1, 'PAY-261116-0001', 1, '2026-11-16 10:50:00', 'DP', 93750.00, 100000.00, 6250.00, 'BANK', 2, 'DP 25% awal'),
            (2, 'PAY-261116-0002', 1, '2026-11-16 14:13:00', 'PELUNASAN', 281250.00, 300000.00, 18750.00, 'TUNAI', 1, 'Pelunasan 100% tunai di sanggar'),
            (3, 'PAY-261116-0003', 2, '2026-11-16 10:50:00', 'DP', 93750.00, 93750.00, 0.00, 'QRIS', 2, 'Pembayaran DP 25% via QRIS'),
            (4, 'PAY-261110-0004', 3, '2026-11-10 09:30:00', 'PELUNASAN', 200000.00, 200000.00, 0.00, 'TUNAI', 1, 'Pembayaran Lunas Tunai'),
            (5, 'PAY-261112-0005', 4, '2026-11-12 14:20:00', 'PELUNASAN', 250000.00, 250000.00, 0.00, 'BANK', 2, 'Transfer Bank Mandiri'),
            (6, 'PAY-261115-0006', 5, '2026-11-15 16:45:00', 'DP', 43750.00, 43750.00, 0.00, 'QRIS', 1, 'Pembayaran DP 25% QRIS')
            ON DUPLICATE KEY UPDATE `jumlah_bayar` = VALUES(`jumlah_bayar`);");

        // Seeders: Laporan Pemasukan
        $pdo->exec("INSERT INTO `laporan_pemasukan` (`id_pemasukan`, `no_transaksi`, `id_booking`, `tanggal`, `kategori_pemasukan`, `nominal`, `metode_pembayaran`, `id_user`, `keterangan`) VALUES
            (1, 'BKK-00443', 1, '2026-11-19', 'Sewa Busana', 100000.00, 'BANK', 2, 'Penyewaan busana tari jaipong'),
            (2, 'BKK-00444', 1, '2026-11-16', 'Sewa Busana', 281250.00, 'TUNAI', 1, 'Pelunasan sewa busana jaipong'),
            (3, 'BKK-00445', 3, '2026-11-10', 'Sewa Busana', 200000.00, 'TUNAI', 1, 'Sewa kostum Saman Aceh'),
            (4, 'BKK-00446', 4, '2026-11-12', 'Sewa Busana', 250000.00, 'BANK', 2, 'Sewa beskap pengantin Solo'),
            (5, 'BKK-00447', 5, '2026-11-15', 'Sewa Busana', 43750.00, 'QRIS', 1, 'DP sewa kostum tari piring')
            ON DUPLICATE KEY UPDATE `nominal` = VALUES(`nominal`);");

        // Seeders: Laporan Pengeluaran
        $pdo->exec("INSERT INTO `laporan_pengeluaran` (`id_pengeluaran`, `no_transaksi`, `tanggal`, `kategori_beban`, `nominal`, `metode_pembayaran`, `pic_staf`, `keterangan`) VALUES
            (1, 'BKK-00214', '2026-11-10', 'Laundry & Perawatan', 150000.00, 'TUNAI', 'Ahmad Rivaldy', 'Laundry kilat 10 paket busana tari pasca acara'),
            (2, 'BKK-00215', '2026-11-12', 'Gaji Staf & Asisten Rias', 350000.00, 'TUNAI', 'Ivan', 'Honor kru penata rias wisuda'),
            (3, 'BKK-00216', '2026-11-15', 'Pengadaan Stok Busana Baru', 500000.00, 'BANK', 'Amanah Solusi', 'Beli kain prada emas & aksesoris bros siger'),
            (4, 'EXP-00128', '2026-11-18', 'Operasional & Listrik', 850000.00, 'TUNAI', 'Ivan', 'Tagihan listrik sanggar dan air PDAM')
            ON DUPLICATE KEY UPDATE `nominal` = VALUES(`nominal`);");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "Database $dbName synced successfully!\n";
    } catch (Exception $e) {
        echo "Error on $dbName: " . $e->getMessage() . "\n";
    }
}
