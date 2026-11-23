-- =============================================================================
-- DATABASE SCHEMA: SANGGARA MANAGEMENT SYSTEM
-- Sistem Manajemen Penyewaan Busana & Rias Pengantin (Sanggar Bunda Febri - SBF)
-- Purwakarta, Jawa Barat
-- File: database/sanggara_database.sql
-- Engine: MySQL 8.0+ / MariaDB 10.4+ (XAMPP, Laragon, phpMyAdmin, DBeaver)
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `sanggara_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `sanggara_db`;

-- Matikan foreign key check sementara selama proses pembuatan ulang tabel
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `pembayaran_transaksi`;
DROP TABLE IF EXISTS `detail_booking`;
DROP TABLE IF EXISTS `booking`;
DROP TABLE IF EXISTS `laporan_pemasukan`;
DROP TABLE IF EXISTS `laporan_pengeluaran`;
DROP TABLE IF EXISTS `pelanggan`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `kecamatan`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. TABEL: users (Pengguna, Kasir, & Administrator)
-- =============================================================================
CREATE TABLE `users` (
  `id_user` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash password',
  `role` ENUM('admin', 'kasir', 'pemilik') NOT NULL DEFAULT 'kasir',
  `no_telepon` VARCHAR(20) NULL,
  `foto_profil` VARCHAR(255) NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. TABEL: kategori (Kategori Busana, Kostum, & Aksesoris)
-- =============================================================================
CREATE TABLE `kategori` (
  `id_kategori` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(60) NOT NULL,
  `slug` VARCHAR(80) NOT NULL UNIQUE,
  `deskripsi` VARCHAR(100) NULL COMMENT 'Maks 100 karakter sesuai form input modal',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_kategori_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. TABEL: produk (Katalog Busana, Kostum Tari, Gaun Adat, & Aksesoris)
-- =============================================================================
CREATE TABLE `produk` (
  `id_produk` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_produk` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Contoh: PRD-001, BJU-JAI-01',
  `id_kategori` INT UNSIGNED NOT NULL,
  `nama_produk` VARCHAR(150) NOT NULL,
  `kualitas` VARCHAR(60) NOT NULL DEFAULT 'Grade A' COMMENT 'Contoh: Grade A (Sutra), Grade B (Lengkap), Kuningan Tebal',
  `harga_sewa` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Tarif sewa per hari / per pemakaian',
  `denda_per_hari` DECIMAL(12,2) NOT NULL DEFAULT 25000.00 COMMENT 'Denda per hari jika terlambat dikembalikan',
  `stok_total` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Total unit busana fisik yang dimiliki sanggar',
  `stok_siap` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Unit yang siap disewakan',
  `stok_disewa` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unit yang sedang dibawa customer',
  `stok_cuci` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unit yang sedang di laundry / dibersihkan',
  `stok_rusak` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unit yang sedang dalam perbaikan / reparasi',
  `foto_produk` VARCHAR(255) NULL,
  `deskripsi` TEXT NULL,
  `status` ENUM('tersedia', 'habis', 'perawatan', 'nonaktif') NOT NULL DEFAULT 'tersedia',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`)
    REFERENCES `kategori` (`id_kategori`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_produk_kategori` (`id_kategori`),
  INDEX `idx_produk_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. TABEL: kecamatan (17 Kecamatan Purwakarta untuk Analisa Pasar & Wilayah)
-- =============================================================================
CREATE TABLE `kecamatan` (
  `id_kecamatan` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_kecamatan` VARCHAR(50) NOT NULL UNIQUE,
  `zona_wilayah` ENUM('Pusat Kota', 'Industri & Utara', 'Selatan Pegunungan', 'Danau & Wisata') NOT NULL DEFAULT 'Pusat Kota',
  `target_pasar` INT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Target booking per tahun',
  INDEX `idx_kecamatan_zona` (`zona_wilayah`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. TABEL: pelanggan (Master Data Pelanggan / Konsumen)
-- =============================================================================
CREATE TABLE `pelanggan` (
  `id_pelanggan` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_pelanggan` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Contoh: CUST-001',
  `nama_pelanggan` VARCHAR(100) NOT NULL,
  `no_telepon` VARCHAR(25) NOT NULL,
  `id_kecamatan` INT UNSIGNED NULL,
  `nama_kecamatan` VARCHAR(50) NULL COMMENT 'Denormalisasi untuk pencarian cepat',
  `kelurahan_desa` VARCHAR(60) NULL,
  `alamat_lengkap` TEXT NULL,
  `total_transaksi` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pelanggan_kecamatan` FOREIGN KEY (`id_kecamatan`)
    REFERENCES `kecamatan` (`id_kecamatan`)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  INDEX `idx_pelanggan_telepon` (`no_telepon`),
  INDEX `idx_pelanggan_kecamatan` (`id_kecamatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. TABEL: booking (Transaksi Sewa, Nota Kasir, & Jadwal Kalender)
-- =============================================================================
CREATE TABLE `booking` (
  `id_booking` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `no_nota` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Contoh: CS/01/261116/0001 atau CS/01/2',
  `no_booking` INT UNSIGNED NOT NULL COMMENT 'Nomor urut booking di nota kasir',
  `id_pelanggan` INT UNSIGNED NOT NULL,
  `id_user` INT UNSIGNED NOT NULL COMMENT 'Kasir yang memproses pesanan',
  `tgl_transaksi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  -- Agenda Kalender Sewa (Ambil [Hijau], Pakai [Biru], Kembali [Kuning])
  `tgl_ambil` DATE NOT NULL COMMENT 'Tanggal customer mengambil baju di sanggar',
  `tgl_pakai` DATE NOT NULL COMMENT 'Tanggal acara / hari H kostum digunakan',
  `tgl_kembali_rencana` DATE NOT NULL COMMENT 'Batas waktu kembali (Maksimal H+3 pemakaian)',
  `tgl_kembali_aktual` DATE NULL COMMENT 'Tanggal aktual barang dikembalikan customer',
  `durasi_sewa_hari` INT UNSIGNED NOT NULL DEFAULT 3,
  
  -- Finansial & Kalkulasi Kasir
  `subtotal_produk` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_tagihan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `opsi_pembayaran` ENUM('lunas', 'dp') NOT NULL DEFAULT 'lunas',
  `persentase_dp` DECIMAL(5,2) NOT NULL DEFAULT 25.00 COMMENT 'Minimal DP 25%',
  `total_dp` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai minimal DP yang wajib dibayar',
  `total_bayar` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Jumlah uang yang sudah dibayarkan saat ini',
  `sisa_tagihan` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Sisa pelunasan (0 jika lunas)',
  `metode_bayar_terakhir` ENUM('TUNAI', 'BANK', 'QRIS') NOT NULL DEFAULT 'TUNAI',
  
  -- Status
  `status_pembayaran` ENUM('DP', 'LUNAS', 'BATAL') NOT NULL DEFAULT 'DP',
  `status_sewa` ENUM('booking', 'diambil', 'dipakai', 'dikembalikan', 'selesai', 'batal') NOT NULL DEFAULT 'booking',
  `denda_keterlambatan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `denda_kerusakan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_denda` DECIMAL(12,2) GENERATED ALWAYS AS (`denda_keterlambatan` + `denda_kerusakan`) STORED,
  
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT `fk_booking_pelanggan` FOREIGN KEY (`id_pelanggan`)
    REFERENCES `pelanggan` (`id_pelanggan`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`id_user`)
    REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_booking_nota` (`no_nota`),
  INDEX `idx_booking_status_bayar` (`status_pembayaran`),
  INDEX `idx_booking_status_sewa` (`status_sewa`),
  INDEX `idx_booking_jadwal` (`tgl_ambil`, `tgl_pakai`, `tgl_kembali_rencana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. TABEL: detail_booking (Rincian Item Pakaian / Aksesoris yang Disewa)
-- =============================================================================
CREATE TABLE `detail_booking` (
  `id_detail` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_booking` INT UNSIGNED NOT NULL,
  `id_produk` INT UNSIGNED NOT NULL,
  `jumlah` INT UNSIGNED NOT NULL DEFAULT 1,
  `harga_satuan` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `kondisi_ambil` VARCHAR(60) NOT NULL DEFAULT 'Baik (Bersih)',
  `kondisi_kembali` VARCHAR(60) NULL DEFAULT 'Belum Kembali',
  CONSTRAINT `fk_detail_booking` FOREIGN KEY (`id_booking`)
    REFERENCES `booking` (`id_booking`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`)
    REFERENCES `produk` (`id_produk`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_detail_booking` (`id_booking`),
  INDEX `idx_detail_produk` (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. TABEL: pembayaran_transaksi (Riwayat Pelunasan, Kasir, Kembalian)
-- =============================================================================
CREATE TABLE `pembayaran_transaksi` (
  `id_pembayaran` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `no_bukti_bayar` VARCHAR(50) NOT NULL UNIQUE,
  `id_booking` INT UNSIGNED NOT NULL,
  `tgl_bayar` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jenis_pembayaran` ENUM('DP', 'PELUNASAN', 'LUNAS_LANGSUNG', 'DENDA') NOT NULL,
  `jumlah_bayar` DECIMAL(12,2) NOT NULL COMMENT 'Nominal yang memotong tagihan',
  `uang_diterima` DECIMAL(12,2) NOT NULL COMMENT 'Nominal uang fisik/transfer dari customer',
  `kembalian` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `metode_bayar` ENUM('TUNAI', 'BANK', 'QRIS') NOT NULL DEFAULT 'TUNAI',
  `id_user` INT UNSIGNED NOT NULL COMMENT 'Kasir yang menerima uang',
  `keterangan` VARCHAR(255) NULL,
  CONSTRAINT `fk_pembayaran_booking` FOREIGN KEY (`id_booking`)
    REFERENCES `booking` (`id_booking`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `fk_pembayaran_user` FOREIGN KEY (`id_user`)
    REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_pembayaran_booking` (`id_booking`),
  INDEX `idx_pembayaran_tgl` (`tgl_bayar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. TABEL: laporan_pemasukan (Buku Mutasi Kas Masuk)
-- =============================================================================
CREATE TABLE `laporan_pemasukan` (
  `id_pemasukan` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `no_transaksi` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Contoh: BKK-00443',
  `id_booking` INT UNSIGNED NULL COMMENT 'Relasi jika bersumber dari penyewaan',
  `tanggal` DATE NOT NULL,
  `kategori_pemasukan` VARCHAR(60) NOT NULL DEFAULT 'Sewa Busana',
  `nominal` DECIMAL(12,2) NOT NULL,
  `metode_pembayaran` ENUM('TUNAI', 'BANK', 'QRIS') NOT NULL,
  `id_user` INT UNSIGNED NOT NULL COMMENT 'Kasir pencatat mutasi',
  `keterangan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pemasukan_booking` FOREIGN KEY (`id_booking`)
    REFERENCES `booking` (`id_booking`)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT `fk_pemasukan_user` FOREIGN KEY (`id_user`)
    REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_pemasukan_tgl` (`tanggal`),
  INDEX `idx_pemasukan_no_trx` (`no_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 10. TABEL: laporan_pengeluaran (Buku Mutasi Beban Operasional Sanggar)
-- =============================================================================
CREATE TABLE `laporan_pengeluaran` (
  `id_pengeluaran` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `no_transaksi` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Contoh: BKK-00214',
  `tanggal` DATE NOT NULL,
  `kategori_beban` VARCHAR(60) NOT NULL COMMENT 'Laundry (38%), Gaji Staf (34%), Stok Busana Baru (18%), Perawatan, Operasional',
  `nominal` DECIMAL(12,2) NOT NULL,
  `metode_pembayaran` ENUM('TUNAI', 'BANK') NOT NULL DEFAULT 'TUNAI',
  `pic_staf` VARCHAR(100) NOT NULL COMMENT 'Nama staf penanggung jawab pengeluaran',
  `bukti_struk` VARCHAR(255) NULL COMMENT 'Foto/Scan kwitansi pembayaran',
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pengeluaran_tgl` (`tanggal`),
  INDEX `idx_pengeluaran_kategori` (`kategori_beban`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- 11. DATABASE VIEWS (Mempermudah Query Dashboard & Analisa Laporan)
-- =============================================================================

-- View A: Ringkasan Arus Kas Bulanan (Untuk Bar Chart Jan - Des di Dashboard & Analisa Keuangan)
CREATE OR REPLACE VIEW `v_arus_kas_bulanan` AS
SELECT 
  DATE_FORMAT(tgl_gabung, '%Y-%m') AS `periode`,
  YEAR(tgl_gabung) AS `tahun`,
  MONTH(tgl_gabung) AS `bulan`,
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
ORDER BY `periode` ASC;

-- View B: Top Ranking Kecamatan Purwakarta (Untuk Progres Bar Dashboard & Analisa Pelanggan)
CREATE OR REPLACE VIEW `v_ranking_kecamatan` AS
SELECT 
  k.id_kecamatan,
  k.nama_kecamatan,
  k.zona_wilayah,
  COUNT(b.id_booking) AS `total_booking`,
  COALESCE(SUM(b.total_tagihan), 0) AS `total_omset`,
  ROUND((COUNT(b.id_booking) / (SELECT GREATEST(COUNT(*), 1) FROM booking) * 100), 1) AS `persentase_pasar`
FROM kecamatan k
LEFT JOIN pelanggan p ON k.id_kecamatan = p.id_kecamatan
LEFT JOIN booking b ON p.id_pelanggan = b.id_pelanggan
GROUP BY k.id_kecamatan, k.nama_kecamatan, k.zona_wilayah
ORDER BY `total_booking` DESC;

-- View C: Ringkasan Kondisi Stok Inventaris (Untuk Status Bar di Dashboard)
CREATE OR REPLACE VIEW `v_status_inventaris` AS
SELECT 
  COALESCE(SUM(stok_total), 0) AS `total_unit`,
  COALESCE(SUM(stok_siap), 0) AS `siap_sewa_unit`,
  COALESCE(SUM(stok_disewa), 0) AS `sedang_disewa_unit`,
  COALESCE(SUM(stok_cuci), 0) AS `sedang_dicuci_unit`,
  COALESCE(SUM(stok_rusak), 0) AS `perbaikan_unit`,
  ROUND((SUM(stok_siap) / GREATEST(SUM(stok_total), 1) * 100), 1) AS `persen_siap_sewa`,
  ROUND((SUM(stok_disewa) / GREATEST(SUM(stok_total), 1) * 100), 1) AS `persen_sedang_disewa`
FROM produk
WHERE status != 'nonaktif';

-- View D: Data Nota Transaksi Kasir (Sesuai Tampilan Tabel di pages/data-nota.html)
CREATE OR REPLACE VIEW `v_data_nota_kasir` AS
SELECT 
  b.id_booking,
  b.no_nota,
  b.no_booking,
  DATE_FORMAT(b.tgl_transaksi, '%d %b %y %H:%i') AS `waktu`,
  UPPER(u.nama_lengkap) AS `kasir`,
  UPPER(p.nama_pelanggan) AS `customer`,
  b.total_dp,
  b.total_tagihan,
  b.total_bayar AS `bayar`,
  b.sisa_tagihan,
  b.metode_bayar_terakhir AS `metode_bayar`,
  b.status_pembayaran AS `status`,
  b.status_sewa
FROM booking b
JOIN users u ON b.id_user = u.id_user
JOIN pelanggan p ON b.id_pelanggan = p.id_pelanggan
ORDER BY b.id_booking ASC;


-- =============================================================================
-- 12. DATA AWAL / SEEDER DUMMY REALISTIS (SEBELUM TRIGGER AKTIF)
-- =============================================================================

-- A. User Kasir & Admin
INSERT INTO `users` (`id_user`, `nama_lengkap`, `email`, `password`, `role`, `no_telepon`, `status`) VALUES
(1, 'Ahmad Rivaldy', 'ahmad.rivaldy@sanggara.id', '$2y$10$e8wFvQ4T71gQ.1B/7lS9xe.5614VbMvN8N9K8U.01kLk37e2Q1X2.', 'kasir', '081802238821', 'aktif'),
(2, 'Ivan', 'ivan@sanggara.id', '$2y$10$e8wFvQ4T71gQ.1B/7lS9xe.5614VbMvN8N9K8U.01kLk37e2Q1X2.', 'kasir', '081234567890', 'aktif'),
(3, 'Amanah Solusi', 'muhamadsolehs871@gmail.com', '$2y$10$e8wFvQ4T71gQ.1B/7lS9xe.5614VbMvN8N9K8U.01kLk37e2Q1X2.', 'admin', '081987654321', 'aktif');

-- B. Kategori
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `slug`, `deskripsi`) VALUES
(1, 'Kostum Tari', 'kostum-tari', 'Busana tari tradisional daerah nusantara'),
(2, 'Baju Pengantin Adat', 'baju-pengantin-adat', 'Pakaian pengantin pernikahan adat nusantara'),
(3, 'Kebaya & Wisuda', 'kebaya-wisuda', 'Kebaya modern dan busana formal wisuda'),
(4, 'Aksesoris', 'aksesoris', 'Mahkota, siger, bros, selendang, dan perhiasan');

-- C. Produk (Identik dengan Menu Kasir & Struk Nota)
INSERT INTO `produk` (`id_produk`, `kode_produk`, `id_kategori`, `nama_produk`, `kualitas`, `harga_sewa`, `denda_per_hari`, `stok_total`, `stok_siap`, `stok_disewa`, `stok_cuci`, `stok_rusak`, `status`) VALUES
(1, 'KT-JAI-01', 1, 'Busana Tari Jaipong Sutra', 'Grade A (Sutra)', 150000.00, 25000.00, 8, 8, 0, 0, 0, 'tersedia'),
(2, 'KT-SMN-02', 1, 'Kostum Tari Saman Aceh', 'Grade B (Lengkap)', 200000.00, 30000.00, 10, 10, 0, 0, 0, 'tersedia'),
(3, 'AK-SGR-01', 4, 'Mahkota Siger Sunda & Bros', 'Kuningan Tebal', 120000.00, 20000.00, 3, 3, 0, 0, 0, 'tersedia'),
(4, 'KT-PRG-03', 1, 'Tari Piring Minangkabau', 'Grade A (Songket)', 175000.00, 25000.00, 6, 6, 0, 0, 0, 'tersedia'),
(5, 'KT-GBY-04', 1, 'Kostum Tari Gambyong', 'Grade A (Kemben Jarik)', 160000.00, 25000.00, 5, 5, 0, 0, 0, 'tersedia'),
(6, 'PA-JWS-01', 2, 'Beskap Jawa Solo Pria', 'Grade A (Jarik & Keris)', 250000.00, 35000.00, 4, 4, 0, 0, 0, 'tersedia'),
(7, 'KT-JAI-02', 1, 'Baju Tari Jaipong Daun Asih (Atasan)', 'Grade A', 60000.00, 15000.00, 15, 13, 2, 0, 0, 'tersedia'),
(8, 'KT-PRD-01', 1, 'Kain Sinjang Jaipong Prada Emas', 'Grade A', 30000.00, 10000.00, 20, 18, 2, 0, 0, 'tersedia'),
(9, 'AK-KMB-02', 4, 'Aksesoris Siger Sunda & Kembang Goyang', 'Standar', 20000.00, 5000.00, 10, 8, 2, 0, 0, 'tersedia'),
(10, 'PK-SLD-01', 1, 'Paket Kostum Tambahan & Selendang', 'Lengkap', 265000.00, 35000.00, 5, 3, 2, 0, 0, 'tersedia');

-- D. 17 Kecamatan Purwakarta
INSERT INTO `kecamatan` (`id_kecamatan`, `nama_kecamatan`, `zona_wilayah`, `target_pasar`) VALUES
(1, 'Kec. Purwakarta', 'Pusat Kota', 200),
(2, 'Kec. Babakancikao', 'Industri & Utara', 120),
(3, 'Kec. Jatiluhur', 'Danau & Wisata', 90),
(4, 'Kec. Campaka', 'Industri & Utara', 80),
(5, 'Kec. Bungursari', 'Industri & Utara', 75),
(6, 'Kec. Pasawahan', 'Pusat Kota', 60),
(7, 'Kec. Sukatani', 'Industri & Utara', 55),
(8, 'Kec. Plered', 'Pusat Kota', 50),
(9, 'Kec. Darangdan', 'Selatan Pegunungan', 45),
(10, 'Kec. Bojong', 'Selatan Pegunungan', 40),
(11, 'Kec. Wanayasa', 'Selatan Pegunungan', 40),
(12, 'Kec. Kiarapedes', 'Selatan Pegunungan', 35),
(13, 'Kec. Cibatu', 'Industri & Utara', 35),
(14, 'Kec. Pondoksalam', 'Pusat Kota', 30),
(15, 'Kec. Tegalwaru', 'Danau & Wisata', 25),
(16, 'Kec. Maniis', 'Danau & Wisata', 20),
(17, 'Kec. Sukasari', 'Danau & Wisata', 20);

-- E. Pelanggan
INSERT INTO `pelanggan` (`id_pelanggan`, `kode_pelanggan`, `nama_pelanggan`, `no_telepon`, `id_kecamatan`, `nama_kecamatan`, `kelurahan_desa`, `alamat_lengkap`, `total_transaksi`) VALUES
(1, 'CUST-001', 'Siti Nurhaliza', '0812-9988-7711', 1, 'Kec. Purwakarta', 'Nagri Kidul', 'Jln. Veteran No. 45', 1),
(2, 'CUST-002', 'Cordelia', '0813-8822-4411', 1, 'Kec. Purwakarta', 'Cipaisan', 'Perum Cihideung Indah Blok B2', 2);

-- F. Transaksi Booking (Sesuai Baris 1 & 2 di Data Nota CS/01/2)
-- Transaksi 1: Lunas (Row 1 di pages/data-nota.html)
INSERT INTO `booking` (
  `id_booking`, `no_nota`, `no_booking`, `id_pelanggan`, `id_user`, `tgl_transaksi`, 
  `tgl_ambil`, `tgl_pakai`, `tgl_kembali_rencana`, `durasi_sewa_hari`,
  `subtotal_produk`, `total_tagihan`, `opsi_pembayaran`, `persentase_dp`, `total_dp`, 
  `total_bayar`, `sisa_tagihan`, `metode_bayar_terakhir`, `status_pembayaran`, `status_sewa`
) VALUES (
  1, 'CS/01/261116/0001', 1, 2, 2, '2026-11-16 10:50:00',
  '2026-11-16', '2026-11-17', '2026-11-20', 3,
  375000.00, 375000.00, 'lunas', 25.00, 93750.00,
  375000.00, 0.00, 'BANK', 'LUNAS', 'diambil'
);

-- Rincian Produk untuk Transaksi 1 (Total = 375.000)
INSERT INTO `detail_booking` (`id_booking`, `id_produk`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 7, 1, 60000.00, 60000.00),   -- Baju Tari Jaipong Daun Asih
(1, 8, 1, 30000.00, 30000.00),   -- Kain Sinjang Jaipong Prada
(1, 9, 1, 20000.00, 20000.00),   -- Aksesoris Siger Sunda & Kembang Goyang
(1, 10, 1, 265000.00, 265000.00); -- Paket Kostum Tambahan & Selendang

-- Riwayat Pembayaran Transaksi 1
INSERT INTO `pembayaran_transaksi` (`no_bukti_bayar`, `id_booking`, `tgl_bayar`, `jenis_pembayaran`, `jumlah_bayar`, `uang_diterima`, `kembalian`, `metode_bayar`, `id_user`, `keterangan`) VALUES
('PAY-261116-0001', 1, '2026-11-16 10:50:00', 'DP', 93750.00, 100000.00, 6250.00, 'BANK', 2, 'DP 25% awal'),
('PAY-261116-0002', 1, '2026-11-16 14:13:00', 'PELUNASAN', 281250.00, 300000.00, 18750.00, 'TUNAI', 1, 'Pelunasan 100% tunai di sanggar');

-- Transaksi 2: DP (Row 2 di pages/data-nota.html)
INSERT INTO `booking` (
  `id_booking`, `no_nota`, `no_booking`, `id_pelanggan`, `id_user`, `tgl_transaksi`, 
  `tgl_ambil`, `tgl_pakai`, `tgl_kembali_rencana`, `durasi_sewa_hari`,
  `subtotal_produk`, `total_tagihan`, `opsi_pembayaran`, `persentase_dp`, `total_dp`, 
  `total_bayar`, `sisa_tagihan`, `metode_bayar_terakhir`, `status_pembayaran`, `status_sewa`
) VALUES (
  2, 'CS/01/2', 2, 2, 2, '2026-11-16 10:50:00',
  '2026-11-18', '2026-11-19', '2026-11-22', 3,
  375000.00, 375000.00, 'dp', 25.00, 93750.00,
  93750.00, 281250.00, 'QRIS', 'DP', 'booking'
);

INSERT INTO `detail_booking` (`id_booking`, `id_produk`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(2, 7, 1, 60000.00, 60000.00),
(2, 8, 1, 30000.00, 30000.00),
(2, 9, 1, 20000.00, 20000.00),
(2, 10, 1, 265000.00, 265000.00);

INSERT INTO `pembayaran_transaksi` (`no_bukti_bayar`, `id_booking`, `tgl_bayar`, `jenis_pembayaran`, `jumlah_bayar`, `uang_diterima`, `kembalian`, `metode_bayar`, `id_user`, `keterangan`) VALUES
('PAY-261116-0003', 2, '2026-11-16 10:50:00', 'DP', 93750.00, 93750.00, 0.00, 'QRIS', 2, 'Pembayaran DP 25% via QRIS');

-- G. Laporan Pemasukan (pages/laporan-pemasukan.html)
INSERT INTO `laporan_pemasukan` (`no_transaksi`, `id_booking`, `tanggal`, `kategori_pemasukan`, `nominal`, `metode_pembayaran`, `id_user`, `keterangan`) VALUES
('BKK-00443', 1, '2026-11-19', 'Sewa Busana', 100000.00, 'BANK', 2, 'Penyewaan busana tari jaipong');

-- H. Laporan Pengeluaran (pages/laporan-pengeluaran.html)
INSERT INTO `laporan_pengeluaran` (`no_transaksi`, `tanggal`, `kategori_beban`, `nominal`, `metode_pembayaran`, `pic_staf`, `keterangan`) VALUES
('BKK-00214', '2026-11-10', 'Laundry & Perawatan', 150000.00, 'TUNAI', 'Ahmad Rivaldy', 'Laundry kilat 10 paket busana tari pasca acara'),
('BKK-00215', '2026-11-12', 'Gaji Staf & Asisten Rias', 350000.00, 'TUNAI', 'Ivan', 'Honor kru penata rias wisuda'),
('BKK-00216', '2026-11-15', 'Pengadaan Stok Busana Baru', 500000.00, 'BANK', 'Amanah Solusi', 'Beli kain prada emas & aksesoris bros siger');


-- =============================================================================
-- 13. TRIGGERS (Dibuat SETELAH Seeder Selesai Agar Tidak Menabrak Seeder)
-- =============================================================================
DELIMITER $$

-- Trigger 1: Otomatis kurangi stok siap sewa saat transaksi baru dibuat di aplikasi
CREATE TRIGGER `trg_after_insert_detail_booking`
AFTER INSERT ON `detail_booking`
FOR EACH ROW
BEGIN
  UPDATE `produk`
  SET 
    `stok_siap` = GREATEST(CAST(`stok_siap` AS SIGNED) - NEW.jumlah, 0),
    `stok_disewa` = `stok_disewa` + NEW.jumlah
  WHERE `id_produk` = NEW.id_produk;
END$$

-- Trigger 2: Otomatis update status booking & rekam kas masuk saat ada pembayaran baru di aplikasi
CREATE TRIGGER `trg_after_insert_pembayaran_transaksi`
AFTER INSERT ON `pembayaran_transaksi`
FOR EACH ROW
BEGIN
  -- Update akumulasi pembayaran pada booking
  UPDATE `booking`
  SET 
    `total_bayar` = `total_bayar` + NEW.jumlah_bayar,
    `sisa_tagihan` = GREATEST(`total_tagihan` - (`total_bayar` + NEW.jumlah_bayar), 0.00),
    `metode_bayar_terakhir` = NEW.metode_bayar,
    `status_pembayaran` = IF((`total_tagihan` - (`total_bayar` + NEW.jumlah_bayar)) <= 0, 'LUNAS', 'DP')
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
END$$

DELIMITER ;

-- Selesai
