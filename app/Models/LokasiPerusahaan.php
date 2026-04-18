<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LokasiPerusahaan extends Model
{
    protected $table = 'lokasi_perusahaan';

    protected $fillable = [
        'perusahaan_id',
        'nama_cabang',
        'alamat_lengkap',
        'kota',
        'provinsi',
        'latitude',
        'longitude',
        'is_head_office'
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }
}