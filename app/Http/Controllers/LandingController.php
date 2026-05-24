<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\AlumniAkademik;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index()
    {
        $totalAlumni = Alumni::count();

        $wilayahResult = DB::selectOne("
            SELECT COUNT(DISTINCT w.id) AS cnt
            FROM wilayah_kalsel w
            WHERE EXISTS (
                SELECT 1 FROM alamat_alumni a
                WHERE a.geom IS NOT NULL
                  AND a.is_current IS TRUE
                  AND ST_Within(a.geom::geometry, w.geom)
            )
            OR EXISTS (
                SELECT 1
                FROM lokasi_perusahaan lp
                JOIN riwayat_pekerjaan rp ON rp.perusahaan_id = lp.perusahaan_id
                WHERE lp.geom IS NOT NULL
                  AND rp.is_current IS TRUE
                  AND ST_Within(lp.geom::geometry, w.geom)
            )
        ");
        $wilayahTerpetakan = (int) ($wilayahResult->cnt ?? 0);

        $profilTracer = Alumni::where(function ($q) {
            $q->whereHas('pekerjaan')
              ->orWhereHas('studiLanjut');
        })->count();

        $minAngkatan = AlumniAkademik::whereNotNull('angkatan')->min('angkatan');
        $maxAngkatan = AlumniAkademik::whereNotNull('angkatan')->max('angkatan');
        $cakupanAngkatan = ($minAngkatan && $maxAngkatan)
            ? "{$minAngkatan}\u{2013}{$maxAngkatan}"
            : '–';

        return view('landing.index', compact(
            'totalAlumni', 'wilayahTerpetakan', 'profilTracer', 'cakupanAngkatan'
        ));
    }
}
