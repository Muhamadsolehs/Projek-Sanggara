<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory;

    protected $table = 'kecamatan';
    protected $primaryKey = 'id_kecamatan';
    public $timestamps = false;

    protected $fillable = [
        'nama_kecamatan',
        'zona_wilayah',
        'target_pasar',
    ];

    public function pelanggan()
    {
        return $this->hasMany(Pelanggan::class, 'id_kecamatan', 'id_kecamatan');
    }
}
