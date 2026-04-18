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
        'jenjang',
        'program_studi',
        'tahun_masuk',
        'tahun_lulus',
        'status'
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}