<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPemasukan extends Model
{
    use HasFactory;

    protected $table = 'laporan_pemasukan';
    protected $primaryKey = 'id_pemasukan';

    protected $fillable = [
        'no_transaksi',
        'id_booking',
        'tanggal',
        'kategori_pemasukan',
        'nominal',
        'metode_pembayaran',
        'id_user',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'float',
            'created_at' => 'datetime',
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
