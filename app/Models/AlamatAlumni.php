<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlamatAlumni extends Model
{
    use HasFactory;

    protected $table = 'alamat_alumni';

    protected $fillable = [
        'alumni_id',
        'alamat_lengkap',
        'kota',
        'provinsi',
        'latitude',
        'longitude',
        'is_current'
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}