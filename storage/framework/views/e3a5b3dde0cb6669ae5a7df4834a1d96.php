<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-create.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin/edit.css')); ?>">
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
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <header class="top-header glass-panel">
        <h1>Edit Data Alumni</h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="<?php echo e(route('admin.alumni.index')); ?>" class="btn-batal">← Kembali</a>
        </div>
    </header>

    <div style="max-width: 1000px; margin: 0 auto;">
        
        <?php if(session('error')): ?>
            <div style="background:#fee2e2;color:#991b1b;padding:15px;margin-bottom:20px;border-radius:12px; font-weight: 700;">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:12px;margin-bottom:20px;">
                <ul style="margin:0; padding-left:20px;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

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

            <form action="<?php echo e(route('admin.alumni.update', $alumni->id)); ?>" method="POST" enctype="multipart/form-data" id="wizardForm">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-step active" id="step1">
                    <h3 style="color: var(--pilkom-blue-dark); margin-bottom: 25px;">Data Pribadi & Akademik</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div><label class="label-admin">NIM</label><input type="text" name="nim" class="custom-input-admin" value="<?php echo e(old('nim', $alumni->nim)); ?>" required></div>
                        <div><label class="label-admin">Nama Lengkap</label><input type="text" name="nama_lengkap" class="custom-input-admin" value="<?php echo e(old('nama_lengkap', $alumni->nama_lengkap)); ?>" required></div>
                        <div>
                            <label class="label-admin">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="custom-input-admin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo e(old('jenis_kelamin', $alumni->jenis_kelamin) == 'L' ? 'selected' : ''); ?>>Laki-laki</option>
                                <option value="P" <?php echo e(old('jenis_kelamin', $alumni->jenis_kelamin) == 'P' ? 'selected' : ''); ?>>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-admin">IPK</label>
                            <input type="number" name="ipk" class="custom-input-admin" value="<?php echo e(old('ipk', $alumni->akademik?->ipk)); ?>" min="0" max="4" step="0.01" placeholder="Contoh: 3.75">
                        </div>
                        <div><label class="label-admin">Angkatan</label><input type="number" name="angkatan" class="custom-input-admin" value="<?php echo e(old('angkatan', $alumni->akademik?->angkatan)); ?>"></div>
                        <div><label class="label-admin">Tahun Lulus</label><input type="number" name="tahun_lulus" class="custom-input-admin" value="<?php echo e(old('tahun_lulus', $alumni->akademik?->tahun_lulus)); ?>" required></div>
                    </div>
                    <div style="margin-top: 20px;">
                        <label class="label-admin">Judul Skripsi</label>
                        <textarea name="judul_skripsi" class="custom-input-admin" rows="2"><?php echo e(old('judul_skripsi', $alumni->akademik?->judul_skripsi)); ?></textarea>
                    </div>
                    <div style="margin-top: 20px; background: rgba(0,74,135,0.03); padding: 20px; border-radius: 15px;">
                        <label class="label-admin">Foto Profil</label>
                        <div class="foto-edit-preview">
                            <img
                                id="edit-preview-foto"
                                class="foto-edit-thumbnail"
                                src="<?php echo e($alumni->foto_profil ? (\Illuminate\Support\Str::startsWith($alumni->foto_profil, ['http://', 'https://']) ? $alumni->foto_profil : asset('storage/' . $alumni->foto_profil)) : '/default.png'); ?>"
                                data-default-src="<?php echo e($alumni->foto_profil ? (\Illuminate\Support\Str::startsWith($alumni->foto_profil, ['http://', 'https://']) ? $alumni->foto_profil : asset('storage/' . $alumni->foto_profil)) : '/default.png'); ?>"
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
                        <div><label class="label-admin">Email</label><input type="email" name="email" class="custom-input-admin" value="<?php echo e(old('email', $alumni->email)); ?>" placeholder="alumni@example.com"></div>
                        <div><label class="label-admin">No. WhatsApp</label><input type="text" name="no_hp" class="custom-input-admin" value="<?php echo e(old('no_hp', $alumni->no_hp)); ?>" placeholder="0812..."></div>
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
                                <input type="text" name="kota_tinggal" id="kota" class="custom-input-admin" value="<?php echo e(old('kota_tinggal', $alumni->alamat?->kota)); ?>" placeholder="Ketik nama kota..." required>
                            </div>
                            <div>
                                <label class="label-admin">Alamat Lengkap</label>
                                <textarea name="alamat_tinggal" id="alamat_lengkap" class="custom-input-admin" rows="3" readonly placeholder="Pinpoint di peta" required><?php echo e(old('alamat_tinggal', $alumni->alamat?->alamat_lengkap)); ?></textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="text" name="latitude_tinggal" id="lat" class="custom-input-admin" value="<?php echo e(old('latitude_tinggal', $alumni->alamat?->latitude)); ?>" readonly required>
                                <input type="text" name="longitude_tinggal" id="lng" class="custom-input-admin" value="<?php echo e(old('longitude_tinggal', $alumni->alamat?->longitude)); ?>" readonly required>
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

            <?php if($alumni->pekerjaan->isEmpty()): ?>
                <div style="text-align: center; padding: 40px; background: rgba(0,74,135,0.03); border-radius: 16px; border: 2px dashed rgba(0,74,135,0.2);">
                    <svg width="40" height="40" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" style="margin-bottom: 10px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <h4 style="color: #475569; margin:0;">Belum Ada Riwayat Pekerjaan</h4>
                    <p style="color: #94a3b8; font-size: 13px; margin-top:5px;">Klik tombol + Tambah Pekerjaan di atas untuk menambahkan data.</p>
                </div>
            <?php else: ?>
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
                            <?php $__currentLoopData = $alumni->pekerjaan->sortBy(function($item){
                                return match($item->status_karir){
                                    'Utama' => 1,
                                    'Sampingan' => 2,
                                    default => 3
                                };
                            }); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr style="background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.04); transition: 0.3s;">
                                <td style="padding: 15px; border-radius: 12px 0 0 12px; border: 1px solid #f1f5f9; border-right: none;">
                                    <span style="font-weight: 800; color: #1e293b; display: block; font-size: 15px;"><?php echo e($p->perusahaan?->nama_perusahaan); ?></span>
                                    <small style="color: #94a3b8; display: flex; align-items: center; gap: 4px; margin-top:4px;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <?php echo e($p->perusahaan?->lokasi->first()?->kota ?? '-'); ?>

                                    </small>
                                    <small style="color: #64748b; display: block; margin-top: 8px; line-height: 1.5;">
                                        Periode:
                                        <?php echo e($p->tanggal_mulai?->translatedFormat('d M Y') ?? '-'); ?>

                                        -
                                        <?php echo e($p->is_current ? 'Sekarang' : ($p->tanggal_selesai?->translatedFormat('d M Y') ?? '-')); ?>

                                    </small>
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    <span style="color: #475569; font-weight: 600;"><?php echo e($p->jabatan); ?></span>
                                    <small style="display: block; color: #94a3b8; margin-top: 6px;">
                                        Masa tunggu:
                                        <?php echo e($p->masa_tunggu !== null ? $p->masa_tunggu . ' bulan' : '-'); ?>

                                    </small>
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                                    <?php if($p->status_karir == 'Utama'): ?>
                                        <span title="Pekerjaan utama yang ditampilkan di peta" style="background: #ecfdf5; color: #10b981; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #10b981; cursor: help;">UTAMA</span>
                                    <?php elseif($p->status_karir == 'Sampingan'): ?>
                                        <span title="Pekerjaan aktif lainnya (Double Job)" style="background: #eff6ff; color: #3b82f6; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #3b82f6; cursor: help;">SAMPINGAN</span>
                                    <?php else: ?>
                                        <span title="Riwayat pekerjaan masa lalu" style="background: #f8fafc; color: #94a3b8; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #e2e8f0; cursor: help;">RIWAYAT</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <?php $__currentLoopData = ['Utama' => ['U', '#10b981', 'Jadikan Pekerjaan Utama'], 'Sampingan' => ['S', '#3b82f6', 'Jadikan Pekerjaan Sampingan'], 'Riwayat' => ['R', '#94a3b8', 'Pindahkan ke Riwayat Lama']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $style): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($p->status_karir != $status): ?>
                                                <form action="<?php echo e(route('admin.pekerjaan.updateStatus', $p->id)); ?>" method="POST" style="display:inline;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="status" value="<?php echo e($status); ?>">
                                                    <button type="submit" title="<?php echo e($style[2]); ?>" style="background: <?php echo e($style[1]); ?>; color: white; border: none; width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 11px; transition: 0.2s;"><?php echo e($style[0]); ?></button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </td>
                                <td style="padding: 15px; border-radius: 0 12px 12px 0; border: 1px solid #f1f5f9; border-left: none; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <?php
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
                                        ?>
        
                                        <button type="button"onclick='editPekerjaan(<?php echo json_encode($dataEdit, 15, 512) ?>)' title="Edit data pekerjaan ini" style="background: #e0f2fe; color: #0284c7; border: none; padding: 8px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>

                                        <form action="<?php echo e(route('admin.pekerjaan.destroy', $p->id)); ?>" method="POST" class="form-hapus-pekerjaan">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="button" class="btn-delete-swal" title="Hapus permanen" style="background: #fee2e2; color: #ef4444; border: none; padding: 8px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php echo $__env->make('admin.komponen.riwayat-pekerjaan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
    window.editConfig = {
        oldLat: <?php echo json_encode($alumni->alamat?->latitude ?? -3.316694, 15, 512) ?>,
        oldLng: <?php echo json_encode($alumni->alamat?->longitude ?? 114.590111, 15, 512) ?>,
        pekerjaanUrl: <?php echo json_encode(url('/admin/pekerjaan'), 15, 512) ?>
    };
    </script>

    <script src="<?php echo e(asset('js/admin/edit.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/admin/edit.blade.php ENDPATH**/ ?>