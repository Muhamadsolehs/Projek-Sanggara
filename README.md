# Sanggara Management System (Sanggar Bunda Febri) - UI & Modular CSS

Repositori ini berisi implementasi tampilan antarmuka (UI) dan **pemisahan file CSS modular per halaman** berdasarkan 15 desain antarmuka sistem manajemen sanggar busana/rias pengantin Sanggara (Sanggar Bunda Febri).

---

## 📁 Struktur Direktori

```text
sanggara_project/
├── index.html                   # Portal navigasi & showcase untuk semua 15 halaman
├── README.md                    # Dokumentasi lengkap arsitektur UI & CSS
├── css/                         # Seluruh styling CSS (dipisahkan per halaman)
│   ├── global.css               # Shared design system, sidebar, navbar, typography, empty state
│   ├── login.css                # CSS Khusus: Halaman Login (Split Screen)
│   ├── dashboard.css            # CSS Khusus: Dashboard Ringkasan Bisnis & Statistik
│   ├── laporan-pemasukan.css    # CSS Khusus: Laporan Data Pemasukan Kas
│   ├── laporan-pengeluaran.css  # CSS Khusus: Laporan Data Pengeluaran Beban
│   ├── analisa-pelanggan.css    # CSS Khusus: Analisa Wilayah & Ranking 17 Kecamatan
│   ├── analisa-keuangan.css     # CSS Khusus: Analisa Cashflow & Donut Kategori/Beban
│   ├── kelola-produk.css        # CSS Khusus: Katalog Master Data Produk/Busana
│   ├── tambah-produk.css        # CSS Khusus: Formulir Tambah Barang (Counter & Upload)
│   ├── kelola-pelanggan.css     # CSS Khusus: Tabel Master Data Pelanggan/Konsumen
│   ├── tambah-pelanggan.css     # CSS Khusus: Formulir Tambah Pelanggan
│   ├── kelola-kategori.css      # CSS Khusus: Master Data Kategori
│   ├── tambah-kategori.css      # CSS Khusus: Formulir Tambah Kategori
│   ├── booking-kalender.css     # CSS Khusus: Kalender Agenda Sewa Bulanan
│   ├── booking-tabel.css        # CSS Khusus: Tabel Rekap Data Booking
│   ├── pesanan-tabel.css        # CSS Khusus: Tabel Rincian Data Pesanan
│   ├── kasir.css                # CSS Khusus: Menu Kasir & Booking Interaktif (POS)
│   ├── data-nota.css            # CSS Khusus: Tabel Rekapitulasi Data Nota & Pelunasan
│   └── nota.css                 # CSS Khusus: Lembar Struk & Nota Thermal Pembayaran
├── js/                          # Script interaksi UI & logika sistem
│   └── global.js                # Interaksi Global: Toggle Sidebar, Persist State, Dropdown Menu
└── pages/                       # File HTML representasi setiap halaman
    ├── login.html
    ├── dashboard.html
    ├── laporan-pemasukan.html
    ├── laporan-pengeluaran.html
    ├── analisa-pelanggan.html
    ├── analisa-keuangan.html
    ├── kelola-produk.html
    ├── tambah-produk.html
    ├── kelola-pelanggan.html
    ├── tambah-pelanggan.html
    ├── kelola-kategori.html
    ├── tambah-kategori.html
    ├── booking-kalender.html
    ├── booking-tabel.html
    ├── pesanan-tabel.html
    ├── kasir.html
    ├── data-nota.html
    └── nota.html
```

---

## 🎨 Pemetaan 15 Halaman & CSS Masing-Masing

