<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nama_lengkap',
        'email',
        'password',
        'role',
        'no_telepon',
        'foto_profil',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'id_user', 'id_user');
    }

    public function pembayaran()
    {
        return $this->hasMany(PembayaranTransaksi::class, 'id_user', 'id_user');
    }
}
