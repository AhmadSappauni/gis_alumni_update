    @extends('admin.layout')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin-create.css') }}">
        <style>
            /* Kunci tinggi elemen status agar tidak mendorong layout (Fix Kembang Kempis) */
        #nim-status, #kota-status {
            display: block;
            height: 18px; /* Beri tinggi tetap */
            margin-top: 4px;
            font-size: 11px;
        }

        /* Fix Tombol Navigasi */
        .btn-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
            min-height: 60px; /* Jaga tinggi area tombol */
        }

        /* Fix Tombol Import Excel di Header */
        .btn-import {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #004a87;
            color: white !important;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0, 74, 135, 0.2);
            transition: 0.3s;
        }

        .btn-import:hover {
            background: #00335d;
            transform: translateY(-2px);
        }
        </style>
    @endpush

    @section('content')
    <header class="top-header glass-panel">
        <h1>Tambah Alumni Baru</h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            {{-- <a href="{{ route('admin.alumni.import') }}" class="btn-import">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Import Excel
            </a>
            <a href="{{ route('admin.alumni.index') }}" style="background: #fdb813; padding: 10px 20px; border-radius: 12px; text-decoration: none; color: #004a87; font-weight: 700; font-size: 13px; box-shadow: 0 4px 12px rgba(253, 184, 19, 0.2);">← Batal</a> --}}
        </div>
    </header>

    <div class="glass-panel" style="padding: 40px; max-width: 1000px; margin: 0 auto;">
        <div class="progress-container">
            <div id="progress-bar"></div>
        </div>
        <div class="step-wizard">
            <div class="step active" id="s1">1</div>
            <div class="step" id="s2">2</div>
            <div class="step" id="s3">3</div>
        </div>

        @if(session('error'))
        <div style="background:#fee2e2;color:#991b1b;padding:10px;margin-bottom:20px;border-radius:8px;">
            {{ session('error') }}
        </div>
        @endif

        @if ($errors->any())
        <div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:15px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.alumni.store') }}" method="POST" enctype="multipart/form-data" id="wizardForm">
            @csrf
            
            <div class="form-step active" id="step1">
                <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Data Pribadi & Akademik</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label class="label-admin">NIM</label>
                        <input type="text" name="nim" id="nim" class="custom-input-admin" placeholder="211013...">

                        <small id="nim-status" style="font-size:12px;"></small>
                    </div>
                    <div>
                        <label class="label-admin">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="custom-input-admin" required>
                    </div>
                    <div>
                        <label class="label-admin">Angkatan</label>
                        <input type="number" name="angkatan" class="custom-input-admin" value="2021">
                    </div>
                    <div>
                        <label class="label-admin">Tahun Lulus</label>
                        <input type="number" name="tahun_lulus" class="custom-input-admin" value="2026">
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <label class="label-admin">Judul Skripsi</label>
                    <textarea name="judul_skripsi" class="custom-input-admin" rows="2"></textarea>
                </div>
                <div style="margin-top: 20px;">
                    <label class="label-admin">Foto Profil</label>
                    <input type="file" name="foto" id="foto" class="custom-input-admin">
                    <img id="preview-foto" style="width:80px; height:80px; object-fit:cover; border-radius:15px; margin-top:10px; display:none; border:2px solid white; box-shadow:0 5px 10px rgba(0,0,0,0.1);">
                </div>
            </div>

            <div class="form-step" id="step2">
                <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Informasi Karir</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label class="label-admin">Pekerjaan / Jabatan</label>
                        <input type="text" name="jabatan" class="custom-input-admin" placeholder="Software Engineer">
                    </div>
                    <div>
                        <label class="label-admin">Kategori Bidang</label>
                        <select name="bidang" class="custom-input-admin">
                            <option>IT & Software</option>
                            <option>Pendidikan / Guru</option>
                            <option>Pemerintahan</option>
                            <option>Wiraswasta</option>
                        </select>
                    </div>
                    <div>
                        <label class="label-admin">Linearitas</label>
                        <select name="linearitas" class="custom-input-admin">
                            <option value="Linier">Linier</option>
                            <option value="Tidak Linier">Tidak Linier</option>
                        </select>
                    </div>
                    <div>
                        <label class="label-admin">Nama Perusahaan</label>
                        <input type="text" name="nama_perusahaan" class="custom-input-admin">
                    </div>
                    <div>
                        <label class="label-admin">Estimasi Gaji (Opsional)</label>
                        <input type="text" name="gaji" class="custom-input-admin" placeholder="Rp 5.000.000">
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <label class="label-admin">Link LinkedIn</label>
                    <input type="url" name="linkedin" class="custom-input-admin" placeholder="https://linkedin.com/in/username">
                </div>
            </div>

            <div class="form-step" id="step3">
                <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Lokasi Kerja (Pemetaan)</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label class="label-admin">Kota / Kabupaten</label>
                        <input type="text" name="kota" id="kota" class="custom-input-admin" placeholder="Ketik nama kota...">
                        <small id="kota-status" style="font-size:12px;color:#64748b;">
                            Sistem mencari berdasarkan nama tempat
                        </small>
                    </div>
                
                    <div >
                        <label class="label-admin">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" id="alamat_lengkap"
                            class="custom-input-admin"
                            rows="2"
                            readonly
                            placeholder="Alamat akan terisi otomatis dari peta"></textarea>
                    </div>
                </div>
                <div id="map-tambah" style="height: 350px; border-radius: 20px; border: 2px solid white; margin-bottom: 20px;"></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label class="label-admin">Latitude</label>
                        <input type="text" name="latitude" id="lat" class="custom-input-admin" readonly required>
                    </div>
                    <div>
                        <label class="label-admin">Longitude</label>
                        <input type="text" name="longitude" id="lng" class="custom-input-admin" readonly required>
                    </div>
                </div>
            </div>

            <div id="review-box" style="display:none; margin-top:35px; padding:25px; border-radius:20px; background: rgba(255,255,255,0.6); border: 1px solid white;">
                <h4 style="margin-bottom:20px; color:#004a87; display:flex; align-items:center; gap:10px; font-weight: 800;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Konfirmasi Data Alumni
                </h4>

                <div class="review-grid">
                    <div class="review-item"><b>Identitas</b><span id="review_nama">-</span><br><small id="review_nim" style="color:#64748b"></small></div>
                    <div class="review-item"><b>Akademik</b><span id="review_angkatan_lulus">-</span></div>
                    <div class="review-item"><b>Karir</b><span id="review_jabatan">-</span><br><small id="review_perusahaan" style="color:#64748b"></small></div>
                    <div class="review-item"><b>Lokasi</b><span id="review_kota">-</span></div>
                </div>
                
                <div style="margin-top:20px; padding:12px 18px; background:linear-gradient(90deg, rgba(0,74,135,0.05) 0%, transparent 100%); border-radius:12px; font-size:12px; color:#004a87; border-left: 4px solid #004a87;">
                    <b>Pinpoint Koordinat:</b> <span id="review_coords" style="font-family: monospace;">-</span>
                </div>
            </div>

            <div class="btn-navigation">
                <button type="button" class="btn-tambah" id="prevBtn" onclick="nextPrev(-1)" style="background: #94a3b8; display: none;">Sebelumnya</button>
                <button type="button" class="btn-tambah" id="nextBtn" onclick="nextPrev(1)" style="margin-left: auto;">Lanjut</button>
            </div>
        </form>
    </div>
    @endsection

    @push('scripts')
        <script src="{{ asset('js/admin/create.js') }}"></script>
    @endpush