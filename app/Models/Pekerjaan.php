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
        'kota',
        'link_linkedin',
        'status_karir', // Tambahkan ini
        'is_current'
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'nim', 'nim');
    }
}
