<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    protected $fillable = [
        'nim',
        'jabatan',
        'bidang_pekerjaan',
        'nama_perusahaan',
        'gaji',
        'kota',
        'alamat_lengkap',
        'link_linkedin',
        'linearitas',
        'latitude',
        'longitude'
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'nim', 'nim');
    }
}
