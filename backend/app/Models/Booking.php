<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'booking';
    protected $primaryKey = 'id_booking';

    protected $fillable = [
        'no_nota',
        'no_booking',
        'id_pelanggan',
        'id_user',
        'tgl_transaksi',
        'tgl_ambil',
        'tgl_pakai',
        'tgl_kembali_rencana',
        'tgl_kembali_aktual',
        'durasi_sewa_hari',
        'subtotal_produk',
        'total_tagihan',
        'opsi_pembayaran',
        'persentase_dp',
        'total_dp',
        'total_bayar',
        'sisa_tagihan',
        'metode_bayar_terakhir',
        'status_pembayaran',
        'status_sewa',
        'denda_keterlambatan',
        'denda_kerusakan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_transaksi' => 'datetime',
            'tgl_ambil' => 'date',
            'tgl_pakai' => 'date',
            'tgl_kembali_rencana' => 'date',
            'tgl_kembali_aktual' => 'date',
            'subtotal_produk' => 'float',
            'total_tagihan' => 'float',
            'total_dp' => 'float',
            'total_bayar' => 'float',
            'sisa_tagihan' => 'float',
            'denda_keterlambatan' => 'float',
            'denda_kerusakan' => 'float',
            'total_denda' => 'float',
        ];
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function details()
    {
        return $this->hasMany(DetailBooking::class, 'id_booking', 'id_booking');
    }

    public function pembayaran()
    {
        return $this->hasMany(PembayaranTransaksi::class, 'id_booking', 'id_booking');
    }

    public function pemasukan()
    {
        return $this->hasMany(LaporanPemasukan::class, 'id_booking', 'id_booking');
    }
}
