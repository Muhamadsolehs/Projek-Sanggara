<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pelanggan;
use App\Models\Booking;
use App\Models\DetailBooking;
use App\Models\PembayaranTransaksi;
use App\Models\LaporanPemasukan;
use App\Models\Kecamatan;
use Illuminate\Support\Facades\DB;

// 1. Update ID 6 (Reza Kurniawan) dan ID 7 (Ibu Sarah)
$p6 = Pelanggan::find(6);
if ($p6) {
    $p6->update([
        'id_kecamatan' => 6, // Kec. Pasawahan
        'nama_kecamatan' => 'Kec. Pasawahan',
        'kelurahan_desa' => 'Pasawahan Kidul',
        'alamat_lengkap' => 'Jl. Raya Pasawahan No. 18',
    ]);
    echo "Updated Reza Kurniawan -> Kec. Pasawahan\n";
}

$p7 = Pelanggan::find(7);
if ($p7) {
    $p7->update([
        'id_kecamatan' => 2, // Kec. Babakancikao
        'nama_kecamatan' => 'Kec. Babakancikao',
        'kelurahan_desa' => 'Maracang',
        'alamat_lengkap' => 'Perum Kota Bukit Indah Blok C3',
    ]);
    echo "Updated Ibu Sarah -> Kec. Babakancikao\n";
}

// 2. Tambah pelanggan di kecamatan lain untuk memperkaya data 17 kecamatan
$additionalCustomers = [
    [
        'kode_pelanggan' => 'CUST-008',
        'nama_pelanggan' => 'Hj. Dewi Sartika',
        'no_telepon' => '0821-1234-9988',
        'id_kecamatan' => 5, // Kec. Bungursari
        'nama_kecamatan' => 'Kec. Bungursari',
        'kelurahan_desa' => 'Cinangka',
        'alamat_lengkap' => 'Jl. Raya Bungursari Kav. 12',
    ],
    [
        'kode_pelanggan' => 'CUST-009',
        'nama_pelanggan' => 'Ahmad Hidayat',
        'no_telepon' => '0878-7766-5544',
        'id_kecamatan' => 8, // Kec. Plered
        'nama_kecamatan' => 'Kec. Plered',
        'kelurahan_desa' => 'Anjun',
        'alamat_lengkap' => 'Sentra Keramik Plered No. 40',
    ],
    [
        'kode_pelanggan' => 'CUST-010',
        'nama_pelanggan' => 'Neng Lilis',
        'no_telepon' => '0896-3322-1100',
        'id_kecamatan' => 11, // Kec. Wanayasa
        'nama_kecamatan' => 'Kec. Wanayasa',
        'kelurahan_desa' => 'Wanayasa Wetan',
        'alamat_lengkap' => 'Kawasan Situ Wanayasa No. 05',
    ],
    [
        'kode_pelanggan' => 'CUST-011',
        'nama_pelanggan' => 'Bapak Cecep Hendra',
        'no_telepon' => '0812-4455-6677',
        'id_kecamatan' => 10, // Kec. Bojong
        'nama_kecamatan' => 'Kec. Bojong',
        'kelurahan_desa' => 'Bojong Barat',
        'alamat_lengkap' => 'Jl. Raya Bojong - Sawit No. 7',
    ],
    [
        'kode_pelanggan' => 'CUST-012',
        'nama_pelanggan' => 'Santi Wulandari',
        'no_telepon' => '0853-9988-1122',
        'id_kecamatan' => 7, // Kec. Sukatani
        'nama_kecamatan' => 'Kec. Sukatani',
        'kelurahan_desa' => 'Sukatani',
        'alamat_lengkap' => 'Jl. Raya Sukatani Km 9',
    ],
    [
        'kode_pelanggan' => 'CUST-013',
        'nama_pelanggan' => 'Fajar Nugraha',
        'no_telepon' => '0819-2233-4455',
        'id_kecamatan' => 9, // Kec. Darangdan
        'nama_kecamatan' => 'Kec. Darangdan',
        'kelurahan_desa' => 'Sawit',
        'alamat_lengkap' => 'Jl. Raya Darangdan No. 23',
    ],
];

foreach ($additionalCustomers as $c) {
    $cust = Pelanggan::firstOrCreate(
        ['kode_pelanggan' => $c['kode_pelanggan']],
        $c + ['total_transaksi' => 0]
    );
    echo "Customer: {$cust->nama_pelanggan} ({$cust->nama_kecamatan})\n";
}

echo "Data customers updated and enriched across 17 kecamatan!\n";
