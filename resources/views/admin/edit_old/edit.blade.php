@extends('admin.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-create.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/edit.css') }}">
    
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
            <button type="button" class="tab-btn active" onclick="switchTab('tab-profil', this)">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Profil & Tempat Tinggal
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('tab-karir', this)">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Riwayat Pekerjaan
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
                        <div><label class="label-admin">Angkatan</label><input type="number" name="angkatan" class="custom-input-admin" value="{{ old('angkatan', $alumni->akademik?->angkatan) }}"></div>
                        <div><label class="label-admin">Tahun Lulus</label><input type="number" name="tahun_lulus" class="custom-input-admin" value="{{ old('tahun_lulus', $alumni->akademik?->tahun_lulus) }}" required></div>
                    </div>
                    <div style="margin-top: 20px;">
                        <label class="label-admin">Judul Skripsi</label>
                        <textarea name="judul_skripsi" class="custom-input-admin" rows="2">{{ old('judul_skripsi', $alumni->akademik?->judul_skripsi) }}</textarea>
                    </div>
                    <div style="margin-top: 20px; background: rgba(0,74,135,0.03); padding: 20px; border-radius: 15px;">
                        <label class="label-admin">Foto Profil</label>
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <img src="{{ $alumni->foto_profil ? asset('storage/' . $alumni->foto_profil) : '/default.png' }}" style="width:90px; height:90px; object-fit:cover; border-radius:20px; border:3px solid white; box-shadow:0 10px 20px rgba(0,0,0,0.1);">
                            <div style="flex-grow: 1;">
                                <input type="file" name="foto" class="custom-input-admin">
                                <small style="color: #64748b; display: block; margin-top: 8px;">Pilih file baru jika ingin mengganti foto</small>
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
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    <span style="color: #475569; font-weight: 600;">{{ $p->jabatan }}</span>
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
        
                                        <button type="button" onclick='editPekerjaan(@json([
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

                                                    "gaji" => $p->gaji_nominal
                                                ]))' 
                                                title="Edit data pekerjaan ini" style="background: #e0f2fe; color: #0284c7; border: none; padding: 8px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
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

    </div>

    @include('admin.komponen.riwayat-pekerjaan')
@endsection

