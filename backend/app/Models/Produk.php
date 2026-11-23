<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'id_produk';

    protected $fillable = [
        'kode_produk',
        'id_kategori',
        'nama_produk',
        'kualitas',
        'harga_sewa',
        'denda_per_hari',
        'stok_total',
        'stok_siap',
        'stok_disewa',
        'stok_cuci',
        'stok_rusak',
        'foto_produk',
        'deskripsi',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga_sewa' => 'float',
            'denda_per_hari' => 'float',
            'stok_total' => 'integer',
            'stok_siap' => 'integer',
            'stok_disewa' => 'integer',
            'stok_cuci' => 'integer',
            'stok_rusak' => 'integer',
        ];
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function detailBooking()
    {
        return $this->hasMany(DetailBooking::class, 'id_produk', 'id_produk');
    }
}
