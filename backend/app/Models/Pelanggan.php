<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'kode_pelanggan',
        'nama_pelanggan',
        'no_telepon',
        'id_kecamatan',
        'nama_kecamatan',
        'kelurahan_desa',
        'alamat_lengkap',
        'total_transaksi',
    ];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'id_kecamatan', 'id_kecamatan');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'id_pelanggan', 'id_pelanggan');
    }
}
