<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumnis';

    protected $fillable = [
        'nim',
        'nama_lengkap',
        'jenis_kelamin',
        'email',
        'no_hp',
        'foto_profil'
    ];

    public function akademik()
    {
        return $this->hasOne(AlumniAkademik::class, 'alumni_id');
    }

    public function alamat()
    {
        return $this->hasOne(AlamatAlumni::class, 'alumni_id')
                    ->where('is_current', true);
    }

    public function pekerjaan()
    {
        return $this->hasMany(RiwayatPekerjaan::class, 'alumni_id');
    }

    public function studiLanjut()
    {
        return $this->hasMany(StudiLanjut::class, 'alumni_id');
    }
}