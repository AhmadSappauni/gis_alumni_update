<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pekerjaan';

    protected $fillable = [
        'alumni_id',
        'perusahaan_id',
        'jabatan',
        'bidang_pekerjaan',
        'status_kerja',
        'is_current',
        'tanggal_mulai',
        'tanggal_selesai',
        'masa_tunggu',
        'status_karir',
        'gaji_nominal'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_current' => 'boolean'
    ];

    // SIDANG-RELASI: Setiap riwayat pekerjaan dimiliki satu alumni melalui alumni_id.
    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }

    // SIDANG-RELASI: Setiap riwayat pekerjaan dapat mengacu pada satu perusahaan.
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }
}
