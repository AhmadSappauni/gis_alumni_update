@extends('admin.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-create.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/edit.css') }}">
    <style>
        .foto-edit-preview {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .foto-edit-thumbnail {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 20px;
            border: 3px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .foto-edit-actions {
            flex-grow: 1;
        }

        .foto-edit-reset {
            display: none;
            margin-top: 10px;
            border: none;
            border-radius: 10px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 14px;
            cursor: pointer;
        }

        .foto-edit-reset.active {
            display: inline-flex;
        }
    </style>
@endpush

@section('content')
    <header class="top-header glass-panel">
        <h1>Edit Data Alumni</h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('admin.alumni.index') }}" class="btn-batal">← Kembali</a>
        </div>
    </header>

    <div style="max-width: 1000px; margin: 0 auto;">
        
        @if (session('error'))
            <div style="background:#fee2e2;color:#991b1b;padding:15px;margin-bottom:20px;border-radius:12px; font-weight: 700;">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:12px;margin-bottom:20px;">
                <ul style="margin:0; padding-left:20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="tab-navigation">
            <button type="button" class="tab-btn active" data-tab="tab-profil" onclick="switchTab('tab-profil', this)">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Profil & Tempat Tinggal
            </button>
            <button type="button" class="tab-btn" data-tab="tab-karir" onclick="switchTab('tab-karir', this)">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Riwayat Pekerjaan
            </button>
            <button type="button" class="tab-btn" data-tab="tab-studi" onclick="switchTab('tab-studi', this)">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 11.5c0 4.418-4.03 8-9 8s-9-3.582-9-8c0-.35.02-.696.06-1.038L12 14z"></path></svg>
                Studi Lanjut
            </button>
        </div>

        <div id="tab-profil" class="tab-pane active glass-panel" style="padding: 40px;">
            <div class="progress-container"><div id="progress-bar"></div></div>
            <div class="step-wizard">
                <div class="step active" id="s1">1</div>
                <div class="step" id="s2">2</div>
                <div class="step" id="s3">3</div>
            </div>

            <form action="{{ route('admin.alumni.update', $alumni->id) }}" method="POST" enctype="multipart/form-data" id="wizardForm">
                @csrf
                @method('PUT')

                <div class="form-step active" id="step1">
                    <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Data Pribadi & Akademik</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div><label class="label-admin">NIM</label><input type="text" name="nim" class="custom-input-admin" value="{{ old('nim', $alumni->nim) }}" required></div>
                        <div><label class="label-admin">Nama Lengkap</label><input type="text" name="nama_lengkap" class="custom-input-admin" value="{{ old('nama_lengkap', $alumni->nama_lengkap) }}" required></div>
                        <div>
                            <label class="label-admin">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="custom-input-admin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" {{ old('jenis_kelamin', $alumni->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('jenis_kelamin', $alumni->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-admin">IPK</label>
                            <input type="number" name="ipk" class="custom-input-admin" value="{{ old('ipk', $alumni->akademik?->ipk) }}" min="0" max="4" step="0.01" placeholder="Contoh: 3.75">
                        </div>
                        <div><label class="label-admin">Angkatan</label><input type="number" name="angkatan" class="custom-input-admin" value="{{ old('angkatan', $alumni->akademik?->angkatan) }}"></div>
                        <div><label class="label-admin">Tahun Lulus</label><input type="number" name="tahun_lulus" class="custom-input-admin" value="{{ old('tahun_lulus', $alumni->akademik?->tahun_lulus) }}" required></div>
                    </div>
                    <div style="margin-top: 20px;">
                        <label class="label-admin">Judul Skripsi</label>
                        <textarea name="judul_skripsi" class="custom-input-admin" rows="2">{{ old('judul_skripsi', $alumni->akademik?->judul_skripsi) }}</textarea>
                    </div>
                    <div style="margin-top: 20px; background: rgba(0,74,135,0.03); padding: 20px; border-radius: 15px;">
                        <label class="label-admin">Foto Profil</label>
                        <div class="foto-edit-preview">
                            <img
                                id="edit-preview-foto"
                                class="foto-edit-thumbnail"
                                src="{{ $alumni->foto_profil ? (\Illuminate\Support\Str::startsWith($alumni->foto_profil, ['http://', 'https://']) ? $alumni->foto_profil : asset('storage/' . $alumni->foto_profil)) : '/default.png' }}"
                                data-default-src="{{ $alumni->foto_profil ? (\Illuminate\Support\Str::startsWith($alumni->foto_profil, ['http://', 'https://']) ? $alumni->foto_profil : asset('storage/' . $alumni->foto_profil)) : '/default.png' }}"
                                alt="Foto profil alumni">
                            <div class="foto-edit-actions">
                                <input type="file" name="foto" id="edit-foto" class="custom-input-admin" accept="image/*">
                                <small style="color: #64748b; display: block; margin-top: 8px;">Pilih file baru jika ingin mengganti foto</small>
                                <button type="button" id="btn-reset-edit-foto" class="foto-edit-reset">Batalkan Ganti Foto</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-step" id="step2">
                    <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Informasi Kontak</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div><label class="label-admin">Email</label><input type="email" name="email" class="custom-input-admin" value="{{ old('email', $alumni->email) }}" placeholder="alumni@example.com"></div>
                        <div><label class="label-admin">No. WhatsApp</label><input type="text" name="no_hp" class="custom-input-admin" value="{{ old('no_hp', $alumni->no_hp) }}" placeholder="0812..."></div>
                    </div>
                </div>

                <div class="form-step" id="step3">
                    <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Lokasi Tempat Tinggal Saat Ini</h3>
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px;">
                        <div>
                            <div id="map-tambah" style="height: 400px; border-radius: 20px; border: 4px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.1);"></div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div>
                                <label class="label-admin">Kota / Kabupaten</label>
                                <input type="text" name="kota_tinggal" id="kota" class="custom-input-admin" value="{{ old('kota_tinggal', $alumni->alamat?->kota) }}" placeholder="Ketik nama kota..." required>
                            </div>
                            <div>
                                <label class="label-admin">Alamat Lengkap</label>
                                <textarea name="alamat_tinggal" id="alamat_lengkap" class="custom-input-admin" rows="3" readonly placeholder="Pinpoint di peta" required>{{ old('alamat_tinggal', $alumni->alamat?->alamat_lengkap) }}</textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="text" name="latitude_tinggal" id="lat" class="custom-input-admin" value="{{ old('latitude_tinggal', $alumni->alamat?->latitude) }}" readonly required>
                                <input type="text" name="longitude_tinggal" id="lng" class="custom-input-admin" value="{{ old('longitude_tinggal', $alumni->alamat?->longitude) }}" readonly required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-navigation">
                    <button type="button" class="btn-tambah" id="prevBtn" onclick="nextPrev(-1)">Sebelumnya</button>
                    <button type="button" class="btn-tambah" id="nextBtn" onclick="nextPrev(1)">Lanjut</button>
                </div>
            </form>
        </div>

        <div id="tab-karir" class="tab-pane glass-panel" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid rgba(0,0,0,0.05);">
                <div>
                    <h3 style="color: #004a87; margin: 0; font-size: 22px; font-weight: 800;">Manajemen Riwayat Karir</h3>
                    <p style="margin: 5px 0 0; font-size: 13px; color: #64748b;">Data ini akan ditampilkan di Peta Sebaran Pekerjaan Alumni.</p>
                </div>
                <button type="button" onclick="openModalKerja()" class="btn-tambah" style="width: auto; padding: 12px 24px; font-size: 14px; background: #004a87;">
                    + Tambah Pekerjaan
                </button>
            </div>

            @if($alumni->pekerjaan->isEmpty())
                <div style="text-align: center; padding: 40px; background: rgba(0,74,135,0.03); border-radius: 16px; border: 2px dashed rgba(0,74,135,0.2);">
                    <svg width="40" height="40" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" style="margin-bottom: 10px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <h4 style="color: #475569; margin:0;">Belum Ada Riwayat Pekerjaan</h4>
                    <p style="color: #94a3b8; font-size: 13px; margin-top:5px;">Klik tombol + Tambah Pekerjaan di atas untuk menambahkan data.</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                        <thead>
                            <tr style="text-align: left; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 10px 15px;">Instansi / Perusahaan</th>
                                <th style="padding: 10px 15px;">Jabatan</th>
                                <th style="padding: 10px 15px;">Status</th>
                                <th style="padding: 10px 15px; text-align: center;">Atur Status</th>
                                <th style="padding: 10px 15px; text-align: center;">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alumni->pekerjaan->sortBy(function($item){
                                return match($item->status_karir){
                                    'Utama' => 1,
                                    'Sampingan' => 2,
                                    default => 3
                                };
                            }) as $p)
                            <tr style="background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.04); transition: 0.3s;">
                                <td style="padding: 15px; border-radius: 12px 0 0 12px; border: 1px solid #f1f5f9; border-right: none;">
                                    <span style="font-weight: 800; color: #1e293b; display: block; font-size: 15px;">{{ $p->perusahaan?->nama_perusahaan }}</span>
                                    <small style="color: #94a3b8; display: flex; align-items: center; gap: 4px; margin-top:4px;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $p->perusahaan?->lokasi->first()?->kota ?? '-' }}
                                    </small>
                                    <small style="color: #64748b; display: block; margin-top: 8px; line-height: 1.5;">
                                        Periode:
                                        {{ $p->tanggal_mulai?->translatedFormat('d M Y') ?? '-' }}
                                        -
                                        {{ $p->is_current ? 'Sekarang' : ($p->tanggal_selesai?->translatedFormat('d M Y') ?? '-') }}
                                    </small>
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    <span style="color: #475569; font-weight: 600;">{{ $p->jabatan }}</span>
                                    <small style="display: block; color: #94a3b8; margin-top: 6px;">
                                        Masa tunggu:
                                        {{ $p->masa_tunggu !== null ? $p->masa_tunggu . ' bulan' : '-' }}
                                    </small>
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    @if($p->status_karir == 'Utama')
                                        <span title="Pekerjaan utama yang ditampilkan di peta" style="background: #ecfdf5; color: #10b981; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #10b981; cursor: help;">UTAMA</span>
                                    @elseif($p->status_karir == 'Sampingan')
                                        <span title="Pekerjaan aktif lainnya (Double Job)" style="background: #eff6ff; color: #3b82f6; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #3b82f6; cursor: help;">SAMPINGAN</span>
                                    @else
                                        <span title="Riwayat pekerjaan masa lalu" style="background: #f8fafc; color: #94a3b8; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #e2e8f0; cursor: help;">RIWAYAT</span>
                                    @endif
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        @foreach(['Utama' => ['U', '#10b981', 'Jadikan Pekerjaan Utama'], 'Sampingan' => ['S', '#3b82f6', 'Jadikan Pekerjaan Sampingan'], 'Riwayat' => ['R', '#94a3b8', 'Pindahkan ke Riwayat Lama']] as $status => $style)
                                            @if($p->status_karir != $status)
                                                <form action="{{ route('admin.pekerjaan.updateStatus', $p->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $status }}">
                                                    <button type="submit" title="{{ $style[2] }}" style="background: {{ $style[1] }}; color: white; border: none; width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 11px; transition: 0.2s;">{{ $style[0] }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td style="padding: 15px; border-radius: 0 12px 12px 0; border: 1px solid #f1f5f9; border-left: none; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        @php
                                        $dataEdit = [
                                            "id" => $p->id,
                                            "nama_perusahaan" => $p->perusahaan?->nama_perusahaan,
                                            "linearitas" => $p->perusahaan?->linearitas,
                                            "link_linkedin" => $p->perusahaan?->link_linkedin,
                                            "jabatan" => $p->jabatan,
                                            "bidang_pekerjaan" => $p->bidang_pekerjaan,
                                            "kota" => $p->perusahaan?->lokasi->first()?->kota,
                                            "alamat_lengkap" => $p->perusahaan?->lokasi->first()?->alamat_lengkap,
                                            "latitude" => $p->perusahaan?->lokasi->first()?->latitude,
                                            "longitude" => $p->perusahaan?->lokasi->first()?->longitude,
                                            "gaji" => $p->gaji_nominal,
                                            "tanggal_mulai" => $p->tanggal_mulai?->format('Y-m-d'),
                                            "tanggal_selesai" => $p->tanggal_selesai?->format('Y-m-d'),
                                            "masa_tunggu" => $p->masa_tunggu,
                                            "is_current" => $p->is_current,
                                            "status_karir" => $p->status_karir,
                                        ];
                                        @endphp
        
                                        <button type="button"onclick='editPekerjaan(@json($dataEdit))' title="Edit data pekerjaan ini" style="background: #e0f2fe; color: #0284c7; border: none; padding: 8px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>

                                        <form action="{{ route('admin.pekerjaan.destroy', $p->id) }}" method="POST" class="form-hapus-pekerjaan">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn-delete-swal" title="Hapus permanen" style="background: #fee2e2; color: #ef4444; border: none; padding: 8px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div id="tab-studi" class="tab-pane glass-panel" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid rgba(0,0,0,0.05);">
                <div>
                    <h3 style="color: #004a87; margin: 0; font-size: 22px; font-weight: 800;">Manajemen Studi Lanjut</h3>
                    <p style="margin: 5px 0 0; font-size: 13px; color: #64748b;">Data ini akan digunakan untuk melihat riwayat pendidikan lanjutan alumni.</p>
                </div>
                <button type="button" onclick="openModalStudi()" class="btn-tambah" style="width: auto; padding: 12px 24px; font-size: 14px; background: #004a87;">
                    + Tambah Studi Lanjut
                </button>
            </div>

            @if($alumni->studiLanjut->isEmpty())
                <div style="text-align: center; padding: 40px; background: rgba(0,74,135,0.03); border-radius: 16px; border: 2px dashed rgba(0,74,135,0.2);">
                    <svg width="40" height="40" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" style="margin-bottom: 10px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 11.5c0 4.418-4.03 8-9 8s-9-3.582-9-8c0-.35.02-.696.06-1.038L12 14z"></path></svg>
                    <h4 style="color: #475569; margin:0;">Belum ada data studi lanjut untuk alumni ini.</h4>
                    <p style="color: #94a3b8; font-size: 13px; margin-top:5px;">Klik tombol + Tambah Studi Lanjut di atas untuk menambahkan data.</p>
                </div>
            @else
                <div style="display: grid; gap: 12px;">
                    @foreach($alumni->studiLanjut->sortByDesc('tahun_masuk') as $s)
                        @php
                            $periodeMulai = $s->tahun_masuk ?? '-';
                            $periodeSelesai = $s->tahun_lulus ?? 'Sekarang';

                            $badge = match($s->status) {
                                'Sedang Berjalan' => ['#ecfdf5', '#10b981', '#10b981'],
                                'Lulus' => ['#eff6ff', '#3b82f6', '#3b82f6'],
                                'Cuti' => ['#fffbeb', '#f59e0b', '#f59e0b'],
                                'Tidak Selesai' => ['#fef2f2', '#ef4444', '#ef4444'],
                                default => ['#f8fafc', '#64748b', '#e2e8f0'],
                            };

                            $dataEditStudi = [
                                'id' => $s->id,
                                'kampus' => $s->kampus,
                                'alamat_kampus' => $s->alamat_kampus,
                                'kota_kampus' => $s->kota_kampus,
                                'provinsi_kampus' => $s->provinsi_kampus,
                                'latitude' => $s->latitude,
                                'longitude' => $s->longitude,
                                'jenjang' => $s->jenjang,
                                'program_studi' => $s->program_studi,
                                'tahun_masuk' => $s->tahun_masuk,
                                'tahun_lulus' => $s->tahun_lulus,
                                'status' => $s->status,
                            ];
                        @endphp

                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); display: flex; justify-content: space-between; gap: 15px;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; gap: 10px; align-items: flex-start;">
                                    <div>
                                        <div style="font-weight: 800; color: #1e293b; font-size: 15px;">{{ $s->kampus }}</div>
                                        <div style="color: #94a3b8; margin-top: 4px; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            {{ collect([$s->kota_kampus, $s->provinsi_kampus])->filter()->implode(', ') ?: '-' }}
                                        </div>
                                        <div style="color: #475569; font-weight: 700; margin-top: 4px;">
                                            {{ $s->jenjang }}
                                            @if($s->program_studi)
                                                - {{ $s->program_studi }}
                                            @endif
                                        </div>
                                        <div style="color: #64748b; margin-top: 8px; font-size: 13px;">
                                            Periode: {{ $periodeMulai }} - {{ $periodeSelesai }}
                                        </div>
                                    </div>
                                    <span style="background: {{ $badge[0] }}; color: {{ $badge[1] }}; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 800; border: 1px solid {{ $badge[2] }}; white-space: nowrap;">
                                        {{ $s->status }}
                                    </span>
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px; align-items: flex-start;">
                                <button type="button" onclick='editStudiLanjut(@json($dataEditStudi))' title="Edit studi lanjut" style="background: #e0f2fe; color: #0284c7; border: none; padding: 10px; border-radius: 12px; cursor: pointer; transition: 0.2s;">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>

                                <form action="{{ route('admin.studi-lanjut.destroy', ['alumni' => $alumni->id, 'studiLanjut' => $s->id]) }}" method="POST" class="form-hapus-studi">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-delete-studi-swal" title="Hapus studi lanjut" style="background: #fee2e2; color: #ef4444; border: none; padding: 10px; border-radius: 12px; cursor: pointer; transition: 0.2s;">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    @include('admin.komponen.riwayat-pekerjaan')
    @include('admin.komponen.studi-lanjut')
@endsection

@push('scripts')
    <script>
    window.editConfig = {
        oldLat: @json($alumni->alamat?->latitude ?? -3.316694),
        oldLng: @json($alumni->alamat?->longitude ?? 114.590111),
        pekerjaanUrl: @json(url('/admin/pekerjaan')),
        studiLanjutBaseUrl: @json(url('/admin/alumni/' . $alumni->id . '/studi-lanjut')),
        initialTab: @json(session('active_tab') ?? request('tab'))
    };
    </script>

    <script src="{{ asset('js/admin/edit.js') }}"></script>
@endpush
