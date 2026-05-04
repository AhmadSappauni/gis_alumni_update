<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AlamatAlumni;
use App\Models\Alumni;
use App\Models\AlumniAkademik;
use App\Models\LokasiPerusahaan;
use App\Models\Perusahaan;
use App\Models\RiwayatPekerjaan;
use App\Models\StudiLanjut;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AdminAlumniController extends Controller
{
    private function normalizeExcelHeaderKey($cell): ?string
    {
        if (!is_string($cell)) {
            return null;
        }

        $text = strtolower(trim($cell));
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/[^a-z0-9]+/i', '_', $text) ?? $text;
        $text = trim($text, '_');

        return $text !== '' ? $text : null;
    }

    private function normalizeJenisKelamin(?string $value): ?string
    {
        return match ($value) {
            'L', 'Laki-laki' => 'L',
            'P', 'Perempuan' => 'P',
            default => null,
        };
    }

    private function shouldJobBeCurrent(Request $request): bool
    {
        return $request->boolean('is_current_pekerjaan');
    }

    private function validatePekerjaanRequest(Request $request): void
    {
        $request->validate([
            'nama_perusahaan'   => 'required|string|max:255',
            'jabatan'           => 'required|string|max:255',
            'kota'              => 'required|string|max:255',
            'bidang_pekerjaan'  => 'required|string|max:255',
            'linearitas'        => 'required|string|max:255',
            'tanggal_mulai'     => 'nullable|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
            'masa_tunggu'       => 'nullable|integer|min:0',
            'alamat_lengkap'    => 'required|string',
            'latitude'          => 'required',
            'longitude'         => 'required',
            'link_linkedin'     => 'nullable|url',
        ]);
    }

    private function validateStudiLanjutRequest(Request $request): array
    {
        $currentYear = now()->year;

        $data = $request->validate([
            'kampus'        => 'required|string|max:255',
            'alamat_kampus' => 'nullable|string|max:500',
            'kota_kampus'   => 'nullable|string|max:255',
            'provinsi_kampus' => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'jenjang'       => 'required|string|max:255',
            'program_studi' => 'nullable|string|max:255',
            'tahun_masuk'   => 'nullable|integer|min:1900|max:' . $currentYear,
            'tahun_lulus'   => 'nullable|integer|min:1900|max:' . ($currentYear + 10),
            'status'        => 'required|string|max:255',
        ]);

        $tahunMasuk = $data['tahun_masuk'] ?? null;
        $tahunLulus = $data['tahun_lulus'] ?? null;

        if ($tahunMasuk !== null && $tahunLulus !== null && (int) $tahunLulus < (int) $tahunMasuk) {
            throw ValidationException::withMessages([
                'tahun_lulus' => 'Tahun lulus tidak boleh lebih kecil dari tahun masuk.',
            ]);
        }

        return $data;
    }

    private function parseMasaTunggu(Request $request, ?int $alumniId = null): ?int
    {
        if ($request->filled('masa_tunggu')) {
            return (int) $request->masa_tunggu;
        }

        if (!$request->filled('tanggal_mulai')) {
            return null;
        }

        $alumni = $alumniId ? Alumni::with('akademik')->find($alumniId) : null;
        $tahunLulus = $alumni?->akademik?->tahun_lulus;

        if (!$tahunLulus) {
            return null;
        }

        try {
            $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfMonth();
            $patokanLulus = Carbon::create((int) $tahunLulus, 1, 1)->startOfMonth();

            return max(0, $patokanLulus->diffInMonths($tanggalMulai, false));
        } catch (\Throwable $e) {
            return null;
        }
    }

   public function index()
    {
        $dataAlumni = Alumni::with([
            'akademik',
            'alamat',
            'pekerjaan.perusahaan',
            'studiLanjut' => function ($query) {
                $query->orderByDesc('tahun_masuk')
                    ->orderByDesc('id');
            }
        ])
        ->latest()
        ->paginate(10);

        $totalAlumni = Alumni::count();

        return view('admin.index', compact('dataAlumni', 'totalAlumni'));
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
                            'lokasiAktif',
                            'lokasi' => function ($lokasi) {
                                $lokasi->orderByDesc('id');
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
            },

            /*
            |--------------------------------------------------------------------------
            | Studi lanjut
            |--------------------------------------------------------------------------
            */
            'studiLanjut' => function ($query) {
                $query->orderByDesc('tahun_masuk')
                    ->orderByDesc('id');
            },

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

        $supabaseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_KEY');
        $supabaseBucket = env('SUPABASE_BUCKET');

        if ($supabaseUrl && $supabaseKey && $supabaseBucket) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => 'Bearer ' . $supabaseKey,
                ])->attach(
                    'file',
                    file_get_contents($file),
                    $filename
                )->post(
                    $supabaseUrl .
                    '/storage/v1/object/' .
                    $supabaseBucket .
                    '/' . $filename
                );

                if ($response->successful()) {
                    return $supabaseUrl
                        . '/storage/v1/object/public/'
                        . $supabaseBucket
                        . '/' . $filename;
                }
            } catch (\Throwable $e) {
                // Fallback ke storage lokal bila Supabase gagal diakses.
            }
        }

        return Storage::disk('public')->putFileAs(
            'alumni_foto',
            $file,
            $filename
        );
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
                'jenis_kelamin' => $this->normalizeJenisKelamin($request->jenis_kelamin),
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
                'alamat_lengkap'  => $request->alamat_lengkap,
                'kota'            => $request->kota,
                'provinsi'        => $request->provinsi,
                'latitude'        => $request->latitude,
                'longitude'       => $request->longitude
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
                'jenis_kelamin' => $this->normalizeJenisKelamin($request->jenis_kelamin),
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

    public function bulkDestroy(Request $request)
    {
        if ($request->boolean('select_all')) {
            $count = Alumni::count();
            Alumni::query()->delete();

            return back()->with('success', $count . ' data alumni berhasil dihapus.');
        }

        $ids = collect($request->input('ids', []))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu data alumni untuk dihapus.');
        }

        Alumni::whereIn('id', $ids)->delete();

        return back()->with('success', $ids->count() . ' data alumni berhasil dihapus.');
    }

    //Pekerjaan 
    public function storePekerjaan(Request $request, $id)
    {
        $this->validatePekerjaanRequest($request);

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
                'alamat_lengkap' => $request->alamat_lengkap,
                'kota'           => $request->kota,
                'provinsi'       => $request->provinsi,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. STATUS KARIR
            |--------------------------------------------------------------------------
            */
            $sudahAdaUtama = RiwayatPekerjaan::where('alumni_id', $id)
                ->where('status_karir', 'Utama')
                ->exists();

            $isCurrentPekerjaan = $this->shouldJobBeCurrent($request);
            $masaTunggu = $this->parseMasaTunggu($request, (int) $id);

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
                'status_karir'     => $isCurrentPekerjaan
                    ? ($sudahAdaUtama ? 'Sampingan' : 'Utama')
                    : 'Riwayat',
                'is_current'       => $isCurrentPekerjaan,
                'tanggal_mulai'    => $request->tanggal_mulai ?: null,
                'tanggal_selesai'  => $isCurrentPekerjaan
                    ? null
                    : ($request->tanggal_selesai ?: null),

                'masa_tunggu' => $masaTunggu,

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
        $this->validatePekerjaanRequest($request);

        DB::transaction(function () use ($request, $id) {

            /*
            |--------------------------------------------------------------------------
            | 1. AMBIL DATA PEKERJAAN
            |--------------------------------------------------------------------------
            */
            $job = RiwayatPekerjaan::findOrFail($id);
            $statusKarirSebelumnya = $job->status_karir;

            $isCurrentPekerjaan = $this->shouldJobBeCurrent($request);
            $masaTunggu = $this->parseMasaTunggu($request, $job->alumni_id);

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
                ->orderByDesc('id')
                ->first();

            if ($lokasi) {

                $lokasi->update([
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'kota'           => $request->kota,
                    'provinsi'       => $request->provinsi,
                    'latitude'       => $request->latitude,
                    'longitude'      => $request->longitude
                ]);

            } else {

                LokasiPerusahaan::create([
                    'perusahaan_id'  => $perusahaan->id,
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'kota'           => $request->kota,
                    'provinsi'       => $request->provinsi,
                    'latitude'       => $request->latitude,
                    'longitude'      => $request->longitude
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. UPDATE RIWAYAT PEKERJAAN
            |--------------------------------------------------------------------------
            */
            $statusKarirBaru = $job->status_karir;

            if (!$isCurrentPekerjaan) {
                $statusKarirBaru = 'Riwayat';
            } elseif ($job->status_karir === 'Riwayat') {
                $adaUtamaLain = RiwayatPekerjaan::where('alumni_id', $job->alumni_id)
                    ->where('id', '!=', $job->id)
                    ->where('status_karir', 'Utama')
                    ->where('is_current', true)
                    ->exists();

                $statusKarirBaru = $adaUtamaLain ? 'Sampingan' : 'Utama';
            }

            $job->update([
                'perusahaan_id'    => $perusahaan->id,
                'jabatan'          => $request->jabatan,
                'bidang_pekerjaan' => $request->bidang_pekerjaan,
                'status_karir'     => $statusKarirBaru,
                'is_current'       => $isCurrentPekerjaan,
                'tanggal_mulai'    => $request->tanggal_mulai ?: null,
                'tanggal_selesai'  => $isCurrentPekerjaan
                    ? null
                    : ($request->tanggal_selesai ?: null),

                'masa_tunggu' => $masaTunggu,

                'gaji_nominal' => $request->gaji_nominal
                    ? preg_replace('/[^0-9]/', '', $request->gaji_nominal)
                    : null
            ]);

            if (!$isCurrentPekerjaan && $statusKarirSebelumnya === 'Utama') {
                $penggantiUtama = RiwayatPekerjaan::where('alumni_id', $job->alumni_id)
                    ->where('id', '!=', $job->id)
                    ->where('is_current', true)
                    ->orderByDesc('id')
                    ->first();

                if ($penggantiUtama) {
                    $penggantiUtama->update([
                        'status_karir' => 'Utama',
                        'is_current' => true
                    ]);
                }
            }
        });

        return back()->with('success', 'Riwayat pekerjaan berhasil diperbarui');
    }

    /*
    |--------------------------------------------------------------------------
    | STUDI LANJUT
    |--------------------------------------------------------------------------
    */

    public function storeStudiLanjut(Request $request, $alumni)
    {
        $alumniModel = Alumni::findOrFail($alumni);

        $data = $this->validateStudiLanjutRequest($request);
        $data['alumni_id'] = $alumniModel->id;

        StudiLanjut::create($data);

        return redirect()
            ->route('admin.alumni.edit', $alumniModel->id)
            ->with('success', 'Studi lanjut berhasil ditambahkan.')
            ->with('active_tab', 'tab-studi');
    }

    public function updateStudiLanjut(Request $request, $alumni, $studiLanjut)
    {
        $alumniModel = Alumni::findOrFail($alumni);

        $studi = StudiLanjut::where('alumni_id', $alumniModel->id)
            ->findOrFail($studiLanjut);

        $data = $this->validateStudiLanjutRequest($request);

        $studi->update($data);

        return redirect()
            ->route('admin.alumni.edit', $alumniModel->id)
            ->with('success', 'Studi lanjut berhasil diperbarui.')
            ->with('active_tab', 'tab-studi');
    }

    public function destroyStudiLanjut($alumni, $studiLanjut)
    {
        $alumniModel = Alumni::findOrFail($alumni);

        $studi = StudiLanjut::where('alumni_id', $alumniModel->id)
            ->findOrFail($studiLanjut);

        $studi->delete();

        return redirect()
            ->route('admin.alumni.edit', $alumniModel->id)
            ->with('success', 'Studi lanjut berhasil dihapus.')
            ->with('active_tab', 'tab-studi');
    }

    public function geocode(Request $r)
    {
        if ($r->type == 'reverse') {
            return Http::get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $r->lat,
                'lon' => $r->lng,
                'addressdetails' => 1
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
            'limit' => 5,
            'addressdetails' => 1
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

        $sheetRows = $data[0] ?? [];
        if (count($sheetRows) < 2) {
            return response()->json([
                'headers' => [],
                'rows'    => [],
            ]);
        }

        // baris pertama dianggap header (contoh: nim, nama_lengkap, dst)
        // normalisasi: lower + non-alnum -> underscore, agar "Kota/Kabupaten" => "kota_kabupaten"
        $headerRow = array_map(fn ($cell) => $this->normalizeExcelHeaderKey($cell), $sheetRows[0]);

        $headers = array_values(array_filter($headerRow, fn ($h) => (bool) $h));

        $result = [];
        foreach (array_slice($sheetRows, 1) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($headerRow as $i => $key) {
                if (!$key) {
                    continue;
                }
                $assoc[$key] = $row[$i] ?? null;
            }

            $nim = trim((string) ($assoc['nim'] ?? ''));
            if ($nim === '') {
                continue;
            }

            $result[] = $assoc;
        }

        return response()->json([
            'headers' => $headers,
            'rows'    => $result,
        ]);
    }

    public function importStore(Request $request)
    {
        // Import bisa memakan waktu lama karena geocoding per baris
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $rowsRaw = $request->input('rows');
        $rows = is_string($rowsRaw) ? json_decode($rowsRaw, true) : $rowsRaw;

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
        $no_map  = 0;

        foreach ($rows as $row) {

            try {

                /*
                |--------------------------------------------------------------------------
                | Validasi Minimal
                |--------------------------------------------------------------------------
                */
                $nim = trim((string) ($row['nim'] ?? ''));

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
                | Mapping Kolom Excel (berdasarkan header)
                |--------------------------------------------------------------------------
                | Wajib minimal: nim
                | Contoh header yang didukung:
                | nim, nama_lengkap, jenis_kelamin, email, no_hp,
                | angkatan, tahun_lulus, tahun_yudisium, nilai_toefl,
                | alamat_lengkap_alumni, kota_alumni, provinsi_alumni,
                | nama_perusahaan, linearitas,
                | alamat_lengkap_perusahaan, kota_perusahaan, provinsi_perusahaan,
                | jabatan, status_kerja, tanggal_mulai, tanggal_selesai,
                | masa_tunggu, status_karir, gaji_nominal,
                | jenjang, tahun_masuk_studi_lanjut, tahun_lulus_studi_lanjut, status_studi_lanjut
                */

                $nama = trim((string) ($row['nama_lengkap'] ?? '-'));
                $jenisKelamin = $this->normalizeJenisKelamin(
                    ($row['jenis_kelamin'] ?? null) !== null ? trim((string) $row['jenis_kelamin']) : null
                );
                $email = trim((string) ($row['email'] ?? '')) ?: null;
                $noHp = trim((string) ($row['no_hp'] ?? '')) ?: null;

                $angkatan = $this->ambilTahun($row['angkatan'] ?? null);
                $tahunLulus = $this->ambilTahun($row['tahun_lulus'] ?? null);
                $tahunYudisium = $this->ambilTahun($row['tahun_yudisium'] ?? null);
                $toefl = is_numeric($row['nilai_toefl'] ?? null) ? (int) $row['nilai_toefl'] : null;

                $alamatAlumni = $this->getRowValue($row, [
                    'alamat_lengkap_alumni',
                    'alamat_alumni',
                    'alamat_domisili',
                    'alamat_tinggal',
                    'alamat_lengkap',
                    'alamat',
                ]);
                $alamatGeocodingAlumni = $this->getRowValue($row, [
                    'alamat_geocoding_alumni',
                    'alamat_geocode_alumni',
                    'alamat_geocoding_domisili',
                    'alamat_geocode_domisili',
                ]);
                $statusAlamatAlumni = $this->getRowValue($row, [
                    'status_alamat_alumni',
                    'status_geocoding_alumni',
                    'status_alamat_domisili',
                    'status_geocoding_domisili',
                ]);
                $kotaAlumni = $this->getRowValue($row, [
                    'kota_kabupaten_alumni',
                    'kota_kabupaten',
                    'kota_alumni',
                    'kota',
                    'kabupaten_alumni',
                    'kabupaten',
                ]);
                $provinsiAlumni = $this->getRowValue($row, [
                    'provinsi_alumni',
                    'provinsi',
                    'propinsi_alumni',
                    'propinsi',
                    'provinsi_domisili',
                    'propinsi_domisili',
                ]);

                $namaPerusahaan = trim((string) ($row['nama_perusahaan'] ?? '')) ?: null;
                $linearitas = trim((string) ($row['linearitas'] ?? '')) ?: null;
                $linearitas = $linearitas ? ucwords(strtolower($linearitas)) : null;

                $alamatPerusahaan = $this->getRowValue($row, [
                    'alamat_lengkap_perusahaan',
                    'alamat_perusahaan',
                    'alamat_kantor',
                    'alamat_instansi',
                    'alamat_lengkap_kantor',
                    'alamat_lengkap_instansi',
                ]);
                $alamatGeocodingPerusahaan = $this->getRowValue($row, [
                    'alamat_geocoding_perusahaan',
                    'alamat_geocode_perusahaan',
                    'alamat_geocoding_kantor',
                    'alamat_geocode_kantor',
                    'alamat_geocoding_instansi',
                    'alamat_geocode_instansi',
                ]);
                $statusAlamatPerusahaan = $this->getRowValue($row, [
                    'status_alamat_perusahaan',
                    'status_geocoding_perusahaan',
                    'status_alamat_kantor',
                    'status_geocoding_kantor',
                    'status_alamat_instansi',
                    'status_geocoding_instansi',
                ]);
                $kotaPerusahaan = $this->getRowValue($row, [
                    'kota_kabupaten_perusahaan',
                    'kota_kabupaten_kantor',
                    'kota_kabupaten_instansi',
                    'kota_perusahaan',
                    'kota_kantor',
                    'kota_instansi',
                    'kabupaten_perusahaan',
                    'kabupaten_kantor',
                    'kabupaten_instansi',
                ]);
                $provinsiPerusahaan = $this->getRowValue($row, [
                    'provinsi_perusahaan',
                    'propinsi_perusahaan',
                    'provinsi_kantor',
                    'propinsi_kantor',
                    'provinsi_instansi',
                    'propinsi_instansi',
                ]);

                $jabatan = trim((string) ($row['jabatan'] ?? '')) ?: null;
                $statusKerja = trim((string) ($row['status_kerja'] ?? '')) ?: null;
                $tanggalMulai = $this->parseTanggal($row['tanggal_mulai'] ?? null);
                $tanggalSelesai = $this->parseTanggal($row['tanggal_selesai'] ?? null);
                $masaTunggu = $this->toInt($row['masa_tunggu'] ?? null);
                $statusKarir = trim((string) ($row['status_karir'] ?? '')) ?: null;
                $gajiNominal = $this->toInt($row['gaji_nominal'] ?? null);

                $jenjangStudi = trim((string) ($row['jenjang'] ?? '')) ?: null;
                $tahunMasukStudi = $this->ambilTahun($row['tahun_masuk_studi_lanjut'] ?? null);
                $tahunLulusStudi = $this->ambilTahun($row['tahun_lulus_studi_lanjut'] ?? null);
                $statusStudi = trim((string) ($row['status_studi_lanjut'] ?? '')) ?: null;

                /*
                |--------------------------------------------------------------------------
                | Tentukan Status Kerja
                |--------------------------------------------------------------------------
                */
                $rawStatus = strtolower((string) ($statusKerja ?? ''));

                // "Belum/Tidak kerja" harus dianggap tidak bekerja (hindari false-positive karena mengandung kata "kerja")
                $isNegatifKerja = str_contains($rawStatus, 'belum') || str_contains($rawStatus, 'tidak');
                $isBekerja = !$isNegatifKerja && (str_contains($rawStatus, 'bekerja') || str_contains($rawStatus, 'kerja'));

                // Jika kolom status_kerja kosong tapi ada data pekerjaan, anggap bekerja
                if (!$rawStatus && ($namaPerusahaan || $jabatan || $tanggalMulai || $tanggalSelesai || $gajiNominal !== null)) {
                    $isBekerja = true;
                }

                // Normalisasi nilai status kerja agar konsisten (dipakai di MapController)
                $statusKerja = $isBekerja ? 'Bekerja' : 'Belum Bekerja';
                $isCurrentPekerjaan = $tanggalSelesai === null
                    || ($statusKarir && str_contains(strtolower($statusKarir), 'utama'));

                // Riwayat pekerjaan hanya dibuat jika memang ada data pekerjaan yang bermakna.
                // Jangan gunakan $statusKerja karena nilainya selalu terisi (Bekerja/Belum Bekerja).
                $hasJobData = $namaPerusahaan
                    || $jabatan
                    || $tanggalMulai
                    || $tanggalSelesai
                    || $masaTunggu !== null
                    || $statusKarir
                    || $gajiNominal !== null;

                /*
                |--------------------------------------------------------------------------
                | Geocoding (otomatis latitude/longitude dari alamat)
                |--------------------------------------------------------------------------
                */
                $geoContextBase = [
                    'nim' => $nim,
                    'nama' => $nama,
                ];

                // 1) Prioritas koordinat dari Excel (jika ada)
                $latExcelAlumni = $this->parseCoordinate($this->getRowValue($row, [
                    'latitude_alumni',
                    'lat_alumni',
                    'latitude_domisili',
                    'lat_domisili',
                    'latitude',
                    'lat',
                ]));
                $lngExcelAlumni = $this->parseCoordinate($this->getRowValue($row, [
                    'longitude_alumni',
                    'lng_alumni',
                    'lon_alumni',
                    'longitude_domisili',
                    'lng_domisili',
                    'lon_domisili',
                    'longitude',
                    'lng',
                    'lon',
                ]));

                $latAlumni = null;
                $lngAlumni = null;
                $alumniGeoLevel = null;
                $alumniGeoQuery = null;

                if ($this->isValidLatLng($latExcelAlumni, $lngExcelAlumni, $provinsiAlumni)) {
                    $latAlumni = $latExcelAlumni;
                    $lngAlumni = $lngExcelAlumni;
                    Log::info('Geocoding alumni (from excel coords)', $geoContextBase + [
                        'lat' => $latAlumni,
                        'lng' => $lngAlumni,
                    ]);
                } else {
                    if ($latExcelAlumni !== null || $lngExcelAlumni !== null) {
                        Log::info('Excel coords invalid (alumni)', $geoContextBase + [
                            'lat' => $latExcelAlumni,
                            'lng' => $lngExcelAlumni,
                            'provinsi' => $provinsiAlumni,
                        ]);
                    }

                    [$latAlumni, $lngAlumni, $alumniGeoLevel, $alumniGeoQuery] = $this->geocodeIfPossible(
                        $alamatAlumni,
                        $kotaAlumni,
                        $provinsiAlumni,
                        $geoContextBase + [
                            'type' => 'alumni',
                            'status_alamat' => $statusAlamatAlumni,
                        ]
                        , $alamatGeocodingAlumni
                    );
                }

                $sourceAlumniCoordinate = 'kosong';
                if ($this->isValidLatLng($latExcelAlumni, $lngExcelAlumni, $provinsiAlumni)) {
                    $sourceAlumniCoordinate = 'excel';
                } elseif ($latAlumni !== null && $lngAlumni !== null) {
                    $sourceAlumniCoordinate = ($alumniGeoLevel === 0)
                        ? 'geocoding_alamat_geocoding'
                        : 'geocoding_alamat_gabungan';
                }

                Log::info('Import koordinat alumni', [
                    'nim' => $nim,
                    'nama' => $nama,
                    'lat_excel' => $latExcelAlumni,
                    'lng_excel' => $lngExcelAlumni,
                    'lat_final' => $latAlumni,
                    'lng_final' => $lngAlumni,
                    'source' => $sourceAlumniCoordinate,
                ]);

                $latExcelPerusahaan = $this->parseCoordinate($this->getRowValue($row, [
                    'latitude_kerja',
                    'lat_kerja',
                    'latitude_perusahaan',
                    'lat_perusahaan',
                    'latitude_kantor',
                    'lat_kantor',
                    'latitude_instansi',
                    'lat_instansi',
                ]));
                $lngExcelPerusahaan = $this->parseCoordinate($this->getRowValue($row, [
                    'longitude_kerja',
                    'lng_kerja',
                    'lon_kerja',
                    'longitude_perusahaan',
                    'lng_perusahaan',
                    'lon_perusahaan',
                    'longitude_kantor',
                    'lng_kantor',
                    'lon_kantor',
                    'longitude_instansi',
                    'lng_instansi',
                    'lon_instansi',
                ]));

                $latPerusahaan = null;
                $lngPerusahaan = null;
                $perusahaanGeoLevel = null;
                $perusahaanGeoQuery = null;

                if ($this->isValidLatLng($latExcelPerusahaan, $lngExcelPerusahaan, $provinsiPerusahaan)) {
                    $latPerusahaan = $latExcelPerusahaan;
                    $lngPerusahaan = $lngExcelPerusahaan;
                    Log::info('Geocoding perusahaan (from excel coords)', $geoContextBase + [
                        'nama_perusahaan' => $namaPerusahaan,
                        'lat' => $latPerusahaan,
                        'lng' => $lngPerusahaan,
                    ]);
                } else {
                    if ($latExcelPerusahaan !== null || $lngExcelPerusahaan !== null) {
                        Log::info('Excel coords invalid (perusahaan)', $geoContextBase + [
                            'nama_perusahaan' => $namaPerusahaan,
                            'lat' => $latExcelPerusahaan,
                            'lng' => $lngExcelPerusahaan,
                            'provinsi' => $provinsiPerusahaan,
                        ]);
                    }

                    [$latPerusahaan, $lngPerusahaan, $perusahaanGeoLevel, $perusahaanGeoQuery] = $this->geocodeIfPossible(
                        $alamatPerusahaan,
                        $kotaPerusahaan,
                        $provinsiPerusahaan,
                        $geoContextBase + [
                            'type' => 'perusahaan',
                            'nama_perusahaan' => $namaPerusahaan,
                            'status_alamat' => $statusAlamatPerusahaan,
                        ]
                        , $alamatGeocodingPerusahaan
                    );
                }

                $sourceKerjaCoordinate = 'kosong';
                if ($this->isValidLatLng($latExcelPerusahaan, $lngExcelPerusahaan, $provinsiPerusahaan)) {
                    $sourceKerjaCoordinate = 'excel';
                } elseif ($latPerusahaan !== null && $lngPerusahaan !== null) {
                    $sourceKerjaCoordinate = ($perusahaanGeoLevel === 0)
                        ? 'geocoding_alamat_geocoding'
                        : 'geocoding_alamat_gabungan';
                }

                Log::info('Import koordinat kerja', [
                    'nim' => $nim,
                    'nama_perusahaan' => $namaPerusahaan,
                    'lat_excel' => $latExcelPerusahaan,
                    'lng_excel' => $lngExcelPerusahaan,
                    'lat_final' => $latPerusahaan,
                    'lng_final' => $lngPerusahaan,
                    'source' => $sourceKerjaCoordinate,
                ]);

                // Catat data yang tersimpan tapi tidak punya koordinat (tidak akan muncul di peta)
                if ($isBekerja) {
                    if ($latPerusahaan === null || $lngPerusahaan === null) {
                        $no_map++;
                    }
                } else {
                    if ($latAlumni === null || $lngAlumni === null) {
                        $no_map++;
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
                    $jenisKelamin,
                    $email,
                    $noHp,
                    $angkatan,
                    $tahunYudisium,
                    $tahunLulus,
                    $toefl,
                    $alamatAlumni,
                    $kotaAlumni,
                    $provinsiAlumni,
                    $latAlumni,
                    $lngAlumni,
                    $isBekerja,
                    $namaPerusahaan,
                    $linearitas,
                    $jabatan,
                    $masaTunggu,
                    $gajiNominal,
                    $statusKerja,
                    $statusKarir,
                    $tanggalMulai,
                    $tanggalSelesai,
                    $isCurrentPekerjaan,
                    $alamatPerusahaan,
                    $kotaPerusahaan,
                    $provinsiPerusahaan,
                    $latPerusahaan,
                    $lngPerusahaan,
                    $hasJobData,
                    $jenjangStudi,
                    $tahunMasukStudi,
                    $tahunLulusStudi,
                    $statusStudi,
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
                        'jenis_kelamin'=> $jenisKelamin,
                        'email'        => $email,
                        'no_hp'        => $noHp,
                        'foto_profil'  => null
                    ]);

                    /*
                    |--------------------------------------------------------------
                    | 2. Akademik
                    |--------------------------------------------------------------
                    */
                    AlumniAkademik::create([
                        'alumni_id'      => $alumni->id,
                        'angkatan'       => $angkatan ?? (int) substr($nim, 0, 2),
                        'tahun_yudisium' => $tahunYudisium,
                        'tahun_lulus'    => $tahunLulus,
                        'nilai_toefl'    => $toefl
                    ]);

                    /*
                    |--------------------------------------------------------------
                    | 3. Simpan domisili alumni (jika ada)
                    |--------------------------------------------------------------
                    */
                    if ($alamatAlumni || $kotaAlumni || $provinsiAlumni) {
                        $alamatUpdate = [
                            'is_current' => true,
                        ];

                        if ($alamatAlumni !== null) {
                            $alamatUpdate['alamat_lengkap'] = $alamatAlumni;
                        }
                        if ($kotaAlumni !== null) {
                            $alamatUpdate['kota'] = $kotaAlumni;
                        }
                        if ($provinsiAlumni !== null) {
                            $alamatUpdate['provinsi'] = $provinsiAlumni;
                        }

                        // Jangan menimpa koordinat lama dengan null.
                        if ($latAlumni !== null && $lngAlumni !== null) {
                            $alamatUpdate['latitude'] = $latAlumni;
                            $alamatUpdate['longitude'] = $lngAlumni;
                        }

                        AlamatAlumni::updateOrCreate(
                            [
                                'alumni_id' => $alumni->id,
                                'is_current' => true,
                            ],
                            $alamatUpdate
                        );
                    }

                    /*
                    |--------------------------------------------------------------
                    | 4. Perusahaan
                    |--------------------------------------------------------------
                    */
                    $perusahaan = null;
                    if ($namaPerusahaan) {
                        $perusahaan = Perusahaan::firstOrCreate(
                            [
                                'nama_perusahaan' => $namaPerusahaan
                            ],
                            [
                                'linearitas'       => $linearitas,
                                'link_linkedin'    => null,
                                'tingkat_instansi' => null
                            ]
                        );

                        if ($linearitas && !$perusahaan->linearitas) {
                            $perusahaan->update(['linearitas' => $linearitas]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------
                    | 5. Lokasi Perusahaan
                    |--------------------------------------------------------------
                    */
                    if ($perusahaan && ($alamatPerusahaan || $kotaPerusahaan || $provinsiPerusahaan)) {
                        $lokasiUpdate = [];

                        if ($kotaPerusahaan !== null) {
                            $lokasiUpdate['kota'] = $kotaPerusahaan;
                        }
                        if ($provinsiPerusahaan !== null) {
                            $lokasiUpdate['provinsi'] = $provinsiPerusahaan;
                        }

                        // Jangan menimpa koordinat lama dengan null.
                        if ($latPerusahaan !== null && $lngPerusahaan !== null) {
                            $lokasiUpdate['latitude'] = $latPerusahaan;
                            $lokasiUpdate['longitude'] = $lngPerusahaan;
                        }

                        LokasiPerusahaan::updateOrCreate(
                            [
                                'perusahaan_id'  => $perusahaan->id,
                                'alamat_lengkap' => $alamatPerusahaan
                            ],
                            $lokasiUpdate
                        );
                    }

                    /*
                    |--------------------------------------------------------------
                    | 6. Riwayat Pekerjaan
                    |--------------------------------------------------------------
                    */
                    if ($hasJobData) {
                        RiwayatPekerjaan::create([
                            'alumni_id'         => $alumni->id,
                            'perusahaan_id'     => $perusahaan?->id,
                            'jabatan'           => $jabatan,
                            'bidang_pekerjaan'  => '-',
                            'status_kerja'      => $statusKerja,
                            'status_karir'      => $statusKarir,
                            'is_current'        => $isCurrentPekerjaan,
                            'tanggal_mulai'     => $tanggalMulai,
                            'tanggal_selesai'   => $tanggalSelesai,
                            'masa_tunggu'       => $masaTunggu,
                            'gaji_nominal'      => $gajiNominal
                        ]);
                    }

                    if ($jenjangStudi || $statusStudi || $tahunMasukStudi || $tahunLulusStudi) {
                        StudiLanjut::create([
                            'alumni_id'        => $alumni->id,
                            'kampus'           => null,
                            'alamat_kampus'    => null,
                            'kota_kampus'      => null,
                            'provinsi_kampus'  => null,
                            'latitude'         => null,
                            'longitude'        => null,
                            'jenjang'          => $jenjangStudi,
                            'program_studi'    => null,
                            'tahun_masuk'      => $tahunMasukStudi,
                            'tahun_lulus'      => $tahunLulusStudi,
                            'status'           => $statusStudi
                        ]);
                    }

                    $success++;
                });

            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        return response()->json([
            'success' => $success,
            'skip'    => $skip,
            'failed'  => $failed
            , 'no_map' => $no_map
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

    private function toInt($val): ?int
    {
        if ($val === null || $val === '') {
            return null;
        }
        if (is_int($val)) {
            return $val;
        }
        if (is_float($val)) {
            return (int) round($val);
        }
        if (is_numeric($val)) {
            return (int) $val;
        }

        $clean = preg_replace('/[^0-9]/', '', (string) $val);
        return $clean === '' ? null : (int) $clean;
    }

    private function parseTanggal($val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        // Excel serial date (hari sejak 1899-12-30)
        if (is_numeric($val) && $val > 20000 && $val < 90000) {
            try {
                return Carbon::createFromTimestampUTC(((float) $val - 25569) * 86400)->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (is_string($val)) {
            $str = trim($val);
            if ($str === '') {
                return null;
            }
            try {
                return Carbon::parse($str)->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function normalizePlaceName(?string $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        // Normalisasi singkatan umum
        $map = [
            '/\bkalsel\b/ui' => 'Kalimantan Selatan',
            '/\bkal[\s\-]*sel\b/ui' => 'Kalimantan Selatan',
            '/\bbjm\b/ui' => 'Banjarmasin',
            '/\bbanjar\s*baru\b/ui' => 'Banjarbaru',
            '/\bhst\b/ui' => 'Hulu Sungai Tengah',
            '/\bhsu\b/ui' => 'Hulu Sungai Utara',
            '/\bhss\b/ui' => 'Hulu Sungai Selatan',
            '/\btanbu\b/ui' => 'Tanah Bumbu',
            '/\btala\b/ui' => 'Tanah Laut',
        ];

        foreach ($map as $pattern => $replace) {
            $text = preg_replace($pattern, $replace, $text) ?? $text;
        }

        // Buang prefix administratif yang sering bikin ambigu
        $text = preg_replace('/\b(kab\.?|kabupaten|kota)\b/ui', '', $text) ?? $text;
        $text = preg_replace('/\b(prov\.?|provinsi|prop\.?|propinsi)\b/ui', '', $text) ?? $text;

        // Rapikan spasi & koma
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s*,\s*/u', ', ', $text) ?? $text;
        $text = trim($text, " \t\n\r\0\x0B,");

        return $text !== '' ? $text : null;
    }

    private function buildGeocodingQuery(?string $alamat = null, ?string $kotaKabupaten = null, ?string $provinsi = null): ?string
    {
        $dynamicParts = [
            $this->normalizePlaceName($alamat),
            $this->normalizePlaceName($kotaKabupaten),
            $this->normalizePlaceName($provinsi),
        ];

        $dynamicParts = array_values(array_filter($dynamicParts, fn ($p) => (bool) $p));
        if (empty($dynamicParts)) {
            return null;
        }

        $parts = array_merge($dynamicParts, ['Indonesia']);

        $parts = array_values(array_filter($parts, fn ($p) => (bool) $p));
        if (empty($parts)) {
            return null;
        }

        // Hindari duplikasi (case-insensitive)
        $unique = [];
        foreach ($parts as $part) {
            $key = strtolower($part);
            if (!isset($unique[$key])) {
                $unique[$key] = $part;
            }
        }

        return implode(', ', array_values($unique));
    }

    private function normalizeGeocodingQuery(?string $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $rawParts = array_map('trim', explode(',', $text));
        $parts = [];

        foreach ($rawParts as $part) {
            $normalized = $this->normalizePlaceName($part);
            if ($normalized) {
                $parts[] = $normalized;
            }
        }

        if (empty($parts)) {
            $normalized = $this->normalizePlaceName($text);
            if ($normalized) {
                $parts[] = $normalized;
            }
        }

        // Pastikan ada "Indonesia" di akhir
        $hasIndonesia = false;
        foreach ($parts as $p) {
            if (strtolower($p) === 'indonesia') {
                $hasIndonesia = true;
                break;
            }
        }

        if (!$hasIndonesia) {
            $parts[] = 'Indonesia';
        }

        // Unique case-insensitive
        $unique = [];
        foreach ($parts as $p) {
            $key = strtolower($p);
            if (!isset($unique[$key])) {
                $unique[$key] = $p;
            }
        }

        return implode(', ', array_values($unique));
    }

    private function buildGeocodingQueries(?string $alamat, ?string $kotaKabupaten, ?string $provinsi, ?string $primaryQuery = null): array
    {
        $q0 = $this->normalizeGeocodingQuery($primaryQuery);
        $q1 = $this->buildGeocodingQuery($alamat, $kotaKabupaten, $provinsi);
        $q2 = $this->buildGeocodingQuery(null, $kotaKabupaten, $provinsi);

        $queries = [
            0 => $q0,
            1 => $q1,
            2 => $q2,
        ];

        // Hapus null dan duplikasi
        $out = [];
        $seen = [];
        foreach ($queries as $level => $q) {
            if (!$q) {
                continue;
            }
            $key = strtolower($q);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[$level] = $q;
        }

        return $out;
    }

    private function parseCoordinate($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $text = trim($value);
        if ($text === '') {
            return null;
        }

        // Support format "114,59258" (koma sebagai desimal)
        $text = str_replace(',', '.', $text);
        // Hapus karakter non angka kecuali - dan .
        $text = preg_replace('/[^0-9\.\-]/', '', $text) ?? $text;

        if ($text === '' || $text === '-' || $text === '.' || $text === '-.') {
            return null;
        }

        return is_numeric($text) ? (float) $text : null;
    }

    private function shouldFlagGeocodingReview(?string $statusAlamat, ?int $fallbackLevel): bool
    {
        if ($fallbackLevel === null) {
            return false;
        }

        // Level 2 = hanya kota/kabupaten + provinsi (akurasi sedang/rendah)
        if ($fallbackLevel < 2) {
            return false;
        }

        $status = strtolower(trim((string) $statusAlamat));
        if ($status === '') {
            return true;
        }

        if (str_contains($status, 'kurang')) {
            return true;
        }
        if (str_contains($status, 'perlu')) {
            return true;
        }
        if (str_contains($status, 'cek')) {
            return true;
        }

        return false;
    }

    private function isValidLatLng(?float $lat, ?float $lng, ?string $provinsiHint = null): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }
        if ($lat < -90 || $lat > 90) {
            return false;
        }
        if ($lng < -180 || $lng > 180) {
            return false;
        }

        // Validasi kasar Indonesia (hindari nyasar keluar negeri)
        if ($lat < -11.5 || $lat > 6.5 || $lng < 94.0 || $lng > 141.5) {
            return false;
        }

        $prov = strtolower((string) $this->normalizePlaceName($provinsiHint));
        $isKalsel = str_contains($prov, 'kalimantan selatan');
        if ($isKalsel) {
            // Validasi kasar Kalimantan Selatan
            // (dibuat agak longgar untuk menghindari false-negative)
            if ($lat < -5.3 || $lat > -0.8 || $lng < 113.3 || $lng > 117.6) {
                return false;
            }
        }

        return true;
    }

    private function getRowValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $val = $row[$key];
            $text = is_string($val) ? trim($val) : (is_numeric($val) ? (string) $val : '');
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function geocodeIfPossible(?string $alamat, ?string $kotaKabupaten, ?string $provinsi, array $context = [], ?string $primaryQuery = null): array
    {
        $queriesByLevel = $this->buildGeocodingQueries($alamat, $kotaKabupaten, $provinsi, $primaryQuery);
        if (empty($queriesByLevel)) {
            return [null, null, null, null];
        }

        static $cache = [];
        static $lastCallAt = 0.0;

        foreach ($queriesByLevel as $level => $q) {

            if (isset($cache[$q])) {
                [$cachedLat, $cachedLng] = $cache[$q];
                Log::debug('Geocoding cache hit', $context + [
                    'query' => $q,
                    'level' => $level,
                    'lat' => $cachedLat,
                    'lng' => $cachedLng,
                ]);

                return [$cachedLat, $cachedLng, $level, $q];
            }

            try {
                // Throttle: max ~1 req/detik (hindari 429 / blokir Nominatim)
                $now = microtime(true);
                $minInterval = 1.05;
                $elapsed = $now - $lastCallAt;
                if ($elapsed < $minInterval) {
                    usleep((int) (($minInterval - $elapsed) * 1_000_000));
                }

                $response = Http::withHeaders([
                    // Nominatim minta identitas aplikasi yang jelas
                    'User-Agent' => 'WebGIS Alumni Pilkom (Laravel)'
                ])
                    ->acceptJson()
                    ->connectTimeout(8)
                    ->timeout(20)
                    ->retry(2, 1100)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q'      => $q,
                        'format' => 'json',
                        'limit'  => 1,
                        'addressdetails' => 1,
                        'countrycodes' => 'id',
                    ]);

                $lastCallAt = microtime(true);

                if ($response->successful() && isset($response->json()[0])) {
                    $geo = $response->json()[0];

                    // Kalau hasilnya cuma level "Indonesia" (terlalu general), anggap gagal supaya tidak salah titik.
                    $displayName = trim(strtolower((string) ($geo['display_name'] ?? '')));
                    $isJustIndonesia = $displayName === 'indonesia' || $displayName === 'republic of indonesia';
                    if ($isJustIndonesia && strlen(trim($q)) > 15) {
                        usleep(350000);
                        continue;
                    }

                    // Guard tambahan: beberapa query gagal dan jatuh ke centroid Indonesia (sering terlihat "nyasar" di laut).
                    $latNum = is_numeric($geo['lat'] ?? null) ? (float) $geo['lat'] : null;
                    $lonNum = is_numeric($geo['lon'] ?? null) ? (float) $geo['lon'] : null;
                    if ($latNum !== null && $lonNum !== null) {
                        $isIndonesiaCentroid = abs($latNum - (-2.4833826)) < 0.01 && abs($lonNum - 117.8902853) < 0.01;
                        if ($isIndonesiaCentroid && strlen(trim($q)) > 15) {
                            Log::info('Geocoding rejected (centroid)', $context + [
                                'query' => $q,
                                'level' => $level,
                                'lat' => $latNum,
                                'lng' => $lonNum,
                            ]);
                            continue;
                        }
                    }

                    if (!$this->isValidLatLng($latNum, $lonNum, $provinsi)) {
                        Log::info('Geocoding rejected (invalid coords)', $context + [
                            'query' => $q,
                            'level' => $level,
                            'lat' => $latNum,
                            'lng' => $lonNum,
                        ]);
                        continue;
                    }

                    $cache[$q] = [$latNum, $lonNum];

                    Log::debug('Geocoding success', $context + [
                        'query' => $q,
                        'level' => $level,
                        'lat' => $latNum,
                        'lng' => $lonNum,
                    ]);

                    if ($this->shouldFlagGeocodingReview($context['status_alamat'] ?? null, (int) $level)) {
                        Log::warning('Geocoding perlu review (akurasi sedang)', $context + [
                            'query' => $q,
                            'fallback_level' => $level,
                            'lat' => $latNum,
                            'lng' => $lonNum,
                        ]);
                    }

                    return [$latNum, $lonNum, $level, $q];
                }

            } catch (\Throwable $e) {
                Log::debug('Geocode error', [
                    'query' => $q,
                    'level' => $level,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Geocode not found', $context + [
            'alamat' => $alamat,
            'kota_kabupaten' => $kotaKabupaten,
            'provinsi' => $provinsi,
        ]);

        return [null, null, null, null];
    }

}
