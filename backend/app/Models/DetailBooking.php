<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailBooking extends Model
{
    use HasFactory;

    protected $table = 'detail_booking';
    protected $primaryKey = 'id_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_booking',
        'id_produk',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'kondisi_ambil',
        'kondisi_kembali',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan' => 'float',
            'subtotal' => 'float',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'id_booking', 'id_booking');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
