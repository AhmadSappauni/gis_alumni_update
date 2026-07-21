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

    // SIDANG-RELASI: Satu perusahaan dapat dirujuk oleh banyak riwayat pekerjaan.
    public function pekerjaan()
    {
        return $this->hasMany(RiwayatPekerjaan::class, 'perusahaan_id');
    }
    // SIDANG-RELASI: Satu perusahaan dapat memiliki banyak catatan lokasi.
    public function lokasi()
    {
        return $this->hasMany(LokasiPerusahaan::class, 'perusahaan_id');
    }

    // SIDANG-RELASI: Eloquent memilih lokasi terbaru yang berstatus aktif dengan latestOfMany.
    public function lokasiAktif()
    {
        return $this->hasOne(LokasiPerusahaan::class, 'perusahaan_id')
                    ->latestOfMany();
    }
}
