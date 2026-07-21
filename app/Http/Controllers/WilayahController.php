<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    /** SIDANG-MAP: GET /wilayah-kalsel mengubah polygon PostGIS menjadi GeoJSON untuk filter peta. */
    public function index(): JsonResponse
    {
        $rows = DB::table('wilayah_kalsel')
            ->select(['id', 'nama', 'level'])
            ->orderByRaw("CASE WHEN level = 'kota' THEN 0 ELSE 1 END")
            ->orderBy('nama')
            ->get();

        return response()->json($rows->map(function ($row) {
            $prefix = $row->level === 'kota' ? 'Kota' : 'Kab.';

            return [
                'id'      => $row->id,
                'nama'    => $row->nama,
                'level'   => $row->level,
                'display' => "{$prefix} {$row->nama}",
            ];
        })->values());
    }
}
