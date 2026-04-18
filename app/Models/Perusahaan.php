<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;

    protected $table = 'perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'tingkat_instansi',
        'linearitas',
        'link_linkedin'
    ];

    public function pekerjaan()
    {
        return $this->hasMany(RiwayatPekerjaan::class, 'perusahaan_id');
    }
    public function lokasi()
    {
        return $this->hasMany(LokasiPerusahaan::class, 'perusahaan_id');
    }

    public function lokasiUtama()
    {
        return $this->hasOne(LokasiPerusahaan::class, 'perusahaan_id')
                    ->where('is_head_office', true);
    }
    public function lokasiAktif()
    {
        return $this->hasOne(LokasiPerusahaan::class, 'perusahaan_id')
                    ->latestOfMany();
    }
}