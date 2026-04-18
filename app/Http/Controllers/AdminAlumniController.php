<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AlamatAlumni;
use App\Models\Alumni;
use App\Models\AlumniAkademik;
use App\Models\LokasiPerusahaan;
use App\Models\Perusahaan;
use App\Models\RiwayatPekerjaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AdminAlumniController extends Controller
{
   public function index()
    {
        $dataAlumni = Alumni::with([
            'akademik',
            'alamat',
            'pekerjaan.perusahaan'
        ])
        ->latest()
        ->paginate(10);

        return view('admin.index', compact('dataAlumni'));
    }

    public function create()
    {
        return view('admin.create');
    }

    public function edit($id)
    {
        /*
        |--------------------------------------------------------------------------
        | Ambil data alumni lengkap untuk halaman edit
        |--------------------------------------------------------------------------
        */
        $alumni = Alumni::with([

            /*
            |--------------------------------------------------------------------------
            | Data akademik
            |--------------------------------------------------------------------------
            */
            'akademik',

            /*
            |--------------------------------------------------------------------------
            | Alamat domisili aktif
            |--------------------------------------------------------------------------
            */
            'alamat' => function ($query) {
                $query->where('is_current', true)
                    ->latest('id');
            },

            /*
            |--------------------------------------------------------------------------
            | Riwayat pekerjaan + relasi perusahaan + lokasi perusahaan
            |--------------------------------------------------------------------------
            */
            'pekerjaan' => function ($query) {
                $query->with([
                    'perusahaan' => function ($q) {
                        $q->with([
                            'lokasi' => function ($lokasi) {
                                $lokasi->orderByDesc('is_head_office')
                                    ->orderByDesc('id');
                            }
                        ]);
                    }
                ])->orderByRaw("
                    CASE 
                        WHEN status_karir = 'Utama' THEN 1
                        WHEN status_karir = 'Sampingan' THEN 2
                        ELSE 3
                    END
                ")->orderByDesc('id');
            }

        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Kirim ke halaman edit
        |--------------------------------------------------------------------------
        */
        return view('admin.edit', compact('alumni'));
    }

    public function checkNim(Request $request)
    {
        return response()->json([
            'exists' => Alumni::where('nim', $request->nim)->exists()
        ]);
    }

    //Supabase 
    private function uploadFoto($file)
    {
        if (!$file) return null;

        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
        ])->attach(
            'file',
            file_get_contents($file),
            $filename
        )->post(
            env('SUPABASE_URL') .
            '/storage/v1/object/' .
            env('SUPABASE_BUCKET') .
            '/' . $filename
        );

        if ($response->failed()) {
            return null;
        }

        return env('SUPABASE_URL')
            . '/storage/v1/object/public/'
            . env('SUPABASE_BUCKET')
            . '/' . $filename;
    }

    public function store(Request $request)
    {
        $request->validate([
            'nim'          => 'required|unique:alumnis,nim',
            'nama_lengkap' => 'required'
        ]);

        DB::transaction(function () use ($request) {

            /*
            |--------------------------------------------------------------------------
            | Status Alumni
            |--------------------------------------------------------------------------
            */
            $isUnemployed = $request->has('is_unemployed');

            /*
            |--------------------------------------------------------------------------
            | Upload Foto (optional)
            |--------------------------------------------------------------------------
            */
            $foto = null;

            if ($request->hasFile('foto')) {
                $foto = $this->uploadFoto($request->file('foto'));
            }

            /*
            |--------------------------------------------------------------------------
            | 1. DATA ALUMNI
            |--------------------------------------------------------------------------
            */
            $alumni = Alumni::create([
                'nim'           => $request->nim,
                'nama_lengkap'  => $request->nama_lengkap,
                'jenis_kelamin' => $request->jenis_kelamin,
                'email'         => $request->email,
                'no_hp'         => $request->no_hp,
                'foto_profil'   => $foto
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2. DATA AKADEMIK
            |--------------------------------------------------------------------------
            */
            AlumniAkademik::create([
                'alumni_id'      => $alumni->id,
                'angkatan'       => $request->angkatan,
                'tahun_lulus'    => $request->tahun_lulus,
                'tahun_yudisium' => $request->tahun_yudisium,
                'judul_skripsi'  => $request->judul_skripsi,
                'ipk'            => $request->ipk,
                'nilai_toefl'    => $request->nilai_toefl,
                'lama_studi'     => $request->lama_studi
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. JIKA BELUM BEKERJA
            | Step 3 = DOMISILI SEKARANG
            |--------------------------------------------------------------------------
            */
            if ($isUnemployed) {

                AlamatAlumni::create([
                    'alumni_id'       => $alumni->id,
                    'alamat_lengkap'  => $request->alamat_lengkap,
                    'kota'            => $request->kota,
                    'provinsi'        => $request->provinsi,
                    'latitude'        => $request->latitude,
                    'longitude'       => $request->longitude,
                    'is_current'      => true
                ]);

                /*
                |--------------------------------------------------------------------------
                | Simpan status belum bekerja
                |--------------------------------------------------------------------------
                */
                RiwayatPekerjaan::create([
                    'alumni_id'        => $alumni->id,
                    'perusahaan_id'    => null,
                    'jabatan'          => 'Belum Bekerja',
                    'bidang_pekerjaan' => null,
                    'status_kerja'     => 'Belum Bekerja',
                    'status_karir'     => 'Pencari Kerja',
                    'is_current'       => true,
                    'masa_tunggu'      => null,
                    'gaji_nominal'     => null
                ]);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 4. JIKA SUDAH BEKERJA
            |--------------------------------------------------------------------------
            */

            /*
            | Perusahaan
            */
            $perusahaan = Perusahaan::firstOrCreate(
                [
                    'nama_perusahaan' => $request->nama_perusahaan
                ],
                [
                    'linearitas'      => $request->linearitas,
                    'link_linkedin'   => $request->link_linkedin,
                    'tingkat_instansi'=> null
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 5. LOKASI PERUSAHAAN
            |--------------------------------------------------------------------------
            */
            LokasiPerusahaan::create([
                'perusahaan_id'   => $perusahaan->id,
                'nama_cabang'     => $request->nama_perusahaan,
                'alamat_lengkap'  => $request->alamat_lengkap,
                'kota'            => $request->kota,
                'provinsi'        => $request->provinsi,
                'latitude'        => $request->latitude,
                'longitude'       => $request->longitude,
                'is_head_office'  => true
            ]);

            /*
            |--------------------------------------------------------------------------
            | 6. RIWAYAT PEKERJAAN
            |--------------------------------------------------------------------------
            */
            RiwayatPekerjaan::create([
                'alumni_id'        => $alumni->id,
                'perusahaan_id'    => $perusahaan->id,

                'jabatan'          => $request->jabatan,
                'bidang_pekerjaan' => $request->bidang_pekerjaan,

                'status_kerja'     => 'Bekerja',
                'status_karir'     => 'Utama',
                'is_current'       => true,

                'masa_tunggu' => $request->masa_tunggu !== ''
                    ? $request->masa_tunggu
                    : null,
                'gaji_nominal' => $request->gaji_nominal
                    ? preg_replace('/[^0-9]/', '', $request->gaji_nominal)
                    : null
            ]);
        });

        return redirect()
            ->route('admin.alumni.index')
            ->with('success', 'Data alumni berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $alumni = Alumni::findOrFail($id);

        DB::transaction(function () use ($request, $alumni) {

            $foto = $alumni->foto_profil;

            if ($request->hasFile('foto')) {
                $foto = $this->uploadFoto($request->file('foto'));
            }

            $alumni->update([
                'nim' => $request->nim,
                'nama_lengkap' => $request->nama_lengkap,
                'jenis_kelamin' => $request->jenis_kelamin,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'foto_profil' => $foto
            ]);

            $alumni->akademik()->updateOrCreate(
                ['alumni_id' => $alumni->id],
                [
                    'angkatan' => $request->angkatan,
                    'tahun_lulus' => $request->tahun_lulus,
                    'tahun_yudisium' => $request->tahun_yudisium,
                    'judul_skripsi' => $request->judul_skripsi,
                    'nilai_toefl' => $request->nilai_toefl,
                    'ipk' => $request->ipk,
                    'lama_studi' => $request->lama_studi
                ]
            );

            $alumni->alamat()->updateOrCreate(
                [
                    'alumni_id' => $alumni->id
                ],
                [
                    'alamat_lengkap' => $request->alamat_tinggal,
                    'kota'           => $request->kota_tinggal,
                    'provinsi'       => $request->provinsi,
                    'latitude'       => $request->latitude_tinggal,
                    'longitude'      => $request->longitude_tinggal,
                    'is_current'     => true
                ]
            );
        });

        return redirect()
            ->route('admin.alumni.index')
            ->with('success', 'Data alumni berhasil diupdate');
    }

    public function destroy($id)
    {
        Alumni::findOrFail($id)->delete();

        return back()->with('success', 'Data alumni berhasil dihapus');
    }

    //Pekerjaan 
    public function storePekerjaan(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {

            /*
            |--------------------------------------------------------------------------
            | 1. PERUSAHAAN
            |--------------------------------------------------------------------------
            */
            $perusahaan = Perusahaan::firstOrCreate(
                [
                    'nama_perusahaan' => $request->nama_perusahaan
                ],
                [
                    'linearitas'    => $request->linearitas,
                    'link_linkedin' => $request->link_linkedin
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 2. LOKASI PERUSAHAAN
            |--------------------------------------------------------------------------
            */
            LokasiPerusahaan::create([
                'perusahaan_id'  => $perusahaan->id,
                'nama_cabang'    => $request->nama_perusahaan,
                'alamat_lengkap' => $request->alamat_lengkap,
                'kota'           => $request->kota,
                'provinsi'       => $request->provinsi,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'is_head_office' => true
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. STATUS KARIR
            |--------------------------------------------------------------------------
            */
            $sudahAdaUtama = RiwayatPekerjaan::where('alumni_id', $id)
                ->where('status_karir', 'Utama')
                ->exists();

            /*
            |--------------------------------------------------------------------------
            | 4. RIWAYAT PEKERJAAN
            |--------------------------------------------------------------------------
            */
            RiwayatPekerjaan::create([
                'alumni_id'        => $id,
                'perusahaan_id'    => $perusahaan->id,
                'jabatan'          => $request->jabatan,
                'bidang_pekerjaan' => $request->bidang_pekerjaan,

                'status_kerja'     => 'Bekerja',
                'status_karir'     => $sudahAdaUtama ? 'Sampingan' : 'Utama',
                'is_current'       => true,

                'masa_tunggu' => $request->masa_tunggu !== ''
                    ? $request->masa_tunggu
                    : null,

                'gaji_nominal' => $request->gaji_nominal
                    ? preg_replace('/[^0-9]/', '', $request->gaji_nominal)
                    : null
            ]);
        });

        return back()->with('success', 'Pekerjaan berhasil ditambahkan');
    }

    public function destroyPekerjaan($id)
    {
        DB::transaction(function () use ($id) {

            /*
            |--------------------------------------------------------------------------
            | 1. Ambil data pekerjaan
            |--------------------------------------------------------------------------
            */
            $job = RiwayatPekerjaan::findOrFail($id);

            $alumniId = $job->alumni_id;
            $perusahaanId = $job->perusahaan_id;
            $isUtama = $job->status_karir === 'Utama';

            /*
            |--------------------------------------------------------------------------
            | 2. Hapus pekerjaan dipilih
            |--------------------------------------------------------------------------
            */
            $job->delete();

            /*
            |--------------------------------------------------------------------------
            | 3. Jika yang dihapus adalah pekerjaan utama,
            | pilih pekerjaan terbaru lain menjadi utama
            |--------------------------------------------------------------------------
            */
            if ($isUtama) {

                $pengganti = RiwayatPekerjaan::where('alumni_id', $alumniId)
                    ->orderByDesc('id')
                    ->first();

                if ($pengganti) {

                    RiwayatPekerjaan::where('alumni_id', $alumniId)
                        ->update([
                            'status_karir' => 'Riwayat',
                            'is_current'   => false
                        ]);

                    $pengganti->update([
                        'status_karir' => 'Utama',
                        'is_current'   => true
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Jika perusahaan sudah tidak dipakai lagi,
            | hapus lokasi perusahaan + perusahaan
            |--------------------------------------------------------------------------
            */
            if ($perusahaanId) {

                $masihDipakai = RiwayatPekerjaan::where('perusahaan_id', $perusahaanId)
                    ->exists();

                if (!$masihDipakai) {

                    LokasiPerusahaan::where('perusahaan_id', $perusahaanId)
                        ->delete();

                    Perusahaan::where('id', $perusahaanId)
                        ->delete();
                }
            }
        });

        return back()->with('success', 'Riwayat pekerjaan berhasil dihapus');
    }

    public function updateStatusKerja(Request $request, $id)
    {
        $job = RiwayatPekerjaan::findOrFail($id);

        RiwayatPekerjaan::where('alumni_id', $job->alumni_id)
            ->update([
                'status_karir' => 'Riwayat',
                'is_current' => false
            ]);

        $job->update([
            'status_karir' => $request->status ?? 'Utama',
            'is_current' => true
        ]);

        return back()->with('success', 'Status pekerjaan diubah');
    }

    public function updatePekerjaan(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {

            /*
            |--------------------------------------------------------------------------
            | 1. AMBIL DATA PEKERJAAN
            |--------------------------------------------------------------------------
            */
            $job = RiwayatPekerjaan::findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | 2. UPDATE / CARI PERUSAHAAN
            |--------------------------------------------------------------------------
            */
            $perusahaan = Perusahaan::firstOrCreate(
                [
                    'nama_perusahaan' => $request->nama_perusahaan
                ],
                [
                    'linearitas'    => $request->linearitas,
                    'link_linkedin' => $request->link_linkedin
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Update data perusahaan jika sudah ada
            |--------------------------------------------------------------------------
            */
            $perusahaan->update([
                'linearitas'    => $request->linearitas,
                'link_linkedin' => $request->link_linkedin
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. UPDATE / CREATE LOKASI PERUSAHAAN
            |--------------------------------------------------------------------------
            */
            $lokasi = LokasiPerusahaan::where('perusahaan_id', $perusahaan->id)
                ->where('is_head_office', false)
                ->first();

            if ($lokasi) {

                $lokasi->update([
                    'nama_cabang'    => $request->nama_perusahaan,
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'kota'           => $request->kota,
                    'provinsi'       => $request->provinsi,
                    'latitude'       => $request->latitude,
                    'longitude'      => $request->longitude
                ]);

            } else {

                LokasiPerusahaan::create([
                    'perusahaan_id'  => $perusahaan->id,
                    'nama_cabang'    => $request->nama_perusahaan,
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'kota'           => $request->kota,
                    'provinsi'       => $request->provinsi,
                    'latitude'       => $request->latitude,
                    'longitude'      => $request->longitude,
                    'is_head_office' => true
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. UPDATE RIWAYAT PEKERJAAN
            |--------------------------------------------------------------------------
            */
            $job->update([
                'perusahaan_id'    => $perusahaan->id,
                'jabatan'          => $request->jabatan,
                'bidang_pekerjaan' => $request->bidang_pekerjaan,

                'masa_tunggu' => $request->masa_tunggu !== ''
                    ? $request->masa_tunggu
                    : null,

                'gaji_nominal' => $request->gaji_nominal
                    ? preg_replace('/[^0-9]/', '', $request->gaji_nominal)
                    : null
            ]);
        });

        return back()->with('success', 'Riwayat pekerjaan berhasil diperbarui');
    }

    public function geocode(Request $r)
    {
        if ($r->type == 'reverse') {
            return Http::get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $r->lat,
                'lon' => $r->lng
            ])->json();
        }

        $q = $r->q;

        if ($r->wilayah == 'kalsel') {
            $q .= ', Kalimantan Selatan, Indonesia';
        } elseif ($r->wilayah == 'indonesia') {
            $q .= ', Indonesia';
        }

        return Http::get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'q' => $q,
            'limit' => 5
        ])->json();
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

        if (!$rows || !is_array($rows)) {
            return response()->json([
                'success' => 0,
                'skip'    => 0,
                'failed'  => 0,
                'message' => 'Data import tidak valid'
            ], 422);
        }

        $success = 0;
        $skip    = 0;
        $failed  = 0;

        foreach ($rows as $row) {

            try {

                /*
                |--------------------------------------------------------------------------
                | Validasi Minimal
                |--------------------------------------------------------------------------
                */
                $nim = trim($row[0] ?? '');

                if (!$nim) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Skip Jika NIM Sudah Ada
                |--------------------------------------------------------------------------
                */
                if (Alumni::where('nim', $nim)->exists()) {
                    $skip++;
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Mapping Kolom Excel
                |--------------------------------------------------------------------------
                | 0  = NIM
                | 1  = Nama
                | 2  = Email
                | 3  = Status Kerja
                | 4  = Lokasi / Alamat
                | 5  = No HP
                | 6  = Tahun Yudisium
                | 7  = Tahun Lulus
                | 10 = Nama Perusahaan
                | 11 = Gaji
                | 12 = Jabatan
                | 13 = TOEFL
                | 14 = Masa Tunggu
                | 15 = Linearitas
                */

                $nama          = trim($row[1] ?? '-');
                $email         = trim($row[2] ?? '');
                $rawStatus     = strtolower(trim($row[3] ?? ''));
                $alamatText    = trim($row[4] ?? '');
                $noHp          = trim((string) ($row[5] ?? ''));
                $tahunYudisium = $this->ambilTahun($row[6] ?? null);
                $tahunLulus    = $this->ambilTahun($row[7] ?? null);

                $namaPerusahaan = trim($row[10] ?? '');
                $gajiNominal    = (int) preg_replace('/[^0-9]/', '', $row[11] ?? 0);
                $jabatan        = trim($row[12] ?? '');
                $toefl          = is_numeric($row[13] ?? null) ? $row[13] : null;
                $masaTunggu     = (int) preg_replace('/[^0-9]/', '', $row[14] ?? 0);

                $linearitas = trim($row[15] ?? '');
                $linearitas = $linearitas
                    ? ucwords(strtolower($linearitas))
                    : 'Cukup Erat';

                /*
                |--------------------------------------------------------------------------
                | Tentukan Status Kerja
                |--------------------------------------------------------------------------
                */
                $isBekerja = str_contains($rawStatus, 'kerja')
                        || str_contains($rawStatus, 'bekerja');

                /*
                |--------------------------------------------------------------------------
                | Geocoding
                |--------------------------------------------------------------------------
                */
                $latitude  = null;
                $longitude = null;
                $kota      = null;
                $provinsi  = null;

                if ($alamatText && $alamatText !== '-') {

                    try {
                        $response = Http::withHeaders([
                            'User-Agent' => 'WebGIS Alumni Pilkom'
                        ])->get('https://nominatim.openstreetmap.org/search', [
                            'q'              => $alamatText,
                            'format'         => 'json',
                            'limit'          => 1,
                            'addressdetails' => 1
                        ]);

                        if ($response->successful() && isset($response->json()[0])) {

                            $geo = $response->json()[0];

                            $latitude  = $geo['lat'] ?? null;
                            $longitude = $geo['lon'] ?? null;

                            $addr = $geo['address'] ?? [];

                            $kota = $addr['city']
                                ?? $addr['town']
                                ?? $addr['municipality']
                                ?? $addr['county']
                                ?? null;

                            $provinsi = $addr['state'] ?? null;
                        }

                        usleep(300000); // delay 0.3 sec

                    } catch (\Exception $e) {
                        // lanjut saja
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Simpan Data
                |--------------------------------------------------------------------------
                */
                DB::transaction(function () use (
                    $nim,
                    $nama,
                    $email,
                    $noHp,
                    $tahunYudisium,
                    $tahunLulus,
                    $toefl,
                    $latitude,
                    $longitude,
                    $kota,
                    $provinsi,
                    $alamatText,
                    $isBekerja,
                    $namaPerusahaan,
                    $linearitas,
                    $jabatan,
                    $masaTunggu,
                    $gajiNominal,
                    &$success
                ) {

                    /*
                    |--------------------------------------------------------------
                    | 1. Alumni
                    |--------------------------------------------------------------
                    */
                    $alumni = Alumni::create([
                        'nim'          => $nim,
                        'nama_lengkap' => $nama,
                        'email'        => $email ?: null,
                        'no_hp'        => $noHp ?: null,
                        'foto_profil'  => null
                    ]);

                    /*
                    |--------------------------------------------------------------
                    | 2. Akademik
                    |--------------------------------------------------------------
                    */
                    AlumniAkademik::create([
                        'alumni_id'      => $alumni->id,
                        'angkatan'       => substr($nim, 0, 2),
                        'tahun_yudisium' => $tahunYudisium,
                        'tahun_lulus'    => $tahunLulus,
                        'nilai_toefl'    => $toefl
                    ]);

                    /*
                    |--------------------------------------------------------------
                    | 3. Jika BELUM Bekerja = simpan domisili
                    |--------------------------------------------------------------
                    */
                    if (!$isBekerja) {

                        AlamatAlumni::create([
                            'alumni_id'       => $alumni->id,
                            'alamat_lengkap'  => $alamatText ?: '-',
                            'kota'            => $kota,
                            'provinsi'        => $provinsi,
                            'latitude'        => $latitude,
                            'longitude'       => $longitude,
                            'is_current'      => true
                        ]);

                        $success++;
                        return;
                    }

                    /*
                    |--------------------------------------------------------------
                    | 4. Perusahaan
                    |--------------------------------------------------------------
                    */
                    $perusahaan = Perusahaan::firstOrCreate(
                        [
                            'nama_perusahaan' => $namaPerusahaan ?: '-'
                        ],
                        [
                            'linearitas'      => $linearitas,
                            'link_linkedin'   => null,
                            'tingkat_instansi'=> null
                        ]
                    );

                    /*
                    |--------------------------------------------------------------
                    | 5. Lokasi Perusahaan
                    |--------------------------------------------------------------
                    */
                    LokasiPerusahaan::firstOrCreate(
                        [
                            'perusahaan_id' => $perusahaan->id,
                            'alamat_lengkap'=> $alamatText ?: '-'
                        ],
                        [
                            'nama_cabang'    => 'Cabang Utama',
                            'kota'           => $kota,
                            'provinsi'       => $provinsi,
                            'latitude'       => $latitude,
                            'longitude'      => $longitude,
                            'is_head_office' => true
                        ]
                    );

                    /*
                    |--------------------------------------------------------------
                    | 6. Riwayat Pekerjaan
                    |--------------------------------------------------------------
                    */
                    RiwayatPekerjaan::create([
                        'alumni_id'         => $alumni->id,
                        'perusahaan_id'     => $perusahaan->id,
                        'jabatan'           => $jabatan ?: '-',
                        'bidang_pekerjaan'  => '-',
                        'status_kerja'      => 'Bekerja',
                        'status_karir'      => 'Utama',
                        'is_current'        => true,
                        'masa_tunggu'       => $masaTunggu,
                        'gaji_nominal'      => $gajiNominal
                    ]);

                    $success++;
                });

            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => $success,
            'skip'    => $skip,
            'failed'  => $failed
        ]);
    }

    private function ambilTahun($val)
    {
        if (!$val) return null;

        // Format ISO
        if (is_string($val) && str_contains($val, 'T')) {
            return (int) substr($val, 0, 4);
        }

        // Format DD/MM/YYYY
        if (is_string($val) && str_contains($val, '/')) {
            $parts = explode('/', $val);
            return (int) end($parts);
        }

        // Excel serial number
        if (is_numeric($val) && $val > 40000) {
            return date('Y', ($val - 25569) * 86400);
        }

        // Tahun biasa
        if (is_numeric($val)) {
            return (int) $val;
        }

        return null;
    }

}