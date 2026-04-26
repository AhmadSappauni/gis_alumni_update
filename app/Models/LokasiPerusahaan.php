<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LokasiPerusahaan extends Model
{
    protected $table = 'lokasi_perusahaan';

    protected $fillable = [
        'perusahaan_id',
        'alamat_lengkap',
        'kota',
        'provinsi',
        'latitude',
        'longitude'
    ];

    protected $casts = [
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
