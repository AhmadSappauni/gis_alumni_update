<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudiLanjut extends Model
{
    use HasFactory;

    protected $table = 'studi_lanjut';

    protected $fillable = [
        'alumni_id',
        'kampus',
        'alamat_kampus',
        'kota_kampus',
        'provinsi_kampus',
        'latitude',
        'longitude',
        'jenjang',
        'program_studi',
        'tahun_masuk',
        'tahun_lulus',
        'status'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}
