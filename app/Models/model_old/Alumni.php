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
        'email',
        'no_hp',
        'angkatan', 
        'tahun_lulus', 
        'judul_skripsi', 
        'foto_profil',
        'kota_tinggal',    
        'alamat_tinggal',  
        'latitude_tinggal',
        'longitude_tinggal',
        'tahun_yudisium', 
        'nilai_toefl', 
    ];

    // Relasi ke tabel pekerjaan
    public function pekerjaans()
    {
        return $this->hasMany(Pekerjaan::class, 'nim', 'nim');
    }
    public function pekerjaanAktif()
    {
        return $this->hasOne(Pekerjaan::class, 'nim', 'nim')
                    ->where('is_current', true);
    }
}
