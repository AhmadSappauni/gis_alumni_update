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
                    'email' => $request->email,
                    'no_hp' => $request->no_hp,
                    'angkatan' => $request->angkatan,
                    'tahun_lulus' => $request->tahun_lulus,
                    'judul_skripsi' => $request->judul_skripsi,
                    'foto_profil' => $fotoPath
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
                    'status_kerja' => 'Bekerja' 
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

            $lokasiPencarian = $row[5] ?? $row[3] ?? null; 
            $lat = null;
            $lng = null;
            
            if ($lokasiPencarian && $lokasiPencarian !== '-') {
                try {
                    // Proses Geocoding via Nominatim API
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'User-Agent' => 'WebGIS-Alumni-ULM' // Wajib ada User-Agent
                    ])->get("https://nominatim.openstreetmap.org/search", [
                        'q'      => $lokasiPencarian,
                        'format' => 'json',
                        'limit'  => 1
                    ]);

                    if ($response->successful() && isset($response->json()[0])) {
                        $lat = $response->json()[0]['lat'];
                        $lng = $response->json()[0]['lon'];
                    }
                    
                    // Jeda 0.5 detik agar tidak melanggar aturan Nominatim (Max 1 req/sec)
                    usleep(500000); 
                } catch (\Exception $e) {
                    // Jika gagal, biarkan lat & lng tetap null
                }
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
                'email' => $row[7] ?? null,
                'no_hp' => $row[8] ?? null,
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
                'latitude' => $lat,
                'longitude' => $lng,
                'is_current' => true,
                'status_kerja' => ($row[3] == '-' || !$row[3]) ? 'Mencari Kerja' : 'Bekerja'
            ]);

            $success++;
        }

        return response()->json([
            'success' => $success,
            'skip' => $skip
        ]);
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
        $fotoPath = $alumni->foto_profil;
        if ($request->hasFile('foto')) {
            if ($alumni->foto_profil && Storage::disk('public')->exists($alumni->foto_profil)) {
                Storage::disk('public')->delete($alumni->foto_profil);
            }
            $fotoPath = $request->file('foto')->store('alumni_foto', 'public');
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
