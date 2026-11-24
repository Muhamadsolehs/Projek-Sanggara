<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pelanggan;
use App\Models\Booking;
use App\Models\DetailBooking;
use App\Models\PembayaranTransaksi;
use Carbon\Carbon;

$dewi = Pelanggan::where('kode_pelanggan', 'CUST-008')->first();
$ahmad = Pelanggan::where('kode_pelanggan', 'CUST-009')->first();
$lilis = Pelanggan::where('kode_pelanggan', 'CUST-010')->first();
$cecep = Pelanggan::where('kode_pelanggan', 'CUST-011')->first();

$sampleBookings = [
    [
        'cust' => $dewi,
        'nota' => 'CS/01/261228/0007',
        'no_booking' => 14,
        'pickup' => '2026-12-28',
        'pakai' => '2026-12-29',
        'pickin' => '2026-12-31',
        'produk_id' => 3, // Kebaya & Wisuda
        'harga' => 180000,
        'total' => 180000,
        'status_sewa' => 'booking',
        'status_bayar' => 'LUNAS',
    ],
    [
        'cust' => $ahmad,
        'nota' => 'CS/01/261229/0008',
        'no_booking' => 15,
        'pickup' => '2026-12-29',
        'pakai' => '2026-12-30',
        'pickin' => '2027-01-01',
        'produk_id' => 5, // Beskap Jawa Solo
        'harga' => 250000,
        'total' => 250000,
        'status_sewa' => 'booking',
        'status_bayar' => 'DP',
    ],
    [
        'cust' => $lilis,
        'nota' => 'CS/01/261230/0009',
        'no_booking' => 16,
        'pickup' => '2026-12-30',
        'pakai' => '2026-12-31',
        'pickin' => '2027-01-02',
        'produk_id' => 1, // Busana Tari Jaipong
        'harga' => 150000,
        'total' => 150000,
        'status_sewa' => 'booking',
        'status_bayar' => 'LUNAS',
    ],
    [
        'cust' => $cecep,
        'nota' => 'CS/01/261231/0010',
        'no_booking' => 17,
        'pickup' => '2026-12-31',
        'pakai' => '2027-01-01',
        'pickin' => '2027-01-03',
        'produk_id' => 2, // Kostum Tari Saman
        'harga' => 200000,
        'total' => 200000,
        'status_sewa' => 'booking',
        'status_bayar' => 'LUNAS',
    ],
];

foreach ($sampleBookings as $idx => $sb) {
    if (!$sb['cust']) continue;

    $b = Booking::where('no_nota', $sb['nota'])->first();
    if (!$b) {
        $b = Booking::create([
            'no_nota' => $sb['nota'],
            'no_booking' => $sb['no_booking'],
            'id_pelanggan' => $sb['cust']->id_pelanggan,
            'id_user' => 1,
            'tgl_transaksi' => Carbon::now(),
            'tgl_ambil' => $sb['pickup'],
            'tgl_pakai' => $sb['pakai'],
            'tgl_kembali_rencana' => $sb['pickin'],
            'durasi_sewa_hari' => 3,
            'subtotal_produk' => $sb['total'],
            'total_tagihan' => $sb['total'],
            'opsi_pembayaran' => $sb['status_bayar'] === 'LUNAS' ? 'lunas' : 'dp',
            'persentase_dp' => $sb['status_bayar'] === 'LUNAS' ? 100 : 25,
            'total_dp' => $sb['status_bayar'] === 'LUNAS' ? $sb['total'] : ($sb['total'] * 0.25),
            'total_bayar' => 0,
            'sisa_tagihan' => $sb['total'],
            'metode_bayar_terakhir' => 'tunai',
            'status_pembayaran' => $sb['status_bayar'],
            'status_sewa' => $sb['status_sewa'],
            'denda_keterlambatan' => 0,
            'denda_kerusakan' => 0,
        ]);
    }

    $detExist = DetailBooking::where('id_booking', $b->id_booking)->first();
    if (!$detExist) {
        DetailBooking::create([
            'id_booking' => $b->id_booking,
            'id_produk' => $sb['produk_id'],
            'jumlah' => 1,
            'harga_satuan' => $sb['harga'],
            'subtotal' => $sb['total'],
        ]);
    }

    $payExist = PembayaranTransaksi::where('id_booking', $b->id_booking)->first();
    if (!$payExist) {
        $nominalBayar = $sb['status_bayar'] === 'LUNAS' ? $sb['total'] : ($sb['total'] * 0.25);
        PembayaranTransaksi::create([
            'id_booking' => $b->id_booking,
            'no_bukti_bayar' => 'PAY-' . date('ymd') . '-00' . ($idx + 7),
            'tgl_bayar' => Carbon::now(),
            'jenis_pembayaran' => $sb['status_bayar'] === 'LUNAS' ? 'LUNAS_LANGSUNG' : 'DP',
            'jumlah_bayar' => $nominalBayar,
            'uang_diterima' => $nominalBayar,
            'kembalian' => 0.00,
            'metode_bayar' => 'TUNAI',
            'id_user' => 1,
            'keterangan' => 'Pembayaran ' . $sb['status_bayar'] . ' sewa busana',
        ]);
    }

    $sb['cust']->update(['total_transaksi' => 1]);
    echo "Added Booking #{$sb['no_booking']} for {$sb['cust']->nama_pelanggan} ({$sb['cust']->nama_kecamatan})\n";
}

echo "All bookings across 17 kecamatan seeded successfully!\n";