| No | Nama Halaman | File CSS Khusus | File HTML | Deskripsi Komponen Utama |
|---|---|---|---|---|
| **01** | **Login** | `css/login.css` | `pages/login.html` | Hero split gradient hijau SBF, login card, email & password dengan toggle visibility. |
| **02** | **Dashboard** | `css/dashboard.css` | `pages/dashboard.html` | Banner sambutan PST, 4 Card KPI (Surplus, Booking, Pasar, Stok), Bar chart arus kas Jan-Nov, Top 5 ranking Kecamatan, Status inventaris. |
| **03** | **Data Pemasukan** | `css/laporan-pemasukan.css` | `pages/laporan-pemasukan.html` | Filter range tanggal, pencarian transaksi, tombol ekspor laporan, tabel mutasi kasir. |
| **04** | **Data Pengeluaran** | `css/laporan-pengeluaran.css` | `pages/laporan-pengeluaran.html` | Filter rentang tanggal, tabel pencatatan beban operasional, bukti struk kas tunai. |
| **05** | **Analisa Pelanggan** | `css/analisa-pelanggan.css` | `pages/analisa-pelanggan.html` | Filter kecamatan & tanggal, progres bar perbandingan 17 kecamatan, Donut chart klaster zona wilayah, 3 Wilayah Teratas. |
| **06** | **Analisa Keuangan** | `css/analisa-keuangan.css` | `pages/analisa-keuangan.html` | Bar chart komparasi pemasukan vs pengeluaran bulanan, Donut sumber pemasukan, Donut pos beban, tabel mutasi arus kas. |
| **07** | **Kelola Produk** | `css/kelola-produk.css` | `pages/kelola-produk.html` | Header dengan tombol Template/Import/Tambah, dropdown filter kategori, tabel produk, empty state illustration. |
| **08** | **Tambah Produk** | `css/tambah-produk.css` | `pages/tambah-produk.html` | Modal header dengan logo, input nama, kategori, kualitas, stok, harga sewa, dropzone upload foto dengan panduan dimensi. |
| **09** | **Kelola Pelanggan** | `css/kelola-pelanggan.css` | `pages/kelola-pelanggan.html` | Tabel master konsumen, filter dropdown alamat, tombol tambah pelanggan, empty state. |
| **10** | **Tambah Pelanggan** | `css/tambah-pelanggan.css` | `pages/tambah-pelanggan.html` | Formulir nama konsumen, nomor telepon, dual dropdown pilihan kecamatan dan kelurahan/desa. |
| **11** | **Kelola Kategori** | `css/kelola-kategori.css` | `pages/kelola-kategori.html` | Tabel master kategori busana dan atribut rias, filter pencarian, status data kosong. |
| **12** | **Tambah Kategori** | `css/tambah-kategori.css` | `pages/tambah-kategori.html` | Input nama kategori, nomor deskripsi dengan limit counter (0/50), tombol batal & simpan. |
| **13** | **Booking (Kalender)** | `css/booking-kalender.css` | `pages/booking-kalender.html` | Kalender agenda bulanan (Minggu - Sabtu), badge Ambil (Hijau), Pakai (Biru), dan Kembali (Kuning/Oranye). |
| **14** | **Booking (Tabel)** | `css/booking-tabel.css` | `pages/booking-tabel.html` | Tabel daftar booking pesanan konsumen, pickup, pakai, pickin, status denda, filter bulan & tahun. |
| **15** | **Pesanan (Tabel)** | `css/pesanan-tabel.css` | `pages/pesanan-tabel.html` | Tabel detail barang/kostum per booking, jumlah kuantitas, aksi, filter periode. |
| **16** | **Kasir (Interaktif)** | `css/kasir.css` | `pages/kasir.html` | Alur kasir lengkap: filter katalog, keranjang dinamis, kalkulasi DP 25%, modal pelanggan baru, dan checkout. |
| **17** | **Nota / Struk SBF** | `css/nota.css` | `pages/nota.html` | Lembar struk fisik Sanggar Bunda Febri (SBF), rincian sewa, DP, sisa pelunasan, dan fungsi cetak thermal. |
| **18** | **Data Nota** | `css/data-nota.css` | `pages/data-nota.html` | Rekapitulasi transaksi nota booking, status pelunasan Lunas/DP, tombol bayar cepat, dan link detail nota. |

---

## 🎨 Design System & Token Warna

Sistem ini menggunakan CSS Variables yang didefinisikan di `css/global.css`:

```css
:root {
  /* Brand Utama */
  --color-primary: #00b87c;        /* Hijau Emerald Sanggara */
  --color-primary-dark: #009e69;
  --color-primary-light: #e6f8f2;
  
  /* Status Warna */
  --color-blue: #0284c7;           /* Pakai / Status Biru */
  --color-amber: #f59e0b;          /* Kembali / Warning Oranye */
  --color-red: #ef4444;            /* Pengeluaran / Bahaya */
  
  /* Font Family */
  --font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
}
```

---

## 🚀 Cara Menjalankan & Membuka

1. Buka file `index.html` langsung di browser untuk melihat portal navigasi dan daftar 15 halaman.
2. Setiap halaman HTML di folder `pages/` dapat dibuka langsung tanpa konfigurasi server tambahan karena menggunakan pure HTML & CSS.
3. Anda dapat mengimpor file CSS masing-masing ke framework yang Anda gunakan (seperti React, Vue, Laravel Blade, Next.js, atau PHP Native).
