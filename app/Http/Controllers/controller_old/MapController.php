<?php



namespace App\Http\Controllers;



use App\Http\Controllers\Controller;

use App\Models\Pekerjaan;

use Illuminate\Http\Request;



class MapController extends Controller

{

    public function index()

    {

        // Ambil data pekerjaan dan join dengan tabel alumni

        $dataPekerjaan = Pekerjaan::join('alumnis', 'pekerjaans.nim', '=', 'alumnis.nim')

            ->select('pekerjaans.*', 'alumnis.nama_lengkap', 'alumnis.tahun_lulus')

            ->get();



        // Kirim data ke view 'map'

        return view('index', compact('dataPekerjaan'));
    }
}
