<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    protected $fillable = [
        'nim', 
        'nama_perusahaan', 
        'jabatan', 
        'linearitas', 
        'alamat_lengkap', 
        'latitude', 
        'longitude',
        'bidang_pekerjaan',
        'gaji',
        'gaji_nominal',
        'kota',
        'link_linkedin',
        'status_karir',
        'status_kerja',
        'masa_tunggu',
        'is_current'
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'gaji_nominal' => 'integer',
        'masa_tunggu' => 'integer',
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'nim', 'nim');
    }

    public function getGajiFormattedAttribute()
    {
        return 'Rp ' . number_format($this->gaji_nominal, 0, ',', '.');
    }
}
