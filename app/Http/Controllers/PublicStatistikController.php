<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicStatistikController extends StatistikController
{
    /** SIDANG-ALUR: Menampilkan GET /statistik dengan opsi filter statistik publik. */
    public function index(Request $request)
    {
        $options = $this->getDashboardOptions();

        $wilayahIdRaw = $request->query('wilayah_id');
        $initialFilters = [
            'angkatan' => $request->query('angkatan'),
            'tahun_lulus' => $request->query('tahun_lulus'),
            'jenis_kelamin' => $request->query('jenis_kelamin'),
            'status_alumni' => $request->query('status_alumni'),
            'bidang_pekerjaan' => $request->query('bidang_pekerjaan'),
            'wilayah_id' => is_numeric($wilayahIdRaw) && (int) $wilayahIdRaw > 0 ? (int) $wilayahIdRaw : null,
        ];

        return view('statistik.index', [
            'angkatanOptions' => $options['angkatanOptions'],
            'tahunLulusOptions' => $options['tahunLulusOptions'],
            'jenisKelaminOptions' => $options['jenisKelaminOptions'],
            'bidangOptions' => $options['bidangOptions'],
            'initialFilters' => $initialFilters,
        ]);
    }
}
