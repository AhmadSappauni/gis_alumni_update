<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Pekerjaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;



class AdminAlumniController extends Controller
{
    public function index()
    {
        $dataAlumni = Alumni::with('pekerjaans')
            ->orderBy('tahun_lulus', 'desc')
            ->paginate(10);

        return view('admin.index', compact('dataAlumni'));
    }


    public function storePekerjaan(Request $request, $nim)
    {
        // 1. Validasi Input
        $request->validate([
            'nama_perusahaan' => 'required',
            'jabatan' => 'required',
            'kota' => 'required',
            'bidang' => 'required',
            'linearitas' => 'required',
            'alamat_lengkap' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // 2. LOGIKA PINTAR: Cek apakah alumni ini sudah punya pekerjaan "Utama"?
        $sudahAdaUtama = Pekerjaan::where('nim', $nim)
                                    ->where('status_karir', 'Utama')
                                    ->exists();

        // 3. Tentukan Status Karir secara otomatis
        // Jika BELUM ada utama -> jadikan Utama
        // Jika SUDAH ada utama -> jadikan Sampingan (agar tidak double di peta)
        $statusKarir = $sudahAdaUtama ? 'Sampingan' : 'Utama';

        // 4. Eksekusi Simpan
        Pekerjaan::create([
            'nim' => $nim,
            'nama_perusahaan' => $request->nama_perusahaan,
            'jabatan' => $request->jabatan,
            'bidang_pekerjaan' => $request->bidang,
            'linearitas' => $request->linearitas,
            'kota' => $request->kota,
            'alamat_lengkap' => $request->alamat_lengkap,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status_kerja' => 'Bekerja',
            'status_karir' => $statusKarir, // <-- Ini kuncinya
            'is_current' => true,           // Keduanya (Utama/Sampingan) dianggap aktif
            'gaji' => $request->gaji,
            'link_linkedin' => $request->link_linkedin
        ]);

        return back()->with('success', 'Riwayat pekerjaan berhasil ditambahkan sebagai ' . $statusKarir . '!');
    }



    public function setMainPekerjaan($id)
    {
        DB::transaction(function () use ($id) {
            $pekerjaan = Pekerjaan::findOrFail($id);

            Pekerjaan::where('nim', $pekerjaan->nim)
                ->update(['is_current' => 0]);

            $pekerjaan->update(['is_current' => 1]);
        });

        return back()->with('success', 'Pekerjaan utama berhasil diubah!');
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
            'email' => 'nullable|email',
            'no_hp' => 'nullable|numeric',
            'tahun_lulus' => 'required|numeric',
            'nama_perusahaan' => 'required',
            'jabatan' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tahun_yudisium' => 'nullable|numeric',
            'nilai_toefl' => 'nullable|numeric',   
            'masa_tunggu' => 'nullable|numeric',   
            'gaji_nominal' => 'nullable|numeric'
        ]);

        $fotoPath = null;

        // if ($request->hasFile('foto')) {
        //     $fotoPath = $request->file('foto')->store('alumni_foto', 'public');
        // }

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');

            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
            ])->attach(
                'file',
                file_get_contents($file),
                $filename
            )->post(env('SUPABASE_URL') . '/storage/v1/object/' . env('SUPABASE_BUCKET') . '/' . $filename);

            if ($response->failed()) {
                dd($response->body()); // debug kalau error
            }

            // simpan URL public
            $fotoPath = env('SUPABASE_URL') . '/storage/v1/object/public/' . env('SUPABASE_BUCKET') . '/' . $filename;
        }

        try {

            DB::transaction(function () use ($request, $fotoPath) {

                Alumni::create([
                    'nim' => $request->nim,
                    'nama_lengkap' => $request->nama_lengkap,
                    'email' => $request->email,
                    'no_hp' => $request->no_hp,
                    'angkatan' => $request->angkatan,
                    'tahun_lulus' => $request->tahun_lulus,
                    'judul_skripsi' => $request->judul_skripsi,
                    'foto_profil' => $fotoPath,
                    'tahun_yudisium' => $request->tahun_yudisium,
                    'nilai_toefl' => $request->nilai_toefl
                ]);
                $isUnemployed = $request->has('is_unemployed');

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
                    'longitude' => $request->longitude,
                    'is_current' => true, 
                    'status_kerja' => 'Bekerja',
                    'masa_tunggu' => $request->masa_tunggu,
                    'gaji_nominal' => $request->gaji,
                    'status_karir' => 'Utama'
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

    private function simplifyAlamat($alamat)
    {
        if (!$alamat) return null;

        // buang nomor rumah
        $alamat = preg_replace('/No\.?\s*\d+/i', '', $alamat);

        // buang kode pos
        $alamat = preg_replace('/\d{5}/', '', $alamat);

        // ambil bagian penting
        if (str_contains($alamat, ',')) {
            $parts = explode(',', $alamat);

            // ambil 2 bagian terakhir
            return trim(implode(',', array_slice($parts, -2)));
        }

        return $alamat;
    }

    public function importStore(Request $request)
    {
        $rows = json_decode($request->rows, true);

        $success = 0;
        $skip = 0;

        foreach ($rows as $row) {

            // 🛑 Skip kalau kosong
            if (!isset($row[0]) || empty($row[0])) {
                continue;
            }

            $nim = trim($row[0]);

            // 🛑 Skip kalau sudah ada
            if (Alumni::where('nim', $nim)->exists()) {
                $skip++;
                continue;
            }

            // =========================
            // 📍 GEOCODING
            // =========================
            $lokasi = $row[4] ?? null;

            if ($lokasi) {
                $lokasi = $this->simplifyAlamat($lokasi);
            }


            $lat = null;
            $lng = null;

            if ($lokasi && $lokasi !== '-') {
                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'WebGIS-Alumni-ULM'
                    ])->get("https://nominatim.openstreetmap.org/search", [
                        'q' => $lokasi,
                        'format' => 'json',
                        'limit' => 1,
                        'addressdetails' => 1
                    ]);

                    if ($response->successful() && isset($response->json()[0])) {
                        $lat = $response->json()[0]['lat'];
                        $lng = $response->json()[0]['lon'];
                    }

                    usleep(300000);
                } catch (\Exception $e) {}
            }
            
            // =========================
            // 🧠 NORMALISASI DATA
            // =========================

            // Linearitas
            // $rawLinearitas = strtolower($row[15] ?? '');
            // $fixLinearitas = ($rawLinearitas === 'erat') ? 'Linier' : 'Tidak Linier';

            $linearitas = $row[15] ?? null;
            if ($linearitas) {
                $linearitas = ucwords(strtolower(trim($linearitas)));
            }

            // Gaji
            $cleanGaji = (int) filter_var($row[11] ?? 0, FILTER_SANITIZE_NUMBER_INT);

            // Masa tunggu
            $cleanMasaTunggu = (int) filter_var($row[14] ?? 0, FILTER_SANITIZE_NUMBER_INT);

            // Status kerja
            $statusKerja = strtolower($row[3] ?? '');
            if (str_contains($statusKerja, 'kerja')) {
                $statusKerja = 'Bekerja';
            } else {
                $statusKerja = 'Tidak Bekerja';
            }

            $noHp = $row[5] ?? null;
            if (is_numeric($noHp)) {
                $noHp = (string) $noHp;
            } else {
                $noHp = null;
            }

            $kota = null;

            if ($response->successful() && isset($response->json()[0])) {
                $data = $response->json()[0];

                $lat = $data['lat'];
                $lng = $data['lon'];

                // 🔥 ambil kota dari address
                $address = $data['address'] ?? [];
                $kota = $address['city']
                    ?? $address['town']
                    ?? $address['municipality']
                    ?? $address['county']
                    ?? $address['state']
                    ?? null;
            }
            // =========================
            // 💾 SIMPAN
            // =========================
            DB::transaction(function () use (
                $kota, $noHp, $row,
                $nim,
                $lat,
                $lng,
                $linearitas,
                $cleanGaji,
                $cleanMasaTunggu,
                $statusKerja,
                &$success
            ) {

                Alumni::create([
                    'nim' => $nim,
                    'nama_lengkap' => $row[1] ?? '-',
                    'email' => $row[2] ?? null,
                    'no_hp' => $noHp,
                    'tahun_yudisium' => $this->ambilTahun($row[6]) ?? null,
                    'tahun_lulus' => $this->ambilTahun($row[7]) ?? null,
                    'nilai_toefl' => $row[13] ?? null,
                    'angkatan' => substr($nim, 0, 2),
                ]);

                Pekerjaan::create([
                    'nim' => $nim,
                    'nama_perusahaan' => $row[10] ?? '-',
                    'jabatan' => $row[12] ?? '-',
                    'bidang_pekerjaan' => '-',
                    'gaji' => $row[11] ?? null,
                    'gaji_nominal' => $cleanGaji,
                    'kota' => $kota,
                    'alamat_lengkap' => $row[4] ?? '-',
                    'linearitas' => $linearitas,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'is_current' => true,
                    'status_kerja' => $statusKerja,
                    'status_karir' => 'Utama',
                    'masa_tunggu' => $cleanMasaTunggu,
                ]);

                $success++;
            });
        }

        return response()->json([
            'success' => $success,
            'skip' => $skip
        ]);
    }

    private function ambilTahun($val)
    {
        if (!$val) return null;

        // kalau format ISO (2024-02-20T...)
        if (is_string($val) && str_contains($val, 'T')) {
            return (int) substr($val, 0, 4);
        }

        // kalau format DD/MM/YYYY
        if (is_string($val) && str_contains($val, '/')) {
            $parts = explode('/', $val);
            return (int) end($parts);
        }

        // kalau sudah angka
        if (is_numeric($val)) {
            return (int) $val;
        }

        return null;
    }

    public function destroy($nim)
    {
        try {
            $alumni = Alumni::where('nim', $nim)->firstOrFail();

            if ($alumni->foto_profil && Storage::disk('public')->exists($alumni->foto_profil)) {
                Storage::disk('public')->delete($alumni->foto_profil);
            }

            DB::transaction(function () use ($alumni) {
                // UBAH pekerjaan() menjadi pekerjaans()
                $alumni->pekerjaans()->delete(); 
                $alumni->delete(); 
            });

            return redirect()->route('admin.alumni.index')->with('success', 'Data alumni berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }



    public function destroyPekerjaan($id)
    {
        $pekerjaan = Pekerjaan::findOrFail($id);
        $nim = $pekerjaan->nim;

        // 1. Hapus pekerjaan tanpa syarat proteksi (Gembok dilepas)
        $pekerjaan->delete();

        // 2. Cek apakah MASIH ADA sisa riwayat pekerjaan lain di database untuk alumni ini
        $sisaPekerjaan = Pekerjaan::where('nim', $nim)->count();

        // 3. Jika masih ada sisa, dan yang dihapus tadi adalah pekerjaan "Utama", 
        // otomatis jadikan riwayat yang paling baru sebagai "Utama"
        if ($sisaPekerjaan > 0 && $pekerjaan->status_karir == 'Utama') {
            Pekerjaan::where('nim', $nim)
                ->latest()
                ->first()
                ->update(['status_karir' => 'Utama', 'is_current' => true]);
        }

        return back()->with('success', 'Riwayat pekerjaan berhasil dihapus!');
    }


    public function edit($nim)
    {
        $alumni = Alumni::with('pekerjaans')->where('nim', $nim)->firstOrFail();
        return view('admin.edit', compact('alumni'));
    }




    public function updateStatusKerja(Request $request, $id)
    {
        $pekerjaan = Pekerjaan::findOrFail($id);
        $statusBaru = $request->status; // Utama, Sampingan, atau Riwayat

        DB::transaction(function () use ($pekerjaan, $statusBaru, $id) {
            if ($statusBaru == 'Utama') {
                // 1. Set pekerjaan lain milik NIM ini jadi Riwayat & is_current false
                Pekerjaan::where('nim', $pekerjaan->nim)
                    ->where('id', '!=', $id)
                    ->where('status_karir', 'Utama')
                    ->update(['status_karir' => 'Riwayat', 'is_current' => false]);
                
                // 2. Set pekerjaan ini jadi Utama & is_current true
                $pekerjaan->update(['status_karir' => 'Utama', 'is_current' => true]);
            } else {
                // Jika diset Sampingan atau Riwayat
                $pekerjaan->update([
                    'status_karir' => $statusBaru,
                    // Sampingan tetap dianggap aktif di peta, Riwayat tidak.
                    'is_current' => ($statusBaru == 'Sampingan') 
                ]);
            }
        });

        return back()->with('success', 'Status karir berhasil diubah!');
    }




    public function update(Request $request, $nim)
    {
        $alumni = Alumni::where('nim', $nim)->firstOrFail();

        // 1. Validasi MURNI HANYA UNTUK PROFIL & TEMPAT TINGGAL
        // (Perhatikan: tidak ada lagi validasi nama_perusahaan, jabatan, dll)
        $request->validate([
            'nim' => 'required|unique:alumnis,nim,' . $alumni->nim . ',nim',
            'nama_lengkap' => 'required',
            'tahun_lulus' => 'required|numeric',
            'kota_tinggal' => 'required',
            'alamat_tinggal' => 'required',
            'latitude_tinggal' => 'required',
            'longitude_tinggal' => 'required',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // 2. Proses Foto Profil
        // $fotoPath = $alumni->foto_profil;
        // if ($request->hasFile('foto')) {
        //     if ($alumni->foto_profil && Storage::disk('public')->exists($alumni->foto_profil)) {
        //         Storage::disk('public')->delete($alumni->foto_profil);
        //     }
        //     $fotoPath = $request->file('foto')->store('alumni_foto', 'public');
        // }

        $fotoPath = null;

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');

            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
            ])->attach(
                'file',
                file_get_contents($file),
                $filename
            )->post(env('SUPABASE_URL') . '/storage/v1/object/' . env('SUPABASE_BUCKET') . '/' . $filename);

            if ($response->failed()) {
                dd($response->body()); // debug kalau error
            }

            // simpan URL public
            $fotoPath = env('SUPABASE_URL') . '/storage/v1/object/public/' . env('SUPABASE_BUCKET') . '/' . $filename;
        }

        try {
            // 3. Langsung Update ke Tabel Alumnis saja
            $alumni->update([
                'nim' => $request->nim,
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'angkatan' => $request->angkatan,
                'tahun_lulus' => $request->tahun_lulus,
                'judul_skripsi' => $request->judul_skripsi,
                'foto_profil' => $fotoPath,
                'kota_tinggal' => $request->kota_tinggal,
                'alamat_tinggal' => $request->alamat_tinggal,
                'latitude_tinggal' => $request->latitude_tinggal,
                'longitude_tinggal' => $request->longitude_tinggal,
            ]);

            return redirect()->route('admin.alumni.index')->with('success', 'Profil & Lokasi Tinggal berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }
    public function updatePekerjaan(Request $request, $id)
    {
        // 1. Validasi inputan modal edit
        $request->validate([
            'nama_perusahaan' => 'required',
            'jabatan' => 'required',
            'bidang' => 'required',
            'linearitas' => 'required',
            'kota' => 'required',
            'alamat_lengkap' => 'required',
        ]);

        // 2. Cari data pekerjaannya
        $pekerjaan = Pekerjaan::findOrFail($id);

        // 3. Update datanya
        $pekerjaan->update([
            'nama_perusahaan' => $request->nama_perusahaan,
            'jabatan' => $request->jabatan,
            'bidang_pekerjaan' => $request->bidang,
            'linearitas' => $request->linearitas,
            'kota' => $request->kota,
            'alamat_lengkap' => $request->alamat_lengkap,
            // Koordinat peta tetap kita update jika nanti kamu menambahkan fitur geser pin di modal edit
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'gaji' => $request->gaji,
            'link_linkedin' => $request->link_linkedin
        ]);

        // 4. Kembalikan ke halaman sebelumnya dengan pesan sukses
        return back()->with('success', 'Riwayat pekerjaan berhasil diperbarui!');
    }
}
