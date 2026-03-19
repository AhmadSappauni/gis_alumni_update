<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\Pekerjaan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AlumniImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {

        // Skip jika NIM sudah ada
        if(Alumni::where('nim',$row['nim'])->exists()){
            return null;
        }

        Alumni::create([
            'nim' => $row['nim'],
            'nama_lengkap' => $row['nama_lengkap'],
            'tahun_lulus' => $row['tahun_lulus']
        ]);

        Pekerjaan::create([
            'nim' => $row['nim'],
            'nama_perusahaan' => $row['nama_perusahaan'] ?? '-',
            'jabatan' => $row['jabatan'] ?? '-',
            'kota' => $row['kota'] ?? '-',
            'alamat_lengkap' => $row['kota'] ?? '-',
            'linearitas' => $row['linearitas'] ?? 'Tidak Linier',
            'latitude' => null,
            'longitude' => null
        ]);

        return null;
    }
}