@push('scripts')
    <script>
        function switchTab(tabId, btn) {
            // Sembunyikan semua tab
            document.querySelectorAll('.tab-pane').forEach(tab => {
                tab.classList.remove('active');
            });
            // Hapus warna aktif di semua tombol
            document.querySelectorAll('.tab-btn').forEach(button => {
                button.classList.remove('active');
            });
            
            // Tampilkan tab yang dipilih
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');

            // Fix Peta Leaflet agar merender ulang jika tab Peta dibuka
            if(tabId === 'tab-profil' && typeof map !== 'undefined') {
                setTimeout(() => { map.invalidateSize(); }, 300);
            }
        }
    </script>

    <script>
        var oldLat = {{ $alumni->alamat?->latitude ?? -3.316694 }};
        var oldLng = {{ $alumni->alamat?->longitude ?? 114.590111 }};
        var map, marker;
        var typingTimer; 
        var doneTypingInterval = 800; // Tunggu 0.8 detik setelah berhenti mengetik

        document.addEventListener("DOMContentLoaded", function() {
            // 1. Inisialisasi Peta Leaflet
            map = L.map('map-tambah').setView([oldLat, oldLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // 2. Buat Marker
            marker = L.marker([oldLat, oldLng], {draggable: true}).addTo(map);

            // Fungsi Reverse Geocoding (Ubah Koordinat jadi Teks Alamat)
            function getAddressFromCoords(lat, lng) {
                const alamatInput = document.getElementById('alamat_lengkap');
                alamatInput.value = " Mengambil detail alamat dari satelit..."; // <--- STATUS LOADING
                
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            alamatInput.value = data.display_name; // <--- HASIL SUKSES
                        } else {
                            alamatInput.value = " Detail alamat tidak ditemukan untuk titik ini. Silakan geser pin merah sedikit.";
                        }
                    })
                    .catch(error => {
                        alamatInput.value = " Gagal mengambil alamat. Periksa koneksi internet.";
                    });
            }

            // 3. Update saat marker digeser
            marker.on('dragend', function (e) {
                var lat = marker.getLatLng().lat;
                var lng = marker.getLatLng().lng;
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                getAddressFromCoords(lat, lng);
            });

            // 4. Update saat peta diklik
            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                getAddressFromCoords(lat, lng);
            });

            // 5. FITUR PENCARIAN DENGAN STATUS LOADING
            const kotaInput = document.getElementById('kota');
            const alamatInput = document.getElementById('alamat_lengkap');

            kotaInput.addEventListener('keyup', function() {
                clearTimeout(typingTimer);
                if (kotaInput.value) {
                    // Beri tahu user bahwa sistem sedang menunggu dia selesai mengetik
                    alamatInput.value = " Menunggu selesai mengetik...";
                    typingTimer = setTimeout(cariLokasi, doneTypingInterval);
                } else {
                    alamatInput.value = ""; // Kosongkan jika input dihapus
                }
            });

            function cariLokasi() {
                let query = kotaInput.value;
                if (query.length < 3) {
                    alamatInput.value = " Ketik minimal 3 huruf untuk mencari lokasi.";
                    return; 
                }

                // Berikan status pencarian ke server
                alamatInput.value = ` Sedang mencari titik lokasi untuk "${query}"...`;

                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}, Indonesia`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            var lat = data[0].lat;
                            var lng = data[0].lon;
                            
                            map.setView([lat, lng], 14); // Pindah kamera peta
                            marker.setLatLng([lat, lng]); // Pindah pin merah
                            
                            document.getElementById('lat').value = lat;
                            document.getElementById('lng').value = lng;
                            
                            // Ambil nama jalan detailnya
                            getAddressFromCoords(lat, lng);
                        } else {
                            alamatInput.value = ` Kota "${query}" tidak ditemukan. Coba nama yang lebih umum.`;
                        }
                    })
                    .catch(error => {
                        alamatInput.value = " Terjadi kesalahan saat mencari lokasi.";
                    });
            }
        });
    </script>

    <script>
        document.querySelectorAll('.btn-delete-swal').forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('.form-hapus-pekerjaan');
                Swal.fire({
                    title: 'Hapus Pekerjaan?', text: "Data ini akan dihapus permanen!",
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8', confirmButtonText: 'Ya, Hapus!', reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Memproses...', didOpen: () => { Swal.showLoading() }, allowOutsideClick: false });
                        form.submit();
                    }
                });
            });
        });
    </script>

    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <script>
        tippy('[title]', { content: (reference) => reference.getAttribute('title'), theme: 'material', placement: 'top', animation: 'shift-away' });
    </script>   

    <script>
        let currentTabStep = 0;
        showStep(currentTabStep);

        function showStep(n) {
            let steps = document.getElementsByClassName("form-step");
            let indicators = document.getElementsByClassName("step");
            
            for (let i = 0; i < steps.length; i++) {
                steps[i].style.display = "none";
                steps[i].classList.remove("active");
                if(indicators[i]) indicators[i].classList.remove("active");
            }
            
            if(steps[n]) {
                steps[n].style.display = "block";
                steps[n].classList.add("active");
                if(indicators[n]) indicators[n].classList.add("active");
            }
            
            // ==========================================
            // KODE BARU: Bangunkan peta saat masuk Step 3 (index 2)
            // ==========================================
            if (n === 2 && typeof map !== 'undefined') {
                setTimeout(function() {
                    map.invalidateSize();
                }, 300); // Jeda 300ms menunggu animasi CSS selesai
            }
            // ==========================================
            
            // Progress bar
            document.getElementById("progress-bar").style.width = (n / (steps.length - 1)) * 100 + "%";

            // Tombol Sebelumnya
            document.getElementById("prevBtn").style.display = (n == 0) ? "none" : "inline";

            // Tombol Lanjut / Simpan
            if (n == (steps.length - 1)) {
                document.getElementById("nextBtn").innerHTML = " Simpan Profil";
                document.getElementById("nextBtn").setAttribute('onclick', "document.getElementById('wizardForm').submit()");
            } else {
                document.getElementById("nextBtn").innerHTML = "Lanjut";
                document.getElementById("nextBtn").setAttribute('onclick', "nextPrev(1)");
            }
        }

        function nextPrev(n) {
            let steps = document.getElementsByClassName("form-step");
            
            // Validasi HTML5 bawaan sebelum lanjut
            if (n == 1) {
                let inputs = steps[currentTabStep].querySelectorAll('input[required], textarea[required], select[required]');
                for (let i = 0; i < inputs.length; i++) {
                    if (!inputs[i].checkValidity()) {
                        inputs[i].reportValidity();
                        return false;
                    }
                }
            }

            steps[currentTabStep].style.display = "none";
            currentTabStep = currentTabStep + n;
            showStep(currentTabStep);
        }
    </script>
    <script>
        var mapKerjaTambah = null, markerKerjaTambah = null;
        var mapKerjaEdit = null, markerKerjaEdit = null;

        // Fungsi Reverse Geocoding Khusus Modal
        function getAlamatModal(lat, lng, inputId) {
            let input = document.getElementById(inputId);
            input.value = "⏳ Mengambil alamat dari peta...";
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => { input.value = data.display_name || "Alamat tidak ditemukan."; })
                .catch(() => { input.value = "Gagal mengambil alamat."; });
        }

        // ==========================================
        // FUNGSI MODAL TAMBAH PEKERJAAN
        // ==========================================
        function openModalKerja() { 
            document.getElementById('modal-pekerjaan').classList.add('active');
            
            // Ciptakan peta hanya jika belum pernah diciptakan
            if (!mapKerjaTambah) {
                mapKerjaTambah = L.map('map-kerja-tambah').setView([-3.316694, 114.590111], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapKerjaTambah);
                markerKerjaTambah = L.marker([-3.316694, 114.590111], {draggable: true}).addTo(mapKerjaTambah);

                markerKerjaTambah.on('dragend', function(e) {
                    let lat = e.target.getLatLng().lat;
                    let lng = e.target.getLatLng().lng;
                    document.getElementById('tambah_lat').value = lat;
                    document.getElementById('tambah_lng').value = lng;
                    getAlamatModal(lat, lng, 'tambah_alamat');
                });
            }
            
            // Refresh ukuran peta agar tidak abu-abu
            setTimeout(() => { mapKerjaTambah.invalidateSize(); }, 300); 
        }

        function closeModalKerja() { 
            document.getElementById('modal-pekerjaan').classList.remove('active'); 
        }

        // ==========================================
        // FUNGSI MODAL EDIT PEKERJAAN
        // ==========================================
        function editPekerjaan(pekerjaan) {
            // 1. Isi form dengan data lama
            document.getElementById('edit_perusahaan').value = pekerjaan.nama_perusahaan;
            document.getElementById('edit_jabatan').value = pekerjaan.jabatan;
            document.getElementById('edit_bidang').value = pekerjaan.bidang_pekerjaan;
            document.getElementById('edit_linearitas').value = pekerjaan.linearitas;
            document.getElementById('edit_kota').value = pekerjaan.kota;
            document.getElementById('edit_alamat').value = pekerjaan.alamat_lengkap;
            document.getElementById('edit_gaji').value = pekerjaan.gaji || '';
            document.getElementById('edit_linkedin').value = pekerjaan.link_linkedin || '';

            let lat = pekerjaan.latitude || -3.316694;
            let lng = pekerjaan.longitude || 114.590111;
            document.getElementById('edit_lat').value = lat;
            document.getElementById('edit_lng').value = lng;

            // 2. Tampilkan Modalnya
            document.getElementById('form-edit-pekerjaan').action = "{{ url('/admin/pekerjaan') }}/" + pekerjaan.id;
            document.getElementById('modal-edit-pekerjaan').classList.add('active');
            
            // 3. Ciptakan atau Update Petanya
            if (!mapKerjaEdit) {
                mapKerjaEdit = L.map('map-kerja-edit').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapKerjaEdit);
                markerKerjaEdit = L.marker([lat, lng], {draggable: true}).addTo(mapKerjaEdit);

                markerKerjaEdit.on('dragend', function(e) {
                    let newLat = e.target.getLatLng().lat;
                    let newLng = e.target.getLatLng().lng;
                    document.getElementById('edit_lat').value = newLat;
                    document.getElementById('edit_lng').value = newLng;
                    getAlamatModal(newLat, newLng, 'edit_alamat');
                });
            } else {
                mapKerjaEdit.setView([lat, lng], 15);
                markerKerjaEdit.setLatLng([lat, lng]);
            }

            // Refresh ukuran peta
            setTimeout(() => { mapKerjaEdit.invalidateSize(); }, 300);
        }

        function closeEditModalKerja() { 
            document.getElementById('modal-edit-pekerjaan').classList.remove('active'); 
        }
    </script>
@endpush