<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPengeluaran extends Model
{
    use HasFactory;

    protected $table = 'laporan_pengeluaran';
    protected $primaryKey = 'id_pengeluaran';

    protected $fillable = [
        'no_transaksi',
        'tanggal',
        'kategori_beban',
        'nominal',
        'metode_pembayaran',
        'pic_staf',
        'bukti_struk',
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
}
