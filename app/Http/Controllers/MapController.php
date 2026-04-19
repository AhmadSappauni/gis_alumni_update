<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\RiwayatPekerjaan;
use Illuminate\Support\Collection;

class MapController extends Controller
{
    public function index()
    {
        $markers = collect();

        /*
        |--------------------------------------------------------------------------
        | 1. ALUMNI YANG BEKERJA
        |--------------------------------------------------------------------------
        | Ambil pekerjaan aktif sekarang
        | Titik marker = lokasi perusahaan
        */

        $pekerja = RiwayatPekerjaan::with([
            'alumni.akademik',
            'alumni.alamat',
            'perusahaan.lokasiUtama',
            'perusahaan.lokasi'
        ])
        ->where('is_current', true)
        ->where('status_kerja', 'Bekerja')
        ->get();

        foreach ($pekerja as $job) {

            $lokasi = $job->perusahaan?->lokasiUtama
                    ?? $job->perusahaan?->lokasi->first();

            if (!$lokasi || !$lokasi->latitude || !$lokasi->longitude) {
                continue;
            }

            $markers->push([
                'id'            => $job->alumni?->id,
                'nim'           => $job->alumni?->nim,
                'nama'          => $job->alumni?->nama_lengkap,
                'foto'          => $job->alumni?->foto_profil,
                'tahun_lulus'   => $job->alumni?->akademik?->tahun_lulus,
                'angkatan'      => $job->alumni?->akademik?->angkatan,

                'status'        => 'Bekerja',
                'status_icon'   => 'working',

                'latitude'      => (float) $lokasi->latitude,
                'longitude'     => (float) $lokasi->longitude,

                'kota'          => $lokasi->kota,
                'provinsi'      => $lokasi->provinsi,
                'alamat'        => $lokasi->alamat_lengkap,

                'perusahaan'    => $job->perusahaan?->nama_perusahaan,
                'jabatan'       => $job->jabatan,
                'bidang'        => $job->bidang_pekerjaan,
                'linearitas'    => $job->perusahaan?->linearitas,
                'gaji'          => $job->gaji_nominal,
                'masa_tunggu'   => $job->masa_tunggu,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. ALUMNI BELUM BEKERJA
        |--------------------------------------------------------------------------
        | Ambil alumni yang tidak punya pekerjaan aktif bekerja
        | Titik marker = domisili alumni
        */

        $belumKerja = Alumni::with([
            'akademik',
            'alamat',
            'pekerjaan' => function ($q) {
                $q->where('is_current', true);
            }
        ])
        ->get();

        foreach ($belumKerja as $alumni) {

            $punyaKerjaAktif = $alumni->pekerjaan
                ->where('status_kerja', 'Bekerja')
                ->count() > 0;

            if ($punyaKerjaAktif) {
                continue;
            }

            $alamat = $alumni->alamat;

            if (!$alamat || !$alamat->latitude || !$alamat->longitude) {
                continue;
            }

            $markers->push([
                'id'            => $alumni->id,
                'nim'           => $alumni->nim,
                'nama'          => $alumni->nama_lengkap,
                'foto'          => $alumni->foto_profil,
                'tahun_lulus'   => $alumni->akademik?->tahun_lulus,
                'angkatan'      => $alumni->akademik?->angkatan,

                'status'        => 'Belum Bekerja',
                'status_icon'   => 'unemployed',

                'latitude'      => (float) $alamat->latitude,
                'longitude'     => (float) $alamat->longitude,

                'kota'          => $alamat->kota,
                'provinsi'      => $alamat->provinsi,
                'alamat'        => $alamat->alamat_lengkap,

                'perusahaan'    => null,
                'jabatan'       => null,
                'bidang'        => null,
                'linearitas'    => null,
                'gaji'          => null,
                'masa_tunggu'   => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. SORTING
        |--------------------------------------------------------------------------
        */

        $markers = $markers
            ->sortByDesc(function ($item) {
                return $item['status'] === 'Bekerja' ? 1 : 0;
            })
            ->values();

        return view('index', [
            'dataPekerjaan' => $markers
        ]);
    }
}