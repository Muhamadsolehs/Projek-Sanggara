<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranTransaksi extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_transaksi';
    protected $primaryKey = 'id_pembayaran';
    public $timestamps = false;

    protected $fillable = [
        'no_bukti_bayar',
        'id_booking',
        'tgl_bayar',
        'jenis_pembayaran',
        'jumlah_bayar',
        'uang_diterima',
        'kembalian',
        'metode_bayar',
        'id_user',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_bayar' => 'datetime',
            'jumlah_bayar' => 'float',
            'uang_diterima' => 'float',
            'kembalian' => 'float',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'id_booking', 'id_booking');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
