<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Pekerjaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class AdminAlumniController extends Controller
{
    public function index()
    {
        $dataAlumni = Alumni::with('pekerjaan')
            ->orderBy('tahun_lulus', 'desc')
            ->paginate(10);

        return view('admin.index', compact('dataAlumni'));
    }

    public function create()
    {
        return view('admin.create');
    }

    public function store(Request $request)
    {

        $request->validate([
            'nim' => 'required|unique:alumnis,nim',
            'nama_lengkap' => 'required',
            'tahun_lulus' => 'required|numeric',
            'nama_perusahaan' => 'required',
            'jabatan' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $fotoPath = null;

        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('alumni_foto', 'public');
        }

        try {

            DB::transaction(function () use ($request, $fotoPath) {

                Alumni::create([
                    'nim' => $request->nim,
                    'nama_lengkap' => $request->nama_lengkap,
                    'angkatan' => $request->angkatan,
                    'tahun_lulus' => $request->tahun_lulus,
                    'judul_skripsi' => $request->judul_skripsi,
                    'foto_profil' => $fotoPath
                ]);

                Pekerjaan::create([
                    'nim' => $request->nim,
                    'nama_perusahaan' => $request->nama_perusahaan,
                    'jabatan' => $request->jabatan,
                    'bidang_pekerjaan' => $request->bidang,
                    'gaji' => $request->gaji,
                    'kota' => $request->kota,
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'link_linkedin' => $request->linkedin,
                    'linearitas' => $request->linearitas,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ]);
            });

            return redirect()->route('admin.alumni.index')
                ->with('success', 'Data Alumni berhasil ditambahkan');
        } catch (\Exception $e) {

            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
    public function checkNim(Request $request)
    {
        $nim = $request->nim;

        $exists = Alumni::where('nim', $nim)->exists();

        return response()->json([
            'exists' => $exists
        ]);
    }

    public function importPage()
    {
        return view('admin.import.import-excel');
    }

    public function importPreview(Request $request)
    {
        $file = $request->file('file');

        $data = Excel::toArray([], $file);

        $rows = $data[0];

        array_shift($rows); // hapus header excel

        return response()->json($rows);
    }

    public function importStore(Request $request)
    {
        $rows = json_decode($request->rows, true);

        $success = 0;
        $skip = 0;

        foreach ($rows as $row) {

            $nim = $row[0];

            if (Alumni::where('nim', $nim)->exists()) {
                $skip++;
                continue;
            }

            $rawLinearitas = $row[6] ?? 'Tidak Linier';
            if (str_contains(strtolower($rawLinearitas), 'line')) {
                $fixLinearitas = 'Linier'; 
            } else {
                $fixLinearitas = 'Tidak Linier';
            }

            Alumni::create([
                'nim' => $row[0],
                'nama_lengkap' => $row[1],
                'tahun_lulus' => $row[2],
                'angkatan' => null,
                'judul_skripsi' => null,
                'foto_profil' => null
            ]);

            Pekerjaan::create([
                'nim' => $row[0],
                'nama_perusahaan' => $row[3] ?? '-',
                'jabatan' => $row[4] ?? '-',
                'bidang_pekerjaan' => '-',
                'gaji' => null,
                'kota' => $row[5] ?? '-',
                'alamat_lengkap' => $row[5] ?? '-',
                'link_linkedin' => null,
                'linearitas' => $fixLinearitas,
                'latitude' => null,
                'longitude' => null
            ]);

            $success++;
        }

        return response()->json([
            'success' => $success,
            'skip' => $skip
        ]);
    }
}
