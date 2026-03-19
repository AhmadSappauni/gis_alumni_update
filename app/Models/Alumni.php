<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    protected $primaryKey = 'nim';
    public $incrementing = false; // Karena NIM adalah string/primary key
    protected $keyType = 'string';

    protected $fillable = [
        'nim', 
        'nama_lengkap', 
        'angkatan', 
        'tahun_lulus', 
        'judul_skripsi', 
        'foto_profil'
    ];

    // Relasi ke tabel pekerjaan
    public function pekerjaan()
    {
        return $this->hasOne(Pekerjaan::class, 'nim', 'nim');
    }
}
