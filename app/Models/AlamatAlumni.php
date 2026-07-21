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

    protected $casts = [
        'is_current' => 'boolean',
    ];

    // SIDANG-RELASI: Setiap baris alamat mengacu pada satu alumni melalui alumni_id.
    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}
