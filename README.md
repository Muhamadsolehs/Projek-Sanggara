# Sanggara Management System (Sanggar Bunda Febri - SBF)

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org)
[![CSS3](https://img.shields.io/badge/CSS3-Modular-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org)

**Sanggara Management System** adalah platform sistem informasi operasional berbasis web terpadu yang dikembangkan khusus untuk **Sanggar Bunda Febri (SBF)** di Kabupaten Purwakarta. Sistem ini mengelola seluruh rantai bisnis persewaan busana adat, kebaya wisuda, gaun modern, kostum tari daerah nusantara, hingga aksesoris pernikahan secara terintegrasi dengan backend **Laravel 13 REST API** dan basis data relasional **MySQL 8.0 (`db_sanggara`)**.

---

## 🌟 Fitur & Karakteristik Utama Sistem

1. **Model Non-Stok (*Single Item / Unique Asset*)**
   - Setiap pakaian adat dan kebaya di sanggar merupakan barang fisik bernilai tinggi satuan (`stok_total = 1`, `stok_siap = 1/0`).
   - Sistem menghilangkan input kuantitas grosir dan berfokus pada pelacakan status ketersediaan busana (`tersedia`, `disewa`, `perawatan`, `nonaktif`).

2. **Aturan Siklus Ketat 3 Hari Sewa (Maksimal 1 Hari Pakai)**
   - **Tanggal Ambil (Pickup)**: Wajib H-1 dari acara (contoh: Tanggal 2).
   - **Tanggal Pakai**: Hari-H acara (durasi pemakaian maksimal 1 hari, contoh: Tanggal 3).
   - **Tanggal Kembali (Pickin/Kembali)**: Wajib H+1 dari acara (contoh: Tanggal 4).

3. **Penyimpanan 2 Metode Pembayaran Berbeda (Tanpa Menambah Tabel Baru)**
   - Mendukung pencatatan metode pembayaran berbeda antara DP (misal **Tunai**) dan Pelunasan (misal **Transfer Bank** / **QRIS**).
   - Memanfaatkan relasi audit `pembayaran_transaksi` dan kolom cerdas pada tabel `booking`: `metode_bayar_dp`, `metode_bayar_pelunasan`, serta ringkasan otomatis `metode_bayar_terakhir`.
   - Tabel Data Nota menampilkan badge pil ganda: `[TUNAI (DP)] + [BANK (LUNAS)]`.

4. **Perhitungan Denda Keterlambatan Otomatis 10%**
   - Apabila pakaian sewa belum dikembalikan melewati batas tanggal pengembalian rencana (lewat dari H+1), sistem secara otomatis menerapkan denda penalti sebesar **10% dari total nilai sewa barang**.

5. **Integrasi Geografis Real-Time (API Wilayah Ogan Purwakarta)**
   - Terkoneksi langsung dengan API resmi Pemkab Purwakarta (`https://api.ogan.purwakartakab.go.id`) untuk memuat 17 Kecamatan dan seluruh Kelurahan/Desa secara dinamis (*cascading dropdown*) serta kolom detail Alamat Lengkap/Jalan (RT/RW/No. Rumah).

6. **Format Cetak Struk Nota Kasir Termal SBF (Thermal POS Receipt)**
   - Format cetak standar kertas kasir 80mm/58mm resmi Sanggar Bunda Febri (SBF) lengkap dengan rincian produk sewa, highlight status pembayaran (LUNAS 100% / DP), rincian metode pembayaran ganda, serta klausul kebijakan sanggar.

7. **Laporan Arus Kas & Analisa Finansial**
   - Otomasi pencatatan buku kas masuk dari DP, Pelunasan, dan Denda.
   - Pencatatan beban pengeluaran (laundry/dry cleaning, perawatan payet/jahit, utilitas, staf).
   - Dashboard KPI pendapatan, tren grafik bulanan, dan ranking sewa per kecamatan.

---

## 📁 Struktur Direktori Proyek

```text
sanggara_project/
├── backend/                             # Backend Framework Laravel 13 (REST API)
│   ├── app/
│   │   ├── Http/Controllers/Api/        # API Controllers (Kasir, DataNota, Produk, Pelanggan, dll)
│   │   └── Models/                      # Eloquent ORM Models (Booking, Produk, Pelanggan, dll)
│   ├── routes/
│   │   └── api.php                      # Endpoint RESTful API Sanggara
│   ├── database/                        # Migrasi, Seeder, & Konfigurasi DB
│   └── public/                          # Server Static Web Assets
├── css/                                 # Styling CSS Modular Berbasis Desain Sanggara
│   ├── global.css                       # Design System Tokens, Variables, Layout & Typography
│   ├── kasir.css                        # UI Menu Kasir (POS) & Keranjang Interaktif
│   ├── data-nota.css                    # UI Tabel Data Nota & Dual-Pill Metode Bayar
│   ├── nota.css                         # UI & Print Styling Struk Termal Kasir SBF
│   ├── dashboard.css                    # UI Dashboard Utama & Metrik KPI
│   ├── kelola-produk.css                # UI Master Busana & Filter Kategori
│   ├── kelola-pelanggan.css             # UI Master Pelanggan & Form API Ogan
│   └── ...                              # CSS Modular Lainnya
├── js/                                  # Logika Client-Side & API Adapter
│   ├── api.js                           # SanggaraAPI Client (Fetch API ke Laravel Backend)
│   └── global.js                        # Handler Navigasi, Sidebar, & State Persisten
├── pages/                               # Antarmuka Pengguna (HTML5 Semantik)
│   ├── dashboard.html                   # Dashboard Analisa & Metrik
│   ├── kasir.html                       # Kasir Persewaan & Booking (POS)
│   ├── data-nota.html                   # Rekapitulasi Nota & Modal Pelunasan
│   ├── nota.html                        # Lembar Struk Nota Termal Siap Cetak
│   ├── kelola-produk.html               # Manajemen Katalog Busana
│   ├── kelola-pelanggan.html            # Manajemen Konsumen & Wilayah
│   ├── kelola-kategori.html             # Manajemen Kategori Busana
│   ├── laporan.html                     # Rekap Laporan Pemasukan & Pengeluaran
│   └── login.html                       # Otentikasi Staf / Kasir
├── database/                            # Backup Skema SQL
│   └── sanggara_database.sql            # DDL, Trigger, & Seeder MySQL db_sanggara
├── DOKUMENTASI_SISTEM_SANGGARA.pdf      # Dokumentasi Lengkap Sistem (Format PDF Cetak A4)
├── DOKUMENTASI_SISTEM_SANGGARA.html     # Dokumentasi Lengkap Sistem (Format Dokumen Web)
├── DOKUMENTASI_SISTEM_SANGGARA.md       # Dokumentasi Lengkap Sistem (Format Markdown)
├── index.html                           # Portal Navigasi Halaman
└── README.md                            # Panduan & Dokumentasi Proyek Ini
```

---

## 🗄️ Arsitektur Skema Basis Data (`db_sanggara`)

Sistem menggunakan basis data relasional **MySQL 8.0** dengan skema utama:

* **`users`**: Akun pengguna sistem (Role: `admin`, `kasir`, `pemilik`).
* **`kategori`**: Kategori busana (*Kostum Tari*, *Baju Pengantin Adat*, *Kebaya & Wisuda*, *Aksesoris*).
* **`produk`**: Master aset pakaian (Model non-stok: `stok_total = 1`, `stok_siap = 1/0`, tarif sewa, denda/hari).
* **`pelanggan`**: Master data penyewa (terintegrasi API Ogan Purwakarta: kecamatan, kelurahan, alamat jalan).
* **`booking`**: Master transaksi sewa (`no_nota`, `tgl_ambil`, `tgl_pakai`, `tgl_kembali_rencana`, `total_tagihan`, `total_dp`, `metode_bayar_dp`, `metode_bayar_pelunasan`, `metode_bayar_terakhir`, `status_pembayaran`, `status_sewa`, `denda_keterlambatan`).
* **`detail_booking`**: Rincian busana yang disewa dalam tiap faktur booking.
* **`pembayaran_transaksi`**: Buku kas dan log audit pembayaran (`DP`, `PELUNASAN`, `LUNAS_LANGSUNG`, `DENDA`) beserta instrumen bayar (`TUNAI`, `BANK`, `QRIS`), uang diterima, dan kembalian.
* **`laporan_pemasukan` & `laporan_pengeluaran`**: Pembukuan arus kas sanggar.

### Otomasi Database Trigger (MySQL Engine)
1. **`trg_after_insert_pembayaran_transaksi`**: Otomatis memperbarui `total_bayar`, menghitung `sisa_tagihan`, mengisi `metode_bayar_dp` / `metode_bayar_pelunasan`, memperbarui status menjadi `LUNAS` / `DP`, dan menyusun ringkasan `metode_bayar_terakhir` pada tabel `booking`.
2. **`trg_after_insert_detail_booking`**: Otomatis mengunci busana pada tabel `produk` (`stok_siap = 0`, `stok_disewa = 1`, status = `habis`) agar tidak terjadi penyewaan ganda pada tanggal yang sama.

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### 1. Prasyarat Sistem
* **PHP** versi 8.2 atau 8.3
* **Composer** (Dependency Manager PHP)
* **MySQL** Server 8.0 (melalui Laragon, XAMPP, atau MySQL Standalone)
* Web Browser modern (Google Chrome, Microsoft Edge, Mozilla Firefox)

### 2. Konfigurasi Basis Data
1. Pastikan servis MySQL aktif di port `3306`.
2. Buat database baru bernama `db_sanggara` (atau import dari `database/sanggara_database.sql`).
3. Sesuaikan konfigurasi pada file `backend/.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_sanggara
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 3. Menjalankan Backend Laravel API
Masuk ke direktori `backend` dan jalankan server lokal:
```powershell
cd backend
composer install
php artisan serve
```
Backend API akan aktif dan siap menerima request di: **`http://127.0.0.1:8000`**

### 4. Mengakses Aplikasi Web (Frontend)
Setelah server Laravel aktif, Anda dapat membuka halaman web langsung melalui browser:
* **Dashboard Utama**: [http://127.0.0.1:8000/pages/dashboard.html](http://127.0.0.1:8000/pages/dashboard.html)
* **Menu Kasir Persewaan (POS)**: [http://127.0.0.1:8000/pages/kasir.html](http://127.0.0.1:8000/pages/kasir.html)
* **Data Nota & Pembayaran**: [http://127.0.0.1:8000/pages/data-nota.html](http://127.0.0.1:8000/pages/data-nota.html)
* **Cetak Struk Nota**: [http://127.0.0.1:8000/pages/nota.html](http://127.0.0.1:8000/pages/nota.html)
* **Kelola Produk**: [http://127.0.0.1:8000/pages/kelola-produk.html](http://127.0.0.1:8000/pages/kelola-produk.html)
* **Kelola Pelanggan (API Ogan)**: [http://127.0.0.1:8000/pages/kelola-pelanggan.html](http://127.0.0.1:8000/pages/kelola-pelanggan.html)
* **Kelola Kategori**: [http://127.0.0.1:8000/pages/kelola-kategori.html](http://127.0.0.1:8000/pages/kelola-kategori.html)
* **Laporan Keuangan**: [http://127.0.0.1:8000/pages/laporan.html](http://127.0.0.1:8000/pages/laporan.html)

---

## 📚 Dokumen Spesifikasi Sistem Lengkap

Dokumentasi komprehensif mencakup *Software Requirements Specification (SRS)*, Use Case, Activity Flow, ERD, dan Kamus Data tersedia dalam:
* 📄 **File PDF Resmi**: [DOKUMENTASI_SISTEM_SANGGARA.pdf](DOKUMENTASI_SISTEM_SANGGARA.pdf)
* 🌐 **File Dokumen HTML**: [DOKUMENTASI_SISTEM_SANGGARA.html](DOKUMENTASI_SISTEM_SANGGARA.html)
* 📝 **File Dokumen Markdown**: [DOKUMENTASI_SISTEM_SANGGARA.md](DOKUMENTASI_SISTEM_SANGGARA.md)

---

## 👥 Pengembang & Hak Cipta

* **Klien / Mitra Usaha**: Sanggar Bunda Febri (SBF) — Purwakarta, Jawa Barat
* **Sistem**: Sanggara Management System
* **Hak Cipta**: &copy; 2026 **Sanggar Bunda Febri (SBF)** &bull; Powered by **Pratama Solusi Teknologi**
