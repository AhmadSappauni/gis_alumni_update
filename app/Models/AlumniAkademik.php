<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniAkademik extends Model
{
    use HasFactory;

    protected $table = 'alumni_akademik';

    protected $fillable = [
        'alumni_id',
        'angkatan',
        'tahun_lulus',
        'tahun_yudisium',
        'judul_skripsi',
        'ipk',
        'nilai_toefl',
        'lama_studi'
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}