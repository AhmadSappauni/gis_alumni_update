<?php

namespace App\Http\Controllers;

use App\Models\RiwayatPekerjaan;

class MapController extends Controller
{
    public function index()
    {
        $dataPekerjaan = RiwayatPekerjaan::with([
            'alumni.akademik',
            'alumni.alamat',
            'perusahaan.lokasiUtama'
        ])
        ->where('is_current', true)
        ->get();

        return view('index', compact('dataPekerjaan'));
    }
}