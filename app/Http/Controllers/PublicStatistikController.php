<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicStatistikController extends StatistikController
{
    public function index(Request $request)
    {
        $options = $this->getDashboardOptions();

        $initialFilters = [
            'angkatan' => $request->query('angkatan'),
            'tahun_lulus' => $request->query('tahun_lulus'),
            'jenis_kelamin' => $request->query('jenis_kelamin'),
            'status_alumni' => $request->query('status_alumni'),
            'bidang_pekerjaan' => $request->query('bidang_pekerjaan'),
            'wilayah' => $request->query('wilayah'),
        ];

        return view('statistik.index', [
            'angkatanOptions' => $options['angkatanOptions'],
            'tahunLulusOptions' => $options['tahunLulusOptions'],
            'jenisKelaminOptions' => $options['jenisKelaminOptions'],
            'bidangOptions' => $options['bidangOptions'],
            'wilayahOptions' => $options['wilayahOptions'],
            'initialFilters' => $initialFilters,
        ]);
    }
}